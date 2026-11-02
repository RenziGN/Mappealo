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
    $sql = "SELECT * FROM users";

    $stmt = $connection->prepare($sql);

    $stmt->execute();

    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Usuarios</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="container py-5">

    <div class="d-flex
    justify-content-between
    align-items-center
    mb-4">

        <h1>Lista de Usuarios</h1>

        <a
        href="../../index.php"
        class="btn btn-outline-primary">

            Volver al Inicio

        </a>

    </div>

    <?php if (isset($_SESSION['errors'])  && !empty($_SESSION['errors'])): ?>
    <div class="alert alert-danger">
        <strong>Ocurrieron los siguientes errores:</strong>
        <ul>
            <?php foreach ($_SESSION['errors'] as $error): ?>
                <li><?php echo $error; ?></li>
            <?php endforeach; $_SESSION = [];?>
        </ul>
    </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['success']) && !empty($_SESSION['success'])): ?>
    <div class="alert alert-success">
        <?php echo $_SESSION['success']; ?>
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
                        <?= $user['id'] ?>
                    </td>

                    <td>
                        <?= htmlspecialchars($user['email']) ?>
                    </td>

                    <td>

                        <?= $user['is_admin']
                        ? 'Sí'
                        : 'No' ?>

                    </td>

                    <td>

                        <a
                        href="edit_user.php?id=<?= $user['id'] ?>"
                        class="btn btn-warning btn-sm">

                            Editar

                        </a>

                        <a
                        href="../../handler/admin/delete_user_handler.php?id=<?= $user['id'] ?>"
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