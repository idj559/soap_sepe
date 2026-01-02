<?php
namespace local_soap_sepe;

use DOMDocument;
use DOMXPath;
use DOMNode;
use DOMElement;
use Exception;

defined('MOODLE_INTERNAL') || die();

/**
 * Servidor SOAP optimizado para generar XML limpio (estilo Axis2/SEPE).
 */
class soap_server {

    private $request_dom;
    private $xpath;

    // Namespaces oficiales
    const NS_SOAP = 'http://schemas.xmlsoap.org/soap/envelope/';
    const NS_WSSE = 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd';
    const NS_IMPL = 'http://impl.ws.application.proveedorcentro.meyss.spee.es'; // p867
    const NS_SALIDA = 'http://salida.bean.domain.common.proveedorcentro.meyss.spee.es'; // p148
    const NS_ENTSAL = 'http://entsal.bean.domain.common.proveedorcentro.meyss.spee.es'; // p465
    const NS_XSI = 'http://www.w3.org/2001/XMLSchema-instance';

    public function __construct($xml_content) {
        $this->request_dom = new DOMDocument();
        $this->request_dom->preserveWhiteSpace = false;
        
        $prev = libxml_use_internal_errors(true);
        if (!$this->request_dom->loadXML($xml_content)) {
            libxml_clear_errors();
            libxml_use_internal_errors($prev);
            throw new Exception("XML mal formado.", 400); 
        }
        libxml_use_internal_errors($prev);

        $this->xpath = new DOMXPath($this->request_dom);
        $this->xpath->registerNamespace('soapenv', self::NS_SOAP);
        $this->xpath->registerNamespace('wsse', self::NS_WSSE);
        $this->xpath->registerNamespace('p867', self::NS_IMPL);
    }

    public function validate_security() {
        global $DB;
        $username_node = $this->xpath->query('//wsse:Security//wsse:Username')->item(0);
        $password_node = $this->xpath->query('//wsse:Security//wsse:Password')->item(0);

        if (!$username_node || !$password_node) throw new Exception("Faltan credenciales WS-Security.", 401);

        $username = $username_node->nodeValue;
        $password = $password_node->nodeValue;

        $user = $DB->get_record('user', ['username' => $username, 'deleted' => 0, 'suspended' => 0]);
        if (!$user || !validate_internal_user_password($user, $password)) {
             throw new Exception("Credenciales incorrectas.", 401);
        }
    }

    public function get_action() {
        $nodes = $this->xpath->query('//soapenv:Body/*');
        if ($nodes->length === 0) $nodes = $this->xpath->query('//*[local-name()="Body"]/*');
        if ($nodes->length === 0) throw new Exception("Body vacío.");
        return $nodes->item(0)->localName;
    }

    public function get_body_data_as_array() {
        $nodes = $this->xpath->query('//soapenv:Body/*');
        if ($nodes->length === 0) $nodes = $this->xpath->query('//*[local-name()="Body"]/*');
        return ($nodes->length > 0) ? $this->xml_to_array($nodes->item(0)) : [];
    }

    // --- GENERADORES DE RESPUESTA ---

    public function generate_response_datos_centro($action_name, $codigo_retorno, $data = []) {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $wrapper = $this->_create_base_structure($dom, $action_name . 'Response');

        $resp_datos = $dom->createElementNS(self::NS_SALIDA, 'p148:RESPUESTA_DATOS_CENTRO');
        $resp_datos->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:p465', self::NS_ENTSAL);
        
        $this->_append_status_block($dom, $resp_datos, $codigo_retorno);

        if ($codigo_retorno == 0 && !empty($data)) {
            $nodo_datos = $dom->createElementNS(self::NS_ENTSAL, 'p465:DATOS_IDENTIFICATIVOS');
            $this->array_to_xml($data, $nodo_datos, $dom);
            $resp_datos->appendChild($nodo_datos);
        }

        $wrapper->appendChild($resp_datos);
        return $dom->saveXML();
    }

    public function generate_response_accion($action_name, $codigo_retorno, $data = []) {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $wrapper = $this->_create_base_structure($dom, $action_name . 'Response');

        $resp_obt = $dom->createElementNS(self::NS_SALIDA, 'p148:RESPUESTA_OBT_ACCION');
        $resp_obt->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:p465', self::NS_ENTSAL);

        $this->_append_status_block($dom, $resp_obt, $codigo_retorno);

        $nodo_acc = $dom->createElementNS(self::NS_ENTSAL, 'p465:ACCION_FORMATIVA');
        if ($codigo_retorno == 0 && !empty($data)) {
            $this->array_to_xml($data, $nodo_acc, $dom);
        } else {
            // CORRECCIÓN: Usar setAttribute (sin NS) para evitar la repetición de xmlns:xsi
            $nodo_acc->setAttribute('xsi:nil', 'true');
        }
        $resp_obt->appendChild($nodo_acc);

        $wrapper->appendChild($resp_obt);
        return $dom->saveXML();
    }

    public function generate_response_eliminar($codigo_retorno) {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $wrapper = $this->_create_base_structure($dom, 'eliminarAccionResponse');

        $resp_el = $dom->createElementNS(self::NS_SALIDA, 'p148:RESPUESTA_ELIMINAR_ACCION');
        $this->_append_status_block($dom, $resp_el, $codigo_retorno);
        $wrapper->appendChild($resp_el);
        return $dom->saveXML();
    }

    public function generate_response_lista($codigo_retorno, $lista = []) {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $wrapper = $this->_create_base_structure($dom, 'obtenerListaAccionesResponse');

        $resp_lista = $dom->createElementNS(self::NS_SALIDA, 'p148:RESPUESTA_OBT_LISTA_ACCIONES');
        $resp_lista->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:p465', self::NS_ENTSAL);

        $this->_append_status_block($dom, $resp_lista, $codigo_retorno);

        if ($codigo_retorno == 0 && !empty($lista)) {
            foreach ($lista as $item) {
                $nodo = $dom->createElementNS(self::NS_ENTSAL, 'p465:ID_ACCION');
                $this->array_to_xml($item, $nodo, $dom);
                $resp_lista->appendChild($nodo);
            }
        } else {
            // CORRECCIÓN: Usar setAttribute (sin NS) para lista vacía limpia
            $nodo = $dom->createElementNS(self::NS_ENTSAL, 'p465:ID_ACCION');
            $nodo->setAttribute('xsi:nil', 'true'); 
            $resp_lista->appendChild($nodo);
        }
        
        $wrapper->appendChild($resp_lista);
        return $dom->saveXML();
    }
    
    public function generate_fault($message, $code = 'Server') {
        return '<?xml version="1.0"?><soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/"><soapenv:Body><soapenv:Fault><faultcode>'.$code.'</faultcode><faultstring>'.htmlspecialchars($message).'</faultstring></soapenv:Fault></soapenv:Body></soapenv:Envelope>';
    }

    // --- HELPERS PRIVADOS ---

    private function _create_base_structure($dom, $node_name) {
        $envelope = $dom->createElementNS(self::NS_SOAP, 'soapenv:Envelope');
        $envelope->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:soapenv', self::NS_SOAP);
        $envelope->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:soapenc', 'http://schemas.xmlsoap.org/soap/encoding/');
        $envelope->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:xsd', 'http://www.w3.org/2001/XMLSchema');
        $envelope->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:xsi', self::NS_XSI);
        
        // Declaramos namespaces globales adicionales en el Envelope para limpiar el body
        $envelope->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:p867', self::NS_IMPL);
        $envelope->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:p148', self::NS_SALIDA);
        $envelope->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:p465', self::NS_ENTSAL);
        
        $dom->appendChild($envelope);
        $body = $dom->createElementNS(self::NS_SOAP, 'soapenv:Body');
        $envelope->appendChild($body);
        
        $main = $dom->createElementNS(self::NS_IMPL, 'p867:' . $node_name);
        $body->appendChild($main);
        return $main;
    }

    private function _append_status_block($dom, $parent, $code) {
        $parent->appendChild($dom->createElement('CODIGO_RETORNO', $code));
        $err = $dom->createElement('ETIQUETA_ERROR');
        
        // CORRECCIÓN: Usar setAttribute (sin NS) para evitar redundancia
        // Como 'xmlns:xsi' ya está en el Envelope, esto generará 'xsi:nil="true"' limpio.
        $err->setAttribute('xsi:nil', 'true');
        
        $parent->appendChild($err);
    }

    private function xml_to_array(DOMNode $node) {
        $result = [];
        if ($node->nodeType === XML_TEXT_NODE) return $node->nodeValue;
        if ($node->hasChildNodes()) {
            foreach ($node->childNodes as $child) {
                $name = $child->localName;
                if (!$name) continue;
                $childData = $this->xml_to_array($child);
                if (is_array($childData) && empty($childData) && trim($child->nodeValue) !== '') {
                     $childData = trim($child->nodeValue);
                }
                if (isset($result[$name])) {
                    if (!is_array($result[$name]) || !isset($result[$name][0])) $result[$name] = [$result[$name]];
                    $result[$name][] = $childData;
                } else {
                    $result[$name] = $childData;
                }
            }
        }
        if (empty($result) && $node->nodeValue !== '') return $node->nodeValue;
        return $result;
    }

    private function array_to_xml($data, DOMElement $parent, DOMDocument $dom) {
        if (!is_array($data)) {
            $parent->nodeValue = htmlspecialchars((string)$data);
            return;
        }
        foreach ($data as $key => $val) {
            if (is_numeric($key)) continue;
            
            if (is_array($val) && isset($val[0])) {
                foreach ($val as $item) {
                    $sub = $dom->createElement($key);
                    $this->array_to_xml($item, $sub, $dom);
                    $parent->appendChild($sub);
                }
            } else {
                if ($val === null) continue; 
                
                $sub = $dom->createElement($key);
                $this->array_to_xml($val, $sub, $dom);
                $parent->appendChild($sub);
            }
        }
    }
}