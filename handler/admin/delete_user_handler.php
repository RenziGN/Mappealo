<?php

require_once __DIR__ . '/../../config/auth.php';

requerir_admin();

require_once __DIR__ . '/../../config/connection.php';

function volver_a_usuarios(string $tipo, string $mensaje): void
{
    $_SESSION[$tipo] = $tipo === 'errors' ? [$mensaje] : $mensaje;
    header("Location: ../../pages/admin/user_list.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_valido($_POST['csrf_token'] ?? null)) {
    volver_a_usuarios('errors', "La acción no es válida o la sesión expiró. Intentá nuevamente.");
}

$id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

if ($id === false) {
    volver_a_usuarios('errors', "El ID de usuario no es válido.");
}

if ($id === usuario_actual_id()) {
    volver_a_usuarios('errors', "No podés eliminar tu propia cuenta desde el panel.");
}

try {
    $stmt = $connection->prepare("DELETE FROM usuario WHERE id_usuario = :id AND id_usuario <> 0");
    $stmt->execute([':id' => $id]);

    if ($stmt->rowCount() === 0) {
        volver_a_usuarios('errors', "El usuario no existe.");
    }

    volver_a_usuarios('success', "Usuario ID: $id | Eliminado exitosamente.");
} catch (PDOException $e) {
    error_log('Mappealo - eliminar usuario: ' . $e->getMessage());
    volver_a_usuarios('errors', "No se pudo eliminar el usuario.");
}
