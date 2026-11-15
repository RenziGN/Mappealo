<?php

require_once __DIR__ . '/../config/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    responder_json(405, ['status' => 'error', 'message' => 'Método no permitido.']);
}

$idUsuario = requerir_usuario_registrado_api();

require_once __DIR__ . '/../config/connection.php';

try {
    $stmt = $connection->prepare("
        SELECT
            r.id_reporte,
            r.descripcion,
            r.fecha_incidente,
            r.fecha_reporte,
            r.estado,
            ub.latitud,
            ub.longitud,
            ub.direccion,
            CASE
                WHEN rb.id_reporte IS NOT NULL THEN 'delito'
                WHEN ic.id_reporte IS NOT NULL THEN 'comunitario'
                ELSE 'otro'
            END AS categoria,
            COALESCE(tr.nombre, ti.nombre) AS tipo,
            (SELECT ROUND(AVG(v.valor), 1) FROM valoracion v WHERE v.id_reporte = r.id_reporte) AS promedio_calificacion,
            (SELECT COUNT(*) FROM valoracion v WHERE v.id_reporte = r.id_reporte) AS cantidad_calificaciones,
            (SELECT COUNT(*) FROM comentario c WHERE c.id_reporte = r.id_reporte) AS cantidad_comentarios
        FROM reporte r
        INNER JOIN ubicacion ub ON r.id_ubicacion = ub.id_ubicacion
        LEFT JOIN robo rb ON r.id_reporte = rb.id_reporte
        LEFT JOIN tipo_robo tr ON rb.id_tipo_robo = tr.id_tipo_robo
        LEFT JOIN incidente_comunitario ic ON r.id_reporte = ic.id_reporte
        LEFT JOIN tipo_incidente ti ON ic.id_tipo_incidente = ti.id_tipo_incidente
        WHERE r.id_usuario = :u
        ORDER BY r.fecha_reporte DESC, r.id_reporte DESC
    ");
    $stmt->execute([':u' => $idUsuario]);
    $reportes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    responder_json(200, [
        'status' => 'success',
        'total' => count($reportes),
        'data' => $reportes
    ]);

} catch (PDOException $e) {
    responder_json(500, ['status' => 'error', 'message' => 'No se pudo cargar tu historial.']);
}
