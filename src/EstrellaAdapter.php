<?php

function consultarEstrella(string $origen): string
{
    $direccion = getenv('REYES_SOAP_URL');
    if (!$direccion) {
        throw new RuntimeException('Falta configurar el servicio de la Estrella.');
    }

    $origenXml = htmlspecialchars($origen, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    $xml = <<<XML
    <soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/"
                   xmlns:eb="http://belen.beatlech/estrella">
      <soap:Body>
        <eb:ConsultarRutaRequest>
          <eb:origen>{$origenXml}</eb:origen>
          <eb:destino>Belen</eb:destino>
        </eb:ConsultarRutaRequest>
      </soap:Body>
    </soap:Envelope>
    XML;

    $conexion = curl_init($direccion);
    curl_setopt_array($conexion, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $xml,
        CURLOPT_HTTPHEADER => ['Content-Type: text/xml; charset=utf-8'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 3,
    ]);

    $respuesta = curl_exec($conexion);
    $estadoHttp = curl_getinfo($conexion, CURLINFO_HTTP_CODE);
    curl_close($conexion);

    if ($respuesta === false || $estadoHttp >= 400) {
        throw new RuntimeException('El servicio de la Estrella no está disponible.');
    }

    $documento = @simplexml_load_string($respuesta, 'SimpleXMLElement', LIBXML_NONET);
    $fallos = $documento === false ? [] : $documento->xpath('//*[local-name()="Fault"]');
    $valores = $documento === false ? [] : $documento->xpath('//*[local-name()="visibilidad"]');
    $visibilidad = trim((string) ($valores[0] ?? ''));

    if ($fallos || !in_array($visibilidad, ['VISIBLE', 'NUBLADA'], true)) {
        throw new RuntimeException('La Estrella devolvió un fallo o una respuesta inválida.');
    }

    return $visibilidad;
}
