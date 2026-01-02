<?php
/**
 * Endpoint del Servicio Web SOAP para el SEPE.
 */

// 1. CARGA DE CONFIGURACIÓN
require_once(__DIR__ . '/../../config.php');

// 2. BLINDAJE DE SALIDA (CRÍTICO PARA EL VALIDADOR JAVA)
// Desactivamos cualquier mensaje de error visible de PHP o Moodle
// para asegurar que SOLO se envía XML limpio.
@error_reporting(0);
@ini_set('display_errors', '0');
$CFG->debug = 0; // Apagar modo debug de Moodle temporalmente para este script

// Limpiamos cualquier buffer que Moodle haya podido abrir (espacios en blanco, warnings)
while (ob_get_level()) ob_end_clean();

// Iniciamos nuestro propio buffer para capturar salidas inesperadas
ob_start();

use local_soap_sepe\soap_server;
use local_soap_sepe\sepe_manager;

$xml_input = file_get_contents('php://input');

// Comprobación de navegador (GET)
if (empty($xml_input)) {
    ob_end_clean(); // Limpiar buffer
    header('Content-Type: text/plain; charset=utf-8');
    die('Endpoint Activo. Esperando peticiones SOAP XML.');
}

// Preparar cabeceras SOAP
header('Content-Type: text/xml; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');

try {
    // 3. INSTANCIAR CLASES
    $server = new soap_server($xml_input);
    $server->validate_security(); // Validar usuario/pass

    $action = $server->get_action();
    $manager = new sepe_manager();
    
    $response_xml = '';
    $retorno = 0; 

    // 4. ENRUTAMIENTO
    switch ($action) {
        case 'crearCentro':
            $raw_data = $server->get_body_data_as_array();
            
            // CORRECCIÓN: El SEPE suele enviar los datos dentro de <DATOS_IDENTIFICATIVOS>
            // Si existe esa clave, la usamos. Si no, usamos el array original.
            $data = $raw_data['DATOS_IDENTIFICATIVOS'] ?? $raw_data;

            try {
                $manager->crear_centro($data);
                $datos = $manager->obtener_datos_centro();
            } catch (Exception $e) {
                $retorno = 1; 
                error_log('SEPE crearCentro Error: ' . $e->getMessage());
                $datos = [];
            }
            $response_xml = $server->generate_response_datos_centro('crearCentro', $retorno, $datos);
            break;

        case 'obtenerDatosCentro':
            try {
                $datos = $manager->obtener_datos_centro();
                if (!$datos) $retorno = 1;
            } catch (Exception $e) {
                $retorno = 1;
                $datos = [];
            }
            $response_xml = $server->generate_response_datos_centro('obtenerDatosCentro', $retorno, $datos);
            break;

        case 'crearAccion':
            $data = $server->get_body_data_as_array();
            $accion = $data['ACCION_FORMATIVA'] ?? $data;
            try {
                $manager->crear_accion($accion);
                
                // Si llegamos aquí, fue éxito (se insertó)
                $response_xml = $server->generate_response_accion('crearAccion', 0, $accion);
                
            } catch (Exception $e) {
                // Si salta error (DUPLICADO), devolvemos RETORNO 1 y VACÍO
                $retorno = 1;
                error_log('SEPE crearAccion Error (esperado si es duplicado): ' . $e->getMessage());
                
                // IMPORTANTE: Pasar array vacío para que salga nil
                $response_xml = $server->generate_response_accion('crearAccion', 1, []);
            }
            break;

        case 'obtenerAccion':
            $data = $server->get_body_data_as_array();
            $id = $data['ID_ACCION'] ?? $data;
            try {
                $res = $manager->obtener_accion($id);
                if (!$res) $retorno = 1; // 1 = No encontrado / Error
            } catch (Exception $e) {
                $retorno = 1;
                $res = [];
                error_log('SEPE obtenerAccion Error: ' . $e->getMessage());
            }
            $response_xml = $server->generate_response_accion('obtenerAccion', $retorno, $res);
            break;

        case 'eliminarAccion':
            $data = $server->get_body_data_as_array();
            $id = $data['ID_ACCION'] ?? $data;
            try {
                $manager->eliminar_accion($id);
            } catch (Exception $e) {
                $retorno = 1;
                error_log('SEPE eliminarAccion Error: ' . $e->getMessage());
            }
            $response_xml = $server->generate_response_eliminar($retorno);
            break;
            
        case 'obtenerListaAcciones':
             try {
                $lista = $manager->obtener_lista_acciones();
             } catch (Exception $e) {
                $retorno = 1;
                $lista = [];
                error_log('SEPE obtenerListaAcciones Error: ' . $e->getMessage());
             }
             $response_xml = $server->generate_response_lista($retorno, $lista);
             break;

        default:
            throw new Exception("Operación desconocida: $action");
    }

    // Limpiamos cualquier basura que se haya generado en el buffer antes de enviar el XML
    ob_clean();
    echo $response_xml;

} catch (Throwable $e) { // 'Throwable' captura Errores Fatales de PHP 7+ y Excepciones
    ob_clean(); // Limpiar basura antes del error
    http_response_code(500); // Avisar al cliente de fallo servidor
    
    // Loguear el error real en el archivo de logs del servidor
    error_log('SEPE FATAL ERROR: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());

    // Devolver un XML de fallo válido para que el cliente Java no cierre conexión de golpe
    if (isset($server)) {
        echo $server->generate_fault('Error interno del servidor. Contacte con soporte.');
    } else {
        echo '<?xml version="1.0"?><soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/"><soapenv:Body><soapenv:Fault><faultcode>Server</faultcode><faultstring>Critical Error</faultstring></soapenv:Fault></soapenv:Body></soapenv:Envelope>';
    }
}