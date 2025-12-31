<?php
namespace local_soap_sepe;

use DOMDocument;
use DOMXPath;
use DOMNode;
use DOMElement;

defined('MOODLE_INTERNAL') || die();

class soap_server {

    private $request_dom;
    private $xpath;

    public function __construct($xml_content) {
        $this->request_dom = new DOMDocument();
        // Opciones vitales para parsear XML sucio o con namespaces complejos
        $this->request_dom->preserveWhiteSpace = false;
        
        if (!$this->request_dom->loadXML($xml_content)) {
            throw new \Exception("XML mal formado o inválido.", 2); 
        }
        $this->xpath = new DOMXPath($this->request_dom);
        
        // Registrar namespaces comunes del SEPE para facilitar queries específicas si hacen falta
        $this->xpath->registerNamespace('soapenv', 'http://schemas.xmlsoap.org/soap/envelope/');
        $this->xpath->registerNamespace('wsse', 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd');
    }

    /**
     * Valida las credenciales WS-Security contra un usuario de Moodle.
     * (Igual que antes, pero asegúrate de tener esta lógica)
     */
    public function validate_security() {
        global $DB;
        $username_node = $this->xpath->query('//wsse:Username')->item(0);
        $password_node = $this->xpath->query('//wsse:Password')->item(0);

        if (!$username_node || !$password_node) {
            throw new \Exception("Faltan credenciales de seguridad WS-Security", -2);
        }

        $username = $username_node->nodeValue;
        $password = $password_node->nodeValue;

        // Validar contra Moodle (Usuario específico para el servicio)
        $user = $DB->get_record('user', ['username' => $username, 'deleted' => 0, 'suspended' => 0]);

        if (!$user || !validate_internal_user_password($user, $password)) {
             throw new \Exception("Credenciales inválidas o usuario no encontrado", -2);
        }
        
        // Opcional: Verificar capability
        // if (!user_has_capability('local/soap_sepe:manage', \context_system::instance(), $user)) { ... }
        
        return true;
    }

    /**
     * Obtiene el nombre de la operación (ej: crearAccion)
     */
    public function get_action() {
        $body_child = $this->xpath->query('//soapenv:Body/*')->item(0);
        if (!$body_child) {
            throw new \Exception("El cuerpo SOAP está vacío.", 2);
        }
        return $body_child->localName;
    }

    /**
     * Convierte el nodo principal de la petición (ej: ACCION_FORMATIVA) en un array PHP.
     * Busca dentro del Body el primer hijo y lo procesa.
     */
    public function get_body_data_as_array() {
        $body_node = $this->xpath->query('//soapenv:Body/*')->item(0);
        if (!$body_node) return [];

        // Convertimos recursivamente
        // A menudo la petición viene envuelta en un nodo "Wrapper" (ej: crearAccion), 
        // y los datos reales están dentro (ej: ACCION_FORMATIVA).
        // Dependiendo del WSDL, a veces queremos el wrapper o lo de dentro.
        // Para crearAccion, el PDF dice que llega un nodo <ACCION_FORMATIVA>.
        
        // Buscamos directamente el nodo de datos útil si existe, o parseamos todo el wrapper.
        // Estrategia: Parsear todo el wrapper del Body.
        return $this->xml_to_array($body_node);
    }

    /**
     * Función auxiliar recursiva para convertir DOM a Array
     */
    private function xml_to_array(DOMNode $node) {
        $result = [];

        if ($node->nodeType === XML_TEXT_NODE) {
            return $node->nodeValue;
        }

        if ($node->hasChildNodes()) {
            foreach ($node->childNodes as $child) {
                // Ignorar nodos de texto vacíos (formato)
                if ($child->nodeType === XML_TEXT_NODE && trim($child->nodeValue) === '') {
                    continue;
                }

                // Obtener nombre sin namespace (localName)
                $name = $child->localName;

                // Llamada recursiva
                $childData = ($child->nodeType === XML_TEXT_NODE) 
                             ? $child->nodeValue 
                             : $this->xml_to_array($child);

                // Manejo de listas (elementos repetidos con el mismo nombre)
                if (isset($result[$name])) {
                    if (!is_array($result[$name]) || !isset($result[$name][0])) {
                        // Si ya existe pero no es una lista numérica, lo convertimos en una
                        $result[$name] = [$result[$name]];
                    }
                    $result[$name][] = $childData;
                } else {
                    $result[$name] = $childData;
                }
            }
        }
        
        // Si el nodo no tenía hijos útiles (solo texto directo), devolver el texto
        if (empty($result) && !empty($node->nodeValue)) {
            return $node->nodeValue;
        }

        return $result;
    }

    /**
     * Genera la respuesta XML SOAP Genérica.
     * @param string $action_response_node Nombre del nodo respuesta (ej: crearAccionResponse)
     * @param int $codigo_retorno
     * @param string|null $extra_xml XML string crudo para inyectar dentro (ej: <ID_ACCION>...</ID_ACCION>)
     */
    public function generate_response($action_response_node, $codigo_retorno, $extra_xml = null) {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = true;

        $envelope = $dom->createElement('soapenv:Envelope');
        $envelope->setAttribute('xmlns:soapenv', 'http://schemas.xmlsoap.org/soap/envelope/');
        $dom->appendChild($envelope);
        
        $body = $dom->createElement('soapenv:Body');
        $envelope->appendChild($body);
        
        // Nodo respuesta específico de la operación
        $response_node = $dom->createElement('p867:' . $action_response_node);
        $response_node->setAttribute('xmlns:p867', 'http://impl.ws.application.proveedorcentro.meyss.spee.es');
        $body->appendChild($response_node);
        
        // Contenedor interno estándar (según tus ejemplos anteriores)
        // Nota: El nombre de este nodo varía según la operación.
        // Para crearAccion es p148:RESPUESTA_OBT_ACCION
        // Para crearCentro es p148:RESPUESTA_DATOS_CENTRO
        // Tendrás que pasar este nombre como parámetro o deducirlo.
        // Por simplicidad, asumiremos que lo pasas en $extra_xml o lo parametrizamos luego.
        
        // Para este ejemplo, inyectamos el XML extra directamente si viene construido,
        // o construimos lo básico.
        
        // ... (Aquí puedes reutilizar tu lógica anterior de generación de XML, 
        // pero adaptada para recibir parámetros en lugar de hardcodear) ...
        
        return $dom->saveXML();
    }
    
    // Método específico para crearAccionResponse (para simplificar index.php)
    public function generate_response_crear_accion($codigo_retorno, $data_accion = []) {
        $dom = new DOMDocument('1.0', 'UTF-8');
        
        $envelope = $dom->createElement('soapenv:Envelope');
        $envelope->setAttribute('xmlns:soapenv', 'http://schemas.xmlsoap.org/soap/envelope/');
        $dom->appendChild($envelope);
        
        $body = $dom->createElement('soapenv:Body');
        $envelope->appendChild($body);
        
        $main = $dom->createElement('p867:crearAccionResponse');
        $main->setAttribute('xmlns:p867', 'http://impl.ws.application.proveedorcentro.meyss.spee.es');
        $body->appendChild($main);
        
        $resp = $dom->createElement('p148:RESPUESTA_OBT_ACCION');
        $resp->setAttribute('xmlns:p148', 'http://salida.bean.domain.common.proveedorcentro.meyss.spee.es');
        $resp->setAttribute('xmlns:p465', 'http://entsal.bean.domain.common.proveedorcentro.meyss.spee.es');
        $main->appendChild($resp);
        
        $resp->appendChild($dom->createElement('CODIGO_RETORNO', $codigo_retorno));
        
        $err = $dom->createElement('ETIQUETA_ERROR');
        $err->setAttribute('xsi:nil', 'true');
        $resp->appendChild($err);
        
        $acc = $dom->createElement('p465:ACCION_FORMATIVA');
        if ($codigo_retorno == 0 && !empty($data_accion)) {
            // Devolver datos mínimos de confirmación (ID y Código)
            // Según tu lógica anterior, devolvías casi todo. 
            // Aquí reconstruimos lo básico para confirmar.
            
            $id_acc = $dom->createElement('ID_ACCION');
            // Ojo: $data_accion es el array limpio, usamos las keys limpias
            $id_acc->appendChild($dom->createElement('ORIGEN_ACCION', $data_accion['ID_ACCION']['ORIGEN_ACCION'] ?? ''));
            $id_acc->appendChild($dom->createElement('CODIGO_ACCION', $data_accion['ID_ACCION']['CODIGO_ACCION'] ?? ''));
            $acc->appendChild($id_acc);
            
            // ... Puedes añadir más campos si el SEPE lo exige en el retorno ...
        } else {
            $acc->setAttribute('xsi:nil', 'true');
        }
        $resp->appendChild($acc);
        
        return $dom->saveXML();
    }
}