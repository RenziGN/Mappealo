<?php
require_once __DIR__ . '/../../config/auth.php';
requerir_admin();
require_once __DIR__ . '/../../config/connection.php';
require_once __DIR__ . '/_nav.php';
require_once __DIR__ . '/../../config/credibilidad.php';

$id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

$reporte = false;
if ($id !== false) {
    $stmt = $connection->prepare("
        SELECT
            r.*, u.nombre_usuario, u.email, ub.direccion, ub.latitud, ub.longitud,
            CASE WHEN rb.id_reporte IS NOT NULL THEN 'delito' WHEN ic.id_reporte IS NOT NULL THEN 'comunitario' ELSE 'otro' END AS categoria,
            COALESCE(tr.nombre, ti.nombre, '-') AS tipo,
            rb.hubo_violencia, rb.hubo_arma, rb.multiples_delincuentes
        FROM reporte r
        INNER JOIN usuario u ON r.id_usuario = u.id_usuario
        INNER JOIN ubicacion ub ON r.id_ubicacion = ub.id_ubicacion
        LEFT JOIN robo rb ON r.id_reporte = rb.id_reporte
        LEFT JOIN tipo_robo tr ON rb.id_tipo_robo = tr.id_tipo_robo
        LEFT JOIN incidente_comunitario ic ON r.id_reporte = ic.id_reporte
        LEFT JOIN tipo_incidente ti ON ic.id_tipo_incidente = ti.id_tipo_incidente
        WHERE r.id_reporte = :id
    ");
    $stmt->execute([':id' => $id]);
    $reporte = $stmt->fetch(PDO::FETCH_ASSOC);
}

if (!$reporte) {
    $_SESSION['errors'] = ["El reporte no existe o fue eliminado."];
    header("Location: reportes.php");
    exit;
}

$consultar = function (string $sql) use ($connection, $id) {
    $stmt = $connection->prepare($sql);
    $stmt->execute([':id' => $id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
};

$denuncias = $consultar("
    SELECT a.id_advertencia, a.motivo, a.fecha, u.nombre_usuario, u.email
    FROM advertencia a INNER JOIN usuario u ON a.id_usuario = u.id_usuario
    WHERE a.id_reporte = :id ORDER BY a.fecha DESC
");
$calificaciones = $consultar("
    SELECT v.id_valoracion, v.valor, v.fecha, u.nombre_usuario
    FROM valoracion v INNER JOIN usuario u ON v.id_usuario = u.id_usuario
    WHERE v.id_reporte = :id ORDER BY v.fecha DESC
");
$comentarios = $consultar("
    SELECT c.id_comentario, c.contenido, c.fecha, u.nombre_usuario
    FROM comentario c INNER JOIN usuario u ON c.id_usuario = u.id_usuario
    WHERE c.id_reporte = :id ORDER BY c.fecha ASC
");

$credibilidad = credibilidad_reporte($connection, $id);
$credibilidadAutor = (int) $reporte['id_usuario'] > 0 ? credibilidad_usuario($connection, (int) $reporte['id_usuario']) : null;

$promedio = $calificaciones ? round(array_sum(array_column($calificaciones, 'valor')) / count($calificaciones), 1) : null;
$siNo = fn($v) => $v === null ? '-' : ((int) $v ? 'Sí' : 'No');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <?php admin_head('Reporte #' . (int) $id); ?>
</head>
<body class="admin-body">
<div class="container py-4">

    <?php admin_nav($connection, 'reportes'); ?>
    <?php admin_mensajes(); ?>

    <a href="reportes.php" class="small text-decoration-none">&larr; Volver a reportes</a>

    <div class="card shadow-sm border-0 my-3">
        <div class="card-body">
            <div class="d-flex flex-wrap justify-content-between gap-2">
                <div>
                    <span class="badge <?= $reporte['categoria'] === 'delito' ? 'text-bg-danger' : 'text-bg-success' ?>"><?= admin_e(ucfirst($reporte['categoria'])) ?></span>
                    <h2 class="h4 mt-1 mb-0">#<?= (int) $id ?> · <?= admin_e($reporte['tipo']) ?></h2>
                </div>
                <div>
                    <?= admin_form_accion('borrar_reporte', ['id' => $id], 'Borrar reporte', 'btn btn-danger',
                        "¿Borrar el reporte #$id? También se borran sus comentarios, calificaciones y denuncias. No se puede deshacer.") ?>
                </div>
            </div>
            <p class="mt-3 mb-3" style="white-space: pre-line;"><?= admin_e($reporte['descripcion']) ?></p>
            <dl class="row small mb-0">
                <dt class="col-sm-3">Publicado por</dt>
                <dd class="col-sm-9"><?= admin_e($reporte['nombre_usuario']) ?> <span class="text-muted">(<?= admin_e($reporte['email']) ?>)</span></dd>
                <dt class="col-sm-3">Fecha del incidente</dt>
                <dd class="col-sm-9"><?= admin_fecha($reporte['fecha_incidente']) ?></dd>
                <dt class="col-sm-3">Fecha de publicación</dt>
                <dd class="col-sm-9"><?= admin_fecha($reporte['fecha_reporte']) ?></dd>
                <dt class="col-sm-3">Ubicación</dt>
                <dd class="col-sm-9">
                    <?= admin_e($reporte['direccion']) ?>
                    <a href="https://www.openstreetmap.org/?mlat=<?= (float) $reporte['latitud'] ?>&mlon=<?= (float) $reporte['longitud'] ?>#map=18/<?= (float) $reporte['latitud'] ?>/<?= (float) $reporte['longitud'] ?>" target="_blank" rel="noopener">(ver en el mapa)</a>
                </dd>
                <?php if ($reporte['categoria'] === 'delito'): ?>
                    <dt class="col-sm-3">Gravedad</dt>
                    <dd class="col-sm-9">Violencia: <?= $siNo($reporte['hubo_violencia']) ?> · Arma de fuego: <?= $siNo($reporte['hubo_arma']) ?> · 2 o más delincuentes: <?= $siNo($reporte['multiples_delincuentes']) ?></dd>
                <?php endif; ?>
            </dl>
        </div>
    </div>

    <div class="card shadow-sm border-0 mb-3">
        <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-2">
            <strong>Índice de credibilidad</strong>
            <?= admin_credibilidad($credibilidad) ?>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-7">
                    <div class="small text-muted mb-1">Cómo se calcula para este reporte</div>
                    <ul class="list-unstyled small mb-0">
                        <?php foreach ($credibilidad['factores'] ?? [] as $f): ?>
                            <li class="d-flex justify-content-between border-bottom py-1">
                                <span><?= admin_e($f['texto']) ?></span>
                                <strong class="<?= !empty($f['base']) ? '' : ($f['puntos'] > 0 ? 'text-success' : 'text-danger') ?>">
                                    <?= !empty($f['base']) ? '' : ($f['puntos'] > 0 ? '+' : '') ?><?= (int) $f['puntos'] ?>
                                </strong>
                            </li>
                        <?php endforeach; ?>
                        <li class="d-flex justify-content-between pt-1"><span>Total (entre 0 y 100)</span><strong><?= (int) ($credibilidad['puntaje'] ?? 0) ?></strong></li>
                    </ul>
                </div>
                <div class="col-md-5">
                    <div class="small text-muted mb-1">Credibilidad actual del autor</div>
                    <?php if ($credibilidadAutor): ?>
                        <?= admin_credibilidad($credibilidadAutor) ?>
                        <div class="small text-muted mt-1"><?= admin_e($reporte['nombre_usuario']) ?>: es el punto de partida de sus próximos reportes.</div>
                    <?php else: ?>
                        <span class="small text-muted">Invitado (anónimo): base fija de <?= CRED_BASE_INVITADO ?>. No se calcula historial porque todos los invitados comparten la misma cuenta.</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0 mb-3 <?= $denuncias ? 'border-start border-danger border-4' : '' ?>">
        <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-2">
            <strong>Denuncias (<?= count($denuncias) ?>)</strong>
            <?php if ($denuncias): ?>
                <?= admin_form_accion('descartar_denuncias', ['id' => $id], 'Descartar todas (el reporte es válido)', 'btn btn-sm btn-outline-secondary',
                    "¿Descartar las " . count($denuncias) . " denuncia(s)? El reporte sigue publicado.") ?>
            <?php endif; ?>
        </div>
        <ul class="list-group list-group-flush">
            <?php if (!$denuncias): ?>
                <li class="list-group-item text-muted small">Este reporte no tiene denuncias.</li>
            <?php endif; ?>
            <?php foreach ($denuncias as $d): ?>
                <li class="list-group-item d-flex justify-content-between align-items-start gap-2">
                    <div>
                        <div><?= admin_e($d['motivo']) ?></div>
                        <div class="small text-muted"><?= admin_e($d['nombre_usuario']) ?> (<?= admin_e($d['email']) ?>) · <?= admin_fecha($d['fecha']) ?></div>
                    </div>
                    <?= admin_form_accion('borrar_denuncia', ['id' => $d['id_advertencia']], 'Descartar', 'btn btn-sm btn-outline-secondary', "¿Descartar esta denuncia?") ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>

    <div class="row g-3">
        <div class="col-lg-7">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white"><strong>Comentarios (<?= count($comentarios) ?>)</strong></div>
                <ul class="list-group list-group-flush">
                    <?php if (!$comentarios): ?>
                        <li class="list-group-item text-muted small">Sin comentarios.</li>
                    <?php endif; ?>
                    <?php foreach ($comentarios as $c): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-start gap-2">
                            <div>
                                <div class="small"><strong><?= admin_e($c['nombre_usuario']) ?></strong> <span class="text-muted">· <?= admin_fecha($c['fecha']) ?></span></div>
                                <div style="white-space: pre-line; word-break: break-word;"><?= admin_e($c['contenido']) ?></div>
                            </div>
                            <?= admin_form_accion('borrar_comentario', ['id' => $c['id_comentario']], 'Borrar', 'btn btn-sm btn-outline-danger', "¿Borrar este comentario? No se puede deshacer.") ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white">
                    <strong>Calificaciones (<?= count($calificaciones) ?>)</strong>
                    <?php if ($promedio !== null): ?>
                        <span class="ms-2 text-warning"><?= admin_estrellas($promedio) ?></span>
                        <span class="small text-muted"><?= $promedio ?></span>
                    <?php endif; ?>
                </div>
                <ul class="list-group list-group-flush">
                    <?php if (!$calificaciones): ?>
                        <li class="list-group-item text-muted small">Sin calificaciones.</li>
                    <?php endif; ?>
                    <?php foreach ($calificaciones as $v): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center gap-2">
                            <div>
                                <span class="text-warning"><?= admin_estrellas($v['valor']) ?></span>
                                <div class="small text-muted"><?= admin_e($v['nombre_usuario']) ?> · <?= admin_fecha($v['fecha']) ?></div>
                            </div>
                            <?= admin_form_accion('borrar_calificacion', ['id' => $v['id_valoracion']], 'Borrar', 'btn btn-sm btn-outline-danger', "¿Borrar esta calificación?") ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
</div>
</body>
</html>
