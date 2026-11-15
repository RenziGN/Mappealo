<?php

require_once __DIR__ . '/../config/auth.php';

if (!isset($_SESSION['user'])) {
    responder_json(401, ['status' => 'error', 'message' => 'Sesión no iniciada.']);
}

require_once __DIR__ . '/../config/connection.php';

$idReporte = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

if ($idReporte === false) {
    responder_json(400, ['status' => 'error', 'message' => 'Reporte inválido.']);
}

$idUsuario = usuario_actual_id();

try {
    $stmt = $connection->prepare("
        SELECT
            r.id_reporte,
            r.descripcion,
            r.fecha_incidente,
            r.fecha_reporte,
            r.estado,
            u.nombre_usuario,
            ub.direccion,
            CASE
                WHEN rb.id_reporte IS NOT NULL THEN 'delito'
                WHEN ic.id_reporte IS NOT NULL THEN 'comunitario'
                ELSE 'otro'
            END AS categoria,
            tr.nombre AS tipo_robo,
            ti.nombre AS tipo_incidente
        FROM reporte r
        INNER JOIN usuario u ON r.id_usuario = u.id_usuario
        INNER JOIN ubicacion ub ON r.id_ubicacion = ub.id_ubicacion
        LEFT JOIN robo rb ON r.id_reporte = rb.id_reporte
        LEFT JOIN tipo_robo tr ON rb.id_tipo_robo = tr.id_tipo_robo
        LEFT JOIN incidente_comunitario ic ON r.id_reporte = ic.id_reporte
        LEFT JOIN tipo_incidente ti ON ic.id_tipo_incidente = ti.id_tipo_incidente
        WHERE r.id_reporte = :id
    ");
    $stmt->execute([':id' => $idReporte]);
    $reporte = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$reporte) {
        responder_json(404, ['status' => 'error', 'message' => 'El reporte no existe o fue eliminado.']);
    }

    $stmt = $connection->prepare("
        SELECT ROUND(AVG(valor), 1) AS promedio, COUNT(*) AS cantidad
        FROM valoracion
        WHERE id_reporte = :id
    ");
    $stmt->execute([':id' => $idReporte]);
    $calificacion = $stmt->fetch(PDO::FETCH_ASSOC);

    $stmt = $connection->prepare("
        SELECT c.id_comentario, c.contenido, c.fecha, u.nombre_usuario
        FROM comentario c
        INNER JOIN usuario u ON c.id_usuario = u.id_usuario
        WHERE c.id_reporte = :id
        ORDER BY c.fecha ASC, c.id_comentario ASC
    ");
    $stmt->execute([':id' => $idReporte]);
    $comentarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $miCalificacion = null;
    $yaDenuncie = false;

    if ($idUsuario !== null) {
        $stmt = $connection->prepare("SELECT valor FROM valoracion WHERE id_reporte = :r AND id_usuario = :u LIMIT 1");
        $stmt->execute([':r' => $idReporte, ':u' => $idUsuario]);
        $valor = $stmt->fetchColumn();
        $miCalificacion = $valor === false ? null : (int) $valor;

        $stmt = $connection->prepare("SELECT 1 FROM advertencia WHERE id_reporte = :r AND id_usuario = :u LIMIT 1");
        $stmt->execute([':r' => $idReporte, ':u' => $idUsuario]);
        $yaDenuncie = (bool) $stmt->fetchColumn();
    }

    responder_json(200, [
        'status' => 'success',
        'reporte' => $reporte,
        'calificacion' => [
            'promedio' => $calificacion['promedio'] !== null ? (float) $calificacion['promedio'] : null,
            'cantidad' => (int) $calificacion['cantidad'],
            'mia' => $miCalificacion
        ],
        'comentarios' => $comentarios,
        'ya_denuncie' => $yaDenuncie,
        'puede_interactuar' => $idUsuario !== null,
        'csrf' => csrf_token()
    ]);

} catch (PDOException $e) {
    responder_json(500, ['status' => 'error', 'message' => 'Error al consultar el reporte.']);
}
