<?php
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/connection.php';
require_once __DIR__ . '/../../config/cifrado.php';
require_once __DIR__ . '/../../config/limites.php';

function volver_al_registro(array $errores): void
{
    $_SESSION['errors'] = $errores;
    header("Location: ../../pages/auth/register.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../../pages/auth/register.php");
    exit;
}

$errors = [];
$email = normalizar_email((string) ($_POST["email"] ?? ""));
$password = (string) ($_POST["password"] ?? "");
$confirmPassword = (string) ($_POST["confirmPassword"] ?? "");

if (parece_bot('registro', $_POST)) {
    volver_al_registro(["No pudimos procesar el registro. Esperá unos segundos y volvé a intentar."]);
}

if ($email === '') {
    $errors[] = "El correo electrónico es obligatorio.";
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 150) {
    $errors[] = "El formato del correo electrónico no es válido.";
}

if ($password === '') {
    $errors[] = "La contraseña es obligatoria.";
} elseif (strlen($password) < 8 || strlen($password) > 72) {
    $errors[] = "La contraseña debe tener entre 8 y 72 caracteres.";
}

if ($password !== $confirmPassword) {
    $errors[] = "Las contraseñas no coinciden.";
}

if ($errors) {
    volver_al_registro($errors);
}

try {
    // Máximo 10 cuentas nuevas por hora desde la misma conexión
    if (limite_superado($connection, 'registro', clave_limite(ip_cliente()), 10, 3600)) {
        volver_al_registro(["Se crearon demasiadas cuentas desde esta conexión. Probá de nuevo en una hora."]);
    }

    // El nombre público sale del correo, limitado a caracteres seguros
    $nombre_usuario = preg_replace('/[^a-z0-9._-]/i', '', explode('@', $email)[0]) ?: 'usuario';
    $nombre_usuario = mb_substr($nombre_usuario, 0, 100);

    // Las cuentas nuevas nunca son administradoras: eso lo decide un administrador desde el panel
    $stmt = $connection->prepare("
        INSERT INTO usuario (nombre_usuario, email, email_indice, password, es_admin)
        VALUES (:nombre_usuario, :email, :email_indice, :password, 0)
    ");
    $stmt->execute([
        ':nombre_usuario' => $nombre_usuario,
        ':email' => cifrar($email),
        ':email_indice' => indice_email($email),
        ':password' => password_hash($password, PASSWORD_BCRYPT),
    ]);

    $_SESSION['success'] = "Registro exitoso. Ahora puedes iniciar sesión.";
    header("Location: ../../pages/auth/login.php");
    exit;
} catch (PDOException | RuntimeException $e) {
    if ($e->getCode() !== '23000') {
        error_log('Mappealo - registro: ' . $e->getMessage());
    }
    volver_al_registro([
        $e->getCode() === '23000'
            ? "Ya existe una cuenta con ese correo electrónico."
            : "Error al guardar en la base de datos. Intentá nuevamente."
    ]);
}
