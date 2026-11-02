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
        // 1. Si tu formulario no tiene campo para "nombre_usuario", extraemos la primera parte del email como fallback
        $nombre_usuario = isset($_POST['nombre_usuario']) && !empty(trim($_POST['nombre_usuario'])) 
            ? trim($_POST['nombre_usuario']) 
            : explode('@', $email)[0];

        // 2. Consulta adaptada al nuevo modelo de base de datos
        $sql = "INSERT INTO usuario (nombre_usuario, email, password, es_admin) 
                VALUES (:nombre_usuario, :email, :password, :es_admin)";
                
        $stmt = $connection->prepare($sql);
        $stmt->bindValue(':nombre_usuario', $nombre_usuario);
        $stmt->bindValue(':email', $email);
        $stmt->bindValue(':password', password_hash($password, PASSWORD_BCRYPT));
        $stmt->bindValue(':es_admin', $isAdmin ?? 0);            
        $stmt->execute();

        // 3. Guardar el mensaje en la sesión ANTES de hacer la redirección
        $_SESSION['success'] = "Registro exitoso. Ahora puedes iniciar sesión.";
        header("Location: ../../pages/auth/login.php");
        exit;
    } catch (PDOException $e) {
        $db_error = "Error al guardar en la base de datos: " . $e->getMessage();
        // Si querés ver el error exacto en pantalla si falla:
        // echo $db_error; exit;
    }

        
    }else{
        $_SESSION['errors'] = $errors;
        header("Location: ../../pages/auth/register.php");
        exit;
    }
}

?>