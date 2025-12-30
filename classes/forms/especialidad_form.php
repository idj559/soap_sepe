<?php
namespace local_soap_sepe\forms;
defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir . '/formslib.php');

class especialidad_form extends \moodleform {
    public function definition() {
        $m = $this->_form;

        $m->addElement('hidden', 'id');        $m->setType('id', PARAM_INT);
        $m->addElement('hidden', 'accionid');  $m->setType('accionid', PARAM_INT);

        // Datos básicos
        $m->addElement('text', 'origen_especialidad', get_string('ORIGEN_ESPECIALIDAD','local_soap_sepe'));
        $m->setType('origen_especialidad', PARAM_ALPHANUMEXT);  $m->addRule('origen_especialidad', null, 'required');

        $m->addElement('text', 'area_profesional', get_string('AREA_PROFESIONAL','local_soap_sepe'));
        $m->setType('area_profesional', PARAM_ALPHANUMEXT);     $m->addRule('area_profesional', null, 'required');

        $m->addElement('text', 'codigo_especialidad', get_string('CODIGO_ESPECIALIDAD','local_soap_sepe'));
        $m->setType('codigo_especialidad', PARAM_ALPHANUMEXT);  $m->addRule('codigo_especialidad', null, 'required');

        // Centro (ID_CENTRO) fijo a 1 (único centro)
        $m->addElement('hidden', 'id_centro'); $m->setType('id_centro', PARAM_INT); $m->setDefault('id_centro', 1);

        // Fechas y modalidad
        $m->addElement('text', 'fecha_inicio', get_string('FECHA_INICIO','local_soap_sepe'));
        $m->setType('fecha_inicio', PARAM_RAW_TRIMMED);  $m->addRule('fecha_inicio', null, 'required');

        $m->addElement('text', 'fecha_fin', get_string('FECHA_FIN','local_soap_sepe'));
        $m->setType('fecha_fin', PARAM_RAW_TRIMMED);     $m->addRule('fecha_fin', null, 'required');

        $m->addElement('text', 'modalidad_imparticion', get_string('MODALIDAD_IMPARTICION','local_soap_sepe'));
        $m->setType('modalidad_imparticion', PARAM_ALPHANUMEXT); $m->addRule('modalidad_imparticion', null, 'required');

        // Duración por tipo
        $m->addElement('text', 'horas_presencial', get_string('HORAS_PRESENCIAL','local_soap_sepe'));
        $m->setType('horas_presencial', PARAM_INT);      $m->addRule('horas_presencial', null, 'required');

        $m->addElement('text', 'horas_teleformacion', get_string('HORAS_TELEFORMACION','local_soap_sepe'));
        $m->setType('horas_teleformacion', PARAM_INT);   $m->addRule('horas_teleformacion', null, 'required');

        // FK opcionales (pueden ir como selects si quieres poblarlos desde sus tablas)
        $m->addElement('text', 'id_centro_presencial', get_string('ID_CENTRO_PRESENCIAL','local_soap_sepe'));
        $m->setType('id_centro_presencial', PARAM_INT);

        $m->addElement('text', 'id_tutor_formador', get_string('ID_TUTOR_FORMADOR','local_soap_sepe'));
        $m->setType('id_tutor_formador', PARAM_INT);

        $this->add_action_buttons();
    }
}
