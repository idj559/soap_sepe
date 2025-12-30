<?php

function local_crear_accion_process_request($requestXML) {
    // Cargar la solicitud SOAP
    $dom = new DOMDocument();
    $dom->loadXML($requestXML);

    //guardadoAccionBD($dom);

    // Crear el documento de respuesta
    $responseDOM = new DOMDocument('1.0', 'UTF-8');
    $responseDOM->formatOutput = true;

    // Nodo raíz SOAP
    $envelope = $responseDOM->createElement('soapenv:Envelope');
    $envelope->setAttribute('xmlns:soapenv', 'http://schemas.xmlsoap.org/soap/envelope/');
    $envelope->setAttribute('xmlns:soapenc', 'http://schemas.xmlsoap.org/soap/encoding/');
    $envelope->setAttribute('xmlns:xsd', 'http://www.w3.org/2001/XMLSchema');
    $envelope->setAttribute('xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
    $responseDOM->appendChild($envelope);

    // Cuerpo del mensaje SOAP
    $body = $responseDOM->createElement('soapenv:Body');
    $envelope->appendChild($body);

    // Nodo de respuesta principal
    $crearAccionResponse = $responseDOM->createElement('p867:crearAccionResponse');
    $crearAccionResponse->setAttribute('xmlns:p867', 'http://impl.ws.application.proveedorcentro.meyss.spee.es');

    $body->appendChild($crearAccionResponse);

    $respuesta = $responseDOM->createElement('p148:RESPUESTA_OBT_ACCION');
    $respuesta->setAttribute('xmlns:p465', 'http://entsal.bean.domain.common.proveedorcentro.meyss.spee.es');
    $respuesta->setAttribute('xmlns:p148', 'http://salida.bean.domain.common.proveedorcentro.meyss.spee.es');

    $crearAccionResponse->appendChild($respuesta);

    $numRetorno = guardadoAccionBD($dom);

    if($numRetorno == 1){
        $codigoRetorno = $responseDOM->createElement('CODIGO_RETORNO', $numRetorno);
        $respuesta->appendChild($codigoRetorno);

        // Nodo ETIQUETA_ERROR
        $etiquetaError = $responseDOM->createElement('ETIQUETA_ERROR');
        $etiquetaError->setAttribute('xsi:nil', 'true');
        $respuesta->appendChild($etiquetaError);

        // Nodo ACCION_FORMATIVA
        $accionFormativa = $responseDOM->createElement('p465:ACCION_FORMATIVA');
        $accionFormativa->setAttribute('xsi:nil', 'true');
        $respuesta->appendChild($accionFormativa);

        return $responseDOM->saveXML();
    }

    // Nodo CODIGO_RETORNO
    $codigoRetorno = $responseDOM->createElement('CODIGO_RETORNO', $numRetorno);
    $respuesta->appendChild($codigoRetorno);

    // Nodo ETIQUETA_ERROR
    $etiquetaError = $responseDOM->createElement('ETIQUETA_ERROR');
    $etiquetaError->setAttribute('xsi:nil', 'true');
    $respuesta->appendChild($etiquetaError);

    // Nodo ACCION_FORMATIVA
    $accionFormativa = $responseDOM->createElement('p465:ACCION_FORMATIVA');
    $respuesta->appendChild($accionFormativa);

    // Subnodos de ACCION_FORMATIVA
    $idAccion = $responseDOM->createElement('ID_ACCION');
    $idAccion->appendChild($responseDOM->createElement('ORIGEN_ACCION', getValueFromRequest($dom, 'ORIGEN_ACCION')));
    $idAccion->appendChild($responseDOM->createElement('CODIGO_ACCION', getValueFromRequest($dom, 'CODIGO_ACCION')));
    $accionFormativa->appendChild($idAccion);

    $accionFormativa->appendChild($responseDOM->createElement('SITUACION', getValueFromRequest($dom, 'SITUACION')));

    // Nodo ID_ESPECIALIDAD_PRINCIPAL
    $idEspecialidadPrincipal = $responseDOM->createElement('ID_ESPECIALIDAD_PRINCIPAL');
    $idEspecialidadPrincipal->appendChild($responseDOM->createElement('ORIGEN_ESPECIALIDAD', getValueFromRequest($dom, 'ORIGEN_ESPECIALIDAD')));
    $idEspecialidadPrincipal->appendChild($responseDOM->createElement('AREA_PROFESIONAL', getValueFromRequest($dom, 'AREA_PROFESIONAL')));
    $idEspecialidadPrincipal->appendChild($responseDOM->createElement('CODIGO_ESPECIALIDAD', getValueFromRequest($dom, 'CODIGO_ESPECIALIDAD')));
    $accionFormativa->appendChild($idEspecialidadPrincipal);

    $accionFormativa->appendChild($responseDOM->createElement('DURACION', getValueFromRequest($dom, 'DURACION')));
    $accionFormativa->appendChild($responseDOM->createElement('FECHA_INICIO', getValueFromRequest($dom, 'FECHA_INICIO')));
    $accionFormativa->appendChild($responseDOM->createElement('FECHA_FIN', getValueFromRequest($dom, 'FECHA_FIN')));
    $accionFormativa->appendChild($responseDOM->createElement('IND_ITINERARIO_COMPLETO', getValueFromRequest($dom, 'IND_ITINERARIO_COMPLETO')));
    $accionFormativa->appendChild($responseDOM->createElement('TIPO_FINANCIACION', getValueFromRequest($dom, 'TIPO_FINANCIACION')));
    $accionFormativa->appendChild($responseDOM->createElement('NUMERO_ASISTENTES', getValueFromRequest($dom, 'NUMERO_ASISTENTES')));

    // Nodo DESCRIPCION_ACCION
    $descripcionAccion = $responseDOM->createElement('DESCRIPCION_ACCION');
    $descripcionAccion->appendChild($responseDOM->createElement('DENOMINACION_ACCION', getValueFromRequest($dom, 'DENOMINACION_ACCION')));
    $descripcionAccion->appendChild($responseDOM->createElement('INFORMACION_GENERAL', getValueFromRequest($dom, 'INFORMACION_GENERAL')));
    $descripcionAccion->appendChild($responseDOM->createElement('HORARIOS', getValueFromRequest($dom, 'HORARIOS')));
    $descripcionAccion->appendChild($responseDOM->createElement('REQUISITOS', getValueFromRequest($dom, 'REQUISITOS')));
    $descripcionAccion->appendChild($responseDOM->createElement('CONTACTO_ACCION', getValueFromRequest($dom, 'CONTACTO_ACCION')));

    $accionFormativa->appendChild($descripcionAccion);

    // Nodo ESPECIALIDADES_ACCION
    $especialidadesAccion = $responseDOM->createElement('ESPECIALIDADES_ACCION');
    $especialidadNodes = getNodesByPath($dom, '//ESPECIALIDADES_ACCION/ESPECIALIDAD');

    foreach ($especialidadNodes as $especialidadNode) {
        $especialidad = $responseDOM->createElement('ESPECIALIDAD');

        // ID_ESPECIALIDAD
        $idEspecialidad = $responseDOM->createElement('ID_ESPECIALIDAD');
        $idEspecialidad->appendChild($responseDOM->createElement('ORIGEN_ESPECIALIDAD', getValueFromNode($especialidadNode, 'ORIGEN_ESPECIALIDAD')));
        $idEspecialidad->appendChild($responseDOM->createElement('AREA_PROFESIONAL', getValueFromNode($especialidadNode, 'AREA_PROFESIONAL')));
        $idEspecialidad->appendChild($responseDOM->createElement('CODIGO_ESPECIALIDAD', getValueFromNode($especialidadNode, 'CODIGO_ESPECIALIDAD')));
        $especialidad->appendChild($idEspecialidad);

        // CENTRO_IMPARTICION
        $centroImparticion = $responseDOM->createElement('CENTRO_IMPARTICION');
        $centroImparticion->appendChild($responseDOM->createElement('ORIGEN_CENTRO', getValueFromNode($especialidadNode, 'ORIGEN_CENTRO')));
        $centroImparticion->appendChild($responseDOM->createElement('CODIGO_CENTRO', getValueFromNode($especialidadNode, 'CODIGO_CENTRO')));
        $especialidad->appendChild($centroImparticion);

        // MODALIDAD_IMPARTICION y DATOS_DURACION
        $especialidad->appendChild($responseDOM->createElement('FECHA_INICIO', getValueFromNode($especialidadNode, 'FECHA_INICIO')));
        $especialidad->appendChild($responseDOM->createElement('FECHA_FIN', getValueFromNode($especialidadNode, 'FECHA_FIN')));
        $especialidad->appendChild($responseDOM->createElement('MODALIDAD_IMPARTICION', getValueFromNode($especialidadNode, 'MODALIDAD_IMPARTICION')));
        $datosDuracion = $responseDOM->createElement('DATOS_DURACION');
        $datosDuracion->appendChild($responseDOM->createElement('HORAS_PRESENCIAL', getValueFromNode($especialidadNode, 'HORAS_PRESENCIAL')));
        $datosDuracion->appendChild($responseDOM->createElement('HORAS_TELEFORMACION', getValueFromNode($especialidadNode, 'HORAS_TELEFORMACION')));
        $especialidad->appendChild($datosDuracion);

        // CENTROS_SESIONES_PRESENCIALES
        $centrosSesionesPresenciales = $responseDOM->createElement('CENTROS_SESIONES_PRESENCIALES');
        $centrosPresencialesNodes = $especialidadNode->getElementsByTagName('CENTRO_PRESENCIAL');
        foreach ($centrosPresencialesNodes as $centroPresencialNode) {
            $centroPresencial = $responseDOM->createElement('CENTRO_PRESENCIAL');
            $centroPresencial->appendChild($responseDOM->createElement('ORIGEN_CENTRO', getValueFromNode($centroPresencialNode, 'ORIGEN_CENTRO')));
            $centroPresencial->appendChild($responseDOM->createElement('CODIGO_CENTRO', getValueFromNode($centroPresencialNode, 'CODIGO_CENTRO')));
            $centrosSesionesPresenciales->appendChild($centroPresencial);
        }
        $especialidad->appendChild($centrosSesionesPresenciales);

        // TUTORES_FORMADORES
        $tutoresFormadores = $responseDOM->createElement('TUTORES_FORMADORES');
        $tutoresFormadoresNodes = $especialidadNode->getElementsByTagName('TUTOR_FORMADOR');
        foreach ($tutoresFormadoresNodes as $tutorFormadorNode) {
            $tutorFormador = $responseDOM->createElement('TUTOR_FORMADOR');
            $idTutor = $responseDOM->createElement('ID_TUTOR');
            $idTutor->appendChild($responseDOM->createElement('TIPO_DOCUMENTO', getValueFromNode($tutorFormadorNode, 'TIPO_DOCUMENTO')));
            $idTutor->appendChild($responseDOM->createElement('NUM_DOCUMENTO', getValueFromNode($tutorFormadorNode, 'NUM_DOCUMENTO')));
            $idTutor->appendChild($responseDOM->createElement('LETRA_NIF', getValueFromNode($tutorFormadorNode, 'LETRA_NIF')));
            $tutorFormador->appendChild($idTutor);

            $tutorFormador->appendChild($responseDOM->createElement('ACREDITACION_TUTOR', getValueFromNode($tutorFormadorNode, 'ACREDITACION_TUTOR')));
            $tutorFormador->appendChild($responseDOM->createElement('EXPERIENCIA_PROFESIONAL', getValueFromNode($tutorFormadorNode, 'EXPERIENCIA_PROFESIONAL')));
            $tutorFormador->appendChild($responseDOM->createElement('COMPETENCIA_DOCENTE', getValueFromNode($tutorFormadorNode, 'COMPETENCIA_DOCENTE')));
            $tutorFormador->appendChild($responseDOM->createElement('EXPERIENCIA_MODALIDAD_TELEFORMACION', getValueFromNode($tutorFormadorNode, 'EXPERIENCIA_MODALIDAD_TELEFORMACION')));
            $tutorFormador->appendChild($responseDOM->createElement('FORMACION_MODALIDAD_TELEFORMACION', getValueFromNode($tutorFormadorNode, 'FORMACION_MODALIDAD_TELEFORMACION')));

            $tutoresFormadores->appendChild($tutorFormador);
        }
        $especialidad->appendChild($tutoresFormadores);

        // USO
        $uso = $responseDOM->createElement('USO');

        // Procesar cada bloque de horario
        $horarios = ['HORARIO_MANANA', 'HORARIO_TARDE', 'HORARIO_NOCHE'];
        foreach ($horarios as $horarioTag) {
            // Buscar el nodo horario específico en el XML de entrada
            $horarioNode = $especialidadNode->getElementsByTagName($horarioTag)->item(0);
            if ($horarioNode) {
                $horarioElement = $responseDOM->createElement($horarioTag);

                // Obtener los datos dentro de cada horario
                $numParticipantes = getValueFromNode($horarioNode, 'NUM_PARTICIPANTES');
                $numeroAccesos = getValueFromNode($horarioNode, 'NUMERO_ACCESOS');
                $duracionTotal = getValueFromNode($horarioNode, 'DURACION_TOTAL');

                $horarioElement->appendChild($responseDOM->createElement('NUM_PARTICIPANTES', $numParticipantes));
                $horarioElement->appendChild($responseDOM->createElement('NUMERO_ACCESOS', $numeroAccesos));
                $horarioElement->appendChild($responseDOM->createElement('DURACION_TOTAL', $duracionTotal));

                // Añadir el horario al nodo USO
                $uso->appendChild($horarioElement);
            }
        }

        // Procesar SEGUIMIENTO_EVALUACION
        $seguimientoEvaluacionNode = $especialidadNode->getElementsByTagName('SEGUIMIENTO_EVALUACION')->item(0);
        if ($seguimientoEvaluacionNode) {
            $seguimientoEvaluacion = $responseDOM->createElement('SEGUIMIENTO_EVALUACION');

            // Obtener los valores de SEGUIMIENTO_EVALUACION
            $numParticipantes = getValueFromNode($seguimientoEvaluacionNode, 'NUM_PARTICIPANTES');
            $numeroActividadesAprendizaje = getValueFromNode($seguimientoEvaluacionNode, 'NUMERO_ACTIVIDADES_APRENDIZAJE');
            $numeroIntentos = getValueFromNode($seguimientoEvaluacionNode, 'NUMERO_INTENTOS');
            $numeroActividadesEvaluacion = getValueFromNode($seguimientoEvaluacionNode, 'NUMERO_ACTIVIDADES_EVALUACION');

            $seguimientoEvaluacion->appendChild($responseDOM->createElement('NUM_PARTICIPANTES', $numParticipantes));
            $seguimientoEvaluacion->appendChild($responseDOM->createElement('NUMERO_ACTIVIDADES_APRENDIZAJE', $numeroActividadesAprendizaje));
            $seguimientoEvaluacion->appendChild($responseDOM->createElement('NUMERO_INTENTOS', $numeroIntentos));
            $seguimientoEvaluacion->appendChild($responseDOM->createElement('NUMERO_ACTIVIDADES_EVALUACION', $numeroActividadesEvaluacion));

            // Añadir SEGUIMIENTO_EVALUACION al nodo USO
            $uso->appendChild($seguimientoEvaluacion);
        }

        // Añadir USO al nodo ESPECIALIDAD
        $especialidad->appendChild($uso);


        $especialidadesAccion->appendChild($especialidad);

    }
    $accionFormativa->appendChild($especialidadesAccion);

    // Nodo PARTICIPANTES
    $participantes = $responseDOM->createElement('PARTICIPANTES');

    // Procesar cada nodo PARTICIPANTE
    $participanteNodes = getNodesByPath($dom, '//PARTICIPANTES/PARTICIPANTE');
    foreach ($participanteNodes as $participanteNode) {
        $participante = $responseDOM->createElement('PARTICIPANTE');

        // ID_PARTICIPANTE
        $idParticipante = $responseDOM->createElement('ID_PARTICIPANTE');
        appendChildNodes($idParticipante, $participanteNode, $responseDOM, ['TIPO_DOCUMENTO', 'NUM_DOCUMENTO', 'LETRA_NIF']);
        $participante->appendChild($idParticipante);

        // INDICADOR_COMPETENCIAS_CLAVE
        $participante->appendChild($responseDOM->createElement('INDICADOR_COMPETENCIAS_CLAVE', getValueFromNode($participanteNode, 'INDICADOR_COMPETENCIAS_CLAVE')));

        // CONTRATO_FORMACION
        $contratoFormacionNode = $participanteNode->getElementsByTagName('CONTRATO_FORMACION')->item(0);
        $contratoFormacion = $responseDOM->createElement('CONTRATO_FORMACION');
        if($contratoFormacionNode->hasChildNodes()){
            $contratoFormacion->appendChild($responseDOM->createElement('ID_CONTRATO_CFA', getValueFromNode($contratoFormacionNode, 'ID_CONTRATO_CFA')));
            $contratoFormacion->appendChild($responseDOM->createElement('CIF_EMPRESA', getValueFromNode($contratoFormacionNode, 'CIF_EMPRESA')));

            // ID_TUTOR_EMPRESA
            $idTutorEmpresaNode = $participanteNode->getElementsByTagName('ID_TUTOR_EMPRESA')->item(0);
            if ($idTutorEmpresaNode) {
                $idTutorEmpresa = $responseDOM->createElement('ID_TUTOR_EMPRESA');
                appendChildNodes($idTutorEmpresa, $idTutorEmpresaNode, $responseDOM, ['TIPO_DOCUMENTO', 'NUM_DOCUMENTO', 'LETRA_NIF']);
                $contratoFormacion->appendChild($idTutorEmpresa);
            }

            // ID_TUTOR_FORMACION
            $idTutorFormacionNode = $participanteNode->getElementsByTagName('ID_TUTOR_FORMACION')->item(0);
            if ($idTutorFormacionNode) {
                $idTutorFormacion = $responseDOM->createElement('ID_TUTOR_FORMACION');
                appendChildNodes($idTutorFormacion, $idTutorFormacionNode, $responseDOM, ['TIPO_DOCUMENTO', 'NUM_DOCUMENTO', 'LETRA_NIF']);
                $contratoFormacion->appendChild($idTutorFormacion);
            }
        }
        
        $participante->appendChild($contratoFormacion);

        // ESPECIALIDADES_PARTICIPANTE
        $especialidadesParticipante = $responseDOM->createElement('ESPECIALIDADES_PARTICIPANTE');
        $especialidadNodes = $participanteNode->getElementsByTagName('ESPECIALIDAD');
        foreach ($especialidadNodes as $especialidadNode) {
            $especialidad = $responseDOM->createElement('ESPECIALIDAD');

            // ID_ESPECIALIDAD
            $idEspecialidad = $responseDOM->createElement('ID_ESPECIALIDAD');
            $idEspecialidad->appendChild($responseDOM->createElement('ORIGEN_ESPECIALIDAD', getValueFromNode($especialidadNode, 'ORIGEN_ESPECIALIDAD')));
            $idEspecialidad->appendChild($responseDOM->createElement('AREA_PROFESIONAL', getValueFromNode($especialidadNode, 'AREA_PROFESIONAL')));
            $idEspecialidad->appendChild($responseDOM->createElement('CODIGO_ESPECIALIDAD', getValueFromNode($especialidadNode, 'CODIGO_ESPECIALIDAD')));
            $especialidad->appendChild($idEspecialidad);

            if(getValueFromNode($especialidadNode, 'FECHA_ALTA'))
                $especialidad->appendChild($responseDOM->createElement('FECHA_ALTA', getValueFromNode($especialidadNode, 'FECHA_ALTA')));
            if(getValueFromNode($especialidadNode, 'FECHA_BAJA'))
                $especialidad->appendChild($responseDOM->createElement('FECHA_BAJA', getValueFromNode($especialidadNode, 'FECHA_BAJA')));

            // TUTORIAS_PRESENCIALES
            $tutoriasPresenciales = $responseDOM->createElement('TUTORIAS_PRESENCIALES');
            $tutoriasPresencialesNode = $especialidadNode->getElementsByTagName('TUTORIAS_PRESENCIALES')->item(0);

            if($tutoriasPresencialesNode->hasChildNodes()){
                $tutoriaNodes = $especialidadNode->getElementsByTagName('TUTORIA_PRESENCIAL');
                foreach ($tutoriaNodes as $tutoriaNode) {
                    $tutoriaPresencial = $responseDOM->createElement('TUTORIA_PRESENCIAL');
                    $centroPresencialTutoria = $responseDOM->createElement('CENTRO_PRESENCIAL_TUTORIA');
                    $centroPresencialTutoria->appendChild($responseDOM->createElement('ORIGEN_CENTRO', getValueFromNode($tutoriaNode, 'ORIGEN_CENTRO')));
                    $centroPresencialTutoria->appendChild($responseDOM->createElement('CODIGO_CENTRO', getValueFromNode($tutoriaNode, 'CODIGO_CENTRO')));
                    $tutoriaPresencial->appendChild($centroPresencialTutoria);

                    $tutoriaPresencial->appendChild($responseDOM->createElement('FECHA_INICIO', getValueFromNode($tutoriaNode, 'FECHA_INICIO')));
                    $tutoriaPresencial->appendChild($responseDOM->createElement('FECHA_FIN', getValueFromNode($tutoriaNode, 'FECHA_FIN')));
                    $tutoriasPresenciales->appendChild($tutoriaPresencial);
                }
            }else{
                $tutoriasPresenciales->setAttribute('xsi:nil', 'true');
            }
            
            $especialidad->appendChild($tutoriasPresenciales);

            // EVALUACION_FINAL
            $evaluacionFinalNode = $especialidadNode->getElementsByTagName('EVALUACION_FINAL')->item(0);
            if ($evaluacionFinalNode) {
                $evaluacionFinal = $responseDOM->createElement('EVALUACION_FINAL');

                // Verificar si hay datos en CENTRO_PRESENCIAL_EVALUACION
                $centroPresencialEvaluacionNode = $evaluacionFinalNode->getElementsByTagName('CENTRO_PRESENCIAL_EVALUACION')->item(0);
                if ($centroPresencialEvaluacionNode) {
                    $centroPresencialEvaluacion = $responseDOM->createElement('CENTRO_PRESENCIAL_EVALUACION');
                    $centroPresencialEvaluacion->appendChild($responseDOM->createElement('ORIGEN_CENTRO', getValueFromNode($centroPresencialEvaluacionNode, 'ORIGEN_CENTRO')));
                    $centroPresencialEvaluacion->appendChild($responseDOM->createElement('CODIGO_CENTRO', getValueFromNode($centroPresencialEvaluacionNode, 'CODIGO_CENTRO')));
                    $evaluacionFinal->appendChild($centroPresencialEvaluacion);
                }

                // Verificar si hay datos en FECHA_INICIO y FECHA_FIN
                $fechaInicio = getValueFromNode($evaluacionFinalNode, 'FECHA_INICIO');
                $fechaFin = getValueFromNode($evaluacionFinalNode, 'FECHA_FIN');
                if (!empty($fechaInicio)) {
                    $evaluacionFinal->appendChild($responseDOM->createElement('FECHA_INICIO', $fechaInicio));
                }
                if (!empty($fechaFin)) {
                    $evaluacionFinal->appendChild($responseDOM->createElement('FECHA_FIN', $fechaFin));
                }

                // Si no se agregaron nodos hijos, devolver un nodo vacío
                if (!$evaluacionFinal->hasChildNodes()) {
                    $especialidad->appendChild($responseDOM->createElement('EVALUACION_FINAL'));
                } else {
                    $especialidad->appendChild($evaluacionFinal);
                }
            } else {
                // Nodo vacío si no existe EVALUACION_FINAL en la solicitud
                $especialidad->appendChild($responseDOM->createElement('EVALUACION_FINAL'));
            }
            
            // RESULTADOS
            $resultadosNode = $especialidadNode->getElementsByTagName('RESULTADOS')->item(0);
            if ($resultadosNode) {
                $resultados = $responseDOM->createElement('RESULTADOS');

                // Verificar si hay datos en RESULTADO_FINAL
                $resultadoFinal = getValueFromNode($resultadosNode, 'RESULTADO_FINAL');
                if (!empty($resultadoFinal)) {
                    $resultados->appendChild($responseDOM->createElement('RESULTADO_FINAL', $resultadoFinal));
                }

                // Verificar si hay datos en CALIFICACION_FINAL
                $calificacionFinal = getValueFromNode($resultadosNode, 'CALIFICACION_FINAL');
                if (!empty($calificacionFinal)) {
                    $resultados->appendChild($responseDOM->createElement('CALIFICACION_FINAL', $calificacionFinal));
                }

                // Verificar si hay datos en PUNTUACION_FINAL
                $puntuacionFinal = getValueFromNode($resultadosNode, 'PUNTUACION_FINAL');
                if (!empty($puntuacionFinal)) {
                    $resultados->appendChild($responseDOM->createElement('PUNTUACION_FINAL', $puntuacionFinal));
                }

                // Si no se agregaron nodos hijos, devolver un nodo vacío
                if (!$resultados->hasChildNodes()) {
                    $especialidad->appendChild($responseDOM->createElement('RESULTADOS'));
                } else {
                    $especialidad->appendChild($resultados);
                }
            } else {
                // Nodo vacío si no existe RESULTADOS en la solicitud
                $especialidad->appendChild($responseDOM->createElement('RESULTADOS'));
            }

            $especialidadesParticipante->appendChild($especialidad);
        }
        $participante->appendChild($especialidadesParticipante);

        // Añadir PARTICIPANTE a PARTICIPANTES
        $participantes->appendChild($participante);
    }

    // Añadir PARTICIPANTES a la respuesta principal
    $accionFormativa->appendChild($participantes);

    // Retornar XML de respuesta
    return $responseDOM->saveXML();
}

function guardadoAccionBD($dom){
    global $DB;

    // Iniciar la transacción
    $transaction = $DB->start_delegated_transaction();

    try {

        if($DB->get_record('sepeservice_accion_formativa', array('ORIGEN_ACCION' => getValueFromRequest($dom, 'ORIGEN_ACCION'), 'CODIGO_ACCION' => getValueFromRequest($dom, 'CODIGO_ACCION')))){
            return 1;
        }
        // Extraer datos de la acción formativa
        $accionData = [
            'ORIGEN_ACCION' => getValueFromRequest($dom, 'ORIGEN_ACCION'),
            'CODIGO_ACCION' => getValueFromRequest($dom, 'CODIGO_ACCION'),
            'SITUACION' => getValueFromRequest($dom, 'SITUACION'),
            'ORIGEN_ESPECIALIDAD' => getValueFromRequest($dom, 'ORIGEN_ESPECIALIDAD'),
            'AREA_PROFESIONAL' => getValueFromRequest($dom, 'AREA_PROFESIONAL'),
            'CODIGO_ESPECIALIDAD' => getValueFromRequest($dom, 'CODIGO_ESPECIALIDAD'),
            'DURACION' => getValueFromRequest($dom, 'DURACION'),
            'FECHA_INICIO' => getValueFromRequest($dom, 'FECHA_INICIO'),
            'FECHA_FIN' => getValueFromRequest($dom, 'FECHA_FIN'),
            'IND_ITINERARIO_COMPLETO' => getValueFromRequest($dom, 'IND_ITINERARIO_COMPLETO'),
            'TIPO_FINANCIACION' => getValueFromRequest($dom, 'TIPO_FINANCIACION'),
            'NUMERO_ASISTENTES' => getValueFromRequest($dom, 'NUMERO_ASISTENTES'),
            'DENOMINACION_ACCION' => getValueFromRequest($dom, 'DENOMINACION_ACCION'),
            'INFORMACION_GENERAL' => getValueFromRequest($dom, 'INFORMACION_GENERAL'),
            'HORARIOS' => getValueFromRequest($dom, 'HORARIOS'),
            'REQUISITOS' => getValueFromRequest($dom, 'REQUISITOS'),
            'CONTACTO_ACCION' => getValueFromRequest($dom, 'CONTACTO_ACCION'),
            'ID_CENTRO' => 1,
        ];

        // Insertar acción formativa en la base de datos
        $accionId = $DB->insert_record('sepeservice_accion_formativa', (object)$accionData);

        // Procesar especialidades asociadas
        $especialidadNodes = getNodesByPath($dom, '//ESPECIALIDADES_ACCION/ESPECIALIDAD');
        foreach ($especialidadNodes as $especialidadNode) {
            $especialidadData = [
                'ORIGEN_ESPECIALIDAD' => getValueFromNode($especialidadNode, 'ORIGEN_ESPECIALIDAD'),
                'AREA_PROFESIONAL' => getValueFromNode($especialidadNode, 'AREA_PROFESIONAL'),
                'CODIGO_ESPECIALIDAD' => getValueFromNode($especialidadNode, 'CODIGO_ESPECIALIDAD'),
                'FECHA_INICIO' => getValueFromNode($especialidadNode, 'FECHA_INICIO'),
                'FECHA_FIN' => getValueFromNode($especialidadNode, 'FECHA_FIN'),
                'MODALIDAD_IMPARTICION' => getValueFromNode($especialidadNode, 'MODALIDAD_IMPARTICION'),
                'HORAS_PRESENCIAL' => getValueFromNode($especialidadNode, 'HORAS_PRESENCIAL'),
                'HORAS_TELEFORMACION' => getValueFromNode($especialidadNode, 'HORAS_TELEFORMACION'),
                //'ID_ACCION_FORMATIVA' => $accionId,
                'ID_CENTRO' => 1,
            ];

            /*$especialidades = $DB->get_records_sql("
        SELECT e.*
        FROM {sepeservice_especialidad} e
        INNER JOIN {sepeservice_accion_especialidad} ae ON e.id = ae.ID_ESPECIALIDAD
        WHERE ae.ID_ACCION_FORMATIVA = :accion_id
    ", ['accion_id' => $accion->id]);*/

            $existingEspecialidad = $DB->get_record('sepeservice_especialidad', $especialidadData);
/*
            $especialidad = $DB->get_record_sql("
            SELECT e.*
            FROM {sepeservice_especialidad} e
            INNER JOIN {sepeservice_accion_especialidad} ae ON e.id = ae.ID_ESPECIALIDAD
            WHERE ae.ID_ACCION_FORMATIVA = :accion_id
            AND e.CODIGO_ESPECIALIDAD = :codigo_especialidad
            ", ['accion_id' => $accionId, 'codigo_especialidad' => $existingEspecialidad->codigo_especialidad]);*/

            /*$especialidad = $DB->get_record('sepeservice_especialidad', [
                'CODIGO_ESPECIALIDAD' => $especialidadData['CODIGO_ESPECIALIDAD'],
            ]);*/

            

            if (!$existingEspecialidad) {
                // Insertar nueva especialidad si no existe
                $especialidadId = $DB->insert_record('sepeservice_especialidad', (object)$especialidadData);
            } else {
                $especialidadId = $existingEspecialidad->id;
            }

            // Registrar la relación en la tabla intermedia
            $existingRelation = $DB->get_record('sepeservice_accion_especialidad', [
                'ID_ACCION_FORMATIVA' => $accionId,
                'ID_ESPECIALIDAD' => $especialidadId,
            ]);
            if (!$existingRelation) {
                $DB->insert_record('sepeservice_accion_especialidad', (object)[
                    'ID_ACCION_FORMATIVA' => $accionId,
                    'ID_ESPECIALIDAD' => $especialidadId,
                ]);
            }

            
            // Procesar CENTROS_SESIONES_PRESENCIALES
            $centrosSesionesPresencialesNodes = $especialidadNode->getElementsByTagName('CENTRO_PRESENCIAL');
            foreach ($centrosSesionesPresencialesNodes as $centroNode) {
                // Verificar si el centro presencial ya existe
                $centroData = [
                    'ORIGEN_CENTRO' => getValueFromNode($centroNode, 'ORIGEN_CENTRO'),
                    'CODIGO_CENTRO' => getValueFromNode($centroNode, 'CODIGO_CENTRO'),
                ];

                // Buscar o insertar el centro presencial
                $existingCentro = $DB->get_record('sepeservice_centro_presencial', ['CODIGO_CENTRO' => $centroData['CODIGO_CENTRO']]);
                if (!$existingCentro) {
                    $centroId = $DB->insert_record('sepeservice_centro_presencial', (object)$centroData);
                } else {
                    $centroId = $existingCentro->id;
                }

                // Relacionar el centro presencial con la especialidad
                $especialidadCentroData = [
                    'ID_ESPECIALIDAD' => $especialidadId,
                    'ID_CENTRO_PRESENCIAL' => $centroId,
                ];

                $existingEspecialidadCentro = $DB->get_record('sepeservice_especialidad_centro', [
                    'ID_ESPECIALIDAD' => $especialidadId,
                    'ID_CENTRO_PRESENCIAL' => $centroId,
                ]);
                if (!$existingEspecialidadCentro) {
                    $DB->insert_record('sepeservice_especialidad_centro', (object)$especialidadCentroData);
                }
            }

            // Procesar TUTORES_FORMADORES
            $tutoresFormadoresNodes = $especialidadNode->getElementsByTagName('TUTOR_FORMADOR');
            foreach ($tutoresFormadoresNodes as $tutorNode) {
                // Verificar si el tutor formador ya existe
                $tutorData = [
                    'TIPO_DOCUMENTO' => getValueFromNode($tutorNode, 'TIPO_DOCUMENTO'),
                    'NUM_DOCUMENTO' => getValueFromNode($tutorNode, 'NUM_DOCUMENTO'),
                    'LETRA_NIF' => getValueFromNode($tutorNode, 'LETRA_NIF'),
                    'ACREDITACION_TUTOR' => getValueFromNode($tutorNode, 'ACREDITACION_TUTOR'),
                    'EXPERIENCIA_PROFESIONAL' => getValueFromNode($tutorNode, 'EXPERIENCIA_PROFESIONAL'),
                    'COMPETENCIA_DOCENTE' => getValueFromNode($tutorNode, 'COMPETENCIA_DOCENTE'),
                    'EXPERIENCIA_MODALIDAD_TELEFORMACION' => getValueFromNode($tutorNode, 'EXPERIENCIA_MODALIDAD_TELEFORMACION'),
                    'FORMACION_MODALIDAD_TELEFORMACION' => getValueFromNode($tutorNode, 'FORMACION_MODALIDAD_TELEFORMACION'),
                ];

                // Buscar o insertar el tutor formador
                $existingTutor = $DB->get_record('sepeservice_tutor_formador', $tutorData);

                if(!$existingTutor){
                    // Crear tutor
                    $tutorId = $DB->insert_record('sepeservice_tutor_formador', (object)$tutorData);
                    // Crear relacion tutor-especialidad
                    /*$especialidadTutorData = [
                        'ID_ESPECIALIDAD' => $especialidadId,
                        'ID_TUTOR_FORMADOR' => $tutorId,
                    ];
                    $DB->insert_record('sepeservice_especialidad_tutor', (object)$especialidadTutorData);*/
                }else{
                    $tutorId = $existingTutor->id;
                    /*
                    $existingEspecialidadTutor = $DB->get_record('sepeservice_especialidad_tutor', [
                        'ID_ESPECIALIDAD' => $especialidadId,
                        'ID_TUTOR_FORMADOR' => $existingTutor->id,
                    ]);
                    if(!$existingEspecialidadTutor){
                        $especialidadTutorData = [
                            'ID_ESPECIALIDAD' => $especialidadId,
                            'ID_TUTOR_FORMADOR' => $existingTutor->id,
                        ];
                        $DB->insert_record('sepeservice_especialidad_tutor', (object)$especialidadTutorData);
                    }*/
                }

                $existingRelacion = $DB->get_record('sepeservice_accion_especialidad_tutor', [
                    'ID_ACCION_FORMATIVA' => $accionId,
                    'ID_ESPECIALIDAD' => $especialidadId,
                    'ID_TUTOR_FORMADOR' => $tutorId,
                ]);

                if(!$existingRelacion){
                    $relacion = [
                        'ID_ACCION_FORMATIVA' => $accionId,
                        'ID_ESPECIALIDAD' => $especialidadId,
                        'ID_TUTOR_FORMADOR' => $tutorId,
                    ];
                    $DB->insert_record('sepeservice_accion_especialidad_tutor', $relacion);
                }

            }

            // Procesar USO
            $usoNode = $especialidadNode->getElementsByTagName('USO')->item(0);
            
            if ($usoNode) {
                $usoData = [
                    'ID_ACCION_FORMATIVA' => $accionId,
                    'NUM_PARTICIPANTES_M' => 0,
                    'NUM_ACCESOS_M' => 0,
                    'DURACION_TOTAL_M' => 0,
                    'NUM_PARTICIPANTES_T' => 0,
                    'NUM_ACCESOS_T' => 0,
                    'DURACION_TOTAL_T' => 0,
                    'NUM_PARTICIPANTES_N' => 0,
                    'NUM_ACCESOS_N' => 0,
                    'DURACION_TOTAL_N' => 0,
                    'NUM_PARTICIPANTES' => 0,
                    'NUMERO_ACTIVIDADES_APRENDIZAJE' => 0,
                    'NUMERO_INTENTOS' => 0,
                    'NUMERO_ACTIVIDADES_EVALUACION' => 0,
                ];

                // Procesar horarios: MAÑANA, TARDE, NOCHE
                $horarios = ['HORARIO_MANANA', 'HORARIO_TARDE', 'HORARIO_NOCHE'];
                foreach ($horarios as $horarioTag) {
                    $horarioNode = $usoNode->getElementsByTagName($horarioTag)->item(0);
                    if ($horarioNode) {
                        $prefix = substr($horarioTag, 8, 1); // Obtener "M", "T", o "N"
                        $usoData["NUM_PARTICIPANTES_{$prefix}"] = getValueFromNode($horarioNode, 'NUM_PARTICIPANTES');
                        $usoData["NUM_ACCESOS_{$prefix}"] = getValueFromNode($horarioNode, 'NUMERO_ACCESOS');
                        $usoData["DURACION_TOTAL_{$prefix}"] = getValueFromNode($horarioNode, 'DURACION_TOTAL');
                    }
                }

                // Procesar SEGUIMIENTO_EVALUACION
                $seguimientoEvaluacionNode = $usoNode->getElementsByTagName('SEGUIMIENTO_EVALUACION')->item(0);
                if ($seguimientoEvaluacionNode) {
                    $usoData['NUM_PARTICIPANTES'] = getValueFromNode($seguimientoEvaluacionNode, 'NUM_PARTICIPANTES');
                    $usoData['NUMERO_ACTIVIDADES_APRENDIZAJE'] = getValueFromNode($seguimientoEvaluacionNode, 'NUMERO_ACTIVIDADES_APRENDIZAJE');
                    $usoData['NUMERO_INTENTOS'] = getValueFromNode($seguimientoEvaluacionNode, 'NUMERO_INTENTOS');
                    $usoData['NUMERO_ACTIVIDADES_EVALUACION'] = getValueFromNode($seguimientoEvaluacionNode, 'NUMERO_ACTIVIDADES_EVALUACION');
                }

                if($usoData['NUM_PARTICIPANTES'] != 0){
                    // Insertar USO en la base de datos
                    $usoId = $DB->insert_record('sepeservice_uso', (object)$usoData);
                    // Crear la relación entre la especialidad y el uso
                    $especialidadUsoData = [
                        'ID_ESPECIALIDAD' => $especialidadId, // ID de la especialidad actual
                        'ID_USO' => $usoId
                    ];

                    $DB->insert_record('sepeservice_especialidad_uso',$especialidadUsoData);
                }
            }
        }

        // Procesar PARTICIPANTES
        $participantesNodes = getNodesByPath($dom, '//PARTICIPANTES/PARTICIPANTE');
        foreach ($participantesNodes as $participanteNode) {
            // Datos del participante
            $tipoDocumento = getValueFromNode($participanteNode, 'TIPO_DOCUMENTO');
            $numDocumento = getValueFromNode($participanteNode, 'NUM_DOCUMENTO');
            $letraNif = getValueFromNode($participanteNode, 'LETRA_NIF');
            $indicadorCompetenciasClave = getValueFromNode($participanteNode, 'INDICADOR_COMPETENCIAS_CLAVE');

            // Buscar o insertar participante
            $existingParticipante = $DB->get_record('sepeservice_participante', [
                'TIPO_DOCUMENTO' => $tipoDocumento,
                'NUM_DOCUMENTO' => $numDocumento,
                'LETRA_NIF' => $letraNif,
                'INDICADOR_COMPETENCIAS_CLAVE' => $indicadorCompetenciasClave,
                'ID_ACCION_FORMATIVA' => $accionId,
            ]);
            if (!$existingParticipante) {
                $participanteId = $DB->insert_record('sepeservice_participante', (object)[
                    'TIPO_DOCUMENTO' => $tipoDocumento,
                    'NUM_DOCUMENTO' => $numDocumento,
                    'LETRA_NIF' => $letraNif,
                    'INDICADOR_COMPETENCIAS_CLAVE' => $indicadorCompetenciasClave,
                    'ID_ACCION_FORMATIVA' => $accionId, // Asociar a la acción formativa
                ]);
            } else {
                $participanteId = $existingParticipante->id;
            }

            // Procesar CONTRATO_FORMACION
            $contratoNode = $participanteNode->getElementsByTagName('CONTRATO_FORMACION')->item(0);
            if ($contratoNode && $contratoNode->hasChildNodes()) {
                $idContratoCFA = getValueFromNode($contratoNode, 'ID_CONTRATO_CFA');
                $cifEmpresa = getValueFromNode($contratoNode, 'CIF_EMPRESA');

                // Procesar ID_TUTOR_EMPRESA
                $tutorEmpresaNode = $contratoNode->getElementsByTagName('ID_TUTOR_EMPRESA')->item(0);
                $tutorEmpresaId = null;
                if ($tutorEmpresaNode) {
                    $tutorEmpresaData = [
                        'TIPO_DOCUMENTO' => getValueFromNode($tutorEmpresaNode, 'TIPO_DOCUMENTO'),
                        'NUM_DOCUMENTO' => getValueFromNode($tutorEmpresaNode, 'NUM_DOCUMENTO'),
                        'LETRA_NIF' => getValueFromNode($tutorEmpresaNode, 'LETRA_NIF'),
                    ];
                    $existingTutorEmpresa = $DB->get_record('sepeservice_tutor_empresa', [
                        'TIPO_DOCUMENTO' => $tutorEmpresaData['TIPO_DOCUMENTO'],
                        'NUM_DOCUMENTO' => $tutorEmpresaData['NUM_DOCUMENTO'],
                    ]);
                    if (!$existingTutorEmpresa) {
                        $tutorEmpresaId = $DB->insert_record('sepeservice_tutor_empresa', (object)$tutorEmpresaData);
                    } else {
                        $tutorEmpresaId = $existingTutorEmpresa->id;
                    }
                }

                // Procesar ID_TUTOR_FORMACION
                $tutorFormacionNode = $contratoNode->getElementsByTagName('ID_TUTOR_FORMACION')->item(0);
                $tutorFormacionId = null;
                if ($tutorFormacionNode) {
                    $tutorFormacionData = [
                        'TIPO_DOCUMENTO' => getValueFromNode($tutorFormacionNode, 'TIPO_DOCUMENTO'),
                        'NUM_DOCUMENTO' => getValueFromNode($tutorFormacionNode, 'NUM_DOCUMENTO'),
                        'LETRA_NIF' => getValueFromNode($tutorFormacionNode, 'LETRA_NIF'),
                    ];
                    $existingTutorFormacion = $DB->get_record('sepeservice_tutor_formador', [
                        'TIPO_DOCUMENTO' => $tutorFormacionData['TIPO_DOCUMENTO'],
                        'NUM_DOCUMENTO' => $tutorFormacionData['NUM_DOCUMENTO'],
                    ]);
                    if (!$existingTutorFormacion) {
                        $tutorFormacionId = $DB->insert_record('sepeservice_tutor_formador', (object)$tutorFormacionData);
                    } else {
                        $tutorFormacionId = $existingTutorFormacion->id;
                    }
                }

                // Insertar o actualizar el contrato
                $existingContrato = $DB->get_record('sepeservice_contrato_formacion', [
                    'ID_CONTRATO_CFA' => $idContratoCFA,
                ]);
                if (!$existingContrato) {
                    $contratoId = $DB->insert_record('sepeservice_contrato_formacion', (object)[
                        'ID_CONTRATO_CFA' => $idContratoCFA,
                        'CIF_EMPRESA' => $cifEmpresa,
                        'ID_TUTOR_EMPRESA' => $tutorEmpresaId,
                        'ID_TUTOR_FORMACION' => $tutorFormacionId,
                    ]);
                } else {
                    $contratoId = $existingContrato->id;
                }

                // Asociar contrato al participante
                $DB->update_record('sepeservice_participante', (object)[
                    'id' => $participanteId,
                    'ID_CONTRATO_FORMACION' => $contratoId,
                ]);
            }

            // Procesar ESPECIALIDADES_PARTICIPANTE
            $especialidadesParticipanteNode = $participanteNode->getElementsByTagName('ESPECIALIDADES_PARTICIPANTE')->item(0);
            if ($especialidadesParticipanteNode) {
                $especialidadNodes = $especialidadesParticipanteNode->getElementsByTagName('ESPECIALIDAD');
                foreach ($especialidadNodes as $especialidadNode) {
                    // Obtener los datos de la especialidad
                    $especialidadData = [
                        'ORIGEN_ESPECIALIDAD' => getValueFromNode($especialidadNode, 'ORIGEN_ESPECIALIDAD'),
                        'AREA_PROFESIONAL' => getValueFromNode($especialidadNode, 'AREA_PROFESIONAL'),
                        'CODIGO_ESPECIALIDAD' => getValueFromNode($especialidadNode, 'CODIGO_ESPECIALIDAD'),
                        'FECHA_ALTA' => getValueFromNode($especialidadNode, 'FECHA_ALTA'),
                        'FECHA_BAJA' => getValueFromNode($especialidadNode, 'FECHA_BAJA'),
                    ];
                    
                    $existingEspecialidadParticipante = $DB->get_record_sql("
                        SELECT *
                        FROM {sepeservice_especialidad} esp
                        JOIN {sepeservice_accion_especialidad} ae ON esp.id = ae.ID_ESPECIALIDAD
                        WHERE ae.ID_ACCION_FORMATIVA = :id_accion AND
                        esp.CODIGO_ESPECIALIDAD = :codigo_especialidad
                    ", ['id_accion' => $accionId, 'codigo_especialidad' => $especialidadData['CODIGO_ESPECIALIDAD']]);

                    // Buscar la especialidad en la base de datos
                    //$especialidad = $DB->get_record('sepeservice_especialidad', ['CODIGO_ESPECIALIDAD' => $especialidadData['CODIGO_ESPECIALIDAD']]);

                    if ($existingEspecialidadParticipante) {
                        // Insertar relación en `sepeservice_especialidad_participante`
                        $especialidadParticipanteId = $DB->insert_record('sepeservice_especialidad_participante', (object)[
                            'ID_PARTICIPANTE' => $participanteId,
                            'ID_ESPECIALIDAD' => $existingEspecialidadParticipante->id_especialidad,
                            'FECHA_ALTA' => $especialidadData['FECHA_ALTA'],
                            'FECHA_BAJA' => $especialidadData['FECHA_BAJA'],
                        ]);
                    }

                    // Procesar y guardar TUTORIAS_PRESENCIALES
                    $tutoriasPresencialesNode = $especialidadNode->getElementsByTagName('TUTORIAS_PRESENCIALES')->item(0);
                    if ($tutoriasPresencialesNode) {
                        $tutoriaPresencialNodes = $tutoriasPresencialesNode->getElementsByTagName('TUTORIA_PRESENCIAL');
                        foreach ($tutoriaPresencialNodes as $tutoriaPresencialNode) {
                            $centroPresencialId = null;

                            // Buscar o crear el centro presencial asociado
                            $origenCentro = getValueFromNode($tutoriaPresencialNode->getElementsByTagName('CENTRO_PRESENCIAL_TUTORIA')->item(0), 'ORIGEN_CENTRO');
                            $codigoCentro = getValueFromNode($tutoriaPresencialNode->getElementsByTagName('CENTRO_PRESENCIAL_TUTORIA')->item(0), 'CODIGO_CENTRO');
                            if (!empty($origenCentro) && !empty($codigoCentro)) {
                                $centroPresencial = $DB->get_record('sepeservice_centro_presencial', [
                                    'ORIGEN_CENTRO' => $origenCentro,
                                    'CODIGO_CENTRO' => $codigoCentro
                                ]);

                                if (!$centroPresencial) {
                                    $centroPresencialId = $DB->insert_record('sepeservice_centro_presencial', [
                                        'ORIGEN_CENTRO' => $origenCentro,
                                        'CODIGO_CENTRO' => $codigoCentro
                                    ]);
                                } else {
                                    $centroPresencialId = $centroPresencial->id;
                                }
                            }

                            // Insertar la tutoria presencial
                            $DB->insert_record('sepeservice_tutoria_presencial', [
                                'ID_ESPECIALIDAD_PARTICIPANTE' => $especialidadParticipanteId,
                                'ID_CENTRO_PRESENCIAL' => $centroPresencialId,
                                'FECHA_INICIO' => getValueFromNode($tutoriaPresencialNode, 'FECHA_INICIO'),
                                'FECHA_FIN' => getValueFromNode($tutoriaPresencialNode, 'FECHA_FIN')
                            ]);
                        }
                    }



                    // Guardar EVALUACION_FINAL
                    $evaluacionFinalNode = $especialidadNode->getElementsByTagName('EVALUACION_FINAL')->item(0);
                    if ($evaluacionFinalNode) {
                        $centroPresencialId = null;

                        // Buscar o crear el centro presencial asociado
                        $origenCentro = getValueFromNode($evaluacionFinalNode, 'ORIGEN_CENTRO');
                        $codigoCentro = getValueFromNode($evaluacionFinalNode, 'CODIGO_CENTRO');
                        if (!empty($origenCentro) && !empty($codigoCentro)) {
                            $centroPresencial = $DB->get_record('sepeservice_centro_presencial', [
                                'ORIGEN_CENTRO' => $origenCentro,
                                'CODIGO_CENTRO' => $codigoCentro
                            ]);

                            if (!$centroPresencial) {
                                $centroPresencialId = $DB->insert_record('sepeservice_centro_presencial', [
                                    'ORIGEN_CENTRO' => $origenCentro,
                                    'CODIGO_CENTRO' => $codigoCentro
                                ]);
                            } else {
                                $centroPresencialId = $centroPresencial->id;
                            }
                        }

                        // Insertar EVALUACION_FINAL
                        $DB->insert_record('sepeservice_evaluacion_final', [
                            'ID_ESPECIALIDAD_PARTICIPANTE' => $especialidadParticipanteId,
                            'ID_CENTRO_PRESENCIAL' => $centroPresencialId,
                            'FECHA_INICIO' => getValueFromNode($evaluacionFinalNode, 'FECHA_INICIO'),
                            'FECHA_FIN' => getValueFromNode($evaluacionFinalNode, 'FECHA_FIN')
                        ]);
                    }

                    // Guardar RESULTADOS
                    $resultadosNode = $especialidadNode->getElementsByTagName('RESULTADOS')->item(0);
                    if ($resultadosNode) {
                        $DB->insert_record('sepeservice_resultados', [
                            'ID_ESPECIALIDAD_PARTICIPANTE' => $especialidadParticipanteId,
                            'RESULTADO_FINAL' => getValueFromNode($resultadosNode, 'RESULTADO_FINAL'),
                            'CALIFICACION_FINAL' => getValueFromNode($resultadosNode, 'CALIFICACION_FINAL'),
                            'PUNTUACION_FINAL' => getValueFromNode($resultadosNode, 'PUNTUACION_FINAL')
                        ]);
                    }
                }
            }
        }

        // Confirmar la transacción
        $transaction->allow_commit();
        return 0;

    } catch (Exception $e) {
        // Revertir la transacción en caso de error
        $transaction->rollback($e);

        // Generar respuesta de error
        return generateErrorResponse($e->getMessage());
    }
}

// Función para generar respuesta de error
function generateErrorResponse($errorMessage) {
    $responseDOM = new DOMDocument('1.0', 'UTF-8');
    $responseDOM->formatOutput = true;

    $envelope = $responseDOM->createElement('soapenv:Envelope');
    $envelope->setAttribute('xmlns:soapenv', 'http://schemas.xmlsoap.org/soap/envelope/');
    $responseDOM->appendChild($envelope);

    $body = $responseDOM->createElement('soapenv:Body');
    $envelope->appendChild($body);

    $fault = $responseDOM->createElement('soapenv:Fault');
    $body->appendChild($fault);

    $faultcode = $responseDOM->createElement('faultcode', 'Receiver');
    $fault->appendChild($faultcode);

    $faultstring = $responseDOM->createElement('faultstring', htmlspecialchars($errorMessage));
    $fault->appendChild($faultstring);

    return $responseDOM->saveXML();
}

function local_obtener_datos_centro_process_request($requestXML) {
    global $DB;

    try {
        // Obtener el primer registro de la tabla de centros
        $record = $DB->get_record('sepeservice_centro', array(), '*', IGNORE_MULTIPLE);
        //error_log("Record" . var_dump($record));

        if ($record) {
            // Crear la respuesta exitosa con los datos del centro
            return createObtenerDatosCentroResponse(0, null, $record);
        } else {
            // Si no se encuentra ningún registro
            return createObtenerDatosCentroResponse(0, null);
        }
    } catch (dml_exception $e) {
        // Crear una respuesta en caso de error al consultar la base de datos
        return createObtenerDatosCentroResponse(-1, 'Error al consultar la base de datos');
    }
}

function createObtenerDatosCentroResponse($codigoRetorno, $etiquetaError, $record = null) {
    $responseDOM = new DOMDocument('1.0', 'UTF-8');
    $responseDOM->formatOutput = true;

    // Nodo raíz SOAP
    $envelope = $responseDOM->createElement('soapenv:Envelope');
    $envelope->setAttribute('xmlns:soapenv', 'http://schemas.xmlsoap.org/soap/envelope/');
    $envelope->setAttribute('xmlns:soapenc', 'http://schemas.xmlsoap.org/soap/encoding/');
    $envelope->setAttribute('xmlns:xsd', 'http://www.w3.org/2001/XMLSchema');
    $envelope->setAttribute('xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
    $responseDOM->appendChild($envelope);

    // Cuerpo del mensaje SOAP
    $body = $responseDOM->createElement('soapenv:Body');
    $envelope->appendChild($body);

    // Nodo de respuesta principal
    $obtenerDatosCentroResponse = $responseDOM->createElement('p867:obtenerDatosCentroResponse');
    $obtenerDatosCentroResponse->setAttribute('xmlns:p867', 'http://impl.ws.application.proveedorcentro.meyss.spee.es');

    $body->appendChild($obtenerDatosCentroResponse);

    $respuesta = $responseDOM->createElement('p148:RESPUESTA_DATOS_CENTRO');
    $respuesta->setAttribute('xmlns:p465', 'http://entsal.bean.domain.common.proveedorcentro.meyss.spee.es');
    $respuesta->setAttribute('xmlns:p148', 'http://salida.bean.domain.common.proveedorcentro.meyss.spee.es');

    $obtenerDatosCentroResponse->appendChild($respuesta);

    // Nodo CODIGO_RETORNO
    $respuesta->appendChild($responseDOM->createElement('CODIGO_RETORNO', $codigoRetorno));

    // Nodo ETIQUETA_ERROR
    $etiquetaErrorNode = $responseDOM->createElement('ETIQUETA_ERROR', $etiquetaError);
    if ($etiquetaError === null || $codigoRetorno === -1){
        $etiquetaErrorNode->setAttribute('xsi:nil', 'true');
    }
    $respuesta->appendChild($etiquetaErrorNode);

    if($codigoRetorno === -1){
        $datosIdentificativos = $responseDOM->createElement('p465:DATOS_IDENTIFICATIVOS');
        $datosIdentificativos->setAttribute('xsi:nil', 'true');
        $respuesta->appendChild($datosIdentificativos);
    }else if ($codigoRetorno === 0 && $record !== null) {
        // Nodo DATOS_IDENTIFICATIVOS (solo si la respuesta es exitosa)
        $datosIdentificativos = $responseDOM->createElement('p465:DATOS_IDENTIFICATIVOS');

        $idCentro = $responseDOM->createElement('ID_CENTRO');
        $idCentro->appendChild($responseDOM->createElement('ORIGEN_CENTRO', $record->origen_centro));
        $idCentro->appendChild($responseDOM->createElement('CODIGO_CENTRO', $record->codigo_centro));
        $datosIdentificativos->appendChild($idCentro);

        $datosIdentificativos->appendChild($responseDOM->createElement('NOMBRE_CENTRO', $record->nombre_centro));
        $datosIdentificativos->appendChild($responseDOM->createElement('URL_PLATAFORMA', $record->url_plataforma));
        $datosIdentificativos->appendChild($responseDOM->createElement('URL_SEGUIMIENTO', $record->url_seguimiento));
        $datosIdentificativos->appendChild($responseDOM->createElement('TELEFONO', $record->telefono));
        $datosIdentificativos->appendChild($responseDOM->createElement('EMAIL', $record->email));

        $respuesta->appendChild($datosIdentificativos);
    }

    return $responseDOM->saveXML();
}



function local_crear_centro_process_request($requestXML) {
    global $DB;

    // Cargar la solicitud SOAP
    $dom = new DOMDocument();
    $dom->loadXML($requestXML);

    // Extraer los datos del nodo correspondiente
    $origenCentro = getValueFromRequest($dom, 'ORIGEN_CENTRO');
    $codigoCentro = getValueFromRequest($dom, 'CODIGO_CENTRO');
    $nombreCentro = getValueFromRequest($dom, 'NOMBRE_CENTRO');
    $urlPlataforma = getValueFromRequest($dom, 'URL_PLATAFORMA');
    $urlSeguimiento = getValueFromRequest($dom, 'URL_SEGUIMIENTO');
    $telefono = getValueFromRequest($dom, 'TELEFONO');
    $email = getValueFromRequest($dom, 'EMAIL');
    $fechaCreacion = time(); // Timestamp actual

    // Construir el objeto para la inserción
    $data = new stdClass();
    $data->ORIGEN_CENTRO = $origenCentro;
    $data->CODIGO_CENTRO = $codigoCentro;
    $data->NOMBRE_CENTRO = $nombreCentro;
    $data->URL_PLATAFORMA = $urlPlataforma;
    $data->URL_SEGUIMIENTO = $urlSeguimiento;
    $data->TELEFONO = $telefono;
    $data->EMAIL = $email;
    $data->FECHA_CREACION = $fechaCreacion;

    // Insertar en la base de datos
    try {
        $DB->insert_record('sepeservice_centro', $data);

        // Crear la respuesta exitosa
        return createCentroResponse(0, null, $origenCentro, $codigoCentro, $nombreCentro, $urlPlataforma, $urlSeguimiento, $telefono, $email);

    } catch (Exception $e) {
        // Crear la respuesta en caso de error
        return createCentroResponse(-1, 'Error al guardar en la base de datos: ' . $e->getMessage());
    }
}

function createCentroResponse($codigoRetorno, $etiquetaError, $origenCentro = null, $codigoCentro = null, $nombreCentro = null, $urlPlataforma = null, $urlSeguimiento = null, $telefono = null, $email = null) {
    $responseDOM = new DOMDocument('1.0', 'UTF-8');
    $responseDOM->formatOutput = true;

    // Nodo raíz SOAP
    $envelope = $responseDOM->createElement('soapenv:Envelope');
    $envelope->setAttribute('xmlns:soapenv', 'http://schemas.xmlsoap.org/soap/envelope/');
    $envelope->setAttribute('xmlns:soapenc', 'http://schemas.xmlsoap.org/soap/encoding/');
    $envelope->setAttribute('xmlns:xsd', 'http://www.w3.org/2001/XMLSchema');
    $envelope->setAttribute('xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
    $responseDOM->appendChild($envelope);

    // Cuerpo del mensaje SOAP
    $body = $responseDOM->createElement('soapenv:Body');
    $envelope->appendChild($body);

    // Nodo de respuesta
    $crearCentroResponse = $responseDOM->createElement('p867:crearCentroResponse');
    $crearCentroResponse->setAttribute('xmlns:p867', 'http://impl.ws.application.proveedorcentro.meyss.spee.es');
    $body->appendChild($crearCentroResponse);

    $respuesta = $responseDOM->createElement('p148:RESPUESTA_DATOS_CENTRO');
    $respuesta->setAttribute('xmlns:p465', 'http://entsal.bean.domain.common.proveedorcentro.meyss.spee.es');
    $respuesta->setAttribute('xmlns:p148', 'http://salida.bean.domain.common.proveedorcentro.meyss.spee.es');

    $crearCentroResponse->appendChild($respuesta);

    // Nodo CODIGO_RETORNO
    $respuesta->appendChild($responseDOM->createElement('CODIGO_RETORNO', $codigoRetorno));

    // Nodo ETIQUETA_ERROR
    $etiquetaErrorNode = $responseDOM->createElement('ETIQUETA_ERROR', $etiquetaError);
    if ($etiquetaError === null) {
        $etiquetaErrorNode->setAttribute('xsi:nil', 'true');
    }
    $respuesta->appendChild($etiquetaErrorNode);

    // Nodo DATOS_IDENTIFICATIVOS
    if ($codigoRetorno === 0) {
        $datosIdentificativos = $responseDOM->createElement('p465:DATOS_IDENTIFICATIVOS');

        $idCentro = $responseDOM->createElement('ID_CENTRO');
        $idCentro->appendChild($responseDOM->createElement('ORIGEN_CENTRO', $origenCentro));
        $idCentro->appendChild($responseDOM->createElement('CODIGO_CENTRO', $codigoCentro));
        $datosIdentificativos->appendChild($idCentro);

        $datosIdentificativos->appendChild($responseDOM->createElement('NOMBRE_CENTRO', $nombreCentro));
        $datosIdentificativos->appendChild($responseDOM->createElement('URL_PLATAFORMA', $urlPlataforma));
        $datosIdentificativos->appendChild($responseDOM->createElement('URL_SEGUIMIENTO', $urlSeguimiento));
        $datosIdentificativos->appendChild($responseDOM->createElement('TELEFONO', $telefono));
        $datosIdentificativos->appendChild($responseDOM->createElement('EMAIL', $email));

        $respuesta->appendChild($datosIdentificativos);
    }

    return $responseDOM->saveXML();
}

function local_obtener_lista_acciones_process_request($requestXML) {
    global $DB;

    // Crear el documento de respuesta
    $responseDOM = new DOMDocument('1.0', 'UTF-8');
    $responseDOM->formatOutput = true;

    // Nodo raíz SOAP
    $envelope = $responseDOM->createElement('soapenv:Envelope');
    $envelope->setAttribute('xmlns:soapenv', 'http://schemas.xmlsoap.org/soap/envelope/');
    $envelope->setAttribute('xmlns:soapenc', 'http://schemas.xmlsoap.org/soap/encoding/');
    $envelope->setAttribute('xmlns:xsd', 'http://www.w3.org/2001/XMLSchema');
    $envelope->setAttribute('xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
    $responseDOM->appendChild($envelope);

    // Cuerpo del mensaje SOAP
    $body = $responseDOM->createElement('soapenv:Body');
    $envelope->appendChild($body);

    // Nodo de respuesta principal
    $response = $responseDOM->createElement('p867:obtenerListaAccionesResponse');
    $response->setAttribute('xmlns:p867', 'http://impl.ws.application.proveedorcentro.meyss.spee.es');

    $body->appendChild($response);

    $respuestaListaAcciones = $responseDOM->createElement('p148:RESPUESTA_OBT_LISTA_ACCIONES');
    $respuestaListaAcciones->setAttribute('xmlns:p465', 'http://entsal.bean.domain.common.proveedorcentro.meyss.spee.es');
    $respuestaListaAcciones->setAttribute('xmlns:p148', 'http://salida.bean.domain.common.proveedorcentro.meyss.spee.es');

    $response->appendChild($respuestaListaAcciones);

    // Nodo CODIGO_RETORNO
    $codigoRetorno = $responseDOM->createElement('CODIGO_RETORNO', '0');
    $respuestaListaAcciones->appendChild($codigoRetorno);

    // Nodo ETIQUETA_ERROR
    $etiquetaError = $responseDOM->createElement('ETIQUETA_ERROR');
    $etiquetaError->setAttribute('xsi:nil', 'true');
    $respuestaListaAcciones->appendChild($etiquetaError);

    // Obtener las acciones formativas de la base de datos
    $acciones = $DB->get_records('sepeservice_accion_formativa', null, '', 'id, ORIGEN_ACCION, CODIGO_ACCION');

    if (empty($acciones)) {
        // Si no hay acciones, agregar ID_ACCION con xsi:nil
        $idAccion = $responseDOM->createElement('p465:ID_ACCION');
        $idAccion->setAttribute('xsi:nil', 'true');
        $respuestaListaAcciones->appendChild($idAccion);
    } else {
        // Si hay acciones, agregar nodos para cada acción
        foreach ($acciones as $accion) {
            $idAccion = $responseDOM->createElement('p465:ID_ACCION');
            $idAccion->appendChild($responseDOM->createElement('ORIGEN_ACCION', htmlspecialchars($accion->origen_accion)));
            $idAccion->appendChild($responseDOM->createElement('CODIGO_ACCION', htmlspecialchars($accion->codigo_accion)));
            $respuestaListaAcciones->appendChild($idAccion);
        }
    }

    // Retornar XML de respuesta
    return $responseDOM->saveXML();
}

function local_eliminar_accion_process_request($requestXML) {
    global $DB;

    // Cargar la solicitud SOAP
    $dom = new DOMDocument();
    $dom->loadXML($requestXML);

    $responseDOM = new DOMDocument('1.0', 'UTF-8');
    $responseDOM->formatOutput = true;

    // Nodo raíz SOAP
    $envelope = $responseDOM->createElement('soapenv:Envelope');
    $envelope->setAttribute('xmlns:soapenv', 'http://schemas.xmlsoap.org/soap/envelope/');
    $envelope->setAttribute('xmlns:soapenc', 'http://schemas.xmlsoap.org/soap/encoding/');
    $envelope->setAttribute('xmlns:xsd', 'http://www.w3.org/2001/XMLSchema');
    $envelope->setAttribute('xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
    $responseDOM->appendChild($envelope);

    // Cuerpo del mensaje SOAP
    $body = $responseDOM->createElement('soapenv:Body');
    $envelope->appendChild($body);

    // Nodo de respuesta
    $eliminarAccionResponse = $responseDOM->createElement('p867:eliminarAccionResponse');
    $eliminarAccionResponse->setAttribute('xmlns:p867', 'http://impl.ws.application.proveedorcentro.meyss.spee.es');
    $body->appendChild($eliminarAccionResponse);

    $respuesta = $responseDOM->createElement('p148:RESPUESTA_ELIMINAR_ACCION');
    $respuesta->setAttribute('xmlns:p148', 'http://salida.bean.domain.common.proveedorcentro.meyss.spee.es');

    $eliminarAccionResponse->appendChild($respuesta);

    if($DB->get_record('sepeservice_accion_formativa', ['ORIGEN_ACCION' => getValueFromRequest($dom, 'ORIGEN_ACCION'), 'CODIGO_ACCION' => getValueFromRequest($dom, 'CODIGO_ACCION')])) {
        // Eliminar la acción formativa
        $DB->delete_records('sepeservice_accion_formativa', ['ORIGEN_ACCION' => getValueFromRequest($dom, 'ORIGEN_ACCION'), 'CODIGO_ACCION' => getValueFromRequest($dom, 'CODIGO_ACCION')]);

        // Nodo CODIGO_RETORNO
        $codigoRetorno = $responseDOM->createElement('CODIGO_RETORNO', '0');
        $respuesta->appendChild($codigoRetorno);

        // Nodo ETIQUETA_ERROR
        $etiquetaError = $responseDOM->createElement('ETIQUETA_ERROR');
        $etiquetaError->setAttribute('xsi:nil', 'true');
        $respuesta->appendChild($etiquetaError);
    } else {
        // Nodo CODIGO_RETORNO
        $codigoRetorno = $responseDOM->createElement('CODIGO_RETORNO', '1');
        $respuesta->appendChild($codigoRetorno);

        // Nodo ETIQUETA_ERROR
        $etiquetaError = $responseDOM->createElement('ETIQUETA_ERROR');
        $etiquetaError->setAttribute('xsi:nil', 'true');
        $respuesta->appendChild($etiquetaError);
    }

    return $responseDOM->saveXML();
}

function local_obtener_accion_process_request($requestXML){
    global $DB;

    // Cargar la solicitud SOAP
    $dom = new DOMDocument();
    $dom->loadXML($requestXML);

    $responseDOM = new DOMDocument('1.0', 'UTF-8');
    $responseDOM->formatOutput = true;

    // Nodo raíz SOAP
    $envelope = $responseDOM->createElement('soapenv:Envelope');
    $envelope->setAttribute('xmlns:soapenv', 'http://schemas.xmlsoap.org/soap/envelope/');
    $envelope->setAttribute('xmlns:soapenc', 'http://schemas.xmlsoap.org/soap/encoding/');
    $envelope->setAttribute('xmlns:xsd', 'http://www.w3.org/2001/XMLSchema');
    $envelope->setAttribute('xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
    $responseDOM->appendChild($envelope);

    // Cuerpo del mensaje SOAP
    $body = $responseDOM->createElement('soapenv:Body');
    $envelope->appendChild($body);

    // Nodo de respuesta
    $obtenerAccionResponse = $responseDOM->createElement('p867:obtenerAccionResponse');
    $obtenerAccionResponse->setAttribute('xmlns:p867', 'http://impl.ws.application.proveedorcentro.meyss.spee.es');
    $body->appendChild($obtenerAccionResponse);

    $respuesta = $responseDOM->createElement('p148:RESPUESTA_OBT_ACCION');
    $respuesta->setAttribute('xmlns:p465', 'http://entsal.bean.domain.common.proveedorcentro.meyss.spee.es');
    $respuesta->setAttribute('xmlns:p148', 'http://salida.bean.domain.common.proveedorcentro.meyss.spee.es');

    $obtenerAccionResponse->appendChild($respuesta);

    $accion = $DB->get_record('sepeservice_accion_formativa', ['ORIGEN_ACCION' => getValueFromRequest($dom, 'ORIGEN_ACCION'), 'CODIGO_ACCION' => getValueFromRequest($dom, 'CODIGO_ACCION')]);

    if(!$accion) {
        // Nodo CODIGO_RETORNO
        $codigoRetorno = $responseDOM->createElement('CODIGO_RETORNO', '1');
        $respuesta->appendChild($codigoRetorno);

        // Nodo ETIQUETA_ERROR
        $etiquetaError = $responseDOM->createElement('ETIQUETA_ERROR');
        $etiquetaError->setAttribute('xsi:nil', 'true');
        $respuesta->appendChild($etiquetaError);

        // Nodo ACCION_FORMATIVA
        $accionFormativa = $responseDOM->createElement('p465:ACCION_FORMATIVA');
        $accionFormativa->setAttribute('xsi:nil', 'true');
        $respuesta->appendChild($accionFormativa);

        return $responseDOM->saveXML();
    }

    // Nodo CODIGO_RETORNO
    $codigoRetorno = $responseDOM->createElement('CODIGO_RETORNO', '0');
    $respuesta->appendChild($codigoRetorno);

    // Nodo ETIQUETA_ERROR
    $etiquetaError = $responseDOM->createElement('ETIQUETA_ERROR');
    $etiquetaError->setAttribute('xsi:nil', 'true');
    $respuesta->appendChild($etiquetaError);

    // Nodo ACCION_FORMATIVA
    $accionFormativa = $responseDOM->createElement('p465:ACCION_FORMATIVA');
    $respuesta->appendChild($accionFormativa);

    // Subnodos de ACCION_FORMATIVA
    $idAccion = $responseDOM->createElement('ID_ACCION');
    $idAccion->appendChild($responseDOM->createElement('ORIGEN_ACCION', getValueFromRequest($dom, 'ORIGEN_ACCION')));
    $idAccion->appendChild($responseDOM->createElement('CODIGO_ACCION', getValueFromRequest($dom, 'CODIGO_ACCION')));
    $accionFormativa->appendChild($idAccion);

    $accionFormativa->appendChild($responseDOM->createElement('SITUACION', $accion->situacion));

    // Nodo ID_ESPECIALIDAD_PRINCIPAL
    $idEspecialidadPrincipal = $responseDOM->createElement('ID_ESPECIALIDAD_PRINCIPAL');
    $idEspecialidadPrincipal->appendChild($responseDOM->createElement('ORIGEN_ESPECIALIDAD', $accion->origen_especialidad));
    $idEspecialidadPrincipal->appendChild($responseDOM->createElement('AREA_PROFESIONAL', $accion->area_profesional));
    $idEspecialidadPrincipal->appendChild($responseDOM->createElement('CODIGO_ESPECIALIDAD', $accion->codigo_especialidad));
    $accionFormativa->appendChild($idEspecialidadPrincipal);

    $accionFormativa->appendChild($responseDOM->createElement('DURACION', $accion->duracion));
    $accionFormativa->appendChild($responseDOM->createElement('FECHA_INICIO', $accion->fecha_inicio));
    $accionFormativa->appendChild($responseDOM->createElement('FECHA_FIN', $accion->fecha_fin));
    $accionFormativa->appendChild($responseDOM->createElement('IND_ITINERARIO_COMPLETO', $accion->ind_itinerario_completo));
    $accionFormativa->appendChild($responseDOM->createElement('TIPO_FINANCIACION', $accion->tipo_financiacion));
    $accionFormativa->appendChild($responseDOM->createElement('NUMERO_ASISTENTES', $accion->numero_asistentes));

    // Nodo DESCRIPCION_ACCION
    $descripcionAccion = $responseDOM->createElement('DESCRIPCION_ACCION');
    $descripcionAccion->appendChild($responseDOM->createElement('DENOMINACION_ACCION', $accion->denominacion_accion));
    $descripcionAccion->appendChild($responseDOM->createElement('INFORMACION_GENERAL', $accion->informacion_general));
    $descripcionAccion->appendChild($responseDOM->createElement('HORARIOS', $accion->horarios));
    $descripcionAccion->appendChild($responseDOM->createElement('REQUISITOS', $accion->requisitos));
    $descripcionAccion->appendChild($responseDOM->createElement('CONTACTO_ACCION', $accion->contacto_accion));

    $accionFormativa->appendChild($descripcionAccion);

    // Obtener especialidades relacionadas
    $especialidades = $DB->get_records_sql("
        SELECT e.*
        FROM {sepeservice_especialidad} e
        INNER JOIN {sepeservice_accion_especialidad} ae ON e.id = ae.ID_ESPECIALIDAD
        WHERE ae.ID_ACCION_FORMATIVA = :accion_id
    ", ['accion_id' => $accion->id]);

    if (!empty($especialidades)) {
        $especialidadesNode = $responseDOM->createElement('ESPECIALIDADES_ACCION');
        $accionFormativa->appendChild($especialidadesNode);

        foreach ($especialidades as $especialidad) {
            $especialidadNode = $responseDOM->createElement('ESPECIALIDAD');
            $idEspecialidad = $responseDOM->createElement('ID_ESPECIALIDAD');
            $idEspecialidad->appendChild($responseDOM->createElement('ORIGEN_ESPECIALIDAD', $especialidad->origen_especialidad));
            $idEspecialidad->appendChild($responseDOM->createElement('AREA_PROFESIONAL', $especialidad->area_profesional));
            $idEspecialidad->appendChild($responseDOM->createElement('CODIGO_ESPECIALIDAD', $especialidad->codigo_especialidad));
            $especialidadNode->appendChild($idEspecialidad);

            $centro = $DB->get_record('sepeservice_centro', array(), '*', IGNORE_MULTIPLE);

            $centroImparticion = $responseDOM->createElement('CENTRO_IMPARTICION');
            $centroImparticion->appendChild($responseDOM->createElement('ORIGEN_CENTRO', $centro->origen_centro));
            $centroImparticion->appendChild($responseDOM->createElement('CODIGO_CENTRO', '80'.substr($centro->codigo_centro, 2)));
            $especialidadNode->appendChild($centroImparticion);

            // MODALIDAD_IMPARTICION y DATOS_DURACION
            $especialidadNode->appendChild($responseDOM->createElement('FECHA_INICIO', $especialidad->fecha_inicio));
            $especialidadNode->appendChild($responseDOM->createElement('FECHA_FIN', $especialidad->fecha_fin));
            $especialidadNode->appendChild($responseDOM->createElement('MODALIDAD_IMPARTICION', $especialidad->modalidad_imparticion));
            $datosDuracion = $responseDOM->createElement('DATOS_DURACION');
            $datosDuracion->appendChild($responseDOM->createElement('HORAS_PRESENCIAL', $especialidad->horas_presencial));
            $datosDuracion->appendChild($responseDOM->createElement('HORAS_TELEFORMACION', $especialidad->horas_teleformacion));
            $especialidadNode->appendChild($datosDuracion);

            // CENROS_SESIONES_PRESENCIALES
            $centrosSesionesPresenciales = $responseDOM->createElement('CENTROS_SESIONES_PRESENCIALES');

            $centrosPresenciales = $DB->get_records_sql("
                SELECT c.*
                FROM {sepeservice_centro_presencial} c
                INNER JOIN {sepeservice_especialidad_centro} ec ON c.id = ec.ID_CENTRO_PRESENCIAL
                WHERE ec.ID_ESPECIALIDAD = :especialidad_id
                ", ['especialidad_id' => $especialidad->id]);
            if(!empty($centrosPresenciales)){
                foreach ($centrosPresenciales as $centroPresencialNode) {
                    $centroPresencial = $responseDOM->createElement('CENTRO_PRESENCIAL');
                    $centroPresencial->appendChild($responseDOM->createElement('ORIGEN_CENTRO', $centroPresencialNode->origen_centro));
                    $centroPresencial->appendChild($responseDOM->createElement('CODIGO_CENTRO', $centroPresencialNode->codigo_centro));
                    $centrosSesionesPresenciales->appendChild($centroPresencial);
                }
            }
            
            $especialidadNode->appendChild($centrosSesionesPresenciales);

            //TUTORES_FORMADORES
            $tutoresFormadoresNodes = $responseDOM->createElement('TUTORES_FORMADORES');
            /*$tutoresFormadores = $DB->get_records_sql("
                SELECT tf.*
                FROM {sepeservice_tutor_formador} tf
                INNER JOIN {sepeservice_especialidad_tutor} etf ON tf.id = etf.ID_TUTOR_FORMADOR
                INNER JOIN {sepeservice_accion_especialidad} accesp ON etf.ID_ESPECIALIDAD = accesp.ID_ESPECIALIDAD
                WHERE etf.ID_ESPECIALIDAD = :especialidad_id AND accesp.ID_ACCION_FORMATIVA = :accion_id
                ", ['especialidad_id' => $especialidad->id, 'accion_id' => $accion->id]);*/

            $relacionTutorFormadorEspecialidad = $DB->get_records('sepeservice_accion_especialidad_tutor', [
                'ID_ACCION_FORMATIVA' => $accion->id,
                'ID_ESPECIALIDAD' => $especialidad->id
            ]);

            if(!empty($relacionTutorFormadorEspecialidad)){
                foreach ($relacionTutorFormadorEspecialidad as $relacionTfEsp){
                    $tutorFormador = $responseDOM->createElement('TUTOR_FORMADOR');

                    $tutorFormadorNode = $DB->get_record('sepeservice_tutor_formador', [
                        'id' => $relacionTfEsp->id_tutor_formador
                    ]);
                    
                    $idTutor = $responseDOM->createElement('ID_TUTOR');
                    $idTutor->appendChild($responseDOM->createElement('TIPO_DOCUMENTO', $tutorFormadorNode->tipo_documento));
                    $idTutor->appendChild($responseDOM->createElement('NUM_DOCUMENTO', $tutorFormadorNode->num_documento));
                    $idTutor->appendChild($responseDOM->createElement('LETRA_NIF', $tutorFormadorNode->letra_nif));
                    $tutorFormador->appendChild($idTutor);

                    $tutorFormador->appendChild($responseDOM->createElement('ACREDITACION_TUTOR', $tutorFormadorNode->acreditacion_tutor));
                    $tutorFormador->appendChild($responseDOM->createElement('EXPERIENCIA_PROFESIONAL', $tutorFormadorNode->experiencia_profesional));
                    $tutorFormador->appendChild($responseDOM->createElement('COMPETENCIA_DOCENTE', $tutorFormadorNode->competencia_docente));
                    $tutorFormador->appendChild($responseDOM->createElement('EXPERIENCIA_MODALIDAD_TELEFORMACION', $tutorFormadorNode->experiencia_modalidad_teleformacion));
                    $tutorFormador->appendChild($responseDOM->createElement('FORMACION_MODALIDAD_TELEFORMACION', $tutorFormadorNode->formacion_modalidad_teleformacion));
                    
                    $tutoresFormadoresNodes->appendChild($tutorFormador);

                }
            }else{
                $tutoresFormadoresNodes->setAttribute('xsi:nil', 'true');
            }
            $especialidadNode->appendChild($tutoresFormadoresNodes);

            // USO
            $usoNode = $responseDOM->createElement('USO');
            //$uso = $DB->get_record('sepeservice_uso', ['id' => $especialidad->id_uso]);

            $usoRelaciones = $DB->get_records('sepeservice_especialidad_uso', [
                'ID_ESPECIALIDAD' => $especialidad->id
            ]);

            foreach ($usoRelaciones as $usoRelacion){
                $uso = $DB->get_record('sepeservice_uso', [
                    'id' => $usoRelacion->id_uso, 'ID_ACCION_FORMATIVA' => $accion->id
                ]);

                if(!empty($uso)){
                    $horarioM = $responseDOM->createElement('HORARIO_MANANA');
                    $horarioM->appendChild($responseDOM->createElement('NUM_PARTICIPANTES', $uso->num_participantes_m));
                    $horarioM->appendChild($responseDOM->createElement('NUMERO_ACCESOS', $uso->num_accesos_m));
                    $horarioM->appendChild($responseDOM->createElement('DURACION_TOTAL', $uso->duracion_total_m));
                    $usoNode->appendChild($horarioM);
    
                    $horarioT = $responseDOM->createElement('HORARIO_TARDE');
                    $horarioT->appendChild($responseDOM->createElement('NUM_PARTICIPANTES', $uso->num_participantes_t));
                    $horarioT->appendChild($responseDOM->createElement('NUMERO_ACCESOS', $uso->num_accesos_t));
                    $horarioT->appendChild($responseDOM->createElement('DURACION_TOTAL', $uso->duracion_total_t));
                    $usoNode->appendChild($horarioT);
    
                    $horarioN = $responseDOM->createElement('HORARIO_NOCHE');
                    $horarioN->appendChild($responseDOM->createElement('NUM_PARTICIPANTES', $uso->num_participantes_n));
                    $horarioN->appendChild($responseDOM->createElement('NUMERO_ACCESOS', $uso->num_accesos_n));
                    $horarioN->appendChild($responseDOM->createElement('DURACION_TOTAL', $uso->duracion_total_n));
                    $usoNode->appendChild($horarioN);
    
                    $seguimientoEvaluacion = $responseDOM->createElement('SEGUIMIENTO_EVALUACION');
                    $seguimientoEvaluacion->appendChild($responseDOM->createElement('NUM_PARTICIPANTES', $uso->num_participantes));
                    $seguimientoEvaluacion->appendChild($responseDOM->createElement('NUMERO_ACTIVIDADES_APRENDIZAJE', $uso->numero_actividades_aprendizaje));
                    $seguimientoEvaluacion->appendChild($responseDOM->createElement('NUMERO_INTENTOS', $uso->numero_intentos));
                    $seguimientoEvaluacion->appendChild($responseDOM->createElement('NUMERO_ACTIVIDADES_EVALUACION', $uso->numero_actividades_evaluacion));
                    $usoNode->appendChild($seguimientoEvaluacion);
                }
            }
            $especialidadNode->appendChild($usoNode);
            $especialidadesNode->appendChild($especialidadNode);
        }
        
    }

    $accionFormativa->appendChild($especialidadesNode);

    // Obtener participantes relacionados
    $participantes = $DB->get_records('sepeservice_participante', ['ID_ACCION_FORMATIVA' => $accion->id]);

    $participantesNode = $responseDOM->createElement('PARTICIPANTES');
    if(!empty($participantes)){
        foreach($participantes as $participante){
            $participanteNode = $responseDOM->createElement('PARTICIPANTE');
            
            $idParticipante = $responseDOM->createElement('ID_PARTICIPANTE');
            $idParticipante->appendChild($responseDOM->createElement('TIPO_DOCUMENTO', $participante->tipo_documento));
            $idParticipante->appendChild($responseDOM->createElement('NUM_DOCUMENTO', $participante->num_documento));
            $idParticipante->appendChild($responseDOM->createElement('LETRA_NIF', $participante->letra_nif));
            $participanteNode->appendChild($idParticipante);

            // INDICADOR_COMPETENCIAS_CLAVE
            $participanteNode->appendChild($responseDOM->createElement('INDICADOR_COMPETENCIAS_CLAVE', $participante->indicador_competencias_clave));

            // CONTRATO_FORMACION
            $contratoFormacionNode = $responseDOM->createElement('CONTRATO_FORMACION');
            $contratoFormacion = $DB->get_record('sepeservice_contrato_formacion', ['id' => $participante->id_contrato_formacion]);
            if ($contratoFormacion) {
                $contratoFormacionNode->appendChild($responseDOM->createElement('ID_CONTRATO_CFA', $contratoFormacion->id_contrato_cfa));
                $contratoFormacionNode->appendChild($responseDOM->createElement('CIF_EMPRESA', $contratoFormacion->cif_empresa));

                // ID_TUTOR_EMPRESA
                if ($contratoFormacion->id_tutor_empresa) {
                    $tutorEmpresa = $DB->get_record('sepeservice_tutor_empresa', ['id' => $contratoFormacion->id_tutor_empresa]);
                    if ($tutorEmpresa) {
                        $idTutorEmpresaNode = $responseDOM->createElement('ID_TUTOR_EMPRESA');
                        $idTutorEmpresaNode->appendChild($responseDOM->createElement('TIPO_DOCUMENTO', $tutorEmpresa->tipo_documento));
                        $idTutorEmpresaNode->appendChild($responseDOM->createElement('NUM_DOCUMENTO', $tutorEmpresa->num_documento));
                        $idTutorEmpresaNode->appendChild($responseDOM->createElement('LETRA_NIF', $tutorEmpresa->letra_nif));
                        $contratoFormacionNode->appendChild($idTutorEmpresaNode);
                    }
                }

                // ID_TUTOR_FORMACION
                if ($contratoFormacion->id_tutor_formacion) {
                    $tutorFormacion = $DB->get_record('sepeservice_tutor_formador', ['id' => $contratoFormacion->id_tutor_formacion]);
                    if ($tutorFormacion) {
                        $idTutorFormacionNode = $responseDOM->createElement('ID_TUTOR_FORMACION');
                        $idTutorFormacionNode->appendChild($responseDOM->createElement('TIPO_DOCUMENTO', $tutorFormacion->tipo_documento));
                        $idTutorFormacionNode->appendChild($responseDOM->createElement('NUM_DOCUMENTO', $tutorFormacion->num_documento));
                        $idTutorFormacionNode->appendChild($responseDOM->createElement('LETRA_NIF', $tutorFormacion->letra_nif));
                        $contratoFormacionNode->appendChild($idTutorFormacionNode);
                    }
                }
            }
            $participanteNode->appendChild($contratoFormacionNode);

            // ESPECIALIDADES_PARTICIPANTE
            $especialidadesParticipanteNode = $responseDOM->createElement('ESPECIALIDADES_PARTICIPANTE');
            $especialidadesParticipante = $DB->get_records('sepeservice_especialidad_participante', ['ID_PARTICIPANTE' => $participante->id]);
            foreach ($especialidadesParticipante as $especialidadParticipante) {
                $especialidadNode = $responseDOM->createElement('ESPECIALIDAD');

                // ID_ESPECIALIDAD
                $especialidad = $DB->get_record('sepeservice_especialidad', ['id' => $especialidadParticipante->id_especialidad]);
                $idEspecialidadNode = $responseDOM->createElement('ID_ESPECIALIDAD');
                $idEspecialidadNode->appendChild($responseDOM->createElement('ORIGEN_ESPECIALIDAD', $especialidad->origen_especialidad));
                $idEspecialidadNode->appendChild($responseDOM->createElement('AREA_PROFESIONAL', $especialidad->area_profesional));
                $idEspecialidadNode->appendChild($responseDOM->createElement('CODIGO_ESPECIALIDAD', $especialidad->codigo_especialidad));
                $especialidadNode->appendChild($idEspecialidadNode);

                if($especialidadParticipante->fecha_alta)
                    $especialidadNode->appendChild($responseDOM->createElement('FECHA_ALTA', $especialidadParticipante->fecha_alta));
                if($especialidadParticipante->fecha_baja)
                    $especialidadNode->appendChild($responseDOM->createElement('FECHA_BAJA', $especialidadParticipante->fecha_baja));

                // TUTORIAS_PRESENCIALES
                $tutoriasPresencialesNode = $responseDOM->createElement('TUTORIAS_PRESENCIALES');
                $tutoriasPresenciales = $DB->get_records('sepeservice_tutoria_presencial', ['ID_ESPECIALIDAD_PARTICIPANTE' => $especialidadParticipante->id]);
                if(!empty($tutoriasPresenciales)){
                    foreach ($tutoriasPresenciales as $tutoriaPresencial) {
                        $tutoriaPresencialNode = $responseDOM->createElement('TUTORIA_PRESENCIAL');
                    
                        // Centro presencial asociado
                        if ($tutoriaPresencial->id_centro_presencial) {
                            $centroPresencial = $DB->get_record('sepeservice_centro_presencial', ['id' => $tutoriaPresencial->id_centro_presencial]);
                            if ($centroPresencial) {
                                $centroPresencialNode = $responseDOM->createElement('CENTRO_PRESENCIAL_TUTORIA');
                                $centroPresencialNode->appendChild($responseDOM->createElement('ORIGEN_CENTRO', $centroPresencial->origen_centro));
                                $centroPresencialNode->appendChild($responseDOM->createElement('CODIGO_CENTRO', $centroPresencial->codigo_centro));
                                $tutoriaPresencialNode->appendChild($centroPresencialNode);
                            }
                        }
                    
                        // Fechas de la tutoría
                        $tutoriaPresencialNode->appendChild($responseDOM->createElement('FECHA_INICIO', $tutoriaPresencial->fecha_inicio));
                        $tutoriaPresencialNode->appendChild($responseDOM->createElement('FECHA_FIN', $tutoriaPresencial->fecha_fin));
                    
                        // Añadir la tutoría al nodo principal
                        $tutoriasPresencialesNode->appendChild($tutoriaPresencialNode);
                    }
                }else{
                    $tutoriasPresencialesNode->setAttribute('xsi:nil', 'true');
                }
                
                $especialidadNode->appendChild($tutoriasPresencialesNode);

                // Recuperar EVALUACION_FINAL
                $evaluacionFinalNode = $responseDOM->createElement('EVALUACION_FINAL');
                $evaluacionFinal = $DB->get_record('sepeservice_evaluacion_final', ['ID_ESPECIALIDAD_PARTICIPANTE' => $especialidadParticipante->id]);

                if ($evaluacionFinal) {
                    if ($evaluacionFinal->id_centro_presencial) {
                        $centroPresencial = $DB->get_record('sepeservice_centro_presencial', ['id' => $evaluacionFinal->id_centro_presencial]);
                        if ($centroPresencial) {
                            $centroPresencialNode = $responseDOM->createElement('CENTRO_PRESENCIAL_EVALUACION');
                            $centroPresencialNode->appendChild($responseDOM->createElement('ORIGEN_CENTRO', $centroPresencial->origen_centro));
                            $centroPresencialNode->appendChild($responseDOM->createElement('CODIGO_CENTRO', $centroPresencial->codigo_centro));
                            $evaluacionFinalNode->appendChild($centroPresencialNode);
                        }
                    }

                    if($evaluacionFinal->fecha_inicio != '')
                        $evaluacionFinalNode->appendChild($responseDOM->createElement('FECHA_INICIO', $evaluacionFinal->fecha_inicio));
                    if($evaluacionFinal->fecha_fin != '')
                        $evaluacionFinalNode->appendChild($responseDOM->createElement('FECHA_FIN', $evaluacionFinal->fecha_fin));
                }
                $especialidadNode->appendChild($evaluacionFinalNode);


                // Recuperar RESULTADOS
                $resultadosNode = $responseDOM->createElement('RESULTADOS');
                $resultados = $DB->get_record('sepeservice_resultados', ['ID_ESPECIALIDAD_PARTICIPANTE' => $especialidadParticipante->id]);

                if ($resultados) {
                    if (!empty($resultados->resultado_final)) {
                        $resultadosNode->appendChild($responseDOM->createElement('RESULTADO_FINAL', $resultados->resultado_final));
                    }
                    if (!empty($resultados->calificacion_final)) {
                        $resultadosNode->appendChild($responseDOM->createElement('CALIFICACION_FINAL', $resultados->calificacion_final));
                    }
                    if (!empty($resultados->puntuacion_final)) {
                        $resultadosNode->appendChild($responseDOM->createElement('PUNTUACION_FINAL', $resultados->puntuacion_final));
                    }
                }
                $especialidadNode->appendChild($resultadosNode);


                $especialidadesParticipanteNode->appendChild($especialidadNode);
            }
            $participanteNode->appendChild($especialidadesParticipanteNode);

            $participantesNode->appendChild($participanteNode);

        }
    }else{
        $participantesNode->setAttribute('xsi:nil', 'true');
    }

    $accionFormativa->appendChild($participantesNode);
    

    return $responseDOM->saveXML();
}




// Función para obtener nodos por ruta XPath
function getNodesByPath($dom, $xpathQuery) {
    $xpath = new DOMXPath($dom);
    return $xpath->query($xpathQuery);
}

// Función auxiliar para agregar múltiples nodos hijo
function appendChildNodes($parent, $sourceNode, $responseDOM, $childTags) {
    foreach ($childTags as $tag) {
        $value = getValueFromNode($sourceNode, $tag);
        if ($value !== '') {
            $parent->appendChild($responseDOM->createElement($tag, $value));
        }
    }
}

function getValueFromRequest($dom, $tagName) {
$elements = $dom->getElementsByTagName($tagName);
return $elements->length > 0 ? $elements->item(0)->nodeValue : '';
}

function getValueFromNode($node, $tagName) {
$elements = $node->getElementsByTagName($tagName);
return $elements->length > 0 ? $elements->item(0)->nodeValue : '';
}