<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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
