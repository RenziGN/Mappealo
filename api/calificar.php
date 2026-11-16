<?php

require_once __DIR__ . '/../config/auth.php';

$idUsuario = requerir_usuario_registrado_api();
$datos = leer_json_post();

require_once __DIR__ . '/../config/connection.php';
require_once __DIR__ . '/../config/limites.php';

limitar_api($connection, 'calificacion', 30, 300);

$idReporte = filter_var($datos['id_reporte'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$valor = filter_var($datos['valor'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 5]]);

if ($idReporte === false) {
    responder_json(400, ['status' => 'error', 'message' => 'Reporte inválido.']);
}

if ($valor === false) {
    responder_json(422, ['status' => 'error', 'message' => 'La calificación debe ser de 1 a 5 estrellas.']);
}

try {
    $connection->beginTransaction();

    $stmt = $connection->prepare("SELECT 1 FROM reporte WHERE id_reporte = :id");
    $stmt->execute([':id' => $idReporte]);

    if (!$stmt->fetchColumn()) {
        $connection->rollBack();
        responder_json(404, ['status' => 'error', 'message' => 'El reporte no existe o fue eliminado.']);
    }

    $stmt = $connection->prepare("
        SELECT id_valoracion FROM valoracion
        WHERE id_reporte = :r AND id_usuario = :u
        ORDER BY id_valoracion
        FOR UPDATE
    ");
    $stmt->execute([':r' => $idReporte, ':u' => $idUsuario]);
    $existentes = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if ($existentes) {
        $stmt = $connection->prepare("UPDATE valoracion SET valor = :v, fecha = NOW() WHERE id_valoracion = :id");
        $stmt->execute([':v' => $valor, ':id' => $existentes[0]]);

        if (count($existentes) > 1) {
            $sobrantes = array_map('intval', array_slice($existentes, 1));
            $connection->exec("DELETE FROM valoracion WHERE id_valoracion IN (" . implode(',', $sobrantes) . ")");
        }
    } else {
        $stmt = $connection->prepare("
            INSERT INTO valoracion (id_reporte, id_usuario, valor, fecha)
            VALUES (:r, :u, :v, NOW())
        ");
        $stmt->execute([':r' => $idReporte, ':u' => $idUsuario, ':v' => $valor]);
    }

    $stmt = $connection->prepare("SELECT ROUND(AVG(valor), 1) AS promedio, COUNT(*) AS cantidad FROM valoracion WHERE id_reporte = :id");
    $stmt->execute([':id' => $idReporte]);
    $resumen = $stmt->fetch(PDO::FETCH_ASSOC);

    $connection->commit();

    responder_json(200, [
        'status' => 'success',
        'message' => $existentes ? 'Calificación actualizada.' : 'Gracias por calificar.',
        'calificacion' => [
            'promedio' => (float) $resumen['promedio'],
            'cantidad' => (int) $resumen['cantidad'],
            'mia' => $valor
        ]
    ]);

} catch (PDOException $e) {
    if ($connection->inTransaction()) {
        $connection->rollBack();
    }
    responder_json(500, ['status' => 'error', 'message' => 'No se pudo guardar la calificación.']);
}
