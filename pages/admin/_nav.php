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
        'reportes' => ['reportes.php', 'Reportes', null],
        'denuncias' => ['denuncias.php', 'Denuncias', $denunciados],
        'comentarios' => ['comentarios.php', 'Comentarios', null],
        'calificaciones' => ['calificaciones.php', 'Calificaciones', null],
        'usuarios' => ['user_list.php', 'Usuarios', null],
    ];
    ?>
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h1 class="h3 mb-0">Panel de administración</h1>
        <a href="../../index.php" class="btn btn-outline-primary btn-sm">Volver al mapa</a>
    </div>
    <ul class="nav nav-tabs mb-4 flex-nowrap overflow-auto">
        <?php foreach ($pestanas as $clave => [$url, $texto, $contador]): ?>
            <li class="nav-item">
                <a class="nav-link text-nowrap <?= $clave === $activa ? 'active' : '' ?>" href="<?= $url ?>">
                    <?= $texto ?>
                    <?php if ($contador): ?>
                        <span class="badge rounded-pill text-bg-danger ms-1"><?= $contador ?></span>
                    <?php endif; ?>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
    <?php
}
