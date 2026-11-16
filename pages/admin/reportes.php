<?php
require_once __DIR__ . '/../../config/auth.php';
requerir_admin();
require_once __DIR__ . '/../../config/connection.php';
require_once __DIR__ . '/_nav.php';
require_once __DIR__ . '/../../config/credibilidad.php';

$filtro = $_GET['filtro'] ?? 'todos';
$busqueda = trim((string) ($_GET['q'] ?? ''));

$condiciones = [];
$parametros = [];

if ($filtro === 'pendientes') {
    $condiciones[] = "r.estado = 'pendiente'";
} elseif ($filtro === 'rechazados') {
    $condiciones[] = "r.estado = 'rechazado'";
} elseif ($filtro === 'denunciados') {
    $condiciones[] = "(SELECT COUNT(*) FROM advertencia a WHERE a.id_reporte = r.id_reporte) > 0";
} elseif ($filtro === 'delito') {
    $condiciones[] = "rb.id_reporte IS NOT NULL";
} elseif ($filtro === 'comunitario') {
    $condiciones[] = "ic.id_reporte IS NOT NULL";
}

if ($busqueda !== '') {
    $condiciones[] = "(r.descripcion LIKE :q OR u.nombre_usuario LIKE :q OR tr.nombre LIKE :q OR ti.nombre LIKE :q OR ub.direccion LIKE :q)";
    $parametros[':q'] = '%' . $busqueda . '%';
}

$where = $condiciones ? 'WHERE ' . implode(' AND ', $condiciones) : '';

$stmt = $connection->prepare("
    SELECT
        r.id_reporte,
        r.descripcion,
        r.fecha_reporte,
        r.estado,
        u.nombre_usuario,
        ub.direccion,
        CASE WHEN rb.id_reporte IS NOT NULL THEN 'delito' WHEN ic.id_reporte IS NOT NULL THEN 'comunitario' ELSE 'otro' END AS categoria,
        COALESCE(tr.nombre, ti.nombre, '-') AS tipo,
        (SELECT ROUND(AVG(v.valor), 1) FROM valoracion v WHERE v.id_reporte = r.id_reporte) AS promedio,
        (SELECT COUNT(*) FROM valoracion v WHERE v.id_reporte = r.id_reporte) AS calificaciones,
        (SELECT COUNT(*) FROM comentario c WHERE c.id_reporte = r.id_reporte) AS comentarios,
        (SELECT COUNT(*) FROM advertencia a WHERE a.id_reporte = r.id_reporte) AS denuncias
    FROM reporte r
    INNER JOIN usuario u ON r.id_usuario = u.id_usuario
    INNER JOIN ubicacion ub ON r.id_ubicacion = ub.id_ubicacion
    LEFT JOIN robo rb ON r.id_reporte = rb.id_reporte
    LEFT JOIN tipo_robo tr ON rb.id_tipo_robo = tr.id_tipo_robo
    LEFT JOIN incidente_comunitario ic ON r.id_reporte = ic.id_reporte
    LEFT JOIN tipo_incidente ti ON ic.id_tipo_incidente = ti.id_tipo_incidente
    $where
    ORDER BY denuncias DESC, r.fecha_reporte DESC
");
$stmt->execute($parametros);
$reportes = $stmt->fetchAll(PDO::FETCH_ASSOC);

$credibilidades = credibilidad_reportes($connection);

if ($filtro === 'baja_credibilidad') {
    $reportes = array_values(array_filter($reportes, fn($r) => ($credibilidades[(int) $r['id_reporte']]['nivel']['clave'] ?? '') === 'baja'));
}

$estadisticas = $connection->query("
    SELECT
        COUNT(*) AS total,
        SUM(EXISTS (SELECT 1 FROM advertencia a WHERE a.id_reporte = r.id_reporte)) AS denunciados,
        SUM(NOT EXISTS (SELECT 1 FROM valoracion v WHERE v.id_reporte = r.id_reporte)) AS sin_calificar
    FROM reporte r
")->fetch(PDO::FETCH_ASSOC);
$bajaCredibilidad = count(array_filter($credibilidades, fn($c) => ($c['nivel']['clave'] ?? '') === 'baja'));

$filtros = ['todos' => 'Todos', 'pendientes' => 'Pendientes', 'denunciados' => 'Con denuncias', 'baja_credibilidad' => 'Baja credibilidad', 'delito' => 'Delitos', 'comunitario' => 'Comunitarios', 'rechazados' => 'Rechazados'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <?php admin_head('Reportes'); ?>
</head>
<body class="admin-body">
<div class="container py-4">

    <?php admin_nav($connection, 'reportes'); ?>
    <?php admin_mensajes(); ?>

    <form class="row g-2 align-items-center mb-3" method="GET">
        <div class="col-12 col-md-auto">
            <div class="btn-group flex-wrap" role="group">
                <?php foreach ($filtros as $clave => $texto): ?>
                    <a href="?filtro=<?= $clave ?><?= $busqueda !== '' ? '&q=' . urlencode($busqueda) : '' ?>"
                       class="btn btn-sm <?= $filtro === $clave ? 'btn-dark' : 'btn-outline-dark' ?>"><?= $texto ?></a>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="col">
            <input type="hidden" name="filtro" value="<?= admin_e($filtro) ?>">
            <input type="search" name="q" value="<?= admin_e($busqueda) ?>" class="form-control form-control-sm" placeholder="Buscar por descripción, usuario, tipo de reporte o dirección">
        </div>
        <div class="col-auto">
            <button class="btn btn-sm btn-primary">Buscar</button>
        </div>
    </form>

    <div class="admin-stats">
        <a href="?filtro=todos" class="admin-stat">
            <span class="admin-stat-icono azul"><i class="fa-solid fa-map-location-dot"></i></span>
            <span><strong><?= (int) $estadisticas['total'] ?></strong><small>Reportes</small></span>
        </a>
        <a href="?filtro=denunciados" class="admin-stat">
            <span class="admin-stat-icono rojo"><i class="fa-solid fa-flag"></i></span>
            <span><strong><?= (int) $estadisticas['denunciados'] ?></strong><small>Con denuncias</small></span>
        </a>
        <a href="?filtro=baja_credibilidad" class="admin-stat">
            <span class="admin-stat-icono naranja"><i class="fa-solid fa-shield-halved"></i></span>
            <span><strong><?= $bajaCredibilidad ?></strong><small>Baja credibilidad</small></span>
        </a>
        <div class="admin-stat">
            <span class="admin-stat-icono gris"><i class="fa-regular fa-star"></i></span>
            <span><strong><?= (int) $estadisticas['sin_calificar'] ?></strong><small>Sin calificar</small></span>
        </div>
    </div>

    <p class="text-muted small mb-2"><?= count($reportes) ?> reporte(s) en esta vista. Los que tienen denuncias aparecen primero.</p>

    <div class="table-responsive">
        <table class="table table-hover align-middle admin-tabla-reportes">
            <thead>
                <tr>
                    <th>Reporte</th>
                    <th>Autor</th>
                    <th class="text-center">Credibilidad</th>
                    <th class="text-center">Calificación</th>
                    <th class="text-center">Actividad</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!$reportes): ?>
                <tr><td colspan="6" class="text-center text-muted py-4">No hay reportes para mostrar.</td></tr>
            <?php endif; ?>
            <?php foreach ($reportes as $r): ?>
                <tr class="<?= $r['denuncias'] > 0 ? 'fila-denunciada' : '' ?>">
                    <td class="celda-reporte">
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <span class="admin-id">#<?= (int) $r['id_reporte'] ?></span>
                            <?= admin_estado($r['estado']) ?>
                            <span class="badge <?= $r['categoria'] === 'delito' ? 'text-bg-danger' : 'text-bg-success' ?>"><?= admin_e(ucfirst($r['categoria'])) ?></span>
                        </div>
                        <strong class="d-block"><?= admin_e($r['tipo']) ?></strong>
                        <div class="small text-muted text-truncate" title="<?= admin_e($r['descripcion']) ?>"><?= admin_e($r['descripcion']) ?></div>
                    </td>
                    <td class="small text-nowrap">
                        <span class="fw-semibold"><?= admin_e($r['nombre_usuario']) ?></span><br>
                        <span class="text-muted"><?= admin_fecha($r['fecha_reporte']) ?></span>
                    </td>
                    <td class="text-center"><?= admin_credibilidad($credibilidades[(int) $r['id_reporte']] ?? null) ?></td>
                    <td class="text-center text-nowrap">
                        <?php if ($r['calificaciones'] > 0): ?>
                            <span class="text-warning"><?= admin_estrellas($r['promedio']) ?></span>
                            <div class="small text-muted"><?= admin_e($r['promedio']) ?> (<?= (int) $r['calificaciones'] ?>)</div>
                        <?php else: ?>
                            <span class="small text-muted">Sin calificar</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-center text-nowrap">
                        <span class="admin-chip" title="Comentarios"><i class="fa-regular fa-comment"></i> <?= (int) $r['comentarios'] ?></span>
                        <span class="admin-chip <?= $r['denuncias'] > 0 ? 'rojo' : '' ?>" title="Denuncias"><i class="fa-solid fa-flag"></i> <?= (int) $r['denuncias'] ?></span>
                    </td>
                    <td class="text-end text-nowrap">
                        <a href="reporte.php?id=<?= (int) $r['id_reporte'] ?>" class="btn btn-sm btn-outline-primary btn-icono" title="Ver detalle" aria-label="Ver detalle del reporte #<?= (int) $r['id_reporte'] ?>"><i class="fa-solid fa-eye"></i></a>
                        <?= admin_form_accion('borrar_reporte', ['id' => $r['id_reporte']], '<i class="fa-solid fa-trash"></i>', 'btn btn-sm btn-outline-danger btn-icono',
                            "¿Borrar el reporte #{$r['id_reporte']}? También se borran sus comentarios, calificaciones y denuncias. No se puede deshacer.") ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
