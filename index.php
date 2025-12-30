<?php
require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');

// Configurar el encabezado SOAP
header('Content-Type: text/xml; charset=utf-8');

// Capturar el cuerpo de la solicitud SOAP
$requestXML = file_get_contents('php://input');

// Validar las credenciales desde la solicitud SOAP
if (!validate_credentials($requestXML)) {
    $responseDOM = new DOMDocument('1.0', 'UTF-8');
    $responseDOM->formatOutput = true;

    // Nodo raíz SOAP
    $envelope = $responseDOM->createElement('soapenv:Envelope');
    $envelope->setAttribute('xmlns:soapenv', 'http://schemas.xmlsoap.org/soap/envelope/');
    $envelope->setAttribute('xmlns:soapenc', 'http://schemas.xmlsoap.org/soap/encoding/');
    $envelope->setAttribute('xmlns:xsd', 'http://www.w3.org/2001/XMLSchema');
    $envelope->setAttribute('xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
    $responseDOM->appendChild($envelope);

    // Cuerpo del mensaje SOAP
    $body = $responseDOM->createElement('soapenv:Body');
    $envelope->appendChild($body);

    // Nodo de respuesta principal
    $obtenerDatosCentroResponse = $responseDOM->createElement('p867:obtenerDatosCentroResponse');
    $obtenerDatosCentroResponse->setAttribute('xmlns:p867', 'http://impl.ws.application.proveedorcentro.meyss.spee.es');

    $body->appendChild($obtenerDatosCentroResponse);

    $respuesta = $responseDOM->createElement('p148:RESPUESTA_DATOS_CENTRO');
    $respuesta->setAttribute('xmlns:p465', 'http://entsal.bean.domain.common.proveedorcentro.meyss.spee.es');
    $respuesta->setAttribute('xmlns:p148', 'http://salida.bean.domain.common.proveedorcentro.meyss.spee.es');

    $obtenerDatosCentroResponse->appendChild($respuesta);

    // Nodo CODIGO_RETORNO
    $respuesta->appendChild($responseDOM->createElement('CODIGO_RETORNO', '-1'));

    // Nodo ETIQUETA_ERROR
    $etiquetaErrorNode = $responseDOM->createElement('ETIQUETA_ERROR', '');
    $etiquetaErrorNode->setAttribute('xsi:nil', 'true');
    
    $respuesta->appendChild($etiquetaErrorNode);

    echo $responseDOM->saveXML();

    //echo createErrorResponse('Credenciales inválidas');
}else{
    // Procesar la solicitud SOAP y devolver la respuesta
    $responseXML = processSoapRequest($requestXML);
    echo $responseXML;
}

function validate_credentials($requestXML) {
    global $DB;

    $dom = new DOMDocument();
    $dom->loadXML($requestXML);

    // Extraer el nodo UsernameToken del encabezado SOAP
    $xpath = new DOMXPath($dom);
    $xpath->registerNamespace('wsse', 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd');
    $usernameNode = $xpath->query('//wsse:Username')->item(0);
    $passwordNode = $xpath->query('//wsse:Password')->item(0);

    if (!$usernameNode || !$passwordNode) {
        return false; // Credenciales no presentes
    }

    $username = $usernameNode->nodeValue;
    $password = $passwordNode->nodeValue;

    // Verificar las credenciales contra la base de datos
    $user = $DB->get_record('user', ['username' => $username]);

    if (!$user || !password_verify($password, $user->password)) {
        return false; // Credenciales inválidas
    }

    return true; // Credenciales válidas
}


function createErrorResponse($errorMessage) {
    $responseDOM = new DOMDocument('1.0', 'UTF-8');
    $responseDOM->formatOutput = true;

    $envelope = $responseDOM->createElement('soapenv:Envelope');
    $envelope->setAttribute('xmlns:soapenv', 'http://schemas.xmlsoap.org/soap/envelope/');
    $responseDOM->appendChild($envelope);

    $body = $responseDOM->createElement('soapenv:Body');
    $envelope->appendChild($body);

    $fault = $responseDOM->createElement('soapenv:Fault');
    $body->appendChild($fault);

    $faultCode = $responseDOM->createElement('faultcode', 'Client');
    $faultString = $responseDOM->createElement('faultstring', $errorMessage);
    $fault->appendChild($faultCode);
    $fault->appendChild($faultString);

    return $responseDOM->saveXML();
}


// Procesar la solicitud y determinar la función a invocar
function processSoapRequest($requestXML) {
    // Cargar la solicitud SOAP en un DOMDocument
    $dom = new DOMDocument();
    $dom->loadXML($requestXML);

    // Extraer el nombre del método solicitado
    $xpath = new DOMXPath($dom);
    $xpath->registerNamespace('soapenv', 'http://schemas.xmlsoap.org/soap/envelope/');
    $methodNode = $xpath->query('//soapenv:Body/*')->item(0);

    if (!$methodNode) {
        // Si no se encuentra un método, devolver un error SOAP
        return generateSoapFault('Client', 'Método SOAP no encontrado.');
    }

    $methodName = $methodNode->localName;

    // Enrutamiento a la función correspondiente
    switch ($methodName) {
        case 'crearAccion':
            return local_crear_accion_process_request($requestXML);
        case 'crearCentro':
            return local_crear_centro_process_request($requestXML);
        case 'obtenerDatosCentro':
            return local_obtener_datos_centro_process_request($requestXML);
        case 'obtenerListaAcciones':
            return local_obtener_lista_acciones_process_request($requestXML);
            break;
        case 'obtenerAccion':
            return local_obtener_accion_process_request($requestXML);
            break;
        case 'eliminarAccion':
            return local_eliminar_accion_process_request($requestXML);
            break;
        default:
            // Si no hay un método correspondiente, devolver un error SOAP
            return generateSoapFault('Client', "Método SOAP desconocido: $methodName");
    }
}

// Función para generar una respuesta de fallo SOAP
function generateSoapFault($faultCode, $faultString) {
    $responseDOM = new DOMDocument('1.0', 'UTF-8');
    $responseDOM->formatOutput = true;

    // Crear la estructura SOAP Fault
    $envelope = $responseDOM->createElement('soapenv:Envelope');
    $envelope->setAttribute('xmlns:soapenv', 'http://schemas.xmlsoap.org/soap/envelope/');
    $responseDOM->appendChild($envelope);

    $body = $responseDOM->createElement('soapenv:Body');
    $envelope->appendChild($body);

    $fault = $responseDOM->createElement('soapenv:Fault');
    $body->appendChild($fault);

    $fault->appendChild($responseDOM->createElement('faultcode', $faultCode));
    $fault->appendChild($responseDOM->createElement('faultstring', $faultString));

    return $responseDOM->saveXML();
}


