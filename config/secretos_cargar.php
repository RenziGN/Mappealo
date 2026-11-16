<?php

function secreto(string $clave, ?string $porDefecto = null): ?string
{
    static $secretos = null;

    if ($secretos === null) {
        $archivo = __DIR__ . '/secretos.php';
        $secretos = is_file($archivo) ? (require $archivo) : [];
    }

    $valor = $secretos[$clave] ?? null;

    return ($valor === null || $valor === '') ? $porDefecto : (string) $valor;
}
