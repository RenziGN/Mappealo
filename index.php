<?php

session_start();

if(!isset($_SESSION['user'])) {

    header("Location: pages/auth/login.php");

    exit;
}

$user = $_SESSION['user'];
$is_guest = isset($user['rol']) && $user['rol'] === 'guest';
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

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
      integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY="
       crossorigin=""/>
        <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/home.css?v=4">

</head>

<body>

<main >
            
             <div id="map"></div>


            <div class="search-box">
                <div class="card shadow rounded-4 border-0 p-2">
                    <div class="input-group align-items-center">
                        
                        <button class="btn btn-link text-dark p-2 border-0 me-2" type="button" id="btnAbrirDrawer">
                           <i class="bi bi-list fs-4"></i>
                        </button>

                        <input type="text" class="form-control border-0 ps-1" placeholder="Buscar en Mappealo...">
                        
                        <span class="input-group-text bg-transparent border-0"><i class="bi bi-search text-muted"></i></span>
                        
                        <span class="input-group-text bg-transparent border-0 pre-logo-search">
                            <img src="assets/Img/LOGO MAPPEALO 1.png" alt="Mappealo" class="img-search-logo">
                        </span>
                    </div>
                </div>
            </div>

            <div class="filter-bar">
                <div class="d-flex gap-2">
                    <button id="btnFiltroTodo" class="btn btn-dark btn-sm rounded-pill px-3 shadow-sm">Todo</button>
                    
                    <button id="btnFiltroRobos" class="btn btn-white bg-white btn-sm rounded-pill px-3 shadow-sm border">
                        <img src="assets/Img/ladron-icon.png" alt="Robos" class="img-filtro"> Robos
                    </button>
                    
                    <button id="btnFiltroComunidad" class="btn btn-white bg-white btn-sm rounded-pill px-3 shadow-sm border">
                        <img src="assets/Img/comunidad.png" alt="Comunidad" class="img-filtro"> Comunidad
                    </button>
                </div>
                <!-- Deslizador de Subtipos de Robos -->
                <div id="subfiltros-robos" class="panel-subfiltros d-none">
                    <label class="subfiltro-item">
                        <input type="checkbox" class="filtro-subtipo" data-cat="delito" value="Robo a mano armada" checked>
                        <span>A mano armada</span>
                    </label>
                    <label class="subfiltro-item">
                        <input type="checkbox" class="filtro-subtipo" data-cat="delito" value="Hurto / Arrebato en vía pública" checked>
                        <span>Hurto / Arrebato</span>
                    </label>
                    <label class="subfiltro-item">
                        <input type="checkbox" class="filtro-subtipo" data-cat="delito" value="Robo de vehículo / Motochorros" checked>
                        <span>Vehículo / Motochorros</span>
                    </label>
                </div>

                <!-- Deslizador de Subtipos de Comunidad -->
                <div id="subfiltros-comunidad" class="panel-subfiltros d-none">
                    <label class="subfiltro-item">
                        <input type="checkbox" class="filtro-subtipo" data-cat="comunitario" value="Luminaria rota" checked>
                        <span>Luminaria rota</span>
                    </label>
                    <label class="subfiltro-item">
                        <input type="checkbox" class="filtro-subtipo" data-cat="comunitario" value="Bache / Calle anegada" checked>
                        <span>Bache / Calle anegada</span>
                    </label>
                    <label class="subfiltro-item">
                        <input type="checkbox" class="filtro-subtipo" data-cat="comunitario" value="Basura acumulada" checked>
                        <span>Basura acumulada</span>
                    </label>
                </div>

                
            </div>

            <div class="report-box">
                <button class="btn btn-primary btn-lg shadow rounded-pill px-4 py-2">
                    <i class="bi bi-plus-lg me-2"></i> REPORTAR ALERTA
                </button>
            </div>

            <div class="sidebar-buttons">
                <button class="btn-circle btn-police" title="Policía">
                    <img src="assets/Img/policia.png" alt="Policía" class="img-vehiculo">
                </button>
                <button class="btn-circle btn-ambulance" title="Ambulancia">
                    <img src="assets/Img/ambulancia.png" alt="Ambulancia" class="img-vehiculo">
                </button>
                <button class="btn-circle btn-fire" title="Bomberos">
                    <img src="assets/Img/bombero.png" alt="Bomberos" class="img-vehiculo">
                </button>
            </div>

            <div class="bottom-buttons">
                <button id="btnCalor" class="btn btn-light shadow-sm">
                    <img src="assets/Img/fuego.png" alt="Calor" class="img-fuego"> Calor
                </button>
                <button class="btn btn-light shadow-sm" title="Centrar">
                    <i class="bi bi-geo-fill me-1"></i> Centrar
                </button>
                <button class="btn-circle btn-sos" title="S.O.S Emergencias">
                    <i class="bi bi-telephone-fill fs-5"></i>
                </button>
            </div>

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
                  <option value="Robo a mano armada">Robo a mano armada</option>
                  <option value="Hurto / Arrebato en vía pública">Hurto / Arrebato en vía pública</option>
                  <option value="Robo de vehículo / Motochorros">Robo de vehículo / Motochorros</option>
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
                    <button type="button" class="incident-card active">
                        <i class="fa-solid fa-lightbulb"></i>
                        <strong>Luminaria rota</strong>
                        <span>Falta total o parcial de iluminación en la vía pública.</span>
                    </button>

                    <button type="button" class="incident-card">
                        <i class="fa-solid fa-road"></i>
                        <strong>Bache / Calle anegada</strong>
                        <span>Pozo peligroso o calle intransitable.</span>
                    </button>

                    <button type="button" class="incident-card">
                        <i class="fa-solid fa-trash-can"></i>
                        <strong>Basura acumulada</strong>
                        <span>Microbasural o residuos que obstruyen la vereda.</span>
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

    
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
    
    <script src="./assets/js/post_form.js"></script>
    <script src="./assets/js/map.js"></script>

    <!-- Overlay oscuro de fondo -->
<div id="drawer-overlay" class="drawer-overlay d-none"></div>

<!-- Panel Lateral Nav-Drawer -->
<aside id="nav-drawer" class="nav-drawer">
    <!-- Cabecera dinámica -->
    <?php if (!$is_guest): ?>
        <?php 
            $email_completo = $user['email'] ?? 'Usuario';
            $alias_usuario = explode('@', $email_completo)[0];
        ?>
        <div class="drawer-header verified">
            <div class="avatar-container">
                <i class="fa-solid fa-circle-user avatar-icon"></i>
                <span class="badge-verified"><i class="fa-solid fa-check"></i></span>
            </div>
            <div class="user-meta">
                <h3 title="<?= htmlspecialchars($email_completo) ?>">
                    <?= htmlspecialchars($alias_usuario) ?>
                </h3>
                <span class="user-role-badge">Usuario verificado</span>
            </div>
        </div>
    <?php else: ?>
        <div class="drawer-header guest">
            <div class="avatar-container">
                <i class="fa-solid fa-circle-user avatar-icon"></i>
            </div>
            <div class="user-meta">
                <h3>Invitado</h3>
                <div class="auth-links">
                    <a href="pages/auth/login.php" class="link-auth">Iniciar sesión</a>
                    <span class="auth-divider">o</span>
                    <a href="pages/auth/register.php" class="link-auth">Crear una cuenta</a>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Secciones de Navegación -->
    <div class="drawer-body">
        
        <!-- Alertas Vecinales -->
        <div class="drawer-section">
            <h4 class="section-title">Alertas vecinales</h4>
            
            <a href="#mis-reportes" class="drawer-item" id="btnDrawerMisReportes">
                <i class="fa-solid fa-clock-rotate-left"></i>
                <span>Tus reportes</span>
            </a>

            <button type="button" class="drawer-item" id="btnDrawerCargarRobo">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <span>Cargar delito</span>
            </button>
            <button type="button" class="drawer-item" id="btnDrawerCargarComunitario">
                <i class="fa-solid fa-bullhorn"></i>
                <span>Cargar reporte barrial</span>
            </button>
        </div>

        <!-- Capas del Mapa -->
        <div class="drawer-section">
            <h4 class="section-title">Capas del mapa</h4>
            <div class="drawer-toggle-item">
                <div class="toggle-info">
                    <i class="fa-solid fa-fire text-danger"></i>
                    <span>Mapa de calor</span>
                </div>
                <div class="form-check form-switch m-0">
                    <input class="form-check-input" type="checkbox" role="switch" id="toggleDrawerCalor">
                </div>
            </div>

            <div class="drawer-toggle-item">
                <div class="toggle-info">
                    <i class="fa-solid fa-draw-polygon text-primary"></i>
                    <span>Jurisdicciones / Zonas</span>
                </div>
                <div class="form-check form-switch m-0">
                    <input class="form-check-input" type="checkbox" role="switch" id="toggleDrawerJurisdiccion">
                </div>
            </div>
        </div>

        <!-- Emergencias Rápidas compactas -->
        <div class="drawer-section">
            <h4 class="section-title">Emergencias directas</h4>
            <div class="sos-grid">
                <a href="tel:911" class="sos-chip police">
                    <i class="fa-solid fa-shield"></i>
                    <span>911</span>
                </a>
                <a href="tel:107" class="sos-chip ambulance">
                    <i class="fa-solid fa-truck-medical"></i>
                    <span>107</span>
                </a>
                <a href="tel:100" class="sos-chip fire">
                    <i class="fa-solid fa-fire-extinguisher"></i>
                    <span>100</span>
                </a>
            </div>
        </div>

        <!-- Administración -->
        <?php if (!empty($user['is_admin']) && !$is_guest): ?>
            <div class="drawer-section">
                <h4 class="section-title">Administración</h4>
                <a href="pages/admin/user_list.php" class="drawer-item admin-item">
                    <i class="fa-solid fa-gear"></i>
                    <span>Panel de Control</span>
                </a>
            </div>
        <?php endif; ?>

        <!-- Botón de Cerrar Sesión inferior para usuarios autenticados -->
        <!-- Botón inferior para salir / cerrar sesión -->
        <!-- Botón inferior para cerrar sesión -->
        <div class="drawer-section mt-auto pt-2">
          <a href="handler/auth/logout_handler.php" class="drawer-item text-danger bg-danger-subtle">
         <i class="fa-solid fa-right-from-bracket text-danger"></i>
         <span class="text-danger fw-bold">Cerrar sesión</span>
          </a>
        </div>

    </div>

    <!-- Pie del Drawer -->
    <!-- Pie del Drawer -->
    <div class="drawer-footer">
     <span class="brand-text d-flex align-items-center justify-content-center gap-2">
        <img src="assets/Img/LOGO MAPPEALO 1.png" alt="Logo Mappealo" class="drawer-logo">
        <span>MAPPEALO<sup>&reg;</sup></span>
     </span>
    </div>
</aside>

<script>
  const isGuest = <?= json_encode($is_guest) ?>;
  const navDrawer = document.getElementById('nav-drawer');
  const drawerOverlay = document.getElementById('drawer-overlay');
  const btnAbrirDrawer = document.getElementById('btnAbrirDrawer');

  function alternarNavDrawer() {
      if (!navDrawer) return;
      const isOpen = navDrawer.classList.contains('open');
      if (isOpen) {
          navDrawer.classList.remove('open');
          drawerOverlay?.classList.add('d-none');
      } else {
          navDrawer.classList.add('open');
          drawerOverlay?.classList.remove('d-none');
      }
  }

  btnAbrirDrawer?.addEventListener('click', (e) => {
      e.preventDefault();
      alternarNavDrawer();
  });

  drawerOverlay?.addEventListener('click', alternarNavDrawer);

  // Apertura de reportes para ambos perfiles
  function dispararFormulario(tipo) {
      alternarNavDrawer();

      if (tipo === 'delito') {
          document.querySelectorAll('.tabs .tab').forEach(t => t.classList.remove('active'));
          document.querySelector('.tabs .tab:first-child')?.classList.add('active');
          document.querySelector('.tab-content.delito')?.classList.remove('hidden');
          document.querySelector('.tab-content.comunitario')?.classList.add('hidden');
      } else {
          document.querySelectorAll('.tabs .tab').forEach(t => t.classList.remove('active'));
          document.querySelector('.tabs .tab:nth-child(2)')?.classList.add('active');
          document.querySelector('.tab-content.delito')?.classList.add('hidden');
          document.querySelector('.tab-content.comunitario')?.classList.remove('hidden');
      }

      // Abre el popup existente
      document.querySelector('.report-box button')?.click();
  }

  document.getElementById('btnDrawerCargarRobo')?.addEventListener('click', () => dispararFormulario('delito'));
  document.getElementById('btnDrawerCargarComunitario')?.addEventListener('click', () => dispararFormulario('comunitario'));

  // Manejo de "Tus reportes"
  document.getElementById('btnDrawerMisReportes')?.addEventListener('click', (e) => {
      e.preventDefault();
      alternarNavDrawer();
      if (isGuest) {
          alert("Al estar como invitado, tus reportes son anónimos. Creá una cuenta o iniciá sesión para administrarlos y seguir su estado.");
      } else {
          alert("Próximamente: Historial de tus alertas publicadas.");
      }
  });

  // Switch de mapa de calor sincronizado con el botón flotante inferior
  document.getElementById('toggleDrawerCalor')?.addEventListener('change', () => {
      document.getElementById('btnCalor')?.click();
  });

  // Switch de jurisdicciones
  document.getElementById('toggleDrawerJurisdiccion')?.addEventListener('change', (e) => {
      if (typeof window.toggleCapaJurisdicciones === 'function') {
          window.toggleCapaJurisdicciones(e.target.checked);
      } else {
          console.log("Capa de jurisdicciones:", e.target.checked);
      }
  });
</script>
</body>
</html>