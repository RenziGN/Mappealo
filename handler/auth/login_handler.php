<?php

require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/connection.php';
require_once __DIR__ . '/../../config/cifrado.php';
require_once __DIR__ . '/../../config/limites.php';

function volver_al_login(array $errores): void
{
    $_SESSION['errors'] = $errores;
    header("Location: ../../pages/auth/login.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    volver_al_login(["Completá el formulario para iniciar sesión."]);
}

$email = normalizar_email((string) ($_POST["email"] ?? ""));
$password = (string) ($_POST["password"] ?? "");

if ($email === '' || $password === '') {
    volver_al_login(["Ingresá tu correo electrónico y tu contraseña."]);
}

if (parece_bot('login', $_POST, false)) {
    volver_al_login(["No pudimos procesar el ingreso. Volvé a intentar."]);
}

$claveCuenta = clave_limite(ip_cliente(), $email);
$claveIp = clave_limite(ip_cliente());

try {
    // Máximo 5 intentos fallidos por cuenta y 20 por conexión cada 15 minutos
    if (intentos_recientes($connection, 'login', $claveCuenta, 900) >= 5
        || intentos_recientes($connection, 'login_ip', $claveIp, 900) >= 20) {
        volver_al_login(["Demasiados intentos fallidos. Esperá 15 minutos y volvé a intentar."]);
    }

    // Busca por la huella del correo; los usuarios todavía sin migrar se buscan por el correo en texto plano
    $stmt = $connection->prepare("
        SELECT id_usuario, email, password, es_admin FROM usuario
        WHERE id_usuario <> 0 AND (email_indice = :indice OR (email_indice IS NULL AND email = :email))
        LIMIT 1
    ");
    $stmt->execute([':indice' => indice_email($email), ':email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        limpiar_intentos($connection, 'login', $claveCuenta);

        if (password_needs_rehash($user['password'], PASSWORD_BCRYPT)) {
            $connection->prepare("UPDATE usuario SET password = :p WHERE id_usuario = :id")
                ->execute([':p' => password_hash($password, PASSWORD_BCRYPT), ':id' => $user['id_usuario']]);
        }

        session_regenerate_id(true);
        unset($_SESSION['csrf_token']);
        $_SESSION['user'] = [
            'id_usuario' => (int) $user['id_usuario'],
            'email' => descifrar($user['email']) ?? $email,
            'is_admin' => (int) $user['es_admin']
        ];
        header("Location: ../../index.php");
        exit;
    }

    registrar_intento($connection, 'login', $claveCuenta);
    registrar_intento($connection, 'login_ip', $claveIp);
    volver_al_login(["Correo electrónico o contraseña incorrectos."]);
} catch (PDOException | RuntimeException $e) {
    error_log('Mappealo - login: ' . $e->getMessage());
    volver_al_login(["Error al consultar la base de datos. Intentá nuevamente."]);
}
