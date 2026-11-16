<?php

function admin_e($texto): string
{
    return htmlspecialchars((string) $texto, ENT_QUOTES, 'UTF-8');
}

function admin_fecha(?string $fecha): string
{
    if (!$fecha) {
        return '';
    }
    $t = strtotime($fecha);
    return $t ? date('d/m/Y H:i', $t) : admin_e($fecha);
}

function admin_estrellas($valor): string
{
    $n = max(0, min(5, (int) round((float) $valor)));
    return str_repeat('★', $n) . str_repeat('☆', 5 - $n);
}

function admin_credibilidad(?array $cred, bool $conTexto = true): string
{
    if (!$cred) {
        return '<span class="text-muted small">-</span>';
    }
    $clase = ['alta' => 'text-bg-success', 'media' => 'text-bg-warning', 'baja' => 'text-bg-danger'][$cred['nivel']['clave']] ?? 'text-bg-secondary';
    $texto = $conTexto ? admin_e($cred['nivel']['texto']) . ' · ' : '';
    return '<span class="badge ' . $clase . '" title="Índice de credibilidad (0 a 100)"><i class="fa-solid fa-shield-halved me-1"></i>' . $texto . (int) $cred['puntaje'] . '</span>';
}

function admin_estado(?string $estado): string
{
    $estado = strtolower((string) $estado);
    $datos = [
        'verificado' => ['text-bg-success', 'fa-circle-check', 'Verificado'],
        'rechazado' => ['text-bg-danger', 'fa-ban', 'Rechazado'],
        'pendiente' => ['text-bg-warning', 'fa-hourglass-half', 'Pendiente'],
    ];
    [$clase, $icono, $texto] = $datos[$estado] ?? $datos['pendiente'];
    return '<span class="badge ' . $clase . '"><i class="fa-solid ' . $icono . ' me-1"></i>' . $texto . '</span>';
}

function admin_mensajes(): void
{
    if (!empty($_SESSION['errors'])) {
        echo '<div class="alert alert-danger"><ul class="mb-0">';
        foreach ((array) $_SESSION['errors'] as $error) {
            echo '<li>' . admin_e($error) . '</li>';
        }
        echo '</ul></div>';
        unset($_SESSION['errors']);
    }

    if (!empty($_SESSION['success'])) {
        echo '<div class="alert alert-success">' . admin_e($_SESSION['success']) . '</div>';
        unset($_SESSION['success']);
    }
}

function admin_form_accion(string $accion, array $campos, string $texto, string $clase, string $confirmacion): string
{
    $html = '<form method="POST" action="../../handler/admin/moderacion_handler.php" class="d-inline" onsubmit="return confirm(' . admin_e(json_encode($confirmacion)) . ');">';
    $html .= '<input type="hidden" name="csrf_token" value="' . admin_e(csrf_token()) . '">';
    $html .= '<input type="hidden" name="accion" value="' . admin_e($accion) . '">';
    $html .= '<input type="hidden" name="volver" value="' . admin_e($_SERVER['REQUEST_URI'] ?? '') . '">';
    foreach ($campos as $nombre => $valor) {
        $html .= '<input type="hidden" name="' . admin_e($nombre) . '" value="' . admin_e($valor) . '">';
    }
    $html .= '<button type="submit" class="' . admin_e($clase) . '">' . $texto . '</button></form>';
    return $html;
}

function admin_nav(PDO $connection, string $activa): void
{
    $denunciados = (int) $connection->query("SELECT COUNT(DISTINCT id_reporte) FROM advertencia")->fetchColumn();

    $pestanas = [
        'reportes' => ['reportes.php', 'Reportes', 'fa-map-location-dot', null],
        'denuncias' => ['denuncias.php', 'Denuncias', 'fa-flag', $denunciados],
        'comentarios' => ['comentarios.php', 'Comentarios', 'fa-comments', null],
        'calificaciones' => ['calificaciones.php', 'Calificaciones', 'fa-star', null],
        'usuarios' => ['user_list.php', 'Usuarios', 'fa-users', null],
    ];
    $email = $_SESSION['user']['email'] ?? '';
    ?>
    <header class="admin-hero">
        <div class="admin-hero-marca">
            <img src="../../assets/Img/LOGO%20MAPPEALO%201.png" alt="Mappealo" class="admin-hero-logo">
            <div>
                <span class="admin-hero-brand">MAPPEALO</span>
                <h1>Panel de administración</h1>
                <span class="admin-hero-usuario"><i class="fa-solid fa-circle-user me-1"></i><?= admin_e($email) ?></span>
            </div>
        </div>
        <div class="admin-hero-acciones">
            <a href="../../index.php" class="btn-hero-mapa"><i class="fa-solid fa-map"></i> Volver al mapa</a>
            <a href="../../handler/auth/logout_handler.php" class="btn-hero-salir"><i class="fa-solid fa-right-from-bracket"></i> Salir</a>
        </div>
    </header>
    <nav>
        <ul class="admin-tabs">
            <?php foreach ($pestanas as $clave => [$url, $texto, $icono, $contador]): ?>
                <li>
                    <a class="<?= $clave === $activa ? 'active' : '' ?>" href="<?= $url ?>" <?= $clave === $activa ? 'aria-current="page"' : '' ?>>
                        <i class="fa-solid <?= $icono ?>"></i> <?= $texto ?>
                        <?php if ($contador): ?>
                            <span class="badge-tab"><?= $contador ?></span>
                        <?php endif; ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </nav>
    <?php
}

function admin_head(string $titulo): void
{
    ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= admin_e($titulo) ?> · Mappealo Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="../../assets/css/admin.css?v=6">
    <?php
}
