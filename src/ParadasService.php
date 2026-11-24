<?php

function listarParadas(array $caravana): array
{
    $paradas = $caravana['paradas'] ?? [];
    usort($paradas, fn (array $a, array $b) => $a['orden'] <=> $b['orden']);

    return $paradas;
}

function agregarParada(array &$caravana, array $datos): array
{
    if (in_array($caravana['estado'], ['EN_BELEN', 'OFRENDA_ENTREGADA'], true)) {
        errorApi(409, 'CARAVANA_YA_LLEGO', 'La caravana ya no admite paradas.');
    }

    $ciudad = $datos['ciudad'] ?? null;
    $orden = $datos['orden'] ?? null;
    $dias = $datos['diasEstancia'] ?? null;

    if (
        !is_string($ciudad) || trim($ciudad) === ''
        || !is_int($orden) || $orden < 1
        || !is_int($dias) || $dias < 0 || $dias > 7
    ) {
        errorApi(400, 'DATOS_INVALIDOS', 'Revisa la ciudad, el orden y los días de estancia.');
    }

    foreach (listarParadas($caravana) as $existente) {
        if ($existente['orden'] === $orden) {
            errorApi(409, 'ORDEN_DUPLICADO', 'Ya hay una parada con ese orden.');
        }
    }

    $parada = [
        'id' => bin2hex(random_bytes(16)),
        'caravanaId' => $caravana['id'],
        'ciudad' => trim($ciudad),
        'orden' => $orden,
        'diasEstancia' => $dias,
        'estado' => 'PLANIFICADA',
    ];

    $caravana['paradas'][] = $parada;

    return $parada;
}

function validarLlegada(array $caravana): void
{
    foreach (listarParadas($caravana) as $parada) {
        if ($parada['estado'] === 'PLANIFICADA') {
            errorApi(422, 'PARADAS_PENDIENTES', 'La caravana tiene paradas pendientes.');
        }
    }
}

function cambiarEstadoParada(array &$caravana, string $id, mixed $estado): array
{
    if (!in_array($estado, ['CUMPLIDA', 'CANCELADA'], true)) {
        errorApi(400, 'DATOS_INVALIDOS', 'El estado debe ser CUMPLIDA o CANCELADA.');
    }

    $paradas = $caravana['paradas'] ?? [];

    foreach ($paradas as $posicion => $parada) {
        if ($parada['id'] !== $id) {
            continue;
        }

        if ($estado === 'CUMPLIDA') {
            foreach ($paradas as $anterior) {
                if ($anterior['orden'] === $parada['orden'] - 1
                    && $anterior['estado'] === 'PLANIFICADA'
                ) {
                    errorApi(409, 'PARADA_ANTERIOR_PENDIENTE', 'La parada anterior sigue planificada.');
                }
            }
        }

        $parada['estado'] = $estado;
        $caravana['paradas'][$posicion] = $parada;

        return $parada;
    }

    errorApi(404, 'PARADA_NO_ENCONTRADA', 'La parada no existe.');
}

function eliminarParada(array &$caravana, string $id): void
{
    foreach ($caravana['paradas'] ?? [] as $posicion => $parada) {
        if ($parada['id'] !== $id) {
            continue;
        }

        if ($parada['estado'] !== 'PLANIFICADA') {
            errorApi(409, 'PARADA_NO_ELIMINABLE', 'Solo se puede eliminar una parada planificada.');
        }

        array_splice($caravana['paradas'], $posicion, 1);
        return;
    }

    errorApi(404, 'PARADA_NO_ENCONTRADA', 'La parada no existe.');
}
