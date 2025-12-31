<?php
namespace local_soap_sepe;

use DOMDocument;
use DOMXPath;
use DOMNode;
use DOMElement;
use Exception;

defined('MOODLE_INTERNAL') || die();

/**
 * Servidor SOAP especializado para el contrato WSDL del SEPE.
 * Maneja el parsing de XML crudo, seguridad WS-Security y generación de respuestas estrictas.
 */
class soap_server {

    private $request_dom;
    private $xpath;

    // Espacios de nombres definidos en el WSDL del SEPE
    const NS_SOAP = 'http://schemas.xmlsoap.org/soap/envelope/';
    const NS_WSSE = 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd';
    const NS_IMPL = 'http://impl.ws.application.proveedorcentro.meyss.spee.es'; // p867
    const NS_SALIDA = 'http://salida.bean.domain.common.proveedorcentro.meyss.spee.es'; // p148
    const NS_ENTSAL = 'http://entsal.bean.domain.common.proveedorcentro.meyss.spee.es'; // p465

    public function __construct($xml_content) {
        $this->request_dom = new DOMDocument();
        $this->request_dom->preserveWhiteSpace = false;
        
        // Silenciar errores de libxml estándar y manejarlos como excepciones
        $prev = libxml_use_internal_errors(true);
        if (!$this->request_dom->loadXML($xml_content)) {
            $errors = libxml_get_errors();
            libxml_clear_errors();
            libxml_use_internal_errors($prev);
            throw new Exception("XML de petición mal formado o inválido.", 400); 
        }
        libxml_use_internal_errors($prev);

        $this->xpath = new DOMXPath($this->request_dom);
        $this->_register_namespaces();
    }

    private function _register_namespaces() {
        $this->xpath->registerNamespace('soapenv', self::NS_SOAP);
        $this->xpath->registerNamespace('wsse', self::NS_WSSE);
        $this->xpath->registerNamespace('p867', self::NS_IMPL);
        // Registramos otros por si fueran necesarios para consultas específicas
        $this->xpath->registerNamespace('p148', self::NS_SALIDA);
        $this->xpath->registerNamespace('p465', self::NS_ENTSAL);
    }

    /**
     * Valida las credenciales WS-Security (UsernameToken) contra la BD de Moodle.
     */
    public function validate_security() {
        global $DB;

        $username_node = $this->xpath->query('//wsse:Security//wsse:Username')->item(0);
        $password_node = $this->xpath->query('//wsse:Security//wsse:Password')->item(0);

        if (!$username_node || !$password_node) {
            throw new Exception("Faltan credenciales de seguridad (WS-Security Username/Password).", 401);
        }

        $username = $username_node->nodeValue;
        $password = $password_node->nodeValue;

        // Validamos contra la tabla de usuarios de Moodle
        // IMPORTANTE: Se recomienda tener un usuario específico en Moodle para este servicio (ej: 'sepe_api')
        $user = $DB->get_record('user', ['username' => $username, 'deleted' => 0, 'suspended' => 0]);

        if (!$user) {
            throw new Exception("Usuario de servicio no encontrado.", 401);
        }

        if (!validate_internal_user_password($user, $password)) {
             throw new Exception("Contraseña de servicio incorrecta.", 401);
        }
        
        // Verificación extra de permisos si fuera necesario
        // if (!user_has_capability('local/soap_sepe:manage', \context_system::instance(), $user)) { ... }
        
        return true;
    }

    /**
     * Obtiene el nombre de la operación solicitada (el hijo directo del Body).
     * Ej: crearAccion, obtenerDatosCentro, etc.
     */
    public function get_action() {
        $body_child = $this->xpath->query('//soapenv:Body/*')->item(0);
        if (!$body_child) {
            throw new Exception("El cuerpo SOAP (Body) está vacío.", 400);
        }
        // Usamos localName para ignorar el prefijo (p867:crearAccion -> crearAccion)
        return $body_child->localName;
    }

    /**
     * Extrae todos los datos del Body y los convierte en un array asociativo.
     * Elimina los namespaces de las claves para facilitar el uso en PHP.
     */
    public function get_body_data_as_array() {
        $body_node = $this->xpath->query('//soapenv:Body/*')->item(0);
        if (!$body_node) return [];

        return $this->xml_to_array($body_node);
    }

    // =========================================================================
    // GENERADORES DE RESPUESTA (ESPECÍFICOS POR OPERACIÓN)
    // =========================================================================

    /**
     * Respuesta para: crearCentro y obtenerDatosCentro
     */
    public function generate_response_datos_centro($action_name, $codigo_retorno, $data = []) {
        $dom = new DOMDocument('1.0', 'UTF-8');
        // Envelope y Body con el wrapper de respuesta (ej: crearCentroResponse)
        $resp_wrapper = $this->_create_base_structure($dom, $action_name . 'Response');

        // Nodo contenedor de datos: p148:RESPUESTA_DATOS_CENTRO
        $resp_datos = $dom->createElement('p148:RESPUESTA_DATOS_CENTRO');
        $resp_datos->setAttribute('xmlns:p148', self::NS_SALIDA);
        $resp_datos->setAttribute('xmlns:p465', self::NS_ENTSAL);
        
        // Bloque estándar de estado
        $this->_append_status_block($dom, $resp_datos, $codigo_retorno);

        // Si hay éxito y datos, los inyectamos (DATOS_IDENTIFICATIVOS)
        if ($codigo_retorno == 0 && !empty($data)) {
            $nodo_datos = $dom->createElement('p465:DATOS_IDENTIFICATIVOS');
            $this->array_to_xml($data, $nodo_datos, $dom);
            $resp_datos->appendChild($nodo_datos);
        }

        $resp_wrapper->appendChild($resp_datos);
        return $dom->saveXML();
    }

    /**
     * Respuesta para: crearAccion y obtenerAccion
     */
    public function generate_response_accion($action_name, $codigo_retorno, $data = []) {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $resp_wrapper = $this->_create_base_structure($dom, $action_name . 'Response');

        // Nodo contenedor: p148:RESPUESTA_OBT_ACCION
        $resp_obt = $dom->createElement('p148:RESPUESTA_OBT_ACCION');
        $resp_obt->setAttribute('xmlns:p148', self::NS_SALIDA);
        $resp_obt->setAttribute('xmlns:p465', self::NS_ENTSAL);

        $this->_append_status_block($dom, $resp_obt, $codigo_retorno);

        // Nodo de datos: p465:ACCION_FORMATIVA
        $nodo_acc = $dom->createElement('p465:ACCION_FORMATIVA');
        if ($codigo_retorno == 0 && !empty($data)) {
            $this->array_to_xml($data, $nodo_acc, $dom);
        } else {
            // Si hay error o está vacío, suele enviarse nil
            $nodo_acc->setAttribute('xsi:nil', 'true');
            $nodo_acc->setAttribute('xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
        }
        $resp_obt->appendChild($nodo_acc);

        $resp_wrapper->appendChild($resp_obt);
        return $dom->saveXML();
    }

    /**
     * Respuesta para: eliminarAccion
     */
    public function generate_response_eliminar($codigo_retorno) {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $resp_wrapper = $this->_create_base_structure($dom, 'eliminarAccionResponse');

        $resp_el = $dom->createElement('p148:RESPUESTA_ELIMINAR_ACCION');
        $resp_el->setAttribute('xmlns:p148', self::NS_SALIDA);

        $this->_append_status_block($dom, $resp_el, $codigo_retorno);

        $resp_wrapper->appendChild($resp_el);
        return $dom->saveXML();
    }

    /**
     * Respuesta para: obtenerListaAcciones
     */
    public function generate_response_lista($codigo_retorno, $lista = []) {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $resp_wrapper = $this->_create_base_structure($dom, 'obtenerListaAccionesResponse');

        $resp_lista = $dom->createElement('p148:RESPUESTA_OBT_LISTA_ACCIONES');
        $resp_lista->setAttribute('xmlns:p148', self::NS_SALIDA);
        $resp_lista->setAttribute('xmlns:p465', self::NS_ENTSAL);

        $this->_append_status_block($dom, $resp_lista, $codigo_retorno);

        // La lista devuelve nodos p465:ID_ACCION repetidos
        if ($codigo_retorno == 0 && !empty($lista)) {
            foreach ($lista as $item) {
                $nodo = $dom->createElement('p465:ID_ACCION');
                // ID_ACCION tiene dentro ORIGEN_ACCION y CODIGO_ACCION sin prefijo
                $this->array_to_xml($item, $nodo, $dom);
                $resp_lista->appendChild($nodo);
            }
        }

        $resp_wrapper->appendChild($resp_lista);
        return $dom->saveXML();
    }

    /**
     * Genera un Fault SOAP genérico en caso de error grave.
     */
    public function generate_fault($message, $code = 'Server') {
        // XML manual string para asegurar que sale pase lo que pase con el DOM
        return '<?xml version="1.0" encoding="UTF-8"?>
        <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/">
           <soapenv:Body>
              <soapenv:Fault>
                 <faultcode>' . $code . '</faultcode>
                 <faultstring>' . htmlspecialchars($message) . '</faultstring>
              </soapenv:Fault>
           </soapenv:Body>
        </soapenv:Envelope>';
    }

    // =========================================================================
    // UTILIDADES PRIVADAS
    // =========================================================================

    /**
     * Crea la estructura base del Envelope y el Body, y devuelve el nodo Response wrapper.
     */
    private function _create_base_structure(DOMDocument $dom, $response_node_name) {
        $envelope = $dom->createElement('soapenv:Envelope');
        $envelope->setAttribute('xmlns:soapenv', self::NS_SOAP);
        $dom->appendChild($envelope);
        
        $body = $dom->createElement('soapenv:Body');
        $envelope->appendChild($body);
        
        // El wrapper de la respuesta (ej: p867:crearAccionResponse)
        $wrapper = $dom->createElement('p867:' . $response_node_name);
        $wrapper->setAttribute('xmlns:p867', self::NS_IMPL);
        $body->appendChild($wrapper);
        
        return $wrapper;
    }

    /**
     * Añade los campos CODIGO_RETORNO y ETIQUETA_ERROR.
     */
    private function _append_status_block(DOMDocument $dom, DOMElement $parent, $code) {
        $parent->appendChild($dom->createElement('CODIGO_RETORNO', $code));
        
        $error = $dom->createElement('ETIQUETA_ERROR');
        if ($code != 0) {
            // Si hay error, aquí podríamos poner texto. Por ahora dejamos nil o vacío según spec.
            // Para debug, podrías poner texto si $code < 0
        }
        $error->setAttribute('xsi:nil', 'true');
        $error->setAttribute('xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
        $parent->appendChild($error);
    }

    /**
     * Convierte recursivamente un DOMNode a un Array PHP.
     * Ignora namespaces en las claves.
     */
    private function xml_to_array(DOMNode $node) {
        $result = [];

        if ($node->nodeType === XML_TEXT_NODE) {
            return $node->nodeValue;
        }

        if ($node->hasChildNodes()) {
            foreach ($node->childNodes as $child) {
                if ($child->nodeType === XML_TEXT_NODE) {
                    $val = trim($child->nodeValue);
                    if ($val === '') continue;
                    // Si el nodo solo tiene texto, devolverlo directamente
                    // Pero si tiene hermanos elementos, cuidado.
                    // Simplificación: si es nodo texto puro sin hermanos elementos, es valor.
                }

                // Usamos localName para quitar 'p465:', etc.
                $name = $child->localName;
                if (!$name) continue; // Skip text nodes here usually

                $childData = $this->xml_to_array($child);
                
                // Si el parser devolvió un array vacío y el nodo tiene valor (texto), intentamos coger el valor
                if (is_array($childData) && empty($childData) && $child->nodeValue !== '') {
                     $cleanVal = trim($child->nodeValue);
                     if ($cleanVal !== '') $childData = $cleanVal;
                }

                // Manejo de listas (mismo tag repetido)
                if (isset($result[$name])) {
                    if (!is_array($result[$name]) || !isset($result[$name][0])) {
                        $result[$name] = [$result[$name]];
                    }
                    $result[$name][] = $childData;
                } else {
                    $result[$name] = $childData;
                }
            }
        }
        
        // Caso borde: nodo vacío o solo atributos (no manejamos atributos en este modelo SEPE)
        if (empty($result) && $node->nodeValue !== '') {
            return $node->nodeValue;
        }

        return $result;
    }

    /**
     * Convierte recursivamente un Array PHP a Nodos XML.
     * @param array|string $data Datos a convertir
     * @param DOMElement $parent Nodo padre donde adjuntar
     * @param DOMDocument $dom Documento para crear elementos
     */
    private function array_to_xml($data, DOMElement $parent, DOMDocument $dom) {
        if (!is_array($data)) {
            $parent->nodeValue = htmlspecialchars((string)$data);
            return;
        }

        foreach ($data as $key => $value) {
            // Si la clave es numérica, significa que el padre es una lista (ej: CENTRO_PRESENCIAL)
            // y $value es un ítem. El nodo padre ya se creó fuera, esto no debería pasar
            // si llamamos a la función correctamente iterando fuera.
            // Pero para seguridad en estructuras anidadas profundas:
            
            if (is_numeric($key)) {
                // Esto ocurre si pasamos un array indexado a procesar.
                // No deberíamos llegar aquí si la estructura del array es correcta
                // (clave => valor), pero si pasa, es difícil inferir el nombre del tag.
                // Asumimos que la lógica de negocio controla las iteraciones.
                continue; 
            }

            // Caso: Valor es array indexado (Lista de elementos repetidos)
            // Ej: 'CENTRO_PRESENCIAL' => [ [...], [...] ]
            if (is_array($value) && isset($value[0])) {
                foreach ($value as $item) {
                    $subnode = $dom->createElement($key);
                    $this->array_to_xml($item, $subnode, $dom);
                    $parent->appendChild($subnode);
                }
            } 
            // Caso: Valor es array asociativo (Objeto hijo único) o valor simple
            else {
                // Omitir valores nulos o vacíos si se desea limpiar el XML (opcional)
                if ($value === null) continue;
                
                $subnode = $dom->createElement($key);
                $this->array_to_xml($value, $subnode, $dom);
                $parent->appendChild($subnode);
            }
        }
    }
}