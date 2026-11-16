<?php
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/limites.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-LN+7fdVzj6u52u30Kp6M/trliBMCMKTyK833zpbD+pXdCLuTusPj697FH4R/5mcr" crossorigin="anonymous">
    <link rel="stylesheet" href="../../assets/css/auth.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
    rel="stylesheet" integrity="sha384-XGjxtQfXaH2tnPFa9x+ruJTuLE3Aa6LhHSWRr1XeTyhezb4abCG4ccI5AkVDxqC+" crossorigin="anonymous">

</head>

<body>

    <div class="auth-container">

        <div class="auth-card">
             <div class="logo-container">
                <img src="../../assets/Img/LOGO MAPPEALO 1.png" alt="Logo Mappealo" class="auth-logo">
            
             </div>

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
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; unset($_SESSION['errors']); ?>
                </ul>
            </div>
            <?php endif; ?>

            <form action="../../handler/auth/register_handler.php" method="POST" id="registerForm">
                <?= campos_antibot('registro') ?>

                <div class="mb-3">

                    <label class="form-label" for="email">
                        Email
                    </label>
                    
                    <div class="input-group-custom">
                     <i class="bi bi-envelope input-icon"></i>
                      <input
                       name="email"
                       id="email"
                       type="email"
                       class="form-control custom-input"
                       placeholder="Ingresa tu correo electrónico"
                       required>
                    </div>

                </div>

                <div class="mb-4">

                    <label class="form-label" for="password">
                        Contraseña
                    </label>
                    
                    <div class="input-group-custom">
                     <i class="bi bi-lock input-icon"></i>
                     <input
                     name="password"
                     id="password"
                     type="password"
                     class="form-control custom-input"
                     placeholder="Ingresa tu contraseña"
                     required>
                    </div>

                </div>

                <div class="mb-4">

                    <label class="form-label" for="confirmPassword">
                        Repetir Contraseña
                    </label>
                    <div class="input-group-custom">
                     <i class="bi bi-lock input-icon"></i>

                     <input
                     name="confirmPassword"
                     id="confirmPassword"
                     type="password"
                     class="form-control custom-input"
                     placeholder="Repite tu contraseña"
                     required>
                    </div> 
            

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
    <script src="../../assets/js/check_register.js?v=2"></script>

</body>
</html>