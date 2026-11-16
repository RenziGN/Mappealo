<?php

require_once __DIR__ . '/../config/auth.php';

$idUsuario = requerir_usuario_registrado_api();
$datos = leer_json_post();

require_once __DIR__ . '/../config/connection.php';

$idReporte = filter_var($datos['id_reporte'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$contenido = trim((string) ($datos['contenido'] ?? ''));

if ($idReporte === false) {
    responder_json(400, ['status' => 'error', 'message' => 'Reporte inválido.']);
}

$largo = mb_strlen($contenido);

if ($largo < 2 || $largo > 500) {
    responder_json(422, ['status' => 'error', 'message' => 'El comentario debe tener entre 2 y 500 caracteres.']);
}

try {
    $stmt = $connection->prepare("SELECT 1 FROM reporte WHERE id_reporte = :id");
    $stmt->execute([':id' => $idReporte]);

    if (!$stmt->fetchColumn()) {
        responder_json(404, ['status' => 'error', 'message' => 'El reporte no existe o fue eliminado.']);
    }

    $stmt = $connection->prepare("
        INSERT INTO comentario (id_reporte, id_usuario, contenido, fecha)
        VALUES (:r, :u, :c, NOW())
    ");
    $stmt->execute([':r' => $idReporte, ':u' => $idUsuario, ':c' => $contenido]);

    responder_json(201, ['status' => 'success', 'message' => 'Comentario publicado.']);

} catch (PDOException $e) {
    responder_json(500, ['status' => 'error', 'message' => 'No se pudo guardar el comentario.']);
}
