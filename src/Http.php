<?php

final class ErrorApi extends RuntimeException
{
    public function __construct(public int $estado, public string $codigo, string $mensaje)
    {
        parent::__construct($mensaje);
    }
}

function errorApi(int $estado, string $codigo, string $mensaje): never
{
    throw new ErrorApi($estado, $codigo, $mensaje);
}

function responder(int $estado, ?array $datos = null): void
{
    http_response_code($estado);
    if ($datos !== null) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }
}

function responderError(ErrorApi $error, string $ruta): void
{
    responder($error->estado, [
        'codigo' => $error->codigo,
        'mensaje' => $error->getMessage(),
        'detalles' => [],
        'timestamp' => gmdate('Y-m-d\TH:i:s\Z'),
        'ruta' => $ruta,
    ]);
}

function leerJson(): array
{
    $datos = json_decode(file_get_contents('php://input'), true);
    if (!is_array($datos)) {
        errorApi(400, 'DATOS_INVALIDOS', 'Envía un cuerpo JSON válido.');
    }
    return $datos;
}

function exigirJefe(): void
{
    $autorizacion = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if ($autorizacion === '') {
        errorApi(401, 'TOKEN_REQUERIDO', 'Envía el token en Authorization.');
    }

    $tokenPaje = getenv('REYES_TOKEN_PAJE');
    if ($tokenPaje && hash_equals('Bearer ' . $tokenPaje, $autorizacion)) {
        errorApi(403, 'ACCESO_DENEGADO', 'El rol PAJE no puede realizar esta operación.');
    }

    $tokenJefe = getenv('REYES_TOKEN_JEFE');
    if (!$tokenJefe || !hash_equals('Bearer ' . $tokenJefe, $autorizacion)) {
        errorApi(403, 'ACCESO_DENEGADO', 'Se requiere el rol JEFE_CARAVANA.');
    }
}
