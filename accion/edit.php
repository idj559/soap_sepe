<?php
require_once(__DIR__.'/../../../config.php');
require_login();
$context = context_system::instance();
require_capability('local/soap_sepe:manage', $context);

$id = optional_param('id', 0, PARAM_INT);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/soap_sepe/accion/edit.php', ['id'=>$id]));
$PAGE->set_title(get_string('editaction', 'local_soap_sepe'));
$PAGE->set_heading(get_string('editaction', 'local_soap_sepe'));

// Carga registro
$record = $id ? $DB->get_record('sepeservice_accion_formativa', ['id'=>$id], '*', MUST_EXIST) : null;

// Form
require_once($CFG->dirroot.'/local/soap_sepe/classes/forms/accion_form.php');
$mform = new \local_soap_sepe\forms\accion_form();

// Procesa
if ($mform->is_cancelled()) {
    redirect(new moodle_url('/local/soap_sepe/accion/index.php'));
} else if ($data = $mform->get_data()) {

    // Mapea MINÚSCULAS (form) ➜ MAYÚSCULAS (BD)
    $rec = (object)[
        'id'                       => $data->id ?: null,
        'ORIGEN_ACCION'            => $data->origen_accion,
        'CODIGO_ACCION'            => $data->codigo_accion,
        'DENOMINACION_ACCION'      => $data->denominacion_accion,
        'SITUACION'                => $data->situacion,
        'ORIGEN_ESPECIALIDAD'      => $data->origen_especialidad,
        'AREA_PROFESIONAL'         => $data->area_profesional,
        'CODIGO_ESPECIALIDAD'      => $data->codigo_especialidad,
        'DURACION'                 => $data->duracion,
        'FECHA_INICIO'             => $data->fecha_inicio,
        'FECHA_FIN'                => $data->fecha_fin,
        'IND_ITINERARIO_COMPLETO'  => $data->ind_itinerario_completo,
        'TIPO_FINANCIACION'        => $data->tipo_financiacion,
        'NUMERO_ASISTENTES'        => $data->numero_asistentes,
        'INFORMACION_GENERAL'      => $data->informacion_general,
        'HORARIOS'                 => $data->horarios,
        'REQUISITOS'               => $data->requisitos,
        'CONTACTO_ACCION'          => $data->contacto_accion,
        'ID_CENTRO'                => $data->id_centro ?: 1,
    ];

    if ($rec->id) {
        $DB->update_record('sepeservice_accion_formativa', $rec);
        redirect(new moodle_url('/local/soap_sepe/accion/index.php'), get_string('updated', 'local_soap_sepe'));
    } else {
        if ($DB->record_exists('sepeservice_accion_formativa', ['CODIGO_ACCION'=>$rec->CODIGO_ACCION])) {
            print_error('actionexists', 'local_soap_sepe');
        }
        $DB->insert_record('sepeservice_accion_formativa', $rec);
        redirect(new moodle_url('/local/soap_sepe/accion/index.php'), get_string('created', 'local_soap_sepe'));
    }
}

// Pintado
echo $OUTPUT->header();
if ($record) {
    // El $record del DML trae minúsculas: set_data funciona directo
    $mform->set_data($record);
} else {
    $mform->set_data((object)['id_centro'=>1]);
}
$mform->display();
echo $OUTPUT->footer();
