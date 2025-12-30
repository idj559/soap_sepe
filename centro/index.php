<?php
require_once(__DIR__ . '/../../../config.php');
require_login();

$context = context_system::instance();
require_capability('moodle/site:config', $context);

// Si da 500 por capability no instalada, comenta temporalmente esta línea:
//require_capability('local/soap_sepe:manage', $context);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/soap_sepe/centro/index.php'));
$PAGE->set_title(get_string('managecenters', 'local_soap_sepe'));
$PAGE->set_heading(get_string('managecenters', 'local_soap_sepe'));

echo $OUTPUT->header();

$addurl = new moodle_url('/local/soap_sepe/centro/edit.php');
echo $OUTPUT->single_button($addurl, get_string('addcenter', 'local_soap_sepe'));

$tablename = 'sepeservice_centro';

$centros = $DB->get_records($tablename, null, 'id ASC');

$table = new html_table();
$table->head = [
    get_string('ORIGEN_CENTRO', 'local_soap_sepe'),
    get_string('CODIGO_CENTRO', 'local_soap_sepe'),
    get_string('NOMBRE_CENTRO', 'local_soap_sepe'),
    get_string('URL_PLATAFORMA', 'local_soap_sepe'),
    get_string('URL_SEGUIMIENTO', 'local_soap_sepe'),
    get_string('TELEFONO', 'local_soap_sepe'),
    get_string('EMAIL', 'local_soap_sepe'),
    ''
];

foreach ($centros as $c) {
    // OJO: propiedades en minúscula
    $urlplataforma = !empty($c->url_plataforma)
        ? html_writer::link(new moodle_url($c->url_plataforma), s($c->url_plataforma))
        : '';
    $urlseguimiento = !empty($c->url_seguimiento)
        ? html_writer::link(new moodle_url($c->url_seguimiento), s($c->url_seguimiento))
        : '';

    $edit = html_writer::link(
        new moodle_url('/local/soap_sepe/centro/edit.php', ['id' => $c->id]),
        get_string('edit')
    );
    $del = html_writer::link(
        new moodle_url('/local/soap_sepe/centro/delete.php', ['id' => $c->id, 'sesskey' => sesskey()]),
        get_string('delete')
    );

    $table->data[] = [
        s($c->origen_centro ?? ''),
        s($c->codigo_centro ?? ''),
        s($c->nombre_centro ?? ''),
        $urlplataforma,
        $urlseguimiento,
        s($c->telefono ?? ''),
        s($c->email ?? ''),
        $edit . ' | ' . $del
    ];
}


echo html_writer::table($table);
echo $OUTPUT->footer();
