<?php session_start(); ?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Login</title>

    <link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css"
    rel="stylesheet">

    <link
    rel="stylesheet"
    href="../../assets/css/auth.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
    rel="stylesheet" >
</head>

<body>

    <div class="auth-container">

        <div class="auth-card">
            <div class="logo-container">
                <img src="../../assets/Img/LOGO MAPPEALO 1.png" alt="Logo Mappealo" class="auth-logo">
            
             </div>

            <h1 class="auth-title">
                Iniciar Sesión
            </h1>

            <p class="auth-subtitle">
                Ingrese a su cuenta
            </p>

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
            <?php endif; $_SESSION = []; ?>

            <form action="../../handler/auth/login_handler.php" method="POST" id="loginForm">

                <div class="mb-3">

                    <label class="form-label">
                        Email
                    </label>
                     <div class="input-group-custom">
                     <i class="bi bi-envelope input-icon"></i> <input
                     name="email"
                     id="email"
                     type="email"
                     class="form-control custom-input"
                     placeholder="Email"
                     required>
                    </div>
                    

                </div>

                <div class="mb-4">

                    <label class="form-label">
                        Contraseña
                    </label>
                    <div class="input-group-custom">
                     <i class="bi bi-lock input-icon"></i> <input
                     name="password"
                     id="password"
                     type="password"
                     class="form-control custom-input"
                     placeholder="Contraseña"
                     required>
                    </div>


                    

                </div>

                <button class="btn custom-btn w-100">

                    Iniciar sesión

                </button>

            </form>

            <p class="bottom-text mt-4">

                ¿No tienes una cuenta?

                <a href="register.php">
                    Registrarse
                </a>

            </p>

        </div>

    </div>

    <script src="../../assets/js/validate_user.js"></script>
    <script src="../../assets/js/check_login.js"></script>
    

</body>
</html>