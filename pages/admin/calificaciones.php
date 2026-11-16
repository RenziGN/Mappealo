<?php
require_once __DIR__ . '/../../config/auth.php';
requerir_admin();
require_once __DIR__ . '/../../config/connection.php';
require_once __DIR__ . '/_nav.php';

$resumen = $connection->query("
    SELECT COUNT(*) AS total, ROUND(AVG(valor), 1) AS promedio,
           SUM(valor = 1) AS v1, SUM(valor = 2) AS v2, SUM(valor = 3) AS v3, SUM(valor = 4) AS v4, SUM(valor = 5) AS v5
    FROM valoracion
")->fetch(PDO::FETCH_ASSOC);

$calificaciones = $connection->query("
    SELECT v.id_valoracion, v.id_reporte, v.valor, v.fecha, u.nombre_usuario, r.descripcion
    FROM valoracion v
    INNER JOIN usuario u ON v.id_usuario = u.id_usuario
    INNER JOIN reporte r ON v.id_reporte = r.id_reporte
    ORDER BY v.fecha DESC, v.id_valoracion DESC
    LIMIT 500
")->fetchAll(PDO::FETCH_ASSOC);

$peorCalificados = $connection->query("
    SELECT r.id_reporte, r.descripcion, ROUND(AVG(v.valor), 1) AS promedio, COUNT(*) AS cantidad
    FROM valoracion v INNER JOIN reporte r ON v.id_reporte = r.id_reporte
    GROUP BY r.id_reporte, r.descripcion
    HAVING AVG(v.valor) <= 2
    ORDER BY promedio ASC, cantidad DESC
    LIMIT 10
")->fetchAll(PDO::FETCH_ASSOC);

$total = (int) $resumen['total'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <?php admin_head('Calificaciones'); ?>
</head>
<body class="admin-body">
<div class="container py-4">

    <?php admin_nav($connection, 'calificaciones'); ?>
    <?php admin_mensajes(); ?>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <div class="text-muted small">Promedio general</div>
                    <div class="display-6"><?= $total ? admin_e($resumen['promedio']) : '-' ?></div>
                    <div class="text-warning"><?= $total ? admin_estrellas($resumen['promedio']) : '' ?></div>
                    <div class="small text-muted"><?= $total ?> calificación(es)</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <div class="text-muted small mb-2">Distribución</div>
                    <?php for ($n = 5; $n >= 1; $n--):
                        $cantidad = (int) $resumen["v$n"];
                        $porcentaje = $total ? round($cantidad * 100 / $total) : 0; ?>
                        <div class="d-flex align-items-center gap-2 small mb-1">
                            <span class="text-nowrap" style="width: 2.5rem;"><?= $n ?> ★</span>
                            <div class="progress flex-grow-1" style="height: 8px;" role="progressbar" aria-valuenow="<?= $porcentaje ?>" aria-valuemin="0" aria-valuemax="100">
                                <div class="progress-bar bg-warning" style="width: <?= $porcentaje ?>%"></div>
                            </div>
                            <span class="text-muted" style="width: 2rem;"><?= $cantidad ?></span>
                        </div>
                    <?php endfor; ?>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <div class="text-muted small mb-2">Reportes peor calificados (promedio ≤ 2)</div>
                    <?php if (!$peorCalificados): ?>
                        <div class="small text-muted">Ninguno.</div>
                    <?php endif; ?>
                    <?php foreach ($peorCalificados as $p): ?>
                        <div class="small mb-1 text-truncate">
                            <a href="reporte.php?id=<?= (int) $p['id_reporte'] ?>">#<?= (int) $p['id_reporte'] ?></a>
                            <span class="text-warning"><?= admin_estrellas($p['promedio']) ?></span>
                            <span class="text-muted">(<?= (int) $p['cantidad'] ?>)</span>
                            <?= admin_e($p['descripcion']) ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="table-responsive bg-white rounded shadow-sm">
        <table class="table align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Calificación</th>
                    <th>Usuario</th>
                    <th>Reporte</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!$calificaciones): ?>
                <tr><td colspan="4" class="text-center text-muted py-4">No hay calificaciones.</td></tr>
            <?php endif; ?>
            <?php foreach ($calificaciones as $v): ?>
                <tr>
                    <td class="text-warning text-nowrap"><?= admin_estrellas($v['valor']) ?></td>
                    <td class="small text-nowrap"><?= admin_e($v['nombre_usuario']) ?><br><span class="text-muted"><?= admin_fecha($v['fecha']) ?></span></td>
                    <td class="small">
                        <a href="reporte.php?id=<?= (int) $v['id_reporte'] ?>">#<?= (int) $v['id_reporte'] ?></a>
                        <div class="text-muted text-truncate" style="max-width: 260px;"><?= admin_e($v['descripcion']) ?></div>
                    </td>
                    <td class="text-end">
                        <?= admin_form_accion('borrar_calificacion', ['id' => $v['id_valoracion']], 'Borrar', 'btn btn-sm btn-outline-danger', "¿Borrar esta calificación?") ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
