<?php

session_start();

require_once '../../config/connection.php';


if (
    !isset($_SESSION['user']) ||
    !$_SESSION['user']['is_admin']
) {

    $_SESSION['errors'] = [
        "Acceso denegado. Solo los administradores pueden acceder a esta página."
    ];

    header("Location: ../../pages/auth/login.php");

    exit;
}

$id = $_GET['id'] ?? null;

if (!$id) {

    $_SESSION['errors'] = [
        "El ID de usuario no fue proporcionado."
    ];

    header("Location: ../../pages/admin/user_list.php");

    exit;
}


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    header("Location: ../../pages/admin/user_list.php");

    exit;
}

$errors = [];


$email =
trim($_POST['email'] ?? '');

$isAdmin =
isset($_POST['is_admin'])
? 1
: 0;


if (empty($email)) {

    $errors[] =
    "El email es obligatorio.";

} elseif (
    !filter_var(
        $email,
        FILTER_VALIDATE_EMAIL
    )
) {

    $errors[] =
    "Formato de email inválido.";
}

if (empty($errors)) {

    try {

        $updateSql =
        "UPDATE users
        SET
            email = :email,
            is_admin = :is_admin
        WHERE id = :id";

        $updateStmt =
        $connection->prepare($updateSql);

        $updateStmt->bindValue(
            ':email',
            $email
        );

        $updateStmt->bindValue(
            ':is_admin',
            $isAdmin,
            PDO::PARAM_BOOL
        );

        $updateStmt->bindValue(
            ':id',
            $id,
            PDO::PARAM_INT
        );

        $updateStmt->execute();

        $_SESSION['success'] =
        "Usuario actualizado exitosamente.";

        header(
            "Location: ../../pages/admin/user_list.php"
        );

        exit;

    } catch(PDOException $e) {

        $_SESSION['errors'] = [
            "Error al actualizar usuario."
        ];

        header(
            "Location: ../../pages/admin/user_list.php"
        );

        exit;
    }

} else {

    $_SESSION['errors'] = $errors;

    header(
            "Location: ../../pages/admin/user_list.php"
    );

    exit;
}
?>