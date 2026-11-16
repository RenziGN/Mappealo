<?php

function conexion_segura(): bool
{
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
}

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_name('MAPPEALOSESSID');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => conexion_segura(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

header_remove('X-Powered-By');
date_default_timezone_set('America/Argentina/Buenos_Aires');

// Cualquier error no previsto se registra en el log y el usuario ve un mensaje genérico
set_exception_handler(function (Throwable $e): void {
    error_log('Mappealo - ' . get_class($e) . ': ' . $e->getMessage() . ' en ' . $e->getFile() . ':' . $e->getLine());
    if (!headers_sent()) {
        http_response_code(500);
    }
    echo 'Ocurrió un error inesperado. Intentá nuevamente más tarde.';
});

function usuario_actual_id(): ?int
{
    $user = $_SESSION['user'] ?? null;

    if (!is_array($user) || ($user['rol'] ?? null) === 'guest') {
        return null;
    }

    $id = filter_var($user['id_usuario'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

    return $id === false ? null : $id;
}

function es_admin(): bool
{
    return usuario_actual_id() !== null && !empty($_SESSION['user']['is_admin']);
}

function requerir_admin(): void
{
    if (!es_admin()) {
        $_SESSION['errors'] = ["Acceso denegado. Solo los administradores pueden acceder a esta página."];
        $raiz = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'], 3)), '/');
        header("Location: {$raiz}/pages/auth/login.php");
        exit;
    }
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrf_valido(?string $token): bool
{
    return is_string($token) && !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function responder_json(int $codigo, array $datos): void
{
    http_response_code($codigo);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($datos);
    exit;
}

function requerir_usuario_registrado_api(): int
{
    $id = usuario_actual_id();

    if ($id === null) {
        responder_json(401, [
            'status' => 'error',
            'message' => 'Tenés que iniciar sesión con una cuenta registrada para hacer esto.'
        ]);
    }

    return $id;
}

function leer_json_post(): array
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        responder_json(405, ['status' => 'error', 'message' => 'Método no permitido.']);
    }

    $datos = json_decode(file_get_contents('php://input'), true);

    if (!is_array($datos)) {
        responder_json(400, ['status' => 'error', 'message' => 'Datos inválidos.']);
    }

    if (!csrf_valido($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null)) {
        responder_json(403, ['status' => 'error', 'message' => 'La sesión expiró. Recargá la página e intentá de nuevo.']);
    }

    return $datos;
}
