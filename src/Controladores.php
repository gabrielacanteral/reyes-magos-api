<?php

function crearCaravana(string $archivo): void
{
    $datos = leerJson();
    $caravana = conCaravanas($archivo, true, fn (array &$lista) => registrarCaravana($lista, $datos));
    header('Location: /api/caravanas/' . $caravana['id']);
    responder(201, $caravana);
}

function consultarCaravanas(string $archivo): void
{
    $estado = $_GET['estado'] ?? null;
    $caravanas = conCaravanas($archivo, false, fn (array $lista) => $lista);
    if ($estado !== null) {
        $caravanas = array_values(array_filter(
            $caravanas,
            fn (array $caravana) => $caravana['estado'] === $estado
        ));
    }
    responder(200, $caravanas);
}

function consultarCaravana(string $archivo, int $id): void
{
    $caravana = conCaravanas($archivo, false, function (array $lista) use ($id) {
        $caravana = $lista[posicionCaravana($lista, $id)];
        $caravana['paradas'] = listarParadas($caravana);
        return $caravana;
    });
    responder(200, $caravana);
}

function actualizarCaravana(string $archivo, int $id, bool $soloEstado): void
{
    $datos = leerJson();
    $caravana = conCaravanas($archivo, true, function (array &$lista) use ($id, $datos, $soloEstado) {
        $posicion = posicionCaravana($lista, $id);
        $actual = $lista[$posicion];
        $lista[$posicion] = $soloEstado
            ? cambiarEstadoCaravana($actual, $datos['estado'] ?? null)
            : editarCaravana($actual, $datos, $lista);
        return $lista[$posicion];
    });
    responder(200, $caravana);
}

function consultarParadas(string $archivo, int $id): void
{
    $paradas = conCaravanas($archivo, false, fn (array $lista) =>
        listarParadas($lista[posicionCaravana($lista, $id)])
    );
    responder(200, $paradas);
}

function crearParada(string $archivo, int $id): void
{
    $datos = leerJson();
    $parada = conCaravanas($archivo, true, function (array &$lista) use ($id, $datos) {
        $posicion = posicionCaravana($lista, $id);
        return agregarParada($lista[$posicion], $datos);
    });
    responder(201, $parada);
}

function actualizarParada(string $archivo, string $id): void
{
    $datos = leerJson();
    $parada = conCaravanas($archivo, true, function (array &$lista) use ($id, $datos) {
        $posicion = posicionCaravanaDeParada($lista, $id);
        return cambiarEstadoParada($lista[$posicion], $id, $datos['estado'] ?? null);
    });
    responder(200, $parada);
}

function borrarParada(string $archivo, string $id): void
{
    conCaravanas($archivo, true, function (array &$lista) use ($id) {
        $posicion = posicionCaravanaDeParada($lista, $id);
        eliminarParada($lista[$posicion], $id);
    });
    responder(204);
}
