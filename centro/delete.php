<?php
require_once(__DIR__ . '/../../../config.php');
require_login();
require_sesskey();

$context = context_system::instance();
// require_capability('local/soap_sepe:manage', $context);

$id = required_param('id', PARAM_INT);

// Nombre real de la tabla:
$record = $DB->get_record('sepeservice_centro', ['id' => $id], '*', MUST_EXIST);

$confirm = optional_param('confirm', 0, PARAM_BOOL);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/soap_sepe/centro/delete.php', ['id' => $id]));
$PAGE->set_title(get_string('deletecenter', 'local_soap_sepe'));
$PAGE->set_heading(get_string('managecenters', 'local_soap_sepe'));

if ($confirm) {
    $DB->delete_records('sepeservice_centro', ['id' => $id]);
    redirect(new moodle_url('/local/soap_sepe/centro/index.php'), get_string('deleted', 'local_soap_sepe'));
}

echo $OUTPUT->header();
echo $OUTPUT->confirm(
    get_string('confirmdelete', 'local_soap_sepe') . '<br><strong>' . s($record->nombre_centro) . '</strong>',
    new moodle_url('/local/soap_sepe/centro/delete.php', ['id' => $id, 'confirm' => 1, 'sesskey' => sesskey()]),
    new moodle_url('/local/soap_sepe/centro/index.php')
);
echo $OUTPUT->footer();
