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
    rel="stylesheet"
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css">
    <link 
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <link rel="stylesheet" href="assets/css/main.css">

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

    <!-- MAPA DE FONDO -->

<main class="mapa">
    <div class="mapa-grid"></div>
</main>

<div class="overlay"></div>


<!-- ==============================
     POPUP
================================ -->

<section class="popup">

    <!-- HEADER -->

    <header class="popup-header">

        <div>
            <span class="brand">
                MAPPEALO
            </span>

            <h1>Nuevo reporte</h1>

            <p>
                Ayudá a mantener informada a tu comunidad.
            </p>
        </div>

        <button class="cerrar">
            <i class="fa-solid fa-xmark"></i>
        </button>

    </header>


    <!-- ==============================
         TABS
    =============================== -->

    <nav class="tabs">

        <button class="tab active">
            <i class="fa-solid fa-triangle-exclamation"></i>
            Delito
        </button>

        <button class="tab">
            <i class="fa-solid fa-people-group"></i>
            Comunitario
        </button>

    </nav>


    <!-- ==============================
         CONTENIDO
    =============================== -->

    <div class="form-content">


        <!-- ==================================
             TAB DELITO
        =================================== -->

        <div class="tab-content delito">

            <!-- INFORMACIÓN GENERAL -->

            <div class="section-title">
                <span>01</span>

                <div>
                    <h2>Información del delito</h2>
                    <p>Contanos qué sucedió.</p>
                </div>
            </div>


            <!-- TIPO DE ROBO -->

            <div class="field">

                <label>
                    Tipo de robo
                    <span class="required">*</span>
                </label>

                <select>
                    <option value="">Seleccionar tipo de robo</option>

                    <option>Robo de vehículo</option>
                    <option>Robo en hogar</option>
                    <option>Robo en comercio</option>
                    <option>Robo en vía pública</option>
                    <option>Robo de bicicleta / moto</option>
                    <option>Robo de autopartes</option>
                    <option>Otro</option>

                </select>

                <small>
                    El tipo de robo será utilizado para ponderar
                    el mapa de calor.
                </small>

            </div>


            <!-- CUANDO SUCEDIÓ -->

            <div class="field">

                <label>
                    ¿Cuándo sucedió?
                    <span class="required">*</span>
                </label>

                <div class="date-row">

                    <input
                        type="date"
                        value="2026-08-25"
                    >

                    <input
                        type="time"
                        value="19:20"
                    >

                    <label class="live-toggle">

                        <input type="checkbox">

                        <span class="switch"></span>

                        <span>
                            En vivo
                        </span>

                    </label>

                </div>

                <small>
                    Si activás "En vivo", se utilizará automáticamente
                    la fecha y hora actual.
                </small>

            </div>

            <!-- ==========================
                 UBICACIÓN
            =========================== -->

            <div class="section-title">

                <span>02</span>

                <div>
                    <h2>Ubicación</h2>
                    <p>
                        La ubicación exacta puede mantenerse privada.
                    </p>
                </div>

            </div>


            <div class="location-box">

                <div class="location-icon">
                    <i class="fa-solid fa-location-dot"></i>
                </div>

                <div>

                    <strong>
                        Ubicación del incidente
                    </strong>

                    <p>
                        Seleccioná una ubicación en el mapa
                        o ingresá una dirección.
                    </p>

                </div>

                <button class="map-button">
                    Seleccionar
                </button>

            </div>

            <!-- ==========================
                 GRAVEDAD
            =========================== -->

            <div class="section-title">

                <span>03</span>

                <div>
                    <h2>Gravedad</h2>
                    <p>
                        Estas respuestas ayudan a determinar
                        el nivel de riesgo.
                    </p>
                </div>

            </div>


            <div class="questions">

                <div class="question">

                    <div>
                        <strong>¿Hubo violencia?</strong>
                        <span>
                            Agresión física o amenaza directa.
                        </span>
                    </div>

                    <div class="yes-no">

                        <button>No</button>
                        <button class="selected">Sí</button>

                    </div>

                </div>


                <div class="question">

                    <div>
                        <strong>¿Hubo arma de fuego?</strong>
                        <span>
                            Incluye exhibición o uso de un arma.
                        </span>
                    </div>

                    <div class="yes-no">

                        <button class="selected">No</button>
                        <button>Sí</button>

                    </div>

                </div>


                <div class="question">

                    <div>
                        <strong>
                            ¿Eran 2 o más delincuentes?
                        </strong>

                        <span>
                            Cantidad aproximada de personas involucradas.
                        </span>
                    </div>

                    <div class="yes-no">

                        <button class="selected">No</button>
                        <button>Sí</button>

                    </div>

                </div>

            </div>


            <!-- ==========================
                 DESCRIPCIÓN
            =========================== -->

            <div class="section-title">

                <span>04</span>

                <div>
                    <h2>Descripción</h2>
                    <p>
                        Contá brevemente qué sucedió.
                    </p>
                </div>

            </div>


            <div class="field">

                <textarea
                    minlength="20"
                    placeholder="Describí el incidente..."
                ></textarea>

                <div class="textarea-footer">
                    <span>Mínimo 20 caracteres</span>
                    <span>0 / 500</span>
                </div>

            </div>

            <!-- CONFIRMACIÓN -->

            <label class="confirmation">

                <input type="checkbox">

                <span>
                    Confirmo que la información proporcionada
                    es verdadera según mi conocimiento.
                </span>

            </label>


            <!-- BOTONES -->

            <div class="form-actions">

                <button class="cancelar">
                    Cancelar
                </button>

                <button class="publicar">
                    <i class="fa-solid fa-paper-plane"></i>
                    Publicar reporte
                </button>

            </div>

        </div>


        <!-- ==================================
             TAB COMUNITARIO
        =================================== -->

        <div class="tab-content comunitario hidden">

            <div class="section-title">

                <span>01</span>

                <div>
                    <h2>Incidente comunitario</h2>

                    <p>
                        Informá problemas que afectan
                        a tu barrio.
                    </p>
                </div>

            </div>


            <!-- TIPO -->

            <div class="field">

                <label>
                    Tipo de incidente
                    <span class="required">*</span>
                </label>

                <div class="incident-types">

                    <button class="incident-card active">

                        <i class="fa-solid fa-road"></i>

                        <strong>Obstrucción</strong>

                        <span>
                            Corte de calle, obra,
                            árbol caído.
                        </span>

                    </button>


                    <button class="incident-card">

                        <i class="fa-solid fa-lightbulb"></i>

                        <strong>Luminaria</strong>

                        <span>
                            Falta de iluminación
                            o cortes de luz.
                        </span>

                    </button>


                    <button class="incident-card">

                        <i class="fa-solid fa-tree"></i>

                        <strong>Espacios verdes</strong>

                        <span>
                            Poda, ramas o residuos.
                        </span>

                    </button>

                </div>

            </div>


            <!-- FECHA -->

            <div class="field">

                <label>
                    ¿Cuándo lo viste?
                    <span class="required">*</span>
                </label>

                <div class="date-row">

                    <input type="date">

                    <input type="time">

                    <label class="live-toggle">

                        <input type="checkbox">

                        <span class="switch"></span>

                        En vivo

                    </label>

                </div>

            </div>


            <!-- UBICACIÓN -->

            <div class="section-title">

                <span>02</span>

                <div>
                    <h2>Ubicación</h2>

                    <p>
                        Indicá dónde se encuentra el problema.
                    </p>
                </div>

            </div>


            <div class="location-box">

                <div class="location-icon">
                    <i class="fa-solid fa-location-dot"></i>
                </div>

                <div>

                    <strong>
                        Seleccionar ubicación
                    </strong>

                    <p>
                        La ubicación será visible
                        para otros usuarios.
                    </p>

                </div>

                <button class="map-button">
                    Seleccionar
                </button>

            </div>


            <!-- DESCRIPCIÓN -->

            <div class="section-title">

                <span>03</span>

                <div>
                    <h2>Descripción</h2>

                    <p>
                        Explicá el problema.
                    </p>
                </div>

            </div>


            <div class="field">

                <textarea
                    placeholder="Ej. Hay un árbol caído bloqueando completamente la calle..."
                ></textarea>

                <div class="textarea-footer">
                    <span>Máximo 500 caracteres</span>
                    <span>0 / 500</span>
                </div>

            </div>

            <!-- CONFIRMACIÓN -->

            <label class="confirmation">

                <input type="checkbox">

                <span>
                    Confirmo que la información proporcionada
                    es correcta según mi conocimiento.
                </span>

            </label>


            <div class="form-actions">

                <button class="cancelar">
                    Cancelar
                </button>

                <button class="publicar">
                    <i class="fa-solid fa-paper-plane"></i>
                    Publicar reporte
                </button>

            </div>

        </div>

    </div>

</section>

<script src="assets/js/post_form.js"></script>
    

</body>
</html>