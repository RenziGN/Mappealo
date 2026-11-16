<?php

require_once __DIR__ . '/../../config/auth.php';

session_regenerate_id(true);
unset($_SESSION['csrf_token']);

$_SESSION['user'] = [
    'id_usuario' => 0,
    'email' => 'invitado@mappealo.com',
    'is_admin' => 0,
    'rol' => 'guest'
];

header("Location: ../../index.php");
exit;
