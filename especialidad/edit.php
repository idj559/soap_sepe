<?php
require_once(__DIR__.'/../../../config.php');
require_login();
$context = context_system::instance();
require_capability('local/soap_sepe:manage', $context);

$accionid = required_param('accionid', PARAM_INT);
$id       = optional_param('id', 0, PARAM_INT);

$accion = $DB->get_record('sepeservice_accion_formativa', ['id' => $accionid], '*', MUST_EXIST);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/soap_sepe/especialidad/edit.php', ['accionid' => $accionid, 'id' => $id]));
$PAGE->set_title(get_string('addespecialidad', 'local_soap_sepe'));
$PAGE->set_heading(get_string('especialidades', 'local_soap_sepe'));

require_once($CFG->dirroot.'/local/soap_sepe/classes/forms/especialidad_form.php');
$mform = new \local_soap_sepe\forms\especialidad_form();

$back = new moodle_url('/local/soap_sepe/especialidad/index.php', ['accionid' => $accionid]);

if ($mform->is_cancelled()) {
    redirect($back);
} else if ($data = $mform->get_data()) {
    $rec = (object)[
        'id'                   => $data->id ?: null,
        'ORIGEN_ESPECIALIDAD'  => $data->origen_especialidad,
        'AREA_PROFESIONAL'     => $data->area_profesional,
        'CODIGO_ESPECIALIDAD'  => $data->codigo_especialidad,
        'ID_CENTRO'            => $data->id_centro ?: 1,
        'FECHA_INICIO'         => $data->fecha_inicio,
        'FECHA_FIN'            => $data->fecha_fin,
        'MODALIDAD_IMPARTICION'=> $data->modalidad_imparticion,
        'HORAS_PRESENCIAL'     => (int)$data->horas_presencial,
        'HORAS_TELEFORMACION'  => (int)$data->horas_teleformacion,
        'ID_CENTRO_PRESENCIAL' => $data->id_centro_presencial ?: null,
        'ID_TUTOR_FORMADOR'    => $data->id_tutor_formador ?: null,
        // No usamos ID_ACCION_FORMATIVA aquí; el vínculo va en la tabla puente
        'ID_USO'               => null
    ];

    if ($rec->id) {
        // Validar que la especialidad pertenece (vinculada) a esta acción mediante la puente
        $belongs = $DB->record_exists('sepeservice_accion_especialidad', [
            'ID_ACCION_FORMATIVA' => $accionid,
            'ID_ESPECIALIDAD'     => $rec->id
        ]);
        if (!$belongs) {
            print_error('nopermissions', 'error');
        }

        $DB->update_record('sepeservice_especialidad', $rec);
        redirect($back, get_string('updated','local_soap_sepe'));
    } else {
        $newid = $DB->insert_record('sepeservice_especialidad', $rec);

        // Enlazar en la tabla puente (evita duplicado)
        if (!$DB->record_exists('sepeservice_accion_especialidad', [
            'ID_ACCION_FORMATIVA' => $accionid,
            'ID_ESPECIALIDAD'     => $newid
        ])) {
            $DB->insert_record('sepeservice_accion_especialidad', (object)[
                'ID_ACCION_FORMATIVA' => $accionid,
                'ID_ESPECIALIDAD'     => $newid
            ]);
        }
        redirect($back, get_string('created','local_soap_sepe'));
    }
}

echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($accion->denominacion_accion), 3);

if ($id) {
    // Cargamos SOLO si está vinculada a esta acción
    $sql = "SELECT e.*
              FROM {sepeservice_accion_especialidad} ae
              JOIN {sepeservice_especialidad} e ON e.id = ae.ID_ESPECIALIDAD
             WHERE ae.ID_ACCION_FORMATIVA = :accionid AND e.id = :id";
    $row = $DB->get_record_sql($sql, ['accionid' => $accionid, 'id' => $id], MUST_EXIST);
    $row->accionid = $accionid;
    $mform->set_data($row);
} else {
    $mform->set_data((object)['accionid' => $accionid, 'id_centro' => 1]);
}

$mform->display();
echo $OUTPUT->footer();
