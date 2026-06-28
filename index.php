<?php

session_start();

if(!isset($_SESSION['user'])) {

    header("Location: pages/auth/login.php");

    exit;
}

$user = $_SESSION['user'];
?>

<!DOCTYPE html>
<html>

<head>

    <title>Home</title>

    <link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css"
    rel="stylesheet">

</head>

<body class="container py-5">

    <div class="card p-4">

        <h1>
            Bienvenido,
            <?php if($user['is_admin']): ?>
                Administrador <?= htmlspecialchars($user['email']) ?>
            <?php else: ?>
                Usuario <?= htmlspecialchars($user['email']) ?>
            <?php endif; ?>
            
        </h1>

        <p>
            Has iniciado sesión correctamente. ¿Qué deseas hacer ahora?
        </p>

        <?php if($user['is_admin']): ?>

            <a
            href="pages/admin/user_list.php"
            class="btn btn-primary">

                Ir al panel de administración de usuarios

            </a>

        <?php endif; ?>

        <a
        href="handler/auth/logout_handler.php"
        class="btn btn-danger">

            Cerrar sesión

        </a>

    </div>

</body>
</html>