<?php

require_once __DIR__ . '/../../config/auth.php';

requerir_admin();

require_once __DIR__ . '/../../config/connection.php';
require_once __DIR__ . '/../../config/cifrado.php';

function volver_a_usuarios(string $tipo, $mensaje): void
{
    $_SESSION[$tipo] = $mensaje;
    header("Location: ../../pages/admin/user_list.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_valido($_POST['csrf_token'] ?? null)) {
    volver_a_usuarios('errors', ["La acción no es válida o la sesión expiró. Intentá nuevamente."]);
}

$id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

if ($id === false) {
    volver_a_usuarios('errors', ["El ID de usuario no es válido."]);
}

$email = normalizar_email((string) ($_POST['email'] ?? ''));
$isAdmin = isset($_POST['is_admin']) ? 1 : 0;
$errors = [];

if ($email === '') {
    $errors[] = "El email es obligatorio.";
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 150) {
    $errors[] = "Formato de email inválido.";
}

if ($id === usuario_actual_id() && $isAdmin === 0) {
    $errors[] = "No podés quitarte el rol de administrador a vos mismo.";
}

if ($errors) {
    volver_a_usuarios('errors', $errors);
}

try {
    $stmt = $connection->prepare("
        UPDATE usuario
        SET email = :email, email_indice = :indice, es_admin = :is_admin
        WHERE id_usuario = :id AND id_usuario <> 0
    ");
    $stmt->execute([
        ':email' => cifrar($email),
        ':indice' => indice_email($email),
        ':is_admin' => $isAdmin,
        ':id' => $id,
    ]);

    if ($id === usuario_actual_id()) {
        $_SESSION['user']['email'] = $email;
    }

    volver_a_usuarios('success', "Usuario actualizado exitosamente.");
} catch (PDOException $e) {
    if ($e->getCode() !== '23000') {
        error_log('Mappealo - editar usuario: ' . $e->getMessage());
    }
    volver_a_usuarios('errors', [
        $e->getCode() === '23000' ? "Ya existe otra cuenta con ese correo." : "Error al actualizar usuario."
    ]);
}
