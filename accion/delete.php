<?php
require_once(__DIR__ . '/../../../config.php');
require_login();

$context = context_system::instance();
require_capability('local/soap_sepe:manage', $context);

$id = required_param('id', PARAM_INT);
require_sesskey();

$record = $DB->get_record('sepeservice_accion_formativa', ['id' => $id], '*', MUST_EXIST);

// Confirmación estándar de Moodle.
$confirm = optional_param('confirm', 0, PARAM_INT);
$ref = new moodle_url('/local/soap_sepe/accion/index.php');

if (!$confirm) {
    echo $OUTPUT->header();
    $message = get_string('deleteconfirm_action', 'local_soap_sepe', s($record->denominacion_accion));
    echo $OUTPUT->confirm($message,
        new moodle_url('/local/soap_sepe/accion/delete.php', ['id' => $id, 'confirm' => 1, 'sesskey' => sesskey()]),
        $ref
    );
    echo $OUTPUT->footer();
    exit;
}

$DB->delete_records('sepeservice_accion_formativa', ['id' => $id]);
redirect($ref, get_string('deleted', 'local_soap_sepe'));
