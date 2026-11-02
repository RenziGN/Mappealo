<?php
session_start();

require_once '../../config/connection.php';

if (
    !isset($_SESSION['user']) ||
    $_SESSION['user']['is_admin'] === 0
) {
    $_SESSION['errors'] = ["Acceso denegado. Solo los administradores pueden acceder a esta página. Hemos cerrado tu sesión por seguridad."];
    header("Location: ../auth/login.php");
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
    $sql = "DELETE FROM users WHERE id = :id";
    $stmt = $connection->prepare($sql);
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();

    header("Location: ../../pages/admin/user_list.php");
    $_SESSION['success'] = "Usuario ID: " . $id . " | Eliminado exitosamente.";
    exit;

} catch (PDOException $e) {
    $_SESSION['errors'] = ["Error al eliminar el usuario: " . $e->getMessage()];
    header("Location: ../../pages/admin/user_list.php");
    exit;
}




