<?php

require_once __DIR__ . '/../config/auth.php';

$idUsuario = requerir_usuario_registrado_api();
$datos = leer_json_post();

require_once __DIR__ . '/../config/connection.php';
require_once __DIR__ . '/../config/reportes.php';

$idReporte = filter_var($datos['id_reporte'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

if ($idReporte === false) {
    responder_json(400, ['status' => 'error', 'message' => 'Reporte inválido.']);
}

try {
    if (!borrar_reporte($connection, $idReporte, $idUsuario)) {
        responder_json(404, ['status' => 'error', 'message' => 'El reporte no existe o no es tuyo.']);
    }

    responder_json(200, ['status' => 'success', 'message' => 'Reporte borrado.']);

} catch (PDOException $e) {
    responder_json(500, ['status' => 'error', 'message' => 'No se pudo borrar el reporte.']);
}
