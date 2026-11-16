<?php

const CRED_BASE_INVITADO = 25;
const CRED_BASE_REGISTRADO = 50;

const CRED_USUARIO_PESO_CALIFICACION = 10;
const CRED_USUARIO_MUESTRA_CALIFICACION = 5;
const CRED_USUARIO_PENALIDAD_DENUNCIA = 10;
const CRED_USUARIO_TOPE_DENUNCIAS = 40;

const CRED_RAFAGA_HORAS = 24;
const CRED_RAFAGA_LIMITE = 3;
const CRED_RAFAGA_PENALIDAD = 8;
const CRED_RAFAGA_TOPE = 24;

const CRED_REPORTE_PESO_CALIFICACION = 12;
const CRED_REPORTE_MUESTRA_CALIFICACION = 3;
const CRED_REPORTE_PENALIDAD_DENUNCIA = 15;
const CRED_REPORTE_TOPE_DENUNCIAS = 60;

function credibilidad_nivel(int $puntaje): array
{
    if ($puntaje >= 70) {
        return ['clave' => 'alta', 'texto' => 'Alta'];
    }
    if ($puntaje >= 40) {
        return ['clave' => 'media', 'texto' => 'Media'];
    }
    return ['clave' => 'baja', 'texto' => 'Baja'];
}

function credibilidad_resultado(array $factores): array
{
    $puntaje = (int) round(array_sum(array_column($factores, 'puntos')));
    $puntaje = max(0, min(100, $puntaje));

    return [
        'puntaje' => $puntaje,
        'nivel' => credibilidad_nivel($puntaje),
        'factores' => array_values(array_filter($factores, fn($f) => $f['puntos'] != 0 || !empty($f['base'])))
    ];
}

function credibilidad_aporte_calificacion(float $suma, int $cantidad, int $peso, int $muestra): int
{
    if ($cantidad === 0) {
        return 0;
    }
    $promedio = $suma / $cantidad;
    $confianza = min(1, $cantidad / $muestra);
    return (int) round(($promedio - 3) * $peso * $confianza);
}

function credibilidad_datos(PDO $connection): array
{
    static $cache = null;

    if ($cache !== null) {
        return $cache;
    }

    $filas = $connection->query("
        SELECT
            r.id_reporte,
            r.id_usuario,
            r.fecha_reporte,
            COALESCE((SELECT SUM(v.valor) FROM valoracion v WHERE v.id_reporte = r.id_reporte AND v.id_usuario <> r.id_usuario), 0) AS suma_calificaciones,
            (SELECT COUNT(*) FROM valoracion v WHERE v.id_reporte = r.id_reporte AND v.id_usuario <> r.id_usuario) AS cantidad_calificaciones,
            (SELECT COUNT(DISTINCT a.id_usuario) FROM advertencia a WHERE a.id_reporte = r.id_reporte AND a.id_usuario <> r.id_usuario) AS cantidad_denuncias
        FROM reporte r
        ORDER BY r.fecha_reporte ASC, r.id_reporte ASC
    ")->fetchAll(PDO::FETCH_ASSOC);

    $reportes = [];
    $porUsuario = [];

    foreach ($filas as $f) {
        $r = [
            'id' => (int) $f['id_reporte'],
            'usuario' => (int) $f['id_usuario'],
            'fecha' => strtotime($f['fecha_reporte']) ?: 0,
            'suma' => (float) $f['suma_calificaciones'],
            'calificaciones' => (int) $f['cantidad_calificaciones'],
            'denuncias' => (int) $f['cantidad_denuncias'],
        ];
        $reportes[$r['id']] = $r;
        $porUsuario[$r['usuario']][] = $r['id'];
    }

    return $cache = ['reportes' => $reportes, 'por_usuario' => $porUsuario];
}

function credibilidad_factores_autor(array $datos, int $idUsuario, ?int $excluirReporte = null): array
{
    if ($idUsuario <= 0) {
        return [[
            'texto' => 'Reporte de invitado (anónimo)',
            'puntos' => CRED_BASE_INVITADO,
            'base' => true
        ]];
    }

    $suma = 0.0;
    $calificaciones = 0;
    $denuncias = 0;

    foreach ($datos['por_usuario'][$idUsuario] ?? [] as $id) {
        if ($id === $excluirReporte) {
            continue;
        }
        $r = $datos['reportes'][$id];
        $suma += $r['suma'];
        $calificaciones += $r['calificaciones'];
        $denuncias += $r['denuncias'];
    }

    $factores = [[
        'texto' => 'Usuario registrado',
        'puntos' => CRED_BASE_REGISTRADO,
        'base' => true
    ]];

    if ($calificaciones > 0) {
        $promedio = round($suma / $calificaciones, 1);
        $factores[] = [
            'texto' => sprintf('Calificaciones de %s: %s ★ (%d)', $excluirReporte ? 'sus otros reportes' : 'sus reportes', str_replace('.', ',', (string) $promedio), $calificaciones),
            'puntos' => credibilidad_aporte_calificacion($suma, $calificaciones, CRED_USUARIO_PESO_CALIFICACION, CRED_USUARIO_MUESTRA_CALIFICACION)
        ];
    }

    if ($denuncias > 0) {
        $factores[] = [
            'texto' => sprintf('Denuncias recibidas en %s: %d', $excluirReporte ? 'sus otros reportes' : 'sus reportes', $denuncias),
            'puntos' => -min(CRED_USUARIO_TOPE_DENUNCIAS, $denuncias * CRED_USUARIO_PENALIDAD_DENUNCIA)
        ];
    }

    return $factores;
}

function credibilidad_factor_rafaga(array $datos, int $idUsuario, int $momento, ?int $excluirReporte = null): ?array
{
    if ($idUsuario <= 0) {
        return null;
    }

    $desde = $momento - CRED_RAFAGA_HORAS * 3600;
    $cantidad = 0;

    foreach ($datos['por_usuario'][$idUsuario] ?? [] as $id) {
        if ($id === $excluirReporte) {
            continue;
        }
        $fecha = $datos['reportes'][$id]['fecha'];
        if ($fecha >= $desde && $fecha <= $momento) {
            $cantidad++;
        }
    }

    $exceso = $cantidad - CRED_RAFAGA_LIMITE;

    if ($exceso <= 0) {
        return null;
    }

    return [
        'texto' => sprintf('Muchos reportes en poco tiempo: %d en %d h (límite %d)', $cantidad, CRED_RAFAGA_HORAS, CRED_RAFAGA_LIMITE),
        'puntos' => -min(CRED_RAFAGA_TOPE, $exceso * CRED_RAFAGA_PENALIDAD)
    ];
}

function credibilidad_reporte_desde_datos(array $datos, int $idReporte): ?array
{
    $r = $datos['reportes'][$idReporte] ?? null;

    if ($r === null) {
        return null;
    }

    $factores = credibilidad_factores_autor($datos, $r['usuario'], $r['id']);

    $rafaga = credibilidad_factor_rafaga($datos, $r['usuario'], $r['fecha'] - 1, $r['id']);
    if ($rafaga) {
        $factores[] = $rafaga;
    }

    if ($r['calificaciones'] > 0) {
        $promedio = round($r['suma'] / $r['calificaciones'], 1);
        $factores[] = [
            'texto' => sprintf('Calificaciones de este reporte: %s ★ (%d)', str_replace('.', ',', (string) $promedio), $r['calificaciones']),
            'puntos' => credibilidad_aporte_calificacion($r['suma'], $r['calificaciones'], CRED_REPORTE_PESO_CALIFICACION, CRED_REPORTE_MUESTRA_CALIFICACION)
        ];
    }

    if ($r['denuncias'] > 0) {
        $factores[] = [
            'texto' => sprintf('Denuncias de este reporte: %d', $r['denuncias']),
            'puntos' => -min(CRED_REPORTE_TOPE_DENUNCIAS, $r['denuncias'] * CRED_REPORTE_PENALIDAD_DENUNCIA)
        ];
    }

    return credibilidad_resultado($factores);
}

function credibilidad_reporte(PDO $connection, int $idReporte): ?array
{
    return credibilidad_reporte_desde_datos(credibilidad_datos($connection), $idReporte);
}

function credibilidad_reportes(PDO $connection): array
{
    $datos = credibilidad_datos($connection);
    $resultado = [];

    foreach (array_keys($datos['reportes']) as $id) {
        $resultado[$id] = credibilidad_reporte_desde_datos($datos, $id);
    }

    return $resultado;
}

function credibilidad_usuario(PDO $connection, int $idUsuario): array
{
    $datos = credibilidad_datos($connection);
    $factores = credibilidad_factores_autor($datos, $idUsuario);

    $rafaga = credibilidad_factor_rafaga($datos, $idUsuario, time());
    if ($rafaga) {
        $factores[] = $rafaga;
    }

    return credibilidad_resultado($factores);
}
