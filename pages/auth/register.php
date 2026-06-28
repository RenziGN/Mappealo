<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/style.css">

</head>

<body>

    <div class="auth-container">

        <div class="auth-card">

            <h1 class="auth-title">
                Crear Cuenta
            </h1>

            <p class="auth-subtitle">
                Regístrate para continuar
            </p>

            <?php if (isset($_SESSION['errors']) && !empty($_SESSION['errors'])): ?>
            <div class="alert alert-danger">
                <strong>Ocurrieron los siguientes errores:</strong>
                <ul>
                    <?php foreach ($_SESSION['errors'] as $error): ?>
                        <li><?php echo $error; ?></li>
                    <?php endforeach; $_SESSION = [];?>
                </ul>
            </div>
            <?php endif; ?>

            <form action="../../handler/auth/register_handler.php" method="POST" id="registerForm">

                <div class="mb-3">

                    <label class="form-label" for="email">
                        Email
                    </label>

                    <input
                    name="email"
                    id="email"
                    type="email"
                    class="form-control custom-input"
                    placeholder="Ingresa tu correo electrónico"
                    required>

                </div>

                <div class="mb-4">

                    <label class="form-label" for="password">
                        Contraseña
                    </label>

                    <input
                    name="password"
                    id="password"
                    type="password"
                    class="form-control custom-input"
                    placeholder="Ingresa tu contraseña"
                    required>

                </div>

                <div class="mb-4">

                    <label class="form-label" for="confirmPassword">
                        Repetir Contraseña
                    </label>

                    <input
                    name="confirmPassword"
                    id="confirmPassword"
                    type="password"
                    class="form-control custom-input"
                    placeholder="Repite tu contraseña"
                    required>

                </div>

                <button class="btn custom-btn w-100">

                    Registrarse

                </button>

            </form>

            <p class="bottom-text mt-4">

                ¿Ya tienes una cuenta?

                <a href="login.php">
                    Iniciar sesión
                </a>

            </p>

        </div>

    </div>

    <script src="../../assets/js/validate_user.js"></script>
    <script src="../../assets/js/check_register.js"></script>

</body>
</html>