<?php
namespace local_soap_sepe\forms;
defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

class accion_form extends \moodleform {
    public function definition() {
        $m = $this->_form;

        // ⬇️ Todos en minúscula (coinciden con $record del DML).
        $m->addElement('text', 'origen_accion', get_string('ORIGEN_ACCION', 'local_soap_sepe'));
        $m->setType('origen_accion', PARAM_ALPHANUMEXT);
        $m->addRule('origen_accion', null, 'required', null, 'server');

        $m->addElement('text', 'codigo_accion', get_string('CODIGO_ACCION', 'local_soap_sepe'));
        $m->setType('codigo_accion', PARAM_ALPHANUMEXT);
        $m->addRule('codigo_accion', null, 'required', null, 'server');

        $m->addElement('text', 'denominacion_accion', get_string('DENOMINACION_ACCION', 'local_soap_sepe'));
        $m->setType('denominacion_accion', PARAM_TEXT);
        $m->addRule('denominacion_accion', null, 'required', null, 'server');

        $m->addElement('text', 'situacion', get_string('SITUACION', 'local_soap_sepe'));
        $m->setType('situacion', PARAM_ALPHANUMEXT);
        $m->addRule('situacion', null, 'required', null, 'server');

        $m->addElement('text', 'origen_especialidad', get_string('ORIGEN_ESPECIALIDAD', 'local_soap_sepe'));
        $m->setType('origen_especialidad', PARAM_ALPHANUMEXT);
        $m->addRule('origen_especialidad', null, 'required', null, 'server');

        $m->addElement('text', 'area_profesional', get_string('AREA_PROFESIONAL', 'local_soap_sepe'));
        $m->setType('area_profesional', PARAM_ALPHANUMEXT);
        $m->addRule('area_profesional', null, 'required', null, 'server');

        $m->addElement('text', 'codigo_especialidad', get_string('CODIGO_ESPECIALIDAD', 'local_soap_sepe'));
        $m->setType('codigo_especialidad', PARAM_ALPHANUMEXT);
        $m->addRule('codigo_especialidad', null, 'required', null, 'server');

        $m->addElement('text', 'duracion', get_string('DURACION', 'local_soap_sepe'));
        $m->setType('duracion', PARAM_INT);
        $m->addRule('duracion', null, 'required', null, 'server');

        $m->addElement('text', 'fecha_inicio', get_string('FECHA_INICIO', 'local_soap_sepe'));
        $m->setType('fecha_inicio', PARAM_RAW_TRIMMED);
        $m->addHelpButton('fecha_inicio', 'date_ddmmyyyy', 'local_soap_sepe');
        $m->addRule('fecha_inicio', null, 'required', null, 'server');

        $m->addElement('text', 'fecha_fin', get_string('FECHA_FIN', 'local_soap_sepe'));
        $m->setType('fecha_fin', PARAM_RAW_TRIMMED);
        $m->addHelpButton('fecha_fin', 'date_ddmmyyyy', 'local_soap_sepe');
        $m->addRule('fecha_fin', null, 'required', null, 'server');

        $m->addElement('select', 'ind_itinerario_completo', get_string('IND_ITINERARIO_COMPLETO', 'local_soap_sepe'), [
            'SI' => get_string('yes'),
            'NO' => get_string('no')
        ]);
        $m->addRule('ind_itinerario_completo', null, 'required', null, 'server');

        $m->addElement('text', 'tipo_financiacion', get_string('TIPO_FINANCIACION', 'local_soap_sepe'));
        $m->setType('tipo_financiacion', PARAM_ALPHANUMEXT);
        $m->addRule('tipo_financiacion', null, 'required', null, 'server');

        $m->addElement('text', 'numero_asistentes', get_string('NUMERO_ASISTENTES', 'local_soap_sepe'));
        $m->setType('numero_asistentes', PARAM_INT);
        $m->addRule('numero_asistentes', null, 'required', null, 'server');

        $m->addElement('textarea', 'informacion_general', get_string('INFORMACION_GENERAL', 'local_soap_sepe'), 'rows=3');
        $m->setType('informacion_general', PARAM_TEXT);

        $m->addElement('textarea', 'horarios', get_string('HORARIOS', 'local_soap_sepe'), 'rows=3');
        $m->setType('horarios', PARAM_TEXT);

        $m->addElement('textarea', 'requisitos', get_string('REQUISITOS', 'local_soap_sepe'), 'rows=3');
        $m->setType('requisitos', PARAM_TEXT);

        $m->addElement('text', 'contacto_accion', get_string('CONTACTO_ACCION', 'local_soap_sepe'));
        $m->setType('contacto_accion', PARAM_TEXT);

        // Fijos/ocultos
        $m->addElement('hidden', 'id');        $m->setType('id', PARAM_INT);
        $m->addElement('hidden', 'id_centro'); $m->setType('id_centro', PARAM_INT);
        $m->setDefault('id_centro', 1);

        $this->add_action_buttons();
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        $regex = '/^(0[1-9]|[12][0-9]|3[01])\/(0[1-9]|1[0-2])\/\d{4}$/';
        if (!preg_match($regex, $data['fecha_inicio'])) { $errors['fecha_inicio'] = get_string('err_date_format', 'local_soap_sepe'); }
        if (!preg_match($regex, $data['fecha_fin']))    { $errors['fecha_fin']    = get_string('err_date_format', 'local_soap_sepe'); }
        return $errors;
    }
}
