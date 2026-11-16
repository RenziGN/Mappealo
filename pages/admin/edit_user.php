<?php
require_once __DIR__ . '/../../config/auth.php';

requerir_admin();

require_once __DIR__ . '/../../config/connection.php';
require_once __DIR__ . '/../../config/cifrado.php';
require_once __DIR__ . '/_nav.php';

$id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

if ($id === false) {
    $_SESSION['errors'] = ["El ID de usuario no fue proporcionado."];
    header("Location: user_list.php");
    exit;
}

$stmt = $connection->prepare("SELECT id_usuario, nombre_usuario, email, es_admin FROM usuario WHERE id_usuario = :id AND id_usuario <> 0");
$stmt->execute([':id' => $id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    $_SESSION['errors'] = ["El usuario no fue encontrado."];
    header("Location: user_list.php");
    exit;
}

$user['email'] = descifrar($user['email']) ?? '';
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <?php admin_head('Editar usuario'); ?>
</head>

<body class="admin-body">
<div class="container py-4">

    <?php admin_nav($connection, 'usuarios'); ?>

    <a href="user_list.php" class="admin-volver mb-3"><i class="fa-solid fa-arrow-left"></i> Volver a usuarios</a>

    <div class="row justify-content-center">

        <div class="col-md-6">

            <div class="card p-4">

                <h1 class="admin-pagina-titulo mb-4">

                    Editar usuario

                </h1>

                <?php if(!empty($errors)): ?>

                    <div class="alert alert-danger">

                        <ul class="mb-0">

                            <?php foreach($errors as $error): ?>

                                <li>
                                    <?= htmlspecialchars($error) ?>
                                </li>

                            <?php endforeach; ?>

                        </ul>

                    </div>

                <?php endif; ?>

                <form method="POST" id="editUserForm" action="../../handler/admin/edit_user_handler.php">

                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
                    <input type="hidden" name="id" value="<?= (int) $id ?>">

                    <div class="mb-3">

                        <label class="form-label">

                            Email

                        </label>

                        <input
                        type="email"
                        name="email"
                        id="email"
                        class="form-control"
                        value="<?= htmlspecialchars($user['email']) ?>"
                        required>

                    </div>

                    <div class="form-check mb-4">

                        <input
                        type="checkbox"
                        name="is_admin"
                        class="form-check-input"
                        id="is_admin"

                        <?= (int) $user['es_admin'] === 1
                        ? 'checked'
                        : '' ?>>

                        <label
                        class="form-check-label"
                        for="is_admin">

                            Administrador

                        </label>

                    </div>

                    <div class="d-flex gap-2">

                        <button
                        type="submit"
                        class="btn btn-primary">

                            Guardar Cambios

                        </button>

                        <a
                        href="user_list.php"
                        class="btn btn-secondary">

                            Volver

                        </a>

                    </div>

                </form>

            </div>

        </div>

    </div>

    <script src="../../assets/js/validate_user.js"></script>
    <script src="../../assets/js/check_modified_user.js"></script>

</div>
</body>
</html>