<?php
require_once __DIR__ . '/../../config/auth.php';
requerir_admin();
require_once __DIR__ . '/../../config/connection.php';
require_once __DIR__ . '/_nav.php';

$filas = $connection->query("
    SELECT
        a.id_advertencia, a.id_reporte, a.motivo, a.fecha,
        u.nombre_usuario,
        r.descripcion,
        autor.nombre_usuario AS autor_reporte,
        CASE WHEN rb.id_reporte IS NOT NULL THEN 'delito' WHEN ic.id_reporte IS NOT NULL THEN 'comunitario' ELSE 'otro' END AS categoria,
        COALESCE(tr.nombre, ti.nombre, '-') AS tipo
    FROM advertencia a
    INNER JOIN usuario u ON a.id_usuario = u.id_usuario
    INNER JOIN reporte r ON a.id_reporte = r.id_reporte
    INNER JOIN usuario autor ON r.id_usuario = autor.id_usuario
    LEFT JOIN robo rb ON r.id_reporte = rb.id_reporte
    LEFT JOIN tipo_robo tr ON rb.id_tipo_robo = tr.id_tipo_robo
    LEFT JOIN incidente_comunitario ic ON r.id_reporte = ic.id_reporte
    LEFT JOIN tipo_incidente ti ON ic.id_tipo_incidente = ti.id_tipo_incidente
    ORDER BY a.fecha DESC
")->fetchAll(PDO::FETCH_ASSOC);

$porReporte = [];
foreach ($filas as $f) {
    $porReporte[$f['id_reporte']] ??= [
        'id' => (int) $f['id_reporte'],
        'descripcion' => $f['descripcion'],
        'autor' => $f['autor_reporte'],
        'categoria' => $f['categoria'],
        'tipo' => $f['tipo'],
        'ultima' => $f['fecha'],
        'denuncias' => []
    ];
    $porReporte[$f['id_reporte']]['denuncias'][] = $f;
}

uasort($porReporte, fn($a, $b) => [count($b['denuncias']), $b['ultima']] <=> [count($a['denuncias']), $a['ultima']]);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <?php admin_head('Denuncias'); ?>
</head>
<body class="admin-body">
<div class="container py-4">

    <?php admin_nav($connection, 'denuncias'); ?>
    <?php admin_mensajes(); ?>

    <p class="text-muted small">
        <?= count($porReporte) ?> reporte(s) denunciado(s), con <?= count($filas) ?> denuncia(s) en total.
        Para cada uno podés <strong>borrar el reporte</strong> si la denuncia es correcta, o <strong>descartar las denuncias</strong> si el reporte es válido.
    </p>

    <?php if (!$porReporte): ?>
        <div class="alert alert-success">No hay denuncias pendientes. 🎉</div>
    <?php endif; ?>

    <?php foreach ($porReporte as $rep): ?>
        <div class="card shadow-sm border-0 border-start border-danger border-4 mb-3">
            <div class="card-body">
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
                    <div>
                        <span class="badge text-bg-danger"><?= count($rep['denuncias']) ?> denuncia(s)</span>
                        <span class="badge <?= $rep['categoria'] === 'delito' ? 'text-bg-dark' : 'text-bg-success' ?>"><?= admin_e(ucfirst($rep['categoria'])) ?></span>
                        <h2 class="h6 mt-2 mb-1">#<?= $rep['id'] ?> · <?= admin_e($rep['tipo']) ?> <span class="text-muted fw-normal small">por <?= admin_e($rep['autor']) ?></span></h2>
                        <p class="small mb-0" style="white-space: pre-line;"><?= admin_e($rep['descripcion']) ?></p>
                    </div>
                    <div class="d-flex flex-wrap gap-1">
                        <a href="reporte.php?id=<?= $rep['id'] ?>" class="btn btn-sm btn-outline-primary">Ver detalle</a>
                        <?= admin_form_accion('descartar_denuncias', ['id' => $rep['id']], 'Descartar denuncias', 'btn btn-sm btn-outline-secondary',
                            "¿Descartar las denuncias del reporte #{$rep['id']}? El reporte sigue publicado.") ?>
                        <?= admin_form_accion('borrar_reporte', ['id' => $rep['id']], 'Borrar reporte', 'btn btn-sm btn-danger',
                            "¿Borrar el reporte #{$rep['id']}? También se borran sus comentarios, calificaciones y denuncias. No se puede deshacer.") ?>
                    </div>
                </div>
                <ul class="list-unstyled small mt-3 mb-0 border-top pt-2">
                    <?php foreach ($rep['denuncias'] as $d): ?>
                        <li class="mb-1">
                            <i class="text-danger">⚑</i> <?= admin_e($d['motivo']) ?>
                            <span class="text-muted">— <?= admin_e($d['nombre_usuario']) ?>, <?= admin_fecha($d['fecha']) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    <?php endforeach; ?>
</div>
</body>
</html>
