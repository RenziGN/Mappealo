<?php

session_start();

require_once '../../config/connection.php';
$errors = [];


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

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

    if (empty($errors)) {
        try {
            $sql = "SELECT * FROM usuario WHERE email = :email";
            $stmt = $connection->prepare($sql);
            $stmt->bindValue(':email', $email);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password'])) {
                session_regenerate_id(true);
                $_SESSION['user'] = [
                    'id_usuario' => (int) $user['id_usuario'],
                    'email' => $user['email'],
                    'is_admin' => (int) $user['es_admin']
                ];
                header("Location: ../../index.php");
                exit;
            }

            $errors[] = "Correo electrónico o contraseña incorrectos.";
        } catch (PDOException $e) {
            $errors[] = "Error al consultar la base de datos. Intentá nuevamente.";
        }
    }
}

$_SESSION['errors'] = $errors ?: ["Completá el formulario para iniciar sesión."];
header("Location: ../../pages/auth/login.php");
exit;
