<?php

require_once __DIR__ . '/secretos_cargar.php';

function ip_cliente(): string
{
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

function clave_limite(string ...$partes): string
{
    return hash_hmac('sha256', implode('|', $partes), secreto('clave_indice', 'mappealo'));
}

function intentos_recientes(PDO $connection, string $accion, string $clave, int $segundos): int
{
    $stmt = $connection->prepare("
        SELECT COUNT(*) FROM intento_acceso
        WHERE accion = :a AND clave = :c AND momento > (NOW() - INTERVAL :s SECOND)
    ");
    $stmt->bindValue(':a', $accion);
    $stmt->bindValue(':c', $clave);
    $stmt->bindValue(':s', $segundos, PDO::PARAM_INT);
    $stmt->execute();

    return (int) $stmt->fetchColumn();
}

function registrar_intento(PDO $connection, string $accion, string $clave): void
{
    $stmt = $connection->prepare("INSERT INTO intento_acceso (accion, clave, momento) VALUES (:a, :c, NOW())");
    $stmt->execute([':a' => $accion, ':c' => $clave]);

    if (random_int(1, 50) === 1) {
        $connection->exec("DELETE FROM intento_acceso WHERE momento < (NOW() - INTERVAL 1 DAY)");
    }
}

function limpiar_intentos(PDO $connection, string $accion, string $clave): void
{
    $stmt = $connection->prepare("DELETE FROM intento_acceso WHERE accion = :a AND clave = :c");
    $stmt->execute([':a' => $accion, ':c' => $clave]);
}

// Registra el intento y avisa si se pasó del máximo permitido en la ventana de tiempo
function limite_superado(PDO $connection, string $accion, string $clave, int $maximo, int $segundos): bool
{
    if (intentos_recientes($connection, $accion, $clave, $segundos) >= $maximo) {
        return true;
    }

    registrar_intento($connection, $accion, $clave);

    return false;
}

// Campo trampa invisible y hora de carga del formulario
function campos_antibot(string $formulario): string
{
    $_SESSION['formulario_inicio'][$formulario] = time();

    return '<div class="campo-trampa" aria-hidden="true" style="position:absolute;left:-9999px;width:1px;height:1px;overflow:hidden;">'
        . '<label for="sitio_web">No completar</label>'
        . '<input type="text" name="sitio_web" id="sitio_web" tabindex="-1" autocomplete="off">'
        . '</div>';
}

// Un bot suele completar el campo trampa o enviar el formulario en menos de 2 segundos.
// En el login no se mide el tiempo: el autocompletado del navegador permite entrar al instante.
function parece_bot(string $formulario, array $datos, bool $medirTiempo = true): bool
{
    $inicio = $_SESSION['formulario_inicio'][$formulario] ?? null;
    unset($_SESSION['formulario_inicio'][$formulario]);

    if (trim((string) ($datos['sitio_web'] ?? '')) !== '') {
        return true;
    }

    return $medirTiempo && ($inicio === null || (time() - $inicio) < 2);
}

// Para las APIs: corta con 429 si la cuenta (o la conexión) superó el máximo
function limitar_api(PDO $connection, string $accion, int $maximo, int $segundos): void
{
    $id = usuario_actual_id();
    $clave = $id !== null ? clave_limite('usuario', (string) $id) : clave_limite('ip', ip_cliente());

    if (limite_superado($connection, $accion, $clave, $maximo, $segundos)) {
        responder_json(429, ['status' => 'error', 'message' => 'Hiciste muchas acciones seguidas. Esperá unos minutos y volvé a intentar.']);
    }
}
