<?php

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/direccion.php';

if (!isset($_SESSION['user'])) {
    responder_json(401, ['status' => 'error', 'message' => 'Sesión no iniciada.']);
}

$lat = filter_var($_GET['lat'] ?? null, FILTER_VALIDATE_FLOAT);
$lng = filter_var($_GET['lng'] ?? null, FILTER_VALIDATE_FLOAT);

if ($lat === false || $lng === false) {
    responder_json(400, ['status' => 'error', 'message' => 'Coordenadas inválidas.']);
}

require_once __DIR__ . '/../config/connection.php';
require_once __DIR__ . '/../config/limites.php';

limitar_api($connection, 'direccion', 30, 60);

$clave = round($lat, 5) . ',' . round($lng, 5);
$cache = $_SESSION['direcciones'] ?? [];

if (isset($cache[$clave])) {
    responder_json(200, ['status' => 'success', 'direccion' => $cache[$clave]]);
}

$ultima = $_SESSION['ultima_busqueda_direccion'] ?? 0;
$espera = 1.0 - (microtime(true) - $ultima);
if ($espera > 0) {
    usleep((int) ($espera * 1000000));
}
$_SESSION['ultima_busqueda_direccion'] = microtime(true);

$direccion = direccion_desde_coordenadas($lat, $lng);

if ($direccion === null) {
    responder_json(200, ['status' => 'error', 'message' => 'No se encontró la dirección.']);
}

$cache[$clave] = $direccion;
$_SESSION['direcciones'] = array_slice($cache, -50, null, true);

responder_json(200, ['status' => 'success', 'direccion' => $direccion]);
