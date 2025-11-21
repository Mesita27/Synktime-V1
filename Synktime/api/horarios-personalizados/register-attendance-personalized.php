<?php

// Configurar zona horaria de Bogotá, Colombia
require_once __DIR__ . '/../../config/timezone.php';
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Configuración de base de datos desde config
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../utils/justificaciones_utils.php';
require_once __DIR__ . '/../../utils/attendance_status_utils.php';

// Verificar método
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}
try {
    // Leer datos JSON del input
    $jsonInput = file_get_contents('php://input');
    if ($jsonInput) {
        $input = json_decode($jsonInput, true);
    } else {
        $input = $_POST;
    }
    
    // Para testing, usar datos globales si están disponibles
    if (isset($GLOBALS['test_input'])) {
        $input = json_decode($GLOBALS['test_input'], true);
    }
    
    $idEmpleado = $input['id_empleado'] ?? null;
    $fecha = $input['fecha'] ?? getBogotaDate();
    $horaOriginal = $input['hora'] ?? getBogotaTime();
    $hora = formatTimeForAttendance($horaOriginal);
    $metodo = $input['metodo'] ?? 'manual';
    $observaciones = $input['observaciones'] ?? '';
    $foto = $input['foto'] ?? null;
    
    if (!$idEmpleado) {
        echo json_encode(['success' => false, 'message' => 'ID de empleado requerido']);
        exit;
    }
    
    // Verificar si el empleado existe
    $checkEmployeeQuery = "SELECT ID_EMPLEADO, NOMBRE, APELLIDO FROM empleado WHERE ID_EMPLEADO = ?";
    $stmt = $conn->prepare($checkEmployeeQuery);
    $stmt->execute([$idEmpleado]);
    $employee = $stmt->fetch();
    
    if (!$employee) {
        echo json_encode(['success' => false, 'message' => 'Empleado no encontrado']);
        exit;
    }
    
    // Obtener día de la semana (1=Lunes, 7=Domingo)
    $diaSemana = date('N');
    
    // **CORREGIDO: Buscar SOLO horarios personalizados basándose en fechas de vigencia**
    $personalizedScheduleQuery = "
        SELECT 
            ehp.ID_EMPLEADO_HORARIO,
            ehp.ID_EMPLEADO,
            ehp.ID_DIA,
            ehp.HORA_ENTRADA,
            ehp.HORA_SALIDA,
            ehp.TOLERANCIA,
            ehp.NOMBRE_TURNO,
            ehp.ORDEN_TURNO
        FROM empleado_horario_personalizado ehp
        WHERE ehp.ID_EMPLEADO = ? 
        AND ehp.ID_DIA = ? 
        AND ehp.FECHA_DESDE <= ?
        AND (ehp.FECHA_HASTA IS NULL OR ehp.FECHA_HASTA >= ?)
        AND ehp.ACTIVO = 'S'
        ORDER BY ehp.FECHA_DESDE DESC, ehp.ORDEN_TURNO
    ";
    
    $stmt = $conn->prepare($personalizedScheduleQuery);
    $stmt->execute([$idEmpleado, $diaSemana, $fecha, $fecha]);
    $personalizedScheduleResult = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $horarioInfo = null;
    
    if (count($personalizedScheduleResult) > 0) {
        $horariosDisponibles = $personalizedScheduleResult;

        // Filtrar turnos justificados para el día
        $filtradoJustificaciones = filtrarHorariosPorJustificaciones($idEmpleado, $fecha, $horariosDisponibles, $conn);
        $horariosDisponibles = $filtradoJustificaciones['horarios_disponibles'];
        $turnosJustificados = $filtradoJustificaciones['turnos_justificados'] ?? [];
        $todosJustificados = $filtradoJustificaciones['todos_justificados'] ?? false;

        if (empty($horariosDisponibles)) {
            echo json_encode([
                'success' => false,
                'message' => 'Todos los turnos personalizados para este día están justificados. No se requiere registrar asistencia.'
            ]);
            exit;
        }

        // Ordenar por ORDEN_TURNO y hora de entrada para garantizar secuencia
        usort($horariosDisponibles, function($a, $b) {
            $ordenA = (int)($a['ORDEN_TURNO'] ?? 0);
            $ordenB = (int)($b['ORDEN_TURNO'] ?? 0);
            if ($ordenA === $ordenB) {
                return strcmp($a['HORA_ENTRADA'], $b['HORA_ENTRADA']);
            }
            return $ordenA <=> $ordenB;
        });

        // Mapear asistencias existentes por turno (entrada/salida)
        $attendanceStatus = [];
        $scheduleIds = array_column($horariosDisponibles, 'ID_EMPLEADO_HORARIO');

        if (!empty($scheduleIds)) {
            $placeholders = implode(',', array_fill(0, count($scheduleIds), '?'));
            $attendanceQuery = "
                SELECT ID_EMPLEADO_HORARIO, TIPO
                FROM asistencia
                WHERE ID_EMPLEADO = ?
                AND DATE(FECHA) = ?
                AND ID_EMPLEADO_HORARIO IS NOT NULL
                AND ID_EMPLEADO_HORARIO IN ($placeholders)
            ";

            $attendanceParams = array_merge([$idEmpleado, $fecha], $scheduleIds);
            $stmtAttendance = $conn->prepare($attendanceQuery);
            $stmtAttendance->execute($attendanceParams);
            $attendanceRows = $stmtAttendance->fetchAll(PDO::FETCH_ASSOC);

            foreach ($attendanceRows as $row) {
                $turnoId = (int)$row['ID_EMPLEADO_HORARIO'];
                if (!$turnoId) {
                    continue;
                }

                if (!isset($attendanceStatus[$turnoId])) {
                    $attendanceStatus[$turnoId] = ['entrada' => false, 'salida' => false];
                }

                $tipoRegistroExistente = strtoupper($row['TIPO']);
                if ($tipoRegistroExistente === 'ENTRADA') {
                    $attendanceStatus[$turnoId]['entrada'] = true;
                } elseif ($tipoRegistroExistente === 'SALIDA') {
                    $attendanceStatus[$turnoId]['salida'] = true;
                }
            }
        }

        $horaActualTimestamp = strtotime($fecha . ' ' . $hora);
        if ($horaActualTimestamp === false) {
            $horaActualTimestamp = time();
        }

        // Identificar turnos con registros pendientes
        $horariosPendientes = [];
        foreach ($horariosDisponibles as $horario) {
            $turnoId = (int)($horario['ID_EMPLEADO_HORARIO'] ?? 0);
            $status = $attendanceStatus[$turnoId] ?? ['entrada' => false, 'salida' => false];

            if (!$status['entrada'] || !$status['salida']) {
                $horariosPendientes[] = $horario;
            }
        }

        if (empty($horariosPendientes)) {
            $mensaje = $todosJustificados
                ? 'Todos los turnos personalizados para este día están justificados. No se requiere registrar asistencia.'
                : 'Ya se registraron todas las entradas y salidas para los turnos disponibles de hoy.';

            echo json_encode([
                'success' => false,
                'message' => $mensaje
            ]);
            exit;
        }

        $entradaPendiente = null;
        $salidaPendiente = null;

        foreach ($horariosPendientes as $horario) {
            $turnoId = (int)($horario['ID_EMPLEADO_HORARIO'] ?? 0);
            $status = $attendanceStatus[$turnoId] ?? ['entrada' => false, 'salida' => false];

            if (!$status['entrada'] && $entradaPendiente === null) {
                $entradaPendiente = $horario;
            }

            if ($status['entrada'] && !$status['salida'] && $salidaPendiente === null) {
                $salidaPendiente = $horario;
            }
        }

        $obtenerTimestampProgramadoCercano = static function (array $horario, string $tipoRegistro, string $fechaReferencia, int $timestampActual) {
            $horaProgramada = $tipoRegistro === 'ENTRADA' ? ($horario['HORA_ENTRADA'] ?? null) : ($horario['HORA_SALIDA'] ?? null);
            if (!$horaProgramada) {
                return false;
            }

            $fechasCandidatas = [
                date('Y-m-d', strtotime($fechaReferencia . ' -1 day')),
                $fechaReferencia,
                date('Y-m-d', strtotime($fechaReferencia . ' +1 day'))
            ];

            $mejorTimestamp = false;
            $menorDiferencia = PHP_INT_MAX;

            foreach ($fechasCandidatas as $fechaBase) {
                $timestampCandidato = strtotime($fechaBase . ' ' . $horaProgramada);
                if ($timestampCandidato === false) {
                    continue;
                }

                $diferencia = abs($timestampActual - $timestampCandidato);
                if ($diferencia < $menorDiferencia) {
                    $menorDiferencia = $diferencia;
                    $mejorTimestamp = $timestampCandidato;
                }
            }

            return $mejorTimestamp;
        };

        $tipoRegistro = null;
        $horarioSeleccionado = null;

        // Regla de negocio: siempre cerrar entradas abiertas antes de permitir nuevas entradas
        if ($salidaPendiente) {
            $tipoRegistro = 'SALIDA';
            $horarioSeleccionado = $salidaPendiente;
        } elseif ($entradaPendiente) {
            $tipoRegistro = 'ENTRADA';
            $horarioSeleccionado = $entradaPendiente;
        }

        if (!$horarioSeleccionado) {
            echo json_encode([
                'success' => false,
                'message' => 'No hay turnos pendientes de registro para hoy.'
            ]);
            exit;
        }

        // Validar límite de entradas considerando justificaciones
        if ($tipoRegistro === 'ENTRADA') {
            $sqlContarEntradas = "
                SELECT COUNT(DISTINCT a.ID_EMPLEADO_HORARIO) as entradas_registradas
                FROM asistencia a
                JOIN empleado_horario_personalizado ehp ON a.ID_EMPLEADO_HORARIO = ehp.ID_EMPLEADO_HORARIO
                WHERE a.ID_EMPLEADO = ?
                AND a.FECHA = ?
                AND a.TIPO = 'ENTRADA'
                AND ehp.ID_EMPLEADO_HORARIO NOT IN (
                    SELECT turno_id FROM justificaciones
                    WHERE empleado_id = ?
                    AND fecha_falta = ?
                    AND (justificar_todos_turnos = 1 OR turno_id IS NOT NULL)
                )
            ";

            $stmtContar = $conn->prepare($sqlContarEntradas);
            $stmtContar->execute([$idEmpleado, $fecha, $idEmpleado, $fecha]);
            $resultadoContar = $stmtContar->fetch(PDO::FETCH_ASSOC);

            $entradasRegistradas = (int)($resultadoContar['entradas_registradas'] ?? 0);
            $totalTurnosProgramados = count($horariosDisponibles);

            if ($totalTurnosProgramados > 0 && $entradasRegistradas >= $totalTurnosProgramados) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Ya se han registrado entradas para todos los turnos disponibles hoy (' . $entradasRegistradas . ' de ' . $totalTurnosProgramados . ' turnos).'
                ]);
                exit;
            }
        }

        $horarioInfo = $horarioSeleccionado;
        $horarioIdSeleccionado = (int)($horarioInfo['ID_EMPLEADO_HORARIO'] ?? 0);

        // Verificar duplicados según el tipo determinado, respetando el turno específico
        $checkDuplicateQuery = "
            SELECT COUNT(*) as count
            FROM asistencia
            WHERE ID_EMPLEADO = ? AND FECHA = ? AND TIPO = ?
        ";

        $duplicateParams = [$idEmpleado, $fecha, $tipoRegistro];

        if ($horarioIdSeleccionado > 0) {
            $checkDuplicateQuery .= " AND ID_EMPLEADO_HORARIO = ?";
            $duplicateParams[] = $horarioIdSeleccionado;
        }

        $stmt = $conn->prepare($checkDuplicateQuery);
        $stmt->execute($duplicateParams);
        $duplicateResult = $stmt->fetch(PDO::FETCH_ASSOC);

        if (($duplicateResult['count'] ?? 0) > 0) {
            $horaExistente = formatTimeForAttendance($horaOriginal);
            $tipoTexto = strtolower($tipoRegistro);
            echo json_encode([
                'success' => false,
                'message' => "Ya existe un registro de $tipoTexto para este turno en la fecha seleccionada (" . $horaExistente . ")"
            ]);
            exit;
        }

        // Para horarios personalizados, usar NULL en ID_HORARIO 
        // ya que este campo solo acepta IDs de la tabla horario
        $idHorario = null;
        
        // **CORREGIDO: Agregar información del horario personalizado a observaciones**
        $observaciones .= " [Horario Personalizado: {$horarioInfo['NOMBRE_TURNO']} - Turno {$horarioInfo['ORDEN_TURNO']} - ID_EMP_HOR:{$horarioInfo['ID_EMPLEADO_HORARIO']}]";
    } else {
        // **CAMBIO FUNDAMENTAL: Solo horarios personalizados, no tradicionales**
        echo json_encode([
            'success' => false, 
            'message' => 'No se encontraron horarios personalizados asignados para el empleado en este día'
        ]);
        exit;
    }
    
    // Calcular tardanza utilizando la tolerancia configurada
    $tardanza = 'N';
    $detalleTardanza = '';
    if ($horarioInfo) {
    $toleranciaMinutos = normalizarToleranciaMinutos($horarioInfo['TOLERANCIA'] ?? 0);
        $horaEntradaProgramada = $horarioInfo['HORA_ENTRADA'] ?? null;
        $horaSalidaProgramada = $horarioInfo['HORA_SALIDA'] ?? null;

        $timestampActual = strtotime($fecha . ' ' . $hora);

        if ($timestampActual !== false) {
            $calcularTimestampProgramado = static function (string $horaProgramada) use ($fecha, $timestampActual) {
                $fechasCandidatas = [
                    $fecha,
                    date('Y-m-d', strtotime($fecha . ' -1 day')),
                    date('Y-m-d', strtotime($fecha . ' +1 day'))
                ];

                $mejorTimestamp = false;
                $menorDiferencia = PHP_INT_MAX;

                foreach ($fechasCandidatas as $fechaBase) {
                    $timestampCandidato = strtotime($fechaBase . ' ' . $horaProgramada);
                    if ($timestampCandidato === false) {
                        continue;
                    }

                    $diferenciaAbsoluta = abs($timestampActual - $timestampCandidato);
                    if ($diferenciaAbsoluta < $menorDiferencia) {
                        $menorDiferencia = $diferenciaAbsoluta;
                        $mejorTimestamp = $timestampCandidato;
                    }
                }

                return $mejorTimestamp;
            };

            if ($tipoRegistro === 'ENTRADA' && $horaEntradaProgramada) {
                $timestampProgramado = $calcularTimestampProgramado($horaEntradaProgramada);

                if ($timestampProgramado !== false) {
                    $diferenciaMinutos = ($timestampActual - $timestampProgramado) / 60;

                    if ($diferenciaMinutos > $toleranciaMinutos) {
                        $tardanza = 'S';
                        $detalleTardanza = 'Tardanza de ' . round($diferenciaMinutos, 2) . ' min';
                    } elseif ($diferenciaMinutos < 0) {
                        $detalleTardanza = 'Entrada adelantada ' . round(abs($diferenciaMinutos), 2) . ' min';
                    } elseif ($diferenciaMinutos > 0) {
                        $detalleTardanza = 'Entrada tardía dentro de tolerancia (' . round($diferenciaMinutos, 2) . ' min)';
                    }
                }
            } elseif ($tipoRegistro === 'SALIDA' && $horaSalidaProgramada) {
                $timestampProgramado = $calcularTimestampProgramado($horaSalidaProgramada);

                if ($timestampProgramado !== false) {
                    $diferenciaMinutos = ($timestampActual - $timestampProgramado) / 60;

                    if (abs($diferenciaMinutos) > $toleranciaMinutos) {
                        $tardanza = 'S';
                        $detalleTardanza = ($diferenciaMinutos > 0 ? 'Salida tardía' : 'Salida anticipada') . ' de ' . round(abs($diferenciaMinutos), 2) . ' min';
                    } elseif ($diferenciaMinutos !== 0.0) {
                        $detalleTardanza = ($diferenciaMinutos > 0 ? 'Salida tardía' : 'Salida anticipada') . ' dentro de tolerancia (' . round(abs($diferenciaMinutos), 2) . ' min)';
                    }
                }
            }
        }
    }

    if ($detalleTardanza !== '') {
        $observaciones .= ' [' . $detalleTardanza . ']';
    }
    
    // Procesar foto si se envió
    $fotoPath = null;
    $fotoPathCompleto = null;
    if ($foto && strpos($foto, 'data:image') === 0) {
        // Crear directorio si no existe
        $uploadDir = __DIR__ . '/../../uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Generar nombre único para la foto
        $timestamp = date('Y-m-d_H-i-s');
        $fotoFileName = "attendance_{$idEmpleado}_{$timestamp}.jpg";
        $fullPath = $uploadDir . $fotoFileName;
        
        // Decodificar y guardar la imagen
        $imageData = explode(',', $foto)[1];
        $decodedImage = base64_decode($imageData);
        
        if (file_put_contents($fullPath, $decodedImage)) {
            $fotoPath = $fotoFileName; // Solo el nombre del archivo para BD
            $fotoPathCompleto = $fullPath; // Ruta completa para respuesta
        } else {
            $fotoPath = null;
            $fotoPathCompleto = null;
        }
    }
    
    // **CORREGIDO: Insertar en la tabla de asistencia con ID_EMPLEADO_HORARIO**
    $insertQuery = "
        INSERT INTO asistencia (
            ID_EMPLEADO, 
            FECHA, 
            HORA, 
            TIPO, 
            TARDANZA, 
            OBSERVACION, 
            VERIFICATION_METHOD, 
            REGISTRO_MANUAL, 
            ID_HORARIO,
            ID_EMPLEADO_HORARIO,
            FOTO
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ";
    
    $stmt = $conn->prepare($insertQuery);
    
    $registroManual = ($metodo === 'manual') ? 'S' : 'N';
    $verificationMethod = $metodo; // facial, manual, biometrico, etc.
    
    $result = $stmt->execute([
        $idEmpleado, 
        $fecha, 
        $hora, 
        $tipoRegistro, 
        $tardanza,
        $observaciones, 
        $verificationMethod,
        $registroManual,
        null, // **ID_HORARIO: siempre NULL - no usamos horarios tradicionales**
        $horarioInfo['ID_EMPLEADO_HORARIO'], // **ID_EMPLEADO_HORARIO: del horario personalizado**
        $fotoPath
    ]);
    
    if ($result) {
        $insertId = $conn->lastInsertId();
        $responseData = [
            'id' => $insertId,
            'empleado' => $employee['NOMBRE'] . ' ' . $employee['APELLIDO'],
            'fecha' => $fecha,
            'hora' => $hora,
            'tipo' => $tipoRegistro,
            'tardanza' => $tardanza,
            'metodo' => $verificationMethod,
            'horario_personalizado' => true, // **SIEMPRE true - solo horarios personalizados**
            'horario_info' => $horarioInfo,
            'id_horario' => null, // **SIEMPRE NULL - no usamos horarios tradicionales**
            'id_empleado_horario' => $horarioInfo['ID_EMPLEADO_HORARIO'],
            'observaciones' => $observaciones,
            'foto_guardada' => !is_null($fotoPath),
            'foto_path_relativo' => $fotoPath,
            'foto_path_completo' => $fotoPathCompleto,
            'dia_semana' => $diaSemana
        ];

        echo json_encode([
            'success' => true, 
            'message' => 'Asistencia registrada exitosamente',
            'data' => $responseData,
            'registro' => $responseData
        ]);
    } else {
        throw new Exception('Error al insertar en la base de datos');
    }
    
} catch(Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Error del servidor: ' . $e->getMessage()
    ]);
    error_log("Error en register-attendance: " . $e->getMessage());
}
?>