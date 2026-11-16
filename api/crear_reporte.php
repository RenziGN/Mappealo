<?php
// api/crear_reporte.php

require_once __DIR__ . '/../config/auth.php';

if (!isset($_SESSION['user']) || !is_array($_SESSION['user'])) {
    responder_json(401, ['status' => 'error', 'message' => 'Debés iniciar sesión para publicar un reporte.']);
}

$input = leer_json_post();

require_once __DIR__ . '/../config/connection.php';
require_once __DIR__ . '/../config/direccion.php';
require_once __DIR__ . '/../config/cifrado.php';
require_once __DIR__ . '/../config/limites.php';

$esInvitado = ($_SESSION['user']['rol'] ?? null) === 'guest';
$idUsuario = $esInvitado ? 0 : usuario_actual_id();

if (!$esInvitado && $idUsuario === null) {
    responder_json(401, ['status' => 'error', 'message' => 'Iniciá sesión nuevamente para publicar un reporte.']);
}

// Validación de los datos recibidos
$lat = filter_var($input['latitud'] ?? null, FILTER_VALIDATE_FLOAT);
$lng = filter_var($input['longitud'] ?? null, FILTER_VALIDATE_FLOAT);

if ($lat === false || $lng === false || $lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
    responder_json(422, ['status' => 'error', 'message' => 'La ubicación del reporte no es válida.']);
}

$esDelito = (($input['categoria'] ?? '') === 'delito' || ($input['tipo'] ?? '') === 'delito');

$descripcion = trim(preg_replace('/[^\P{C}\n]+/u', '', (string) ($input['descripcion'] ?? '')));
if (mb_strlen($descripcion) > 1000) {
    responder_json(422, ['status' => 'error', 'message' => 'La descripción puede tener hasta 1000 caracteres.']);
}
if ($descripcion === '') {
    $descripcion = 'Sin descripción';
}

$fechaTexto = trim((string) ($input['fecha_hora'] ?? ''));
$fechaIncidente = new DateTime();

if ($fechaTexto !== '') {
    $fecha = DateTime::createFromFormat('!Y-m-d\TH:i', $fechaTexto) ?: DateTime::createFromFormat('!Y-m-d\TH:i:s', $fechaTexto);
    $errores = DateTime::getLastErrors();

    if (!$fecha || ($errores && ($errores['warning_count'] || $errores['error_count']))) {
        responder_json(422, ['status' => 'error', 'message' => 'La fecha del incidente no es válida.']);
    }
    if ($fecha > new DateTime('+5 minutes') || $fecha < new DateTime('-1 year')) {
        responder_json(422, ['status' => 'error', 'message' => 'La fecha del incidente tiene que ser del último año y no puede ser futura.']);
    }
    $fechaIncidente = $fecha;
} elseif ($esDelito) {
    responder_json(422, ['status' => 'error', 'message' => 'Indicá la fecha y hora del delito.']);
}

$dirEscrita = trim(preg_replace('/\s+/u', ' ', (string) ($input['ubicacion']['direccion'] ?? '')));

try {
    // El tipo tiene que existir tal cual en la base
    if ($esDelito) {
        $stmtTipo = $connection->prepare("SELECT id_tipo_robo FROM tipo_robo WHERE nombre = :nom");
        $stmtTipo->execute([':nom' => (string) ($input['tipo_robo'] ?? '')]);
    } else {
        $stmtTipo = $connection->prepare("SELECT id_tipo_incidente FROM tipo_incidente WHERE nombre = :nom");
        $stmtTipo->execute([':nom' => (string) ($input['incidente'] ?? '')]);
    }
    $idTipo = $stmtTipo->fetchColumn();

    if (!$idTipo) {
        responder_json(422, ['status' => 'error', 'message' => 'Elegí un tipo de reporte válido.']);
    }

    // Máximo 5 reportes cada 10 minutos por cuenta (o por conexión, si es invitado)
    $claveLimite = $esInvitado ? clave_limite('invitado', ip_cliente()) : clave_limite('usuario', (string) $idUsuario);
    if (limite_superado($connection, 'reporte', $claveLimite, 5, 600)) {
        responder_json(429, ['status' => 'error', 'message' => 'Publicaste muchos reportes seguidos. Esperá unos minutos.']);
    }

    $dir = $dirEscrita !== ''
        ? mb_substr($dirEscrita, 0, 120)
        : (direccion_desde_coordenadas((float) $lat, (float) $lng) ?? DIRECCION_POR_DEFECTO);

    $connection->beginTransaction();

    if ($esInvitado) {
        $connection->exec("SET SESSION sql_mode = CONCAT_WS(',', @@SESSION.sql_mode, 'NO_AUTO_VALUE_ON_ZERO')");
        $stmtGuest = $connection->prepare("SELECT id_usuario FROM usuario WHERE id_usuario = 0");
        $stmtGuest->execute();
        if ($stmtGuest->fetchColumn() === false) {
            $emailInvitado = 'guest-id-0@mappealo.invalid';
            $stmtGuestInsert = $connection->prepare("
                INSERT IGNORE INTO usuario (id_usuario, nombre_usuario, email, email_indice, password, es_admin)
                VALUES (0, 'Invitado', :email, :indice, :password, 0)
            ");
            $stmtGuestInsert->execute([
                ':email' => cifrar($emailInvitado),
                ':indice' => indice_email($emailInvitado),
                ':password' => password_hash(bin2hex(random_bytes(32)), PASSWORD_DEFAULT),
            ]);

            $stmtGuest->execute();
            if ($stmtGuest->fetchColumn() === false) {
                throw new PDOException('No se pudo crear el usuario invitado con id_usuario 0.');
            }
        }
    }

    //  Insertar en tabla: ubicacion
    $stmtUb = $connection->prepare("
        INSERT INTO ubicacion (latitud, longitud, direccion)
        VALUES (:lat, :lng, :dir)
    ");
    $stmtUb->execute([':lat' => $lat, ':lng' => $lng, ':dir' => $dir]);
    $idUbicacion = $connection->lastInsertId();

    // Insertar en tabla: reporte
    $stmtRep = $connection->prepare("
        INSERT INTO reporte (id_usuario, id_ubicacion, fecha_reporte, fecha_incidente, descripcion, estado)
        VALUES (:id_usuario, :id_ubicacion, NOW(), :fecha_incidente, :descripcion, 'pendiente')
    ");
    $stmtRep->execute([
        ':id_usuario'       => $idUsuario,
        ':id_ubicacion'     => $idUbicacion,
        ':fecha_incidente'  => $fechaIncidente->format('Y-m-d H:i:s'),
        ':descripcion'      => $descripcion
    ]);
    $idReporte = $connection->lastInsertId();

    //  Insertar en tabla hija: robo O incidente_comunitario
    if ($esDelito) {
        $grav = is_array($input['gravedad'] ?? null) ? $input['gravedad'] : [];

        $stmtRobo = $connection->prepare("
            INSERT INTO robo (id_reporte, id_tipo_robo, hubo_violencia, hubo_arma, multiples_delincuentes)
            VALUES (:id_reporte, :id_tipo_robo, :hubo_violencia, :hubo_arma, :multiples_delincuentes)
        ");
        $stmtRobo->execute([
            ':id_reporte'              => $idReporte,
            ':id_tipo_robo'            => $idTipo,
            ':hubo_violencia'          => !empty($grav['violencia']) ? 1 : 0,
            ':hubo_arma'               => !empty($grav['arma_fuego']) ? 1 : 0,
            ':multiples_delincuentes'  => !empty($grav['multiples_delincuentes']) ? 1 : 0
        ]);
    } else {
        $stmtInc = $connection->prepare("
            INSERT INTO incidente_comunitario (id_reporte, id_tipo_incidente)
            VALUES (:id_reporte, :id_tipo_incidente)
        ");
        $stmtInc->execute([':id_reporte' => $idReporte, ':id_tipo_incidente' => $idTipo]);
    }

    $connection->commit();
    responder_json(200, ['status' => 'success', 'message' => 'Reporte guardado con éxito']);

} catch (PDOException $e) {
    if ($connection->inTransaction()) {
        $connection->rollBack();
    }
    error_log('Mappealo - crear reporte: ' . $e->getMessage());
    responder_json(500, ['status' => 'error', 'message' => 'No se pudo guardar el reporte. Intentá nuevamente.']);
}
