<?php

require_once __DIR__ . '/secretos_cargar.php';

const PREFIJO_CIFRADO = 'v1:';

function clave_secreta(string $nombre): string
{
    $hex = secreto($nombre);

    if ($hex === null || !preg_match('/^[0-9a-f]{64}$/i', $hex)) {
        error_log("Mappealo - falta '$nombre' en config/secretos.php");
        throw new RuntimeException('Falta configurar las claves de cifrado.');
    }

    return hex2bin($hex);
}

// AES-256-GCM: el resultado incluye el nonce y la etiqueta de autenticación
function cifrar(string $texto): string
{
    $nonce = random_bytes(12);
    $tag = '';
    $cifrado = openssl_encrypt($texto, 'aes-256-gcm', clave_secreta('clave_cifrado'), OPENSSL_RAW_DATA, $nonce, $tag);

    return PREFIJO_CIFRADO . base64_encode($nonce . $tag . $cifrado);
}

// Devuelve el texto original. Si el valor todavía no está cifrado, lo devuelve tal cual.
function descifrar(?string $valor): ?string
{
    if ($valor === null || !str_starts_with($valor, PREFIJO_CIFRADO)) {
        return $valor;
    }

    $datos = base64_decode(substr($valor, strlen(PREFIJO_CIFRADO)), true);

    if ($datos === false || strlen($datos) < 29) {
        return null;
    }

    $texto = openssl_decrypt(substr($datos, 28), 'aes-256-gcm', clave_secreta('clave_cifrado'), OPENSSL_RAW_DATA, substr($datos, 0, 12), substr($datos, 12, 16));

    return $texto === false ? null : $texto;
}

function normalizar_email(string $email): string
{
    return mb_strtolower(trim($email));
}

// Huella del correo para buscarlo sin guardarlo en texto plano
function indice_email(string $email): string
{
    return hash_hmac('sha256', normalizar_email($email), clave_secreta('clave_indice'));
}
