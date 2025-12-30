<?php
require_once(__DIR__.'/../../../config.php');
require_login();
$context = context_system::instance();
require_capability('local/soap_sepe:manage', $context);

$accionid = required_param('accionid', PARAM_INT);
$accion = $DB->get_record('sepeservice_accion_formativa', ['id'=>$accionid], '*', MUST_EXIST);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/soap_sepe/especialidad/index.php', ['accionid'=>$accionid]));
$PAGE->set_title(get_string('especialidades', 'local_soap_sepe'));
$PAGE->set_heading(get_string('especialidades', 'local_soap_sepe'));

echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($accion->denominacion_accion), 3);

// Botón añadir
$addurl = new moodle_url('/local/soap_sepe/especialidad/edit.php', ['accionid'=>$accionid]);
echo $OUTPUT->single_button($addurl, get_string('addespecialidad', 'local_soap_sepe'));

// --- DEPURACIÓN: contadores y ids vinculados ---
$totalE = $DB->count_records('sepeservice_especialidad');
$totalV = $DB->count_records('sepeservice_accion_especialidad', ['ID_ACCION_FORMATIVA'=>$accionid]);

$idsvinc = $DB->get_fieldset_select('sepeservice_accion_especialidad', 'ID_ESPECIALIDAD', 'ID_ACCION_FORMATIVA = :a', ['a'=>$accionid]);
$txtids  = $idsvinc ? implode(',', $idsvinc) : '(sin vínculos)';

echo $OUTPUT->notification("Depuración → Especialidades totales: $totalE | Vínculos de acción $accionid: $totalV | IDs vinculados: $txtids", 'notifymessage');

// --- LISTADO mediante tabla puente ---
$sql = "
  SELECT e.*
    FROM {sepeservice_accion_especialidad} ae
    JOIN {sepeservice_especialidad} e
      ON e.id = ae.ID_ESPECIALIDAD
   WHERE ae.ID_ACCION_FORMATIVA = :accionid
ORDER BY e.id DESC";
$rows = $DB->get_records_sql($sql, ['accionid'=>$accionid]);

// Fallback: si no hay vínculos, intenta por la FK directa (compatibilidad)
if (!$rows) {
    $rows = $DB->get_records('sepeservice_especialidad', ['ID_ACCION_FORMATIVA'=>$accionid], 'id DESC');
    if ($rows) {
        echo $OUTPUT->notification('Mostrando por ID_ACCION_FORMATIVA (tabla puente vacía).', 'warning');
    }
}

if (!$rows) {
    echo $OUTPUT->notification(get_string('noespecialidades', 'local_soap_sepe'), 'notifymessage');
    echo $OUTPUT->footer(); exit;
}

$table = new html_table();
$table->head = [
    get_string('ORIGEN_ESPECIALIDAD','local_soap_sepe'),
    get_string('AREA_PROFESIONAL','local_soap_sepe'),
    get_string('CODIGO_ESPECIALIDAD','local_soap_sepe'),
    get_string('MODALIDAD_IMPARTICION','local_soap_sepe'),
    get_string('FECHA_INICIO','local_soap_sepe'),
    get_string('FECHA_FIN','local_soap_sepe'),
    ''
];

foreach ($rows as $r) {
    // Moodle devuelve propiedades en minúsculas:
    $origen  = isset($r->origen_especialidad)   ? $r->origen_especialidad   : (isset($r->ORIGEN_ESPECIALIDAD)  ? $r->ORIGEN_ESPECIALIDAD  : '');
    $area    = isset($r->area_profesional)      ? $r->area_profesional      : (isset($r->AREA_PROFESIONAL)     ? $r->AREA_PROFESIONAL     : '');
    $codigo  = isset($r->codigo_especialidad)   ? $r->codigo_especialidad   : (isset($r->CODIGO_ESPECIALIDAD)  ? $r->CODIGO_ESPECIALIDAD  : '');
    $modali  = isset($r->modalidad_imparticion) ? $r->modalidad_imparticion : (isset($r->MODALIDAD_IMPARTICION)? $r->MODALIDAD_IMPARTICION: '');
    $finicio = isset($r->fecha_inicio)          ? $r->fecha_inicio          : (isset($r->FECHA_INICIO)         ? $r->FECHA_INICIO         : '');
    $ffin    = isset($r->fecha_fin)             ? $r->fecha_fin             : (isset($r->FECHA_FIN)            ? $r->FECHA_FIN            : '');

    $edit = html_writer::link(
        new moodle_url('/local/soap_sepe/especialidad/edit.php', ['accionid'=>$accionid, 'id'=>$r->id]),
        get_string('edit')
    );
    $del  = html_writer::link(
        new moodle_url('/local/soap_sepe/especialidad/delete.php', ['accionid'=>$accionid, 'id'=>$r->id, 'sesskey'=>sesskey()]),
        get_string('delete')
    );

    $table->data[] = [
        s($origen), s($area), s($codigo), s($modali), s($finicio), s($ffin),
        $edit.' | '.$del
    ];
}

echo html_writer::table($table);
echo $OUTPUT->footer();
