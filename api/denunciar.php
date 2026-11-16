<?php

require_once __DIR__ . '/../config/auth.php';

$idUsuario = requerir_usuario_registrado_api();
$datos = leer_json_post();

require_once __DIR__ . '/../config/connection.php';
require_once __DIR__ . '/../config/limites.php';

limitar_api($connection, 'denuncia', 10, 3600);

$motivosValidos = [
    'Información falsa',
    'Contenido ofensivo o discriminatorio',
    'Datos personales expuestos',
    'Spam o publicidad',
    'Reporte duplicado',
    'Otro'
];

$idReporte = filter_var($datos['id_reporte'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$motivo = trim((string) ($datos['motivo'] ?? ''));
$detalle = trim((string) ($datos['detalle'] ?? ''));

if ($idReporte === false) {
    responder_json(400, ['status' => 'error', 'message' => 'Reporte inválido.']);
}

if (!in_array($motivo, $motivosValidos, true)) {
    responder_json(422, ['status' => 'error', 'message' => 'Elegí un motivo para la denuncia.']);
}

if ($motivo === 'Otro' && mb_strlen($detalle) < 5) {
    responder_json(422, ['status' => 'error', 'message' => 'Contanos brevemente el motivo de la denuncia.']);
}

$textoMotivo = $detalle !== '' ? $motivo . ': ' . $detalle : $motivo;
$textoMotivo = mb_substr($textoMotivo, 0, 255);

try {
    $stmt = $connection->prepare("SELECT 1 FROM reporte WHERE id_reporte = :id");
    $stmt->execute([':id' => $idReporte]);

    if (!$stmt->fetchColumn()) {
        responder_json(404, ['status' => 'error', 'message' => 'El reporte no existe o fue eliminado.']);
    }

    $stmt = $connection->prepare("SELECT 1 FROM advertencia WHERE id_reporte = :r AND id_usuario = :u LIMIT 1");
    $stmt->execute([':r' => $idReporte, ':u' => $idUsuario]);

    if ($stmt->fetchColumn()) {
        responder_json(409, ['status' => 'error', 'message' => 'Ya denunciaste este reporte. Un administrador lo va a revisar.']);
    }

    $stmt = $connection->prepare("
        INSERT INTO advertencia (id_reporte, id_usuario, motivo, fecha)
        VALUES (:r, :u, :m, NOW())
    ");
    $stmt->execute([':r' => $idReporte, ':u' => $idUsuario, ':m' => $textoMotivo]);

    responder_json(201, ['status' => 'success', 'message' => 'Denuncia enviada. Un administrador la va a revisar.']);

} catch (PDOException $e) {
    responder_json(500, ['status' => 'error', 'message' => 'No se pudo enviar la denuncia.']);
}
