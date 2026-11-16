<?php

require_once __DIR__ . '/../../config/auth.php';

$_SESSION = [];

$cookie = session_get_cookie_params();
setcookie(session_name(), '', [
    'expires' => time() - 3600,
    'path' => $cookie['path'],
    'secure' => $cookie['secure'],
    'httponly' => true,
    'samesite' => 'Lax',
]);

session_destroy();

header("Location: ../../pages/auth/login.php");
exit;
