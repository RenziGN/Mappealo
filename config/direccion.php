<?php

const DIRECCION_POR_DEFECTO = 'Ubicación seleccionada en mapa';

function direccion_desde_coordenadas(float $lat, float $lng): ?string
{
    if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
        return null;
    }

    $url = 'https://nominatim.openstreetmap.org/reverse?' . http_build_query([
        'format' => 'jsonv2',
        'lat' => number_format($lat, 6, '.', ''),
        'lon' => number_format($lng, 6, '.', ''),
        'zoom' => 18,
        'addressdetails' => 1,
        'accept-language' => 'es',
    ]);

    $agente = 'Mappealo/1.0 (proyecto educativo)';

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 6,
            CURLOPT_CONNECTTIMEOUT => 4,
            CURLOPT_USERAGENT => $agente,
            CURLOPT_HTTPHEADER => ['Accept: application/json'],
        ]);
        $respuesta = curl_exec($ch);
        $codigo = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($respuesta === false || $codigo !== 200) {
            return null;
        }
    } else {
        $contexto = stream_context_create(['http' => [
            'method' => 'GET',
            'timeout' => 6,
            'ignore_errors' => true,
            'header' => "User-Agent: $agente\r\nAccept: application/json\r\n",
        ]]);
        $respuesta = @file_get_contents($url, false, $contexto);

        if ($respuesta === false || !preg_match('#^HTTP/\S+\s+200#', $http_response_header[0] ?? '')) {
            return null;
        }
    }

    $datos = json_decode($respuesta, true);
    $a = $datos['address'] ?? null;

    if (!is_array($a)) {
        return null;
    }

    $calle = $a['road'] ?? $a['pedestrian'] ?? $a['footway'] ?? null;
    $altura = $a['house_number'] ?? null;
    $localidad = $a['city'] ?? $a['town'] ?? $a['village'] ?? $a['suburb'] ?? null;

    if (!$calle) {
        return $localidad;
    }

    $texto = $altura ? "$calle $altura" : $calle;

    if ($localidad) {
        $texto .= ", $localidad";
    }

    return mb_substr($texto, 0, 255);
}
