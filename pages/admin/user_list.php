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
    <?php admin_head('Usuarios'); ?>
</head>
<body class="admin-body">
<div class="container py-4">

    <?php admin_nav($connection, 'usuarios'); ?>
    <?php admin_mensajes(); ?>

    <p class="text-muted small"><?= count($users) ?> usuario(s) registrado(s).</p>

    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Usuario</th>
                    <th>Rol</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($users as $user): ?>
                <tr>
                    <td><?= (int) $user['id_usuario'] ?></td>
                    <td>
                        <strong><?= htmlspecialchars($user['nombre_usuario']) ?></strong>
                        <div class="small text-muted"><?= htmlspecialchars($user['email']) ?></div>
                    </td>
                    <td>
                        <?php if ($user['es_admin']): ?>
                            <span class="badge text-bg-dark"><i class="fa-solid fa-shield-halved me-1"></i>Administrador</span>
                        <?php else: ?>
                            <span class="badge text-bg-success">Usuario</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-end text-nowrap">
                        <a href="edit_user.php?id=<?= (int) $user['id_usuario'] ?>" class="btn btn-warning btn-sm">
                            <i class="fa-solid fa-pen"></i> Editar
                        </a>
                        <a href="../../handler/admin/delete_user_handler.php?id=<?= (int) $user['id_usuario'] ?>" class="btn btn-danger btn-sm"
                           onclick="return confirm('¿Eliminar al usuario <?= htmlspecialchars(addslashes($user['email']), ENT_QUOTES) ?>? También se borran sus reportes, comentarios y calificaciones.');">
                            <i class="fa-solid fa-trash"></i> Eliminar
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
