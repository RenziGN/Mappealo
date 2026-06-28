<?php
session_start();
require_once '../../config/connection.php';
$errors = [];
$success = false;
$db_error = "";
//echo "Intentaste registrarte";

if ($_SERVER["REQUEST_METHOD"] == "POST") { 
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";
    $confirmPassword = $_POST["confirmPassword"] ?? "";
    $isAdmin = str_contains($email, "@admin.com") ? 1 : 0;

    if (empty($email)) {
        $errors[] = "El correo electrónico es obligatorio.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "El formato del correo electrónico no es válido.";
    }

    if (empty($password)) {
        $errors[] = "La contraseña es obligatoria.";
    } elseif (strlen($password) < 5) {
        $errors[] = "La contraseña debe tener al menos 5 caracteres.";
    }

    if ($password !== $confirmPassword) {
        $errors[] = "Las contraseñas no coinciden.";
    }

    if (empty($errors)) {
        try {
            $sql = "INSERT INTO users (email, password, is_admin) VALUES (:email, :password, :is_admin)";
            $stmt = $connection->prepare($sql);
            $stmt->bindValue(':email', $email);
            $stmt->bindValue(':password', password_hash($password, PASSWORD_BCRYPT));
            $stmt->bindValue(':is_admin', $isAdmin);            
            $stmt->execute();
            header("Location: ../../pages/auth/login.php");
            $_SESSION['success'] = "Registro exitoso. Ahora puedes iniciar sesión.";
            exit;
        } catch (PDOException $e) {
            $db_error = "Error al guardar en la base de datos: " . $e->getMessage();
        }

        
    }else{
        $_SESSION['errors'] = $errors;
        header("Location: ../../pages/auth/register.php");
        exit;
    }
}

?>