<?php

session_start();


session_regenerate_id(true);


$_SESSION['user'] = [
    'id' => 'guest_' . uniqid(),        
    'email' => 'invitado@mappealo.com',  
    'is_admin' => 0,                    
    'rol' => 'guest'                  
];

header("Location: ../../index.php");
exit;
?>