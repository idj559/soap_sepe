<?php
require_once(__DIR__ . '/../../../config.php');
require_login();

$context = context_system::instance();
require_capability('local/soap_sepe:manage', $context);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/soap_sepe/accion/index.php'));
$PAGE->set_title(get_string('manageactions', 'local_soap_sepe'));
$PAGE->set_heading(get_string('manageactions', 'local_soap_sepe'));

echo $OUTPUT->header();

$addurl = new moodle_url('/local/soap_sepe/accion/edit.php');
echo $OUTPUT->single_button($addurl, get_string('addaction', 'local_soap_sepe'));

$records = $DB->get_records('sepeservice_accion_formativa', null, 'id DESC');

if (!$records) {
    echo $OUTPUT->notification(get_string('noactions', 'local_soap_sepe'), 'notifymessage');
    echo $OUTPUT->footer();
    exit;
}

$table = new html_table();
$table->head = [
    get_string('ORIGEN_ACCION', 'local_soap_sepe'),
    get_string('CODIGO_ACCION', 'local_soap_sepe'),
    get_string('DENOMINACION_ACCION', 'local_soap_sepe'),
    get_string('FECHA_INICIO', 'local_soap_sepe'),
    get_string('FECHA_FIN', 'local_soap_sepe'),
    ''
];

foreach ($records as $r) {
    $view = html_writer::link(
        new moodle_url('/local/soap_sepe/accion/view.php', ['id'=>$r->id]),
        get_string('view')
    );
    $edit = html_writer::link(
        new moodle_url('/local/soap_sepe/accion/edit.php', ['id' => $r->id]),
        get_string('edit')
    );
    $del = html_writer::link(
        new moodle_url('/local/soap_sepe/accion/delete.php', ['id' => $r->id, 'sesskey' => sesskey()]),
        get_string('delete')
    );

    $table->data[] = [
        s($r->origen_accion),
        s($r->codigo_accion),
        s($r->denominacion_accion),
        s($r->fecha_inicio),
        s($r->fecha_fin),
        $view . ' | ' . $edit . ' | ' . $del,
    ];
}

echo html_writer::table($table);
echo $OUTPUT->footer();
