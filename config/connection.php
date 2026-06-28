<?php

$host = "localhost";
$dbname = "user_db";
$user = "root";
$pass = "";

try {
    $connection = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // echo "Conexión exitosa";    
} catch (PDOException $e) {
    echo json_encode(["error" => "Error de conexión: " . $e->getMessage()]);
    exit;
}
