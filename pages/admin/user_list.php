<?php
session_start();
require_once '../../config/connection.php';
require_once '../../config/auth.php';
require_once __DIR__ . '/_nav.php';

if (
    !isset($_SESSION['user']) ||
    empty($_SESSION['user']['is_admin'])
) {
    $_SESSION['errors'] = ["Acceso denegado. Solo los administradores pueden acceder a esta página. Hemos cerrado tu sesión por seguridad."];
    header("Location: ../auth/login.php");
    exit;
}else{
    $sql = "SELECT id_usuario, nombre_usuario, email, es_admin FROM usuario WHERE id_usuario <> 0 ORDER BY id_usuario";

    $stmt = $connection->prepare($sql);

    $stmt->execute();

    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Usuarios · Administración</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="container py-4 bg-light">

    <?php admin_nav($connection, 'usuarios'); ?>

    <?php if (isset($_SESSION['errors'])  && !empty($_SESSION['errors'])): ?>
    <div class="alert alert-danger">
        <strong>Ocurrieron los siguientes errores:</strong>
        <ul>
            <?php foreach ($_SESSION['errors'] as $error): ?>
                <li><?php echo htmlspecialchars($error); ?></li>
            <?php endforeach; unset($_SESSION['errors']); ?>
        </ul>
    </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['success']) && !empty($_SESSION['success'])): ?>
    <div class="alert alert-success">
        <?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
    </div>
    <?php endif; ?>

    <table class="table table-bordered">

        <thead>

            <tr>

                <th>ID</th>

                <th>Email</th>

                <th>Admin</th>

                <th>Acciones</th>

            </tr>

        </thead>

        <tbody>

            <?php foreach($users as $user): ?>

                <tr>

                    <td>
                        <?= $user['id_usuario'] ?>
                    </td>

                    <td>
                        <?= htmlspecialchars($user['email']) ?>
                    </td>

                    <td>

                        <?= $user['es_admin']
                        ? 'Sí'
                        : 'No' ?>

                    </td>

                    <td>

                        <a
                        href="edit_user.php?id=<?= $user['id_usuario'] ?>"
                        class="btn btn-warning btn-sm">

                            Editar

                        </a>

                        <a
                        href="../../handler/admin/delete_user_handler.php?id=<?= $user['id_usuario'] ?>"
                        class="btn btn-danger btn-sm">

                            Eliminar

                        </a>

                    </td>

                </tr>

            <?php endforeach; ?>

        </tbody>

    </table>

</body>
</html>