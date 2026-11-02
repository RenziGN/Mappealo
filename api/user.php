<?php
header('Content-Type: application/json');
require_once '../config/conexion.php';

$tipo = $_GET['tipo'] ?? '';

if (!$tipo) {
    echo json_encode(["error" => "Falta el tipo"]);
    exit;
}

try {
    $sql = "SELECT numero FROM butacas WHERE tipo = :tipo AND disponible = 1";
    $stmt = $conexion->prepare($sql);
    $stmt->bindParam(':tipo', $tipo);
    $stmt->execute();

    $butacas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($butacas);

} catch (PDOException $e) {
    echo json_encode(["error" => $e->getMessage()]);
}
?>