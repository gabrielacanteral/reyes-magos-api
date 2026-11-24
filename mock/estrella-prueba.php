<?php

header('Content-Type: text/xml; charset=utf-8');

$solicitud = @simplexml_load_string(file_get_contents('php://input'), 'SimpleXMLElement', LIBXML_NONET);
$origenes = $solicitud === false ? [] : $solicitud->xpath('//*[local-name()="origen"]');
$origen = (string) ($origenes[0] ?? '');

if ($origen === 'Herodes') {
    http_response_code(500);
    $respuesta = '<soap:Fault><faultcode>soap:Server</faultcode>'
        . '<faultstring>Cielo cubierto</faultstring></soap:Fault>';
} else {
    $visibilidad = $origen === 'Persia' ? 'NUBLADA' : 'VISIBLE';
    $respuesta = "<eb:ConsultarRutaResponse><eb:visibilidad>$visibilidad</eb:visibilidad>"
        . '<eb:diasRestantes>3</eb:diasRestantes></eb:ConsultarRutaResponse>';
}

echo '<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/"'
    . ' xmlns:eb="http://belen.beatlech/estrella">'
    . "<soap:Body>$respuesta</soap:Body></soap:Envelope>";
