<?php
// Prepara la base para las medidas de seguridad. Se puede correr varias veces.
// Uso: php config/migrar_seguridad.php

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/connection.php';
require_once __DIR__ . '/cifrado.php';

$connection->exec("
    CREATE TABLE IF NOT EXISTS intento_acceso (
        id_intento INT(11) NOT NULL AUTO_INCREMENT,
        accion VARCHAR(30) NOT NULL,
        clave CHAR(64) NOT NULL,
        momento DATETIME NOT NULL,
        PRIMARY KEY (id_intento),
        KEY accion_clave_momento (accion, clave, momento)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
");
echo "Tabla intento_acceso lista.\n";

$columnas = $connection->query("SHOW COLUMNS FROM usuario")->fetchAll(PDO::FETCH_COLUMN);

if (!in_array('email_indice', $columnas, true)) {
    $connection->exec("ALTER TABLE usuario MODIFY email VARCHAR(255) NOT NULL, ADD email_indice CHAR(64) NULL AFTER email, ADD UNIQUE KEY email_indice (email_indice)");
    echo "Columna email_indice agregada.\n";
}

$usuarios = $connection->query("SELECT id_usuario, email FROM usuario")->fetchAll(PDO::FETCH_ASSOC);
$actualizar = $connection->prepare("UPDATE usuario SET email = :e, email_indice = :i WHERE id_usuario = :id");
$cifrados = 0;

foreach ($usuarios as $u) {
    if (str_starts_with($u['email'], PREFIJO_CIFRADO)) {
        continue;
    }
    $actualizar->execute([
        ':e' => cifrar(normalizar_email($u['email'])),
        ':i' => indice_email($u['email']),
        ':id' => $u['id_usuario'],
    ]);
    $cifrados++;
}

echo "Correos cifrados: $cifrados de " . count($usuarios) . ".\n";
