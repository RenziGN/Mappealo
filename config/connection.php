<?php

require_once __DIR__ . '/secretos_cargar.php';

$host = secreto('db_host', 'localhost');
$dbname = secreto('db_nombre', 'user_db');
$user = secreto('db_usuario', 'root');
$pass = secreto('db_clave', '');

try {
    $connection = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
} catch (PDOException $e) {
    error_log('Mappealo - conexión a la base: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'No se pudo conectar con la base de datos.']);
    exit;
}
