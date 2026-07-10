<?php
// 1. Iniciamos la sesión
session_start();

// 2. Regeneramos el ID de sesión por seguridad, igual que hace tu compañero
session_regenerate_id(true);

// 3. Estructuramos el array 'user' idéntico al del login común,
// pero con los datos y el rol de invitado.
$_SESSION['user'] = [
    'id' => 'guest_' . uniqid(),        // Genera un ID de texto único temporal
    'email' => 'invitado@mappealo.com',  // Un mail ficticio por si el sistema lo pide
    'is_admin' => 0,                    // 0 significa que NO es admin (obviamente)
    'rol' => 'guest'                    // <--- Tu regla de oro para las restricciones
];

// 4. Lo mandamos a la misma pantalla de bienvenida que usa tu compañero
header("Location: ../../index.php");
exit;
?>