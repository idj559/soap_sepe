<?php
require_once(__DIR__.'/../../../config.php');
require_login();
$context = context_system::instance();
require_capability('local/soap_sepe:manage', $context);

$id = required_param('id', PARAM_INT);
$action = $DB->get_record('sepeservice_accion_formativa', ['id'=>$id], '*', MUST_EXIST);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/soap_sepe/accion/view.php', ['id'=>$id]));
$PAGE->set_title(format_string($action->denominacion_accion));
$PAGE->set_heading(get_string('manageactions', 'local_soap_sepe'));

echo $OUTPUT->header();

// Cabecera con datos básicos:
$dl = new html_table();
$dl->attributes['class'] = 'generaltable';
$dl->head = [get_string('field', 'admin'), get_string('value', 'admin')];
$dl->data = [
    ['ID', s($action->id)],
    [get_string('ORIGEN_ACCION','local_soap_sepe'), s($action->origen_accion)],
    [get_string('CODIGO_ACCION','local_soap_sepe'), s($action->codigo_accion)],
    [get_string('DENOMINACION_ACCION','local_soap_sepe'), s($action->denominacion_accion)],
    [get_string('FECHA_INICIO','local_soap_sepe'), s($action->fecha_inicio)],
    [get_string('FECHA_FIN','local_soap_sepe'), s($action->fecha_fin)],
];
echo html_writer::table($dl);

// Pestañas (solo Especialidades por ahora):
$tabs = [];
$base = new moodle_url('/local/soap_sepe/accion/view.php', ['id'=>$id]);
$tabs[] = new tabobject('especialidades', new moodle_url('/local/soap_sepe/especialidad/index.php', ['accionid'=>$id]),
    get_string('especialidades', 'local_soap_sepe'));
$tabs[] = new tabobject('editar', new moodle_url('/local/soap_sepe/accion/edit.php', ['id'=>$id]),
    get_string('edit'));
$tabs[] = new tabobject('volver', new moodle_url('/local/soap_sepe/accion/index.php'),
    get_string('back'));

print_tabs([$tabs], 'especialidades'); // marca pestaña activa por defecto

echo $OUTPUT->footer();
