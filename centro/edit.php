<?php
require_once(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot . '/local/soap_sepe/classes/forms/centro_form.php');

require_login();
$context = context_system::instance();
// Si ya tienes la capability instalada, descomenta:
// require_capability('local/soap_sepe:manage', $context);

$id = optional_param('id', 0, PARAM_INT);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/soap_sepe/centro/edit.php', ['id' => $id]));
$PAGE->set_title(get_string($id ? 'editcenter' : 'addcenter', 'local_soap_sepe'));
$PAGE->set_heading(get_string('managecenters', 'local_soap_sepe'));

if ($id) {
    // Usa el nombre real de tabla (si no renombraste): sepeservice_centro
    $record = $DB->get_record('sepeservice_centro', ['id' => $id], '*', MUST_EXIST);
} else {
    $record = (object)[
        'id' => 0,
        'origen_centro' => '',
        'codigo_centro' => '',
        'nombre_centro' => '',
        'url_plataforma' => '',
        'url_seguimiento' => '',
        'telefono' => '',
        'email' => ''
    ];
}

$mform = new local_soap_sepe_centro_form(null, []);
$mform->set_data($record);

if ($mform->is_cancelled()) {
    redirect(new moodle_url('/local/soap_sepe/centro/index.php'));
} else if ($data = $mform->get_data()) {
    if ($data->id) {
        $DB->update_record('sepeservice_centro', $data);
    } else {
        $data->fecha_creacion = time(); // si tienes ese campo
        $DB->insert_record('sepeservice_centro', $data);
    }
    redirect(new moodle_url('/local/soap_sepe/centro/index.php'), get_string('saved', 'local_soap_sepe'));
}

echo $OUTPUT->header();
$mform->display();
echo $OUTPUT->footer();
