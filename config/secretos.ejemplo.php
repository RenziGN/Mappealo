<?php
// Copiá este archivo como config/secretos.php y completá los valores.
// config/secretos.php no se sube a Git.

return [
    'db_host' => 'localhost',
    'db_nombre' => 'user_db',
    'db_usuario' => 'root',
    'db_clave' => '',

    // Claves de 64 caracteres hexadecimales. Para generarlas:
    // php -r "echo bin2hex(random_bytes(32));"
    // Si se cambian, los correos ya guardados dejan de poder leerse.
    'clave_cifrado' => '',
    'clave_indice' => '',
];
