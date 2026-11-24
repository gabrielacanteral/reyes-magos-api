<?php

ini_set('display_errors', '0');

require_once __DIR__ . '/src/Http.php';
require_once __DIR__ . '/src/Repositorio.php';
require_once __DIR__ . '/src/EstrellaAdapter.php';
require_once __DIR__ . '/src/ParadasService.php';
require_once __DIR__ . '/src/CaravanasService.php';
require_once __DIR__ . '/src/Controladores.php';

$metodo = $_SERVER['REQUEST_METHOD'];
$ruta = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';
$archivo = getenv('REYES_DATOS') ?: __DIR__ . '/../reyes-magos-caravanas.json';

try {
    if ($metodo === 'GET' && $ruta === '/') {
        responder(200, ['mensaje' => 'API de Reyes Magos funcionando']);
        return;
    }

    $rutas = [
        '#^/api/caravanas$#' => [
            'GET' => [false, fn () => consultarCaravanas($archivo)],
            'POST' => [true, fn () => crearCaravana($archivo)],
        ],
        '#^/api/caravanas/([0-9]+)$#' => [
            'GET' => [false, fn ($id) => consultarCaravana($archivo, (int) $id)],
            'PUT' => [true, fn ($id) => actualizarCaravana($archivo, (int) $id, false)],
        ],
        '#^/api/caravanas/([0-9]+)/estado$#' => [
            'PATCH' => [true, fn ($id) => actualizarCaravana($archivo, (int) $id, true)],
        ],
        '#^/api/caravanas/([0-9]+)/paradas$#' => [
            'GET' => [false, fn ($id) => consultarParadas($archivo, (int) $id)],
            'POST' => [true, fn ($id) => crearParada($archivo, (int) $id)],
        ],
        '#^/api/paradas/([0-9a-f]{32})/estado$#' => [
            'PATCH' => [false, fn ($id) => actualizarParada($archivo, $id)],
        ],
        '#^/api/paradas/([0-9a-f]{32})$#' => [
            'DELETE' => [true, fn ($id) => borrarParada($archivo, $id)],
        ],
    ];

    foreach ($rutas as $patron => $acciones) {
        if (!preg_match($patron, $ruta, $partes)) {
            continue;
        }
        if (!isset($acciones[$metodo])) {
            header('Allow: ' . implode(', ', array_keys($acciones)));
            errorApi(405, 'METODO_NO_PERMITIDO', 'El método no está disponible en esta ruta.');
        }
        [$protegida, $accion] = $acciones[$metodo];
        if ($protegida) {
            exigirJefe();
        }
        $accion(...array_slice($partes, 1));
        return;
    }

    errorApi(404, 'RUTA_NO_ENCONTRADA', 'La ruta no existe.');
} catch (ErrorApi $error) {
    responderError($error, $ruta);
} catch (Throwable $error) {
    error_log($error->getMessage());
    responderError(new ErrorApi(500, 'ERROR_INTERNO', 'No se pudo completar la operación.'), $ruta);
}
