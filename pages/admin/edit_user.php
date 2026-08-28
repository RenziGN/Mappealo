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

$sql =
"SELECT * FROM users
WHERE id = :id";

$stmt = $connection->prepare($sql);

$stmt->bindValue(
    ':id',
    $id,
    PDO::PARAM_INT
);

$stmt->execute();

$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    $_SESSION['errors'] = ["El usuario no fue encontrado."];
    header("Location: ../../pages/admin/user_list.php");
    exit;
}

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
    content="width=device-width, initial-scale=1.0">

    <title>Editar Usuario</title>

    <link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css"
    rel="stylesheet">

</head>

<body class="container py-5">

    <div class="row justify-content-center">

        <div class="col-md-6">

            <div class="card p-4 shadow">

                <h1 class="mb-4">

                    Editar Usuario

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

                <form method="POST" id="editUserForm" action="../../handler/admin/edit_user_handler.php?id=<?= $id ?>">

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

                        <?= $user['is_admin'] === 1
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

</body>
</html>