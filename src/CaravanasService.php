<?php

function editarCaravana(array $caravana, array $datos, array $caravanas): array
{
    if ($caravana['estado'] !== 'DETENIDA') {
        errorApi(409, 'CARAVANA_NO_EDITABLE', 'Solo se puede editar una caravana detenida.');
    }

    return array_replace($caravana, validarCaravana($datos, $caravanas, $caravana['id']));
}

function validarCaravana(array $datos, array $caravanas, ?int $id = null): array
{
    $rey = $datos['rey'] ?? null;
    $ofrenda = $datos['ofrenda'] ?? null;
    $origen = $datos['origen'] ?? null;
    $camellos = $datos['camellos'] ?? null;

    if (
        !in_array($rey, ['MELCHOR', 'GASPAR', 'BALTASAR'], true)
        || !in_array($ofrenda, ['ORO', 'INCIENSO', 'MIRRA'], true)
        || !is_string($origen) || trim($origen) === ''
        || !is_int($camellos) || $camellos < 1 || $camellos > 12
    ) {
        errorApi(400, 'DATOS_INVALIDOS', 'Revisa el rey, la ofrenda, el origen y los camellos.');
    }

    foreach ($caravanas as $otra) {
        if ($otra['id'] !== $id
            && ($otra['rey'] === $rey || $otra['ofrenda'] === $ofrenda)
        ) {
            errorApi(409, 'CARAVANA_DUPLICADA', 'Otra caravana ya usa ese rey o esa ofrenda.');
        }
    }

    return [
        'rey' => $rey,
        'ofrenda' => $ofrenda,
        'origen' => trim($origen),
        'camellos' => $camellos,
    ];
}

function registrarCaravana(array &$caravanas, array $datos): array
{
    $caravana = validarCaravana($datos, $caravanas);
    $caravana['id'] = max([0, ...array_column($caravanas, 'id')]) + 1;
    $caravana['estado'] = 'DETENIDA';
    $caravanas[] = $caravana;

    return $caravana;
}

function cambiarEstadoCaravana(array $caravana, mixed $estado): array
{
    $transiciones = [
        'DETENIDA' => ['EN_CAMINO'],
        'EN_CAMINO' => ['DETENIDA', 'EN_BELEN'],
        'EN_BELEN' => ['OFRENDA_ENTREGADA'],
        'OFRENDA_ENTREGADA' => [],
    ];

    if (!is_string($estado) || !array_key_exists($estado, $transiciones)) {
        errorApi(400, 'DATOS_INVALIDOS', 'Envía un estado válido.');
    }
    if (!in_array($estado, $transiciones[$caravana['estado']], true)) {
        errorApi(409, 'TRANSICION_INVALIDA', 'No se permite ese cambio de estado.');
    }

    if ($estado === 'EN_CAMINO') {
        try {
            $visibilidad = consultarEstrella($caravana['origen']);
        } catch (RuntimeException $error) {
            errorApi(503, 'ESTRELLA_NO_DISPONIBLE', 'No se pudo consultar la Estrella de Belén.');
        }
        if ($visibilidad === 'NUBLADA') {
            errorApi(422, 'ESTRELLA_NUBLADA', 'La caravana debe seguir detenida.');
        }
    }
    if ($estado === 'EN_BELEN') {
        validarLlegada($caravana);
    }

    $caravana['estado'] = $estado;
    return $caravana;
}
