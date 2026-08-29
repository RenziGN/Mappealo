<?php
// api/crear_reporte.php

session_start();
header('Content-Type: application/json; charset=utf-8');

error_reporting(E_ALL);
ini_set('display_errors', 0);

if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Debés iniciar sesión para publicar un reporte.']);
    exit;
}

require_once __DIR__ . '/../config/connection.php';

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Datos inválidos.']);
    exit;
}

try {
    $connection->beginTransaction();

    //  Obtener ID del usuario logueado
    $idUsuario = null;
    if (is_array($_SESSION['user'])) {
        $idUsuario = $_SESSION['user']['id_usuario'] ?? $_SESSION['user']['id'] ?? null;
    } else {
        $idUsuario = $_SESSION['user_id'] ?? $_SESSION['id_usuario'] ?? null;
    }

    if (!$idUsuario) {
        $stmtUser = $connection->query("SELECT id_usuario FROM usuario LIMIT 1");
        $idUsuario = $stmtUser->fetchColumn() ?: 1;
    }

    //  Insertar en tabla: ubicacion
    $lat = $input['latitud'] ?? -34.6658;
    $lng = $input['longitud'] ?? -58.6649;
    $dir = $input['ubicacion']['direccion_privada'] ?? 'Ubicación seleccionada en mapa';

    $stmtUb = $connection->prepare("
        INSERT INTO ubicacion (latitud, longitud, direccion) 
        VALUES (:lat, :lng, :dir)
    ");
    $stmtUb->execute([
        ':lat' => $lat,
        ':lng' => $lng,
        ':dir' => $dir
    ]);
    $idUbicacion = $connection->lastInsertId();

    // Insertar en tabla: reporte
    $descripcion = $input['descripcion'] ?? 'Sin descripción';
    $fechaIncidente = $input['fecha_hora'] ?? date('Y-m-d H:i:s');

    $stmtRep = $connection->prepare("
        INSERT INTO reporte (id_usuario, id_ubicacion, fecha_reporte, fecha_incidente, descripcion, estado) 
        VALUES (:id_usuario, :id_ubicacion, NOW(), :fecha_incidente, :descripcion, 'pendiente')
    ");
    $stmtRep->execute([
        ':id_usuario'       => $idUsuario,
        ':id_ubicacion'     => $idUbicacion,
        ':fecha_incidente'  => $fechaIncidente,
        ':descripcion'      => $descripcion
    ]);
    $idReporte = $connection->lastInsertId();

    //  Insertar en tabla hija: robo O incidente_comunitario
    $esDelito = (($input['categoria'] ?? '') === 'delito' || ($input['tipo'] ?? '') === 'delito');

    if ($esDelito) {
        $tipoRoboTexto = $input['tipo_robo'] ?? '';

        // Buscar coincidencia en tipo_robo o fallback al primero
        $stmtTr = $connection->prepare("SELECT id_tipo_robo FROM tipo_robo WHERE nombre LIKE :nom LIMIT 1");
        $stmtTr->execute([':nom' => '%' . $tipoRoboTexto . '%']);
        $idTipoRobo = $stmtTr->fetchColumn();

        if (!$idTipoRobo) {
            $stmtFirst = $connection->query("SELECT id_tipo_robo FROM tipo_robo LIMIT 1");
            $idTipoRobo = $stmtFirst->fetchColumn() ?: 1;
        }

        $grav = $input['gravedad'] ?? [];
        $huboViolencia = !empty($grav['violencia']) ? 1 : 0;
        $huboArma = !empty($grav['arma_fuego']) ? 1 : 0;
        $multiplesDelincuentes = !empty($grav['multiples_delincuentes']) ? 1 : 0;

        $stmtRobo = $connection->prepare("
            INSERT INTO robo (id_reporte, id_tipo_robo, hubo_violencia, hubo_arma, multiples_delincuentes) 
            VALUES (:id_reporte, :id_tipo_robo, :hubo_violencia, :hubo_arma, :multiples_delincuentes)
        ");
        $stmtRobo->execute([
            ':id_reporte'              => $idReporte,
            ':id_tipo_robo'            => $idTipoRobo,
            ':hubo_violencia'          => $huboViolencia,
            ':hubo_arma'               => $huboArma,
            ':multiples_delincuentes'  => $multiplesDelincuentes
        ]);
    } else {
        $incidenteTexto = $input['incidente'] ?? '';

        // Buscar coincidencia en tipo_incidente o fallback al primero
        $stmtTi = $connection->prepare("SELECT id_tipo_incidente FROM tipo_incidente WHERE nombre LIKE :nom LIMIT 1");
        $stmtTi->execute([':nom' => '%' . $incidenteTexto . '%']);
        $idTipoIncidente = $stmtTi->fetchColumn();

        if (!$idTipoIncidente) {
            $stmtFirstInc = $connection->query("SELECT id_tipo_incidente FROM tipo_incidente LIMIT 1");
            $idTipoIncidente = $stmtFirstInc->fetchColumn() ?: 1;
        }

        $stmtInc = $connection->prepare("
            INSERT INTO incidente_comunitario (id_reporte, id_tipo_incidente) 
            VALUES (:id_reporte, :id_tipo_incidente)
        ");
        $stmtInc->execute([
            ':id_reporte'           => $idReporte,
            ':id_tipo_incidente'    => $idTipoIncidente
        ]);
    }

    $connection->commit();
    echo json_encode(['status' => 'success', 'message' => 'Reporte guardado con éxito']);

} catch (PDOException $e) {
    if ($connection->inTransaction()) {
        $connection->rollBack();
    }
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Error SQL: ' . $e->getMessage()]);
}