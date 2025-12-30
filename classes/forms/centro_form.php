<?php
defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

class local_soap_sepe_centro_form extends moodleform {
    public function definition() {
        $mform = $this->_form;

        // Campos (usa minúsculas: así los devuelve $DB).
        $mform->addElement('text', 'origen_centro', get_string('ORIGEN_CENTRO','local_soap_sepe'));
        $mform->setType('origen_centro', PARAM_ALPHANUMEXT);
        $mform->addRule('origen_centro', null, 'required', null, 'client');
        $mform->addRule('origen_centro', null, 'maxlength', 2, 'client');

        $mform->addElement('text', 'codigo_centro', get_string('CODIGO_CENTRO','local_soap_sepe'));
        $mform->setType('codigo_centro', PARAM_ALPHANUMEXT);
        $mform->addRule('codigo_centro', null, 'required', null, 'client');

        $mform->addElement('text', 'nombre_centro', get_string('NOMBRE_CENTRO','local_soap_sepe'));
        $mform->setType('nombre_centro', PARAM_TEXT);
        $mform->addRule('nombre_centro', null, 'required', null, 'client');
        $mform->addRule('nombre_centro', null, 'maxlength', 40, 'client');

        $mform->addElement('url', 'url_plataforma', get_string('URL_PLATAFORMA','local_soap_sepe'));
        $mform->setType('url_plataforma', PARAM_URL);
        $mform->addRule('url_plataforma', null, 'required', null, 'client');

        $mform->addElement('url', 'url_seguimiento', get_string('URL_SEGUIMIENTO','local_soap_sepe'));
        $mform->setType('url_seguimiento', PARAM_URL);
        $mform->addRule('url_seguimiento', null, 'required', null, 'client');

        $mform->addElement('text', 'telefono', get_string('TELEFONO','local_soap_sepe'));
        $mform->setType('telefono', PARAM_TEXT);
        $mform->addRule('telefono', null, 'required', null, 'client');

        $mform->addElement('text', 'email', get_string('EMAIL','local_soap_sepe'));
        $mform->setType('email', PARAM_EMAIL);
        $mform->addRule('email', null, 'required', null, 'client');

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $this->add_action_buttons();
    }

    // Validaciones extra del lado servidor (opcional pero útil).
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        if (!empty($data['origen_centro']) && core_text::strlen($data['origen_centro']) !== 2) {
            $errors['origen_centro'] = get_string('error');
        }
        if (!empty($data['nombre_centro']) && core_text::strlen($data['nombre_centro']) > 40) {
            $errors['nombre_centro'] = get_string('error');
        }
        return $errors;
    }
}
