<?php
require_once __DIR__ . '/../../config/auth.php';
requerir_admin();
require_once __DIR__ . '/../../config/connection.php';
require_once __DIR__ . '/_nav.php';

$busqueda = trim((string) ($_GET['q'] ?? ''));
$parametros = [];
$where = '';

if ($busqueda !== '') {
    $where = "WHERE c.contenido LIKE :q OR u.nombre_usuario LIKE :q";
    $parametros[':q'] = '%' . $busqueda . '%';
}

$stmt = $connection->prepare("
    SELECT c.id_comentario, c.id_reporte, c.contenido, c.fecha, u.nombre_usuario, r.descripcion
    FROM comentario c
    INNER JOIN usuario u ON c.id_usuario = u.id_usuario
    INNER JOIN reporte r ON c.id_reporte = r.id_reporte
    $where
    ORDER BY c.fecha DESC, c.id_comentario DESC
    LIMIT 500
");
$stmt->execute($parametros);
$comentarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comentarios · Administración</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4">

    <?php admin_nav($connection, 'comentarios'); ?>
    <?php admin_mensajes(); ?>

    <form class="d-flex gap-2 mb-3" method="GET">
        <input type="search" name="q" value="<?= admin_e($busqueda) ?>" class="form-control form-control-sm" placeholder="Buscar por texto o usuario">
        <button class="btn btn-sm btn-primary">Buscar</button>
    </form>

    <p class="text-muted small"><?= count($comentarios) ?> comentario(s), los más recientes primero.</p>

    <div class="table-responsive bg-white rounded shadow-sm">
        <table class="table align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Comentario</th>
                    <th>Usuario</th>
                    <th>Reporte</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!$comentarios): ?>
                <tr><td colspan="4" class="text-center text-muted py-4">No hay comentarios.</td></tr>
            <?php endif; ?>
            <?php foreach ($comentarios as $c): ?>
                <tr>
                    <td style="min-width: 260px; white-space: pre-line; word-break: break-word;"><?= admin_e($c['contenido']) ?></td>
                    <td class="small text-nowrap"><?= admin_e($c['nombre_usuario']) ?><br><span class="text-muted"><?= admin_fecha($c['fecha']) ?></span></td>
                    <td class="small">
                        <a href="reporte.php?id=<?= (int) $c['id_reporte'] ?>">#<?= (int) $c['id_reporte'] ?></a>
                        <div class="text-muted text-truncate" style="max-width: 220px;"><?= admin_e($c['descripcion']) ?></div>
                    </td>
                    <td class="text-end">
                        <?= admin_form_accion('borrar_comentario', ['id' => $c['id_comentario']], 'Borrar', 'btn btn-sm btn-outline-danger', "¿Borrar este comentario? No se puede deshacer.") ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
