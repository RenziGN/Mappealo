<?php
session_start();

if (!isset($_SESSION['user'])) {
    header("Location: pages/auth/login.php");
    exit;
}

$user = $_SESSION['user'];
$is_guest = isset($user['rol']) && $user['rol'] === 'guest';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mappealo - Home</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="assets/css/home.css">
</head>
<body>

    <div class="web-wrapper">
        
       <main class="map-view">
            
            <div id="map-canvas"></div>

            <div class="search-box">
                <div class="card shadow rounded-4 border-0 p-2">
                    <div class="input-group align-items-center">
                        
                        <div class="dropdown me-2">
                            <button class="btn btn-link text-dark p-2 border-0" type="button" data-bs-toggle="dropdown">
                                <i class="bi bi-list fs-4"></i>
                            </button>
                            
                            <ul class="dropdown-menu shadow border-0 mt-2">
                                <li>
                                    <span class="dropdown-item-text fw-bold border-bottom pb-2">
                                        <i class="fa-solid fa-circle-user me-2 text-primary"></i>
                                        <?php 
                                        if ($is_guest) {
                                            echo "Invitado";
                                        } else if ($user['is_admin']) {
                                            echo "Admin: " . $user['email'];
                                        } else {
                                            echo "Usuario: " . $user['email'];
                                        }
                                        ?>
                                    </span>
                                </li>
                                
                                <?php if ($user['is_admin'] && !$is_guest) { ?>
                                    <li>
                                        <a class="dropdown-item mt-2" href="pages/admin/user_list.php">
                                            <i class="fa-solid fa-gear me-2"></i>Panel Admin
                                        </a>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                <?php } ?>
                                
                                <li>
                                    <a class="dropdown-item text-danger mt-2" href="handler/auth/logout_handler.php">
                                        <i class="fa-solid fa-right-from-bracket me-2"></i>Cerrar Sesión
                                    </a>
                                </li>
                            </ul>
                        </div>

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
                    <button class="btn btn-dark btn-sm rounded-pill px-3 shadow-sm">Todo</button>
                    
                    <button class="btn btn-white bg-white btn-sm rounded-pill px-3 shadow-sm border">
                        <img src="assets/Img/ladron-icon.png" alt="Robos" class="img-filtro"> Robos
                    </button>
                    
                    <button class="btn btn-white bg-white btn-sm rounded-pill px-3 shadow-sm border">
                        <img src="assets/Img/comunidad.png" alt="Comunidad" class="img-filtro"> Comunidad
                    </button>
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
                <button class="btn btn-light shadow-sm">
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
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>