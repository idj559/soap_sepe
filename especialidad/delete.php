<?php
require_once(__DIR__.'/../../../config.php');
require_login(); require_sesskey();
$context = context_system::instance();
require_capability('local/soap_sepe:manage', $context);

$accionid = required_param('accionid', PARAM_INT);
$id       = required_param('id', PARAM_INT);

$back = new moodle_url('/local/soap_sepe/especialidad/index.php', ['accionid' => $accionid]);

// Comprobar que el vínculo existe
if (!$DB->record_exists('sepeservice_accion_especialidad', [
    'ID_ACCION_FORMATIVA' => $accionid,
    'ID_ESPECIALIDAD'     => $id
])) {
    print_error('invalidrecord', 'error');
}

$confirm = optional_param('confirm', 0, PARAM_BOOL);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/soap_sepe/especialidad/delete.php', ['accionid' => $accionid, 'id' => $id]));
$PAGE->set_title(get_string('delete'));
$PAGE->set_heading(get_string('especialidades','local_soap_sepe'));

if (!$confirm) {
    $esp = $DB->get_record('sepeservice_especialidad', ['id' => $id], 'id,codigo_especialidad');
    echo $OUTPUT->header();
    echo $OUTPUT->confirm(
        get_string('confirmdelete', 'local_soap_sepe') . ' ' . s($esp ? $esp->codigo_especialidad : $id),
        new moodle_url('/local/soap_sepe/especialidad/delete.php', [
            'accionid' => $accionid, 'id' => $id, 'confirm' => 1, 'sesskey' => sesskey()
        ]),
        $back
    );
    echo $OUTPUT->footer();
    exit;
}

// Desvincular (no borrar especialidad)
$DB->delete_records('sepeservice_accion_especialidad', [
    'ID_ACCION_FORMATIVA' => $accionid,
    'ID_ESPECIALIDAD'     => $id
]);

redirect($back, get_string('deleted','local_soap_sepe'));
