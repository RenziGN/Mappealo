<?php
require_once __DIR__ . '/../../config/auth.php';
requerir_admin();
require_once __DIR__ . '/../../config/connection.php';
require_once __DIR__ . '/_nav.php';

$filtro = $_GET['filtro'] ?? 'todos';
$busqueda = trim((string) ($_GET['q'] ?? ''));

$condiciones = [];
$parametros = [];

if ($filtro === 'denunciados') {
    $condiciones[] = "(SELECT COUNT(*) FROM advertencia a WHERE a.id_reporte = r.id_reporte) > 0";
} elseif ($filtro === 'delito') {
    $condiciones[] = "rb.id_reporte IS NOT NULL";
} elseif ($filtro === 'comunitario') {
    $condiciones[] = "ic.id_reporte IS NOT NULL";
}

if ($busqueda !== '') {
    $condiciones[] = "(r.descripcion LIKE :q OR u.nombre_usuario LIKE :q OR ub.direccion LIKE :q)";
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

$filtros = ['todos' => 'Todos', 'denunciados' => 'Con denuncias', 'delito' => 'Delitos', 'comunitario' => 'Comunitarios'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes · Administración</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
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
            <input type="search" name="q" value="<?= admin_e($busqueda) ?>" class="form-control form-control-sm" placeholder="Buscar por descripción, usuario o dirección">
        </div>
        <div class="col-auto">
            <button class="btn btn-sm btn-primary">Buscar</button>
        </div>
    </form>

    <p class="text-muted small"><?= count($reportes) ?> reporte(s). Los que tienen denuncias aparecen primero.</p>

    <div class="table-responsive bg-white rounded shadow-sm">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Reporte</th>
                    <th>Publicado</th>
                    <th class="text-center">Calificación</th>
                    <th class="text-center">Comentarios</th>
                    <th class="text-center">Denuncias</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!$reportes): ?>
                <tr><td colspan="7" class="text-center text-muted py-4">No hay reportes para mostrar.</td></tr>
            <?php endif; ?>
            <?php foreach ($reportes as $r): ?>
                <tr class="<?= $r['denuncias'] > 0 ? 'table-danger' : '' ?>">
                    <td><?= (int) $r['id_reporte'] ?></td>
                    <td style="min-width: 260px;">
                        <span class="badge <?= $r['categoria'] === 'delito' ? 'text-bg-danger' : 'text-bg-success' ?>"><?= admin_e(ucfirst($r['categoria'])) ?></span>
                        <strong class="ms-1"><?= admin_e($r['tipo']) ?></strong>
                        <div class="small text-muted text-truncate" style="max-width: 360px;" title="<?= admin_e($r['descripcion']) ?>"><?= admin_e($r['descripcion']) ?></div>
                    </td>
                    <td class="small text-nowrap">
                        <?= admin_e($r['nombre_usuario']) ?><br>
                        <span class="text-muted"><?= admin_fecha($r['fecha_reporte']) ?></span>
                    </td>
                    <td class="text-center text-nowrap">
                        <?php if ($r['calificaciones'] > 0): ?>
                            <span class="text-warning"><?= admin_estrellas($r['promedio']) ?></span>
                            <div class="small text-muted"><?= admin_e($r['promedio']) ?> (<?= (int) $r['calificaciones'] ?>)</div>
                        <?php else: ?>
                            <span class="small text-muted">Sin calificar</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-center"><?= (int) $r['comentarios'] ?></td>
                    <td class="text-center">
                        <?php if ($r['denuncias'] > 0): ?>
                            <span class="badge text-bg-danger"><?= (int) $r['denuncias'] ?></span>
                        <?php else: ?>
                            <span class="text-muted">0</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-end text-nowrap">
                        <a href="reporte.php?id=<?= (int) $r['id_reporte'] ?>" class="btn btn-sm btn-outline-primary">Ver detalle</a>
                        <?= admin_form_accion('borrar_reporte', ['id' => $r['id_reporte']], 'Borrar', 'btn btn-sm btn-danger',
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
