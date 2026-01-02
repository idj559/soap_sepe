<?php
namespace local_soap_sepe;

defined('MOODLE_INTERNAL') || die();

/**
 * Clase encargada de la lógica de negocio y persistencia en BD.
 * Incluye procesamiento recursivo completo de Tutores, Centros Presenciales y Uso.
 */
class sepe_manager {

    // =========================================================================
    // GESTIÓN DE CENTRO
    // =========================================================================

    public function crear_centro($data) {
        global $DB;
        $record = new \stdClass();

        // Extraer datos de la sub-estructura ID_CENTRO si existe
        $id_centro_data = $data['ID_CENTRO'] ?? [];

        $record->origen_centro   = $id_centro_data['ORIGEN_CENTRO'] ?? $data['ORIGEN_CENTRO'] ?? '';
        $record->codigo_centro   = $id_centro_data['CODIGO_CENTRO'] ?? $data['CODIGO_CENTRO'] ?? '';
        $record->nombre_centro   = $data['NOMBRE_CENTRO'] ?? '';
        $record->url_plataforma  = $data['URL_PLATAFORMA'] ?? '';
        $record->url_seguimiento = $data['URL_SEGUIMIENTO'] ?? '';
        $record->telefono        = $data['TELEFONO'] ?? '';
        $record->email           = $data['EMAIL'] ?? '';
        $record->fecha_creacion  = time();

        if (empty($record->codigo_centro)) {
            error_log('SEPE crear_centro: CODIGO_CENTRO vacío.');
            throw new \Exception("Faltan datos obligatorios del centro (CODIGO_CENTRO).");
        }

        $existing = $DB->get_record('sepeservice_centro', ['codigo_centro' => $record->codigo_centro]);

        if ($existing) {
            $record->id = $existing->id;
            $DB->update_record('sepeservice_centro', $record);
            return $existing->id;
        } else {
            return $DB->insert_record('sepeservice_centro', $record);
        }
    }

    public function obtener_datos_centro() {
        global $DB;
        $record = $DB->get_record('sepeservice_centro', [], '*', IGNORE_MULTIPLE);
        if (!$record) return null;

        return [
            'ID_CENTRO' => [
                'ORIGEN_CENTRO' => $record->origen_centro,
                'CODIGO_CENTRO' => $record->codigo_centro
            ],
            'NOMBRE_CENTRO'   => $record->nombre_centro,
            'URL_PLATAFORMA'  => $record->url_plataforma,
            'URL_SEGUIMIENTO' => $record->url_seguimiento,
            'TELEFONO'        => $record->telefono,
            'EMAIL'           => $record->email
        ];
    }

    // =========================================================================
    // GESTIÓN DE ACCIONES (CREACIÓN)
    // =========================================================================

    public function crear_accion($data) {
        global $DB;
        $transaction = $DB->start_delegated_transaction();

        try {
            $centro = $DB->get_record('sepeservice_centro', [], 'id', IGNORE_MULTIPLE);
            if (!$centro) throw new \Exception('No hay centro configurado en la BD local.');
            $id_centro_local = $centro->id;

            $accion = new \stdClass();
            $id_data = $data['ID_ACCION'] ?? [];
            $accion->origen_accion           = $id_data['ORIGEN_ACCION'] ?? '';
            $accion->codigo_accion           = $id_data['CODIGO_ACCION'] ?? '';
            $accion->situacion               = $data['SITUACION'] ?? '';
            
            $esp_princ = $data['ID_ESPECIALIDAD_PRINCIPAL'] ?? [];
            $accion->origen_especialidad     = $esp_princ['ORIGEN_ESPECIALIDAD'] ?? '';
            $accion->area_profesional        = $esp_princ['AREA_PROFESIONAL'] ?? '';
            $accion->codigo_especialidad     = $esp_princ['CODIGO_ESPECIALIDAD'] ?? '';
            
            $accion->duracion                = (int)($data['DURACION'] ?? 0);
            $accion->fecha_inicio            = $data['FECHA_INICIO'] ?? '';
            $accion->fecha_fin               = $data['FECHA_FIN'] ?? '';
            $accion->ind_itinerario_completo = $data['IND_ITINERARIO_COMPLETO'] ?? 'NO';
            $accion->tipo_financiacion       = $data['TIPO_FINANCIACION'] ?? '';
            $accion->numero_asistentes       = (int)($data['NUMERO_ASISTENTES'] ?? 0);
            
            $desc = $data['DESCRIPCION_ACCION'] ?? [];
            $accion->denominacion_accion     = $desc['DENOMINACION_ACCION'] ?? '';
            $accion->informacion_general     = $desc['INFORMACION_GENERAL'] ?? '';
            $accion->horarios                = $desc['HORARIOS'] ?? '';
            $accion->requisitos              = $desc['REQUISITOS'] ?? '';
            $accion->contacto_accion         = $desc['CONTACTO_ACCION'] ?? '';
            
            $accion->id_centro               = $id_centro_local;
            $accion->fecha_actualizacion     = time();

            if (empty($accion->codigo_accion)) throw new \Exception("Falta CODIGO_ACCION.");

            // --- CAMBIO CLAVE AQUÍ ---
            $existing_accion = $DB->get_record('sepeservice_accion_formativa', ['codigo_accion' => $accion->codigo_accion]);
            
            if ($existing_accion) {
                // Para pasar el test "ya creada", DEBE fallar si existe.
                throw new \Exception("La acción formativa ya existe: " . $accion->codigo_accion);
            } else {
                $accion->fecha_creacion = time();
                $id_accion = $DB->insert_record('sepeservice_accion_formativa', $accion);
            }
            // --------------------------

            if (!empty($data['ESPECIALIDADES_ACCION']['ESPECIALIDAD'])) {
                $especialidades = $this->_normalize_array($data['ESPECIALIDADES_ACCION']['ESPECIALIDAD']);
                $this->_procesar_especialidades($id_accion, $id_centro_local, $especialidades);
            }

            if (!empty($data['PARTICIPANTES']['PARTICIPANTE'])) {
                $participantes = $this->_normalize_array($data['PARTICIPANTES']['PARTICIPANTE']);
                $this->_procesar_participantes($id_accion, $participantes);
            }

            $transaction->allow_commit();
            return true;

        } catch (\Exception $e) {
            $transaction->rollback($e);
            throw $e;
        }
    }

    // =========================================================================
    // GESTIÓN DE ACCIONES (RECUPERACIÓN - ORDEN ESTRICTO)
    // =========================================================================

    public function obtener_accion($id_accion_data) {
        global $DB;
        $codigo = $id_accion_data['CODIGO_ACCION'] ?? '';
        
        $acc = $DB->get_record('sepeservice_accion_formativa', ['codigo_accion' => $codigo]);
        if (!$acc) return null;

        $result = [];
        
        // 1-10 Campos principales
        $result['ID_ACCION'] = ['ORIGEN_ACCION' => $acc->origen_accion, 'CODIGO_ACCION' => $acc->codigo_accion];
        $result['SITUACION'] = $acc->situacion;
        $result['ID_ESPECIALIDAD_PRINCIPAL'] = ['ORIGEN_ESPECIALIDAD' => $acc->origen_especialidad, 'AREA_PROFESIONAL' => $acc->area_profesional, 'CODIGO_ESPECIALIDAD' => $acc->codigo_especialidad];
        $result['DURACION'] = $acc->duracion;
        $result['FECHA_INICIO'] = $acc->fecha_inicio;
        $result['FECHA_FIN'] = $acc->fecha_fin;
        $result['IND_ITINERARIO_COMPLETO'] = $acc->ind_itinerario_completo;
        $result['TIPO_FINANCIACION'] = $acc->tipo_financiacion;
        $result['NUMERO_ASISTENTES'] = $acc->numero_asistentes;
        $result['DESCRIPCION_ACCION'] = [
            'DENOMINACION_ACCION' => $acc->denominacion_accion,
            'INFORMACION_GENERAL' => $acc->informacion_general,
            'HORARIOS' => $acc->horarios,
            'REQUISITOS' => $acc->requisitos,
            'CONTACTO_ACCION' => $acc->contacto_accion
        ];

        // 11. ESPECIALIDADES_ACCION
        $links = $DB->get_records('sepeservice_accion_especialidad', ['id_accion_formativa' => $acc->id]);
        $esp_xml = [];
        
        foreach ($links as $l) {
            $esp = $DB->get_record('sepeservice_especialidad', ['id' => $l->id_especialidad]);
            if ($esp) {
                $datos_centro_local = $this->obtener_datos_centro();
                
                $esp_node = [];
                $esp_node['ID_ESPECIALIDAD'] = ['ORIGEN_ESPECIALIDAD' => $esp->origen_especialidad, 'AREA_PROFESIONAL' => $esp->area_profesional, 'CODIGO_ESPECIALIDAD' => $esp->codigo_especialidad];
                $esp_node['CENTRO_IMPARTICION'] = $datos_centro_local['ID_CENTRO'] ?? []; 
                $esp_node['FECHA_INICIO'] = $esp->fecha_inicio;
                $esp_node['FECHA_FIN'] = $esp->fecha_fin;
                $esp_node['MODALIDAD_IMPARTICION'] = $esp->modalidad_imparticion;
                $esp_node['DATOS_DURACION'] = ['HORAS_PRESENCIAL' => $esp->horas_presencial, 'HORAS_TELEFORMACION' => $esp->horas_teleformacion];
                
                // Centros Presenciales
                $cp_links = $DB->get_records('sepeservice_especialidad_centro', ['id_especialidad' => $esp->id]);
                if ($cp_links) {
                    $cps_xml = [];
                    foreach ($cp_links as $cpl) {
                        $cp = $DB->get_record('sepeservice_centro_presencial', ['id' => $cpl->id_centro_presencial]);
                        if ($cp) {
                            $cps_xml[] = ['ORIGEN_CENTRO' => $cp->origen_centro, 'CODIGO_CENTRO' => $cp->codigo_centro];
                        }
                    }
                    if (!empty($cps_xml)) $esp_node['CENTROS_SESIONES_PRESENCIALES']['CENTRO_PRESENCIAL'] = $cps_xml;
                }

                // Tutores
                $t_links = $DB->get_records('sepeservice_accion_especialidad_tutor', ['id_accion_formativa'=>$acc->id, 'id_especialidad'=>$esp->id]);
                if ($t_links) {
                    $tuts_xml = [];
                    foreach ($t_links as $tl) {
                        $tut = $DB->get_record('sepeservice_tutor_formador', ['id'=>$tl->id_tutor_formador]);
                        if ($tut) {
                            $tuts_xml[] = [
                                'ID_TUTOR' => ['TIPO_DOCUMENTO'=>$tut->tipo_documento, 'NUM_DOCUMENTO'=>$tut->num_documento, 'LETRA_NIF'=>$tut->letra_nif],
                                'ACREDITACION_TUTOR' => $tut->acreditacion_tutor,
                                'EXPERIENCIA_PROFESIONAL' => $tut->experiencia_profesional,
                                'COMPETENCIA_DOCENTE' => $tut->competencia_docente,
                                'EXPERIENCIA_MODALIDAD_TELEFORMACION' => $tut->experiencia_modalidad_teleformacion,
                                'FORMACION_MODALIDAD_TELEFORMACION' => $tut->formacion_modalidad_teleformacion
                            ];
                        }
                    }
                    if (!empty($tuts_xml)) $esp_node['TUTORES_FORMADORES']['TUTOR_FORMADOR'] = $tuts_xml;
                }

                // Uso
                $uso_link = $DB->get_record('sepeservice_especialidad_uso', ['id_especialidad' => $esp->id]);
                if ($uso_link) {
                    $uso = $DB->get_record('sepeservice_uso', ['id' => $uso_link->id_uso]);
                    if ($uso) {
                        $uso_node = [];
                        if ($uso->duracion_total_m > 0) $uso_node['HORARIO_MANANA'] = ['NUM_PARTICIPANTES'=>$uso->num_participantes_m, 'NUMERO_ACCESOS'=>$uso->num_accesos_m, 'DURACION_TOTAL'=>$uso->duracion_total_m];
                        if ($uso->duracion_total_t > 0) $uso_node['HORARIO_TARDE'] = ['NUM_PARTICIPANTES'=>$uso->num_participantes_t, 'NUMERO_ACCESOS'=>$uso->num_accesos_t, 'DURACION_TOTAL'=>$uso->duracion_total_t];
                        if ($uso->duracion_total_n > 0) $uso_node['HORARIO_NOCHE'] = ['NUM_PARTICIPANTES'=>$uso->num_participantes_n, 'NUMERO_ACCESOS'=>$uso->num_accesos_n, 'DURACION_TOTAL'=>$uso->duracion_total_n];
                        $uso_node['SEGUIMIENTO_EVALUACION'] = [
                            'NUM_PARTICIPANTES' => $uso->num_participantes,
                            'NUMERO_ACTIVIDADES_APRENDIZAJE' => $uso->numero_actividades_aprendizaje,
                            'NUMERO_INTENTOS' => $uso->numero_intentos,
                            'NUMERO_ACTIVIDADES_EVALUACION' => $uso->numero_actividades_evaluacion
                        ];
                        $esp_node['USO'] = $uso_node;
                    }
                }

                $esp_xml[] = $esp_node;
            }
        }
        if (!empty($esp_xml)) $result['ESPECIALIDADES_ACCION']['ESPECIALIDAD'] = $esp_xml;

        // 12. PARTICIPANTES (Simulado para que no rompa si no hay)
        // Puedes implementar la lectura completa de participantes aquí si es necesario
        
        return $result;
    }

    public function eliminar_accion($id_accion_data) {
        global $DB;
        $codigo = $id_accion_data['CODIGO_ACCION'] ?? '';
        $accion = $DB->get_record('sepeservice_accion_formativa', ['codigo_accion' => $codigo]);

        if (!$accion) throw new \Exception("Acción no encontrada.");

        $transaction = $DB->start_delegated_transaction();
        try {
            $DB->delete_records('sepeservice_accion_especialidad', ['id_accion_formativa' => $accion->id]);
            $DB->delete_records('sepeservice_accion_especialidad_tutor', ['id_accion_formativa' => $accion->id]);
            $DB->delete_records('sepeservice_uso', ['id_accion_formativa' => $accion->id]);
            $DB->delete_records('sepeservice_participante', ['id_accion_formativa' => $accion->id]);
            $DB->delete_records('sepeservice_accion_formativa', ['id' => $accion->id]);
            $transaction->allow_commit();
        } catch (\Exception $e) {
            $transaction->rollback($e);
            throw $e;
        }
    }

    public function obtener_lista_acciones() {
        global $DB;
        $acciones = $DB->get_records('sepeservice_accion_formativa', null, '', 'origen_accion, codigo_accion');
        $lista = [];
        foreach ($acciones as $a) {
            $lista[] = ['ORIGEN_ACCION' => $a->origen_accion, 'CODIGO_ACCION' => $a->codigo_accion];
        }
        return $lista;
    }

    // =========================================================================
    // MÉTODOS PRIVADOS DE PROCESAMIENTO (LOGICA INTERNA COMPLETA)
    // =========================================================================

    private function _procesar_especialidades($id_accion, $id_centro_local, $lista) {
        global $DB;
        foreach ($lista as $esp_data) {
            $esp = new \stdClass();
            $id_esp = $esp_data['ID_ESPECIALIDAD'] ?? [];
            
            $esp->origen_especialidad = $id_esp['ORIGEN_ESPECIALIDAD'] ?? '';
            $esp->area_profesional    = $id_esp['AREA_PROFESIONAL'] ?? '';
            $esp->codigo_especialidad = $id_esp['CODIGO_ESPECIALIDAD'] ?? '';
            $esp->id_centro           = $id_centro_local;
            $esp->fecha_inicio        = $esp_data['FECHA_INICIO'] ?? '';
            $esp->fecha_fin           = $esp_data['FECHA_FIN'] ?? '';
            $esp->modalidad_imparticion = $esp_data['MODALIDAD_IMPARTICION'] ?? '';
            
            $dur = $esp_data['DATOS_DURACION'] ?? [];
            $esp->horas_presencial    = (int)($dur['HORAS_PRESENCIAL'] ?? 0);
            $esp->horas_teleformacion = (int)($dur['HORAS_TELEFORMACION'] ?? 0);

            $existing = $DB->get_record('sepeservice_especialidad', [
                'codigo_especialidad' => $esp->codigo_especialidad,
                'id_centro' => $id_centro_local
            ]);
            
            if ($existing) {
                $esp->id = $existing->id;
                $DB->update_record('sepeservice_especialidad', $esp);
                $id_especialidad = $esp->id;
            } else {
                $esp->fecha_creacion = time();
                $id_especialidad = $DB->insert_record('sepeservice_especialidad', $esp);
            }

            // Vínculo Accion-Especialidad
            if (!$DB->record_exists('sepeservice_accion_especialidad', ['id_accion_formativa'=>$id_accion, 'id_especialidad'=>$id_especialidad])) {
                $DB->insert_record('sepeservice_accion_especialidad', [
                    'id_accion_formativa' => $id_accion, 
                    'id_especialidad' => $id_especialidad
                ]);
            }

            // Procesar Centros Presenciales
            if (!empty($esp_data['CENTROS_SESIONES_PRESENCIALES']['CENTRO_PRESENCIAL'])) {
                $cps = $this->_normalize_array($esp_data['CENTROS_SESIONES_PRESENCIALES']['CENTRO_PRESENCIAL']);
                foreach ($cps as $cp_data) {
                    $this->_procesar_centro_presencial($id_especialidad, $cp_data);
                }
            }

            // Procesar Tutores
            if (!empty($esp_data['TUTORES_FORMADORES']['TUTOR_FORMADOR'])) {
                $tuts = $this->_normalize_array($esp_data['TUTORES_FORMADORES']['TUTOR_FORMADOR']);
                foreach ($tuts as $t_data) {
                    $this->_procesar_tutor($id_accion, $id_especialidad, $t_data);
                }
            }

            // Procesar Uso
            if (!empty($esp_data['USO'])) {
                $this->_procesar_uso($id_accion, $id_especialidad, $esp_data['USO']);
            }
        }
    }

    private function _procesar_centro_presencial($id_especialidad, $cp_data) {
        global $DB;
        $cp = new \stdClass();
        $cp->origen_centro = $cp_data['ORIGEN_CENTRO'] ?? '';
        $cp->codigo_centro = $cp_data['CODIGO_CENTRO'] ?? '';

        if (empty($cp->codigo_centro)) return;

        $existing = $DB->get_record('sepeservice_centro_presencial', ['codigo_centro' => $cp->codigo_centro]);
        if ($existing) {
            $id_cp = $existing->id;
        } else {
            $cp->fecha_creacion = time();
            $id_cp = $DB->insert_record('sepeservice_centro_presencial', $cp);
        }

        if (!$DB->record_exists('sepeservice_especialidad_centro', ['id_especialidad'=>$id_especialidad, 'id_centro_presencial'=>$id_cp])) {
            $DB->insert_record('sepeservice_especialidad_centro', ['id_especialidad'=>$id_especialidad, 'id_centro_presencial'=>$id_cp]);
        }
    }

    private function _procesar_tutor($id_accion, $id_especialidad, $t_data) {
        global $DB;
        $id_w = $t_data['ID_TUTOR'] ?? [];
        
        $t = new \stdClass();
        $t->tipo_documento = $id_w['TIPO_DOCUMENTO'] ?? '';
        $t->num_documento  = $id_w['NUM_DOCUMENTO'] ?? '';
        $t->letra_nif      = $id_w['LETRA_NIF'] ?? '';
        $t->acreditacion_tutor          = $t_data['ACREDITACION_TUTOR'] ?? '';
        $t->experiencia_profesional     = $t_data['EXPERIENCIA_PROFESIONAL'] ?? '0';
        $t->competencia_docente         = $t_data['COMPETENCIA_DOCENTE'] ?? '';
        $t->experiencia_modalidad_teleformacion = $t_data['EXPERIENCIA_MODALIDAD_TELEFORMACION'] ?? '';
        $t->formacion_modalidad_teleformacion   = $t_data['FORMACION_MODALIDAD_TELEFORMACION'] ?? '';

        if (empty($t->num_documento)) return;

        $existing = $DB->get_record('sepeservice_tutor_formador', ['num_documento' => $t->num_documento]);
        if ($existing) {
            $t->id = $existing->id;
            $DB->update_record('sepeservice_tutor_formador', $t);
            $id_tutor = $existing->id;
        } else {
            $t->fecha_creacion = time();
            $id_tutor = $DB->insert_record('sepeservice_tutor_formador', $t);
        }

        if (!$DB->record_exists('sepeservice_accion_especialidad_tutor', ['id_accion_formativa'=>$id_accion, 'id_especialidad'=>$id_especialidad, 'id_tutor_formador'=>$id_tutor])) {
            $DB->insert_record('sepeservice_accion_especialidad_tutor', [
                'id_accion_formativa'=>$id_accion, 'id_especialidad'=>$id_especialidad, 'id_tutor_formador'=>$id_tutor
            ]);
        }
    }

    private function _procesar_uso($id_accion, $id_especialidad, $u_data) {
        global $DB;
        $uso = new \stdClass();
        $uso->id_accion_formativa = $id_accion;
        
        $map = ['HORARIO_MANANA'=>'_m', 'HORARIO_TARDE'=>'_t', 'HORARIO_NOCHE'=>'_n'];
        foreach ($map as $tag => $suf) {
            if (isset($u_data[$tag])) {
                $uso->{"num_participantes$suf"} = (int)($u_data[$tag]['NUM_PARTICIPANTES'] ?? 0);
                $uso->{"num_accesos$suf"}       = (int)($u_data[$tag]['NUMERO_ACCESOS'] ?? 0);
                $uso->{"duracion_total$suf"}    = (int)($u_data[$tag]['DURACION_TOTAL'] ?? 0);
            }
        }
        
        if (isset($u_data['SEGUIMIENTO_EVALUACION'])) {
            $seg = $u_data['SEGUIMIENTO_EVALUACION'];
            $uso->num_participantes = (int)($seg['NUM_PARTICIPANTES'] ?? 0);
            $uso->numero_actividades_aprendizaje = (int)($seg['NUMERO_ACTIVIDADES_APRENDIZAJE'] ?? 0);
            $uso->numero_intentos = (int)($seg['NUMERO_INTENTOS'] ?? 0);
            $uso->numero_actividades_evaluacion = (int)($seg['NUMERO_ACTIVIDADES_EVALUACION'] ?? 0);
        }

        $uso->fecha_creacion = time();
        $id_uso = $DB->insert_record('sepeservice_uso', $uso);

        $DB->insert_record('sepeservice_especialidad_uso', ['id_especialidad' => $id_especialidad, 'id_uso' => $id_uso]);
    }

    private function _procesar_participantes($id_accion, $lista) {
        global $DB;
        foreach ($lista as $p_data) {
            $part = new \stdClass();
            $id_p = $p_data['ID_PARTICIPANTE'] ?? [];
            $part->tipo_documento = $id_p['TIPO_DOCUMENTO'] ?? '';
            $part->num_documento  = $id_p['NUM_DOCUMENTO'] ?? '';
            $part->letra_nif      = $id_p['LETRA_NIF'] ?? '';
            $part->indicador_competencias_clave = $p_data['INDICADOR_COMPETENCIAS_CLAVE'] ?? '0';
            $part->id_accion_formativa = $id_accion;

            $existing = $DB->get_record('sepeservice_participante', [
                'num_documento' => $part->num_documento, 
                'id_accion_formativa' => $id_accion
            ]);
            
            if ($existing) {
                $part->id = $existing->id;
                $DB->update_record('sepeservice_participante', $part);
            } else {
                $part->fecha_creacion = time();
                $DB->insert_record('sepeservice_participante', $part);
            }
        }
    }

    private function _normalize_array($node) {
        if (empty($node)) return [];
        if (isset($node[0])) return $node;
        return [$node];
    }
}