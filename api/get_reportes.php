<?php
// api/get_reportes.php

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/connection.php';
require_once __DIR__ . '/../config/credibilidad.php';

try {
    $query = "
        SELECT 
            r.id_reporte,
            r.descripcion,
            r.fecha_incidente,
            r.fecha_reporte,
            r.estado,
            u.nombre_usuario,
            ub.latitud,
            ub.longitud,
            ub.direccion,
            CASE 
                WHEN rb.id_reporte IS NOT NULL THEN 'delito'
                WHEN ic.id_reporte IS NOT NULL THEN 'comunitario'
                ELSE 'otro'
            END AS categoria,
            tr.nombre AS tipo_robo,
            ti.nombre AS tipo_incidente,
            (SELECT ROUND(AVG(v.valor), 1) FROM valoracion v WHERE v.id_reporte = r.id_reporte) AS promedio_calificacion,
            (SELECT COUNT(*) FROM valoracion v WHERE v.id_reporte = r.id_reporte) AS cantidad_calificaciones,
            (SELECT COUNT(*) FROM comentario c WHERE c.id_reporte = r.id_reporte) AS cantidad_comentarios
        FROM reporte r
        INNER JOIN usuario u ON r.id_usuario = u.id_usuario
        INNER JOIN ubicacion ub ON r.id_ubicacion = ub.id_ubicacion
        LEFT JOIN robo rb ON r.id_reporte = rb.id_reporte
        LEFT JOIN tipo_robo tr ON rb.id_tipo_robo = tr.id_tipo_robo
        LEFT JOIN incidente_comunitario ic ON r.id_reporte = ic.id_reporte
        LEFT JOIN tipo_incidente ti ON ic.id_tipo_incidente = ti.id_tipo_incidente
        WHERE r.estado <> 'rechazado'
        ORDER BY r.fecha_reporte DESC
    ";

    $stmt = $connection->query($query);
    $reportes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $credibilidades = credibilidad_reportes($connection);
    foreach ($reportes as &$reporte) {
        $cred = $credibilidades[(int) $reporte['id_reporte']] ?? null;
        $reporte['credibilidad'] = $cred['puntaje'] ?? null;
        $reporte['credibilidad_nivel'] = $cred['nivel']['clave'] ?? null;
    }
    unset($reporte);

    echo json_encode([
        'status' => 'success',
        'total'  => count($reportes),
        'data'   => $reportes
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Error al consultar la base de datos: ' . $e->getMessage()
    ]);
}