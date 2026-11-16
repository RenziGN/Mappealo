<?php
session_start();

require_once '../../config/connection.php';

if (
    !isset($_SESSION['user']) ||
    empty($_SESSION['user']['is_admin'])
) {
    $_SESSION['errors'] = ["Acceso denegado. Solo los administradores pueden acceder a esta página. Hemos cerrado tu sesión por seguridad."];
    header("Location: ../../pages/auth/login.php");
    exit;
}else{

    $id = $_GET['id'] ?? null;

    if (!$id) {
        $_SESSION['errors'] = ["El ID de usuario no fue proporcionado."];
        header("Location: ../../pages/admin/user_list.php");
        exit;
    }
    
}

try {
    $sql = "DELETE FROM usuario WHERE id_usuario = :id AND id_usuario <> 0";
    $stmt = $connection->prepare($sql);
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();

    $_SESSION['success'] = "Usuario ID: " . (int) $id . " | Eliminado exitosamente.";
    header("Location: ../../pages/admin/user_list.php");
    exit;

} catch (PDOException $e) {
    $_SESSION['errors'] = ["Error al eliminar el usuario: " . $e->getMessage()];
    header("Location: ../../pages/admin/user_list.php");
    exit;
}




