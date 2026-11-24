<?php

function conCaravanas(string $archivo, bool $escritura, callable $operacion): mixed
{
    $bloqueo = fopen($archivo . '.lock', 'c');
    if ($bloqueo === false) {
        throw new RuntimeException('No se pudo abrir el archivo de bloqueo.');
    }

    $temporal = null;
    try {
        if (!flock($bloqueo, $escritura ? LOCK_EX : LOCK_SH)) {
            throw new RuntimeException('No se pudo bloquear el archivo de datos.');
        }
        clearstatcache(true, $archivo);
        $caravanas = is_file($archivo)
            ? json_decode(file_get_contents($archivo), true, 512, JSON_THROW_ON_ERROR)
            : [];

        $resultado = $operacion($caravanas);
        if ($escritura) {
            $contenido = json_encode($caravanas, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
            $temporal = tempnam(dirname($archivo), 'reyes-');
            if ($temporal === false
                || file_put_contents($temporal, $contenido) !== strlen($contenido)
                || !rename($temporal, $archivo)
            ) {
                throw new RuntimeException('No se pudo guardar el archivo de datos.');
            }
            $temporal = null;
        }
        return $resultado;
    } finally {
        if (is_string($temporal)) {
            unlink($temporal);
        }
        fclose($bloqueo);
    }
}

function posicionCaravana(array $caravanas, int $id): int
{
    foreach ($caravanas as $posicion => $caravana) {
        if ($caravana['id'] === $id) {
            return $posicion;
        }
    }
    errorApi(404, 'CARAVANA_NO_ENCONTRADA', 'La caravana no existe.');
}

function posicionCaravanaDeParada(array $caravanas, string $id): int
{
    foreach ($caravanas as $posicion => $caravana) {
        if (in_array($id, array_column($caravana['paradas'] ?? [], 'id'), true)) {
            return $posicion;
        }
    }
    errorApi(404, 'PARADA_NO_ENCONTRADA', 'La parada no existe.');
}
