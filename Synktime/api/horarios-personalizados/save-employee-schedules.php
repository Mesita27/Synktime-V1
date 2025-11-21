<?php
require_once __DIR__ . '/../../auth/session.php';
requireAuth();
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');

/**
 * Verifica si dos horarios se superponen, considerando turnos nocturnos
 * @param string $horaEntrada1 Hora de entrada del primer horario (HH:MM:SS)
 * @param string $horaSalida1 Hora de salida del primer horario (HH:MM:SS)
 * @param string $horaEntrada2 Hora de entrada del segundo horario (HH:MM:SS)
 * @param string $horaSalida2 Hora de salida del segundo horario (HH:MM:SS)
 * @return bool True si hay superposición
 */
function horariosSeSuperponen($horaEntrada1, $horaSalida1, $horaEntrada2, $horaSalida2) {
    // Convertir horas a timestamps del mismo día base
    $baseDate = "1970-01-01 ";
    $inicio1 = strtotime($baseDate . $horaEntrada1);
    $fin1 = strtotime($baseDate . $horaSalida1);
    $inicio2 = strtotime($baseDate . $horaEntrada2);
    $fin2 = strtotime($baseDate . $horaSalida2);

    // Ajustar si los horarios terminan al día siguiente (turnos nocturnos)
    if ($fin1 < $inicio1) {
        $fin1 += 86400; // Añadir 24 horas en segundos
    }
    if ($fin2 < $inicio2) {
        $fin2 += 86400;
    }

    // Verificar superposición: un horario termina después de que el otro comienza
    // y el otro termina después de que el primero comienza
    return ($inicio1 < $fin2 && $fin1 > $inicio2);
}

// Verificar método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

try {
    // Obtener información del usuario actual
    $currentUser = getCurrentUser();
    $empresaId = $currentUser['id_empresa'] ?? null;
    
    if (!$empresaId) {
        echo json_encode(['success' => false, 'message' => 'Sesión inválida - empresa no encontrada']);
        exit;
    }

    // Obtener datos del cuerpo de la petición
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data) {
        echo json_encode(['success' => false, 'message' => 'Datos JSON inválidos']);
        exit;
    }

    // Validar datos requeridos
    $requiredFields = ['id_empleado', 'fecha_desde', 'horarios'];
    foreach ($requiredFields as $field) {
        if (!isset($data[$field]) || empty($data[$field])) {
            echo json_encode(['success' => false, 'message' => "Campo requerido: $field"]);
            exit;
        }
    }

    $idEmpleado = (int)$data['id_empleado'];
    $fechaDesde = $data['fecha_desde'];
    $fechaHasta = !empty($data['fecha_hasta']) ? $data['fecha_hasta'] : null;
    $horarios = $data['horarios'];
    $replaceExisting = $data['replace_existing'] ?? false;

    // Validar formato de fechas
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaDesde)) {
        echo json_encode(['success' => false, 'message' => 'Formato de fecha desde inválido']);
        exit;
    }

    if ($fechaHasta && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaHasta)) {
        echo json_encode(['success' => false, 'message' => 'Formato de fecha hasta inválido']);
        exit;
    }

    if ($fechaHasta && $fechaHasta <= $fechaDesde) {
        echo json_encode(['success' => false, 'message' => 'La fecha hasta debe ser posterior a la fecha desde']);
        exit;
    }

    // Verificar que el empleado pertenece a la empresa del usuario
    $sqlVerifyEmployee = "
        SELECT e.ID_EMPLEADO
        FROM empleado e
        JOIN establecimiento est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
        JOIN sede s ON est.ID_SEDE = s.ID_SEDE
        WHERE e.ID_EMPLEADO = :id_empleado 
        AND s.ID_EMPRESA = :empresa_id 
        AND e.ACTIVO = 'S'
    ";

    $stmt = $pdo->prepare($sqlVerifyEmployee);
    $stmt->bindValue(':id_empleado', $idEmpleado);
    $stmt->bindValue(':empresa_id', $empresaId);
    $stmt->execute();

    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Empleado no encontrado o sin permisos']);
        exit;
    }

    // Validar estructura de horarios
    if (!is_array($horarios)) {
        echo json_encode(['success' => false, 'message' => 'Los horarios deben ser un array']);
        exit;
    }

    $horariosValidados = [];
    foreach ($horarios as $horario) {
        // Validar campos requeridos del horario
        $requiredScheduleFields = ['id_dia', 'hora_entrada', 'hora_salida', 'nombre_turno'];
        foreach ($requiredScheduleFields as $field) {
            if (!isset($horario[$field]) || $horario[$field] === '') {
                echo json_encode(['success' => false, 'message' => "Campo requerido en horario: $field"]);
                exit;
            }
        }

        $idDia = (int)$horario['id_dia'];
        $horaEntrada = trim($horario['hora_entrada']);
        $horaSalida = trim($horario['hora_salida']);
        $nombreTurno = trim($horario['nombre_turno']);
        $tolerancia = isset($horario['tolerancia']) ? (int)$horario['tolerancia'] : 15;
        $ordenTurno = isset($horario['orden_turno']) ? (int)$horario['orden_turno'] : 1;
        $observaciones = isset($horario['observaciones']) ? trim($horario['observaciones']) : null;
        $idEmpleadoHorario = isset($horario['id_empleado_horario']) && $horario['id_empleado_horario'] !== ''
            ? (int)$horario['id_empleado_horario']
            : null;

        // Validaciones mejoradas
        if ($idDia < 1 || $idDia > 7) {
            echo json_encode(['success' => false, 'message' => 'ID de día inválido (debe ser 1-7)']);
            exit;
        }

        // Validación mejorada de formato de hora (permite formatos HH:MM y H:MM)
        // También acepta formatos con segundos HH:MM:SS
        if (!preg_match('/^([0-1]?[0-9]|2[0-3]):[0-5][0-9](:[0-5][0-9])?$/', $horaEntrada)) {
            echo json_encode(['success' => false, 'message' => "Formato de hora de entrada inválido: '$horaEntrada' (debe ser HH:MM)"]);
            exit;
        }
        
        if (!preg_match('/^([0-1]?[0-9]|2[0-3]):[0-5][0-9](:[0-5][0-9])?$/', $horaSalida)) {
            echo json_encode(['success' => false, 'message' => "Formato de hora de salida inválido: '$horaSalida' (debe ser HH:MM)"]);
            exit;
        }

        // Normalizar formato a HH:MM (remover segundos si están presentes)
        $horaEntradaParts = explode(':', $horaEntrada);
        $horaEntrada = sprintf('%02d:%02d', 
            intval($horaEntradaParts[0]), 
            intval($horaEntradaParts[1])
        );
        
        $horaSalidaParts = explode(':', $horaSalida);
        $horaSalida = sprintf('%02d:%02d', 
            intval($horaSalidaParts[0]), 
            intval($horaSalidaParts[1])
        );

        // 🌙 NUEVA LÓGICA: Detectar turno nocturno
        $esTurnoNocturno = $horaSalida <= $horaEntrada ? 'S' : 'N';
        
        // Para turnos nocturnos, la validación es diferente
        if ($esTurnoNocturno === 'N' && $horaEntrada >= $horaSalida) {
            echo json_encode(['success' => false, 'message' => 'Para turnos diurnos, la hora de entrada debe ser anterior a la de salida. Para turnos nocturnos (que cruzan medianoche), la hora de salida puede ser menor que la de entrada.']);
            exit;
        }

        if ($tolerancia < 0 || $tolerancia > 120) {
            echo json_encode(['success' => false, 'message' => 'Tolerancia inválida (debe ser 0-120 minutos)']);
            exit;
        }

        if (strlen($nombreTurno) < 1 || strlen($nombreTurno) > 50) {
            echo json_encode(['success' => false, 'message' => 'Nombre del turno inválido (1-50 caracteres)']);
            exit;
        }

        $horariosValidados[] = [
            'id_empleado_horario' => $idEmpleadoHorario,
            'id_dia' => $idDia,
            'hora_entrada' => $horaEntrada,
            'hora_salida' => $horaSalida,
            'nombre_turno' => $nombreTurno,
            'tolerancia' => $tolerancia,
            'orden_turno' => $ordenTurno,
            'observaciones' => $observaciones,
            'es_turno_nocturno' => $esTurnoNocturno,  // 🌙 Campo nocturno
            // ✨ NUEVA FUNCIONALIDAD: Vigencia individual por horario
            'fecha_desde_individual' => isset($horario['fecha_desde']) ? $horario['fecha_desde'] : $fechaDesde,
            'fecha_hasta_individual' => isset($horario['fecha_hasta']) ? $horario['fecha_hasta'] : $fechaHasta
        ];
    }

    if (!empty($horariosValidados)) {
        usort($horariosValidados, function ($a, $b) {
            if ($a['id_dia'] !== $b['id_dia']) {
                return $a['id_dia'] <=> $b['id_dia'];
            }

            $fechaDesdeA = $a['fecha_desde_individual'] ?? '0000-00-00';
            $fechaDesdeB = $b['fecha_desde_individual'] ?? '0000-00-00';
            if ($fechaDesdeA !== $fechaDesdeB) {
                return strcmp($fechaDesdeA, $fechaDesdeB);
            }

            $fechaHastaA = $a['fecha_hasta_individual'] ?? '9999-12-31';
            $fechaHastaB = $b['fecha_hasta_individual'] ?? '9999-12-31';
            if ($fechaHastaA !== $fechaHastaB) {
                return strcmp($fechaHastaA, $fechaHastaB);
            }

            if ($a['hora_entrada'] !== $b['hora_entrada']) {
                return strcmp($a['hora_entrada'], $b['hora_entrada']);
            }

            return ($a['orden_turno'] ?? 0) <=> ($b['orden_turno'] ?? 0);
        });

        $consecutivos = [];
        foreach ($horariosValidados as &$horarioOrdenado) {
            $claveVigencia = $horarioOrdenado['id_dia'] . '|' . ($horarioOrdenado['fecha_desde_individual'] ?? '0000-00-00') . '|' . ($horarioOrdenado['fecha_hasta_individual'] ?? '9999-12-31');
            $consecutivos[$claveVigencia] = ($consecutivos[$claveVigencia] ?? 0) + 1;
            $horarioOrdenado['orden_turno'] = $consecutivos[$claveVigencia];
        }
        unset($horarioOrdenado);
    }

    // Iniciar transacción
    $pdo->beginTransaction();

    try {
        // 🚫 DESACTIVADO: No hacer reemplazo masivo con vigencias individuales
        // Los horarios se actualizan individualmente por id_empleado_horario o se insertan nuevos
        // if ($replaceExisting) {
        //     $sqlDeactivate = "
        //         UPDATE empleado_horario_personalizado
        //         SET ACTIVO = 'N', UPDATED_AT = NOW()
        //         WHERE ID_EMPLEADO = :id_empleado
        //         AND ACTIVO = 'S'
        //         AND (FECHA_HASTA IS NULL OR FECHA_HASTA >= CURDATE())
        //     ";
        //     $stmtDeactivate = $pdo->prepare($sqlDeactivate);
        //     $stmtDeactivate->bindValue(':id_empleado', $idEmpleado);
        //     $stmtDeactivate->execute();
        // }

        // Verificar conflictos de horarios para el mismo día/turno
        $insertedSchedules = [];
        foreach ($horariosValidados as $horario) {
            $idEmpleadoHorario = $horario['id_empleado_horario'] ?? null;
            $existingRecord = null;

            if ($idEmpleadoHorario) {
                $sqlCheckById = "
                    SELECT ID_EMPLEADO_HORARIO, ACTIVO 
                    FROM empleado_horario_personalizado 
                    WHERE ID_EMPLEADO_HORARIO = :id_empleado_horario 
                    AND ID_EMPLEADO = :id_empleado
                ";

                $stmtCheckById = $pdo->prepare($sqlCheckById);
                $stmtCheckById->bindValue(':id_empleado_horario', $idEmpleadoHorario);
                $stmtCheckById->bindValue(':id_empleado', $idEmpleado);
                $stmtCheckById->execute();
                $existingRecord = $stmtCheckById->fetch();
            }

            if (!$existingRecord) {
                // Solo verificar conflictos con registros ACTIVOS (no inactivos)
                $sqlCheckConflict = "
                    SELECT ID_EMPLEADO_HORARIO, ACTIVO, FECHA_DESDE
                    FROM empleado_horario_personalizado
                    WHERE ID_EMPLEADO = :id_empleado
                    AND ID_DIA = :id_dia
                    AND ORDEN_TURNO = :orden_turno
                    AND FECHA_DESDE = :fecha_desde_individual
                    AND ACTIVO = 'S'
                ";

                $stmtCheck = $pdo->prepare($sqlCheckConflict);
                $stmtCheck->bindValue(':id_empleado', $idEmpleado);
                $stmtCheck->bindValue(':id_dia', $horario['id_dia']);
                $stmtCheck->bindValue(':orden_turno', $horario['orden_turno']);
                $stmtCheck->bindValue(':fecha_desde_individual', $horario['fecha_desde_individual']);
                $stmtCheck->execute();
                $existingRecord = $stmtCheck->fetch();
            }

            // Verificar conflictos de horarios activos superpuestos en el mismo día y rangos de fecha
            // Solo verificar contra registros ACTIVOS con diferentes ORDEN_TURNO
            $sqlCheckOverlap = "
                SELECT ID_EMPLEADO_HORARIO, NOMBRE_TURNO, HORA_ENTRADA, HORA_SALIDA, FECHA_DESDE, FECHA_HASTA, ES_TURNO_NOCTURNO
                FROM empleado_horario_personalizado
                WHERE ID_EMPLEADO = :id_empleado
                AND ID_DIA = :id_dia
                AND ACTIVO = 'S'
                AND (:id_empleado_horario IS NULL OR ID_EMPLEADO_HORARIO != :id_empleado_horario)
                AND (
                    -- Verificar superposición REAL de rangos de fechas
                    -- Dos rangos se superponen si: inicio_A <= fin_B AND fin_A >= inicio_B
                    -- Considerando NULL como fecha futura infinita
                    :fecha_desde_individual <= COALESCE(FECHA_HASTA, '9999-12-31')
                    AND COALESCE(:fecha_hasta_individual, '9999-12-31') >= FECHA_DESDE
                )
            ";

            $stmtOverlap = $pdo->prepare($sqlCheckOverlap);
            $stmtOverlap->bindValue(':id_empleado', $idEmpleado);
            $stmtOverlap->bindValue(':id_dia', $horario['id_dia']);
            $stmtOverlap->bindValue(':fecha_desde_individual', $horario['fecha_desde_individual']);
            $stmtOverlap->bindValue(':fecha_hasta_individual', $horario['fecha_hasta_individual']);
            $stmtOverlap->bindValue(':id_empleado_horario', $idEmpleadoHorario);
            $stmtOverlap->execute();

            $potentialOverlaps = $stmtOverlap->fetchAll(PDO::FETCH_ASSOC);

            // Verificar superposición de horarios usando lógica PHP que maneja turnos nocturnos
            foreach ($potentialOverlaps as $existingSchedule) {
                if (horariosSeSuperponen(
                    $horario['hora_entrada'],
                    $horario['hora_salida'],
                    $existingSchedule['HORA_ENTRADA'],
                    $existingSchedule['HORA_SALIDA']
                )) {
                    $fechaHastaExistente = $existingSchedule['FECHA_HASTA'] ? $existingSchedule['FECHA_HASTA'] : 'indefinida';
                    echo json_encode([
                        'success' => false,
                        'message' => "Conflicto de horarios: El turno '{$horario['nombre_turno']}' ({$horario['hora_entrada']}-{$horario['hora_salida']}) desde {$horario['fecha_desde_individual']} se superpone con el turno '{$existingSchedule['NOMBRE_TURNO']}' ({$existingSchedule['HORA_ENTRADA']}-{$existingSchedule['HORA_SALIDA']}) vigente desde {$existingSchedule['FECHA_DESDE']} hasta {$fechaHastaExistente}"
                    ]);
                    exit;
                }
            }

            if ($existingRecord) {
                // Si existe un registro ACTIVO con las mismas características, actualizarlo
                $baseUpdateSql = "
                    UPDATE empleado_horario_personalizado
                    SET HORA_ENTRADA = :hora_entrada,
                        HORA_SALIDA = :hora_salida,
                        TOLERANCIA = :tolerancia,
                        NOMBRE_TURNO = :nombre_turno,
                        FECHA_DESDE = :fecha_desde_individual,
                        FECHA_HASTA = :fecha_hasta_individual,
                        ORDEN_TURNO = :orden_turno,
                        OBSERVACIONES = :observaciones,
                        ES_TURNO_NOCTURNO = :es_turno_nocturno,
                        ACTIVO = 'S',
                        UPDATED_AT = NOW()
                    WHERE ID_EMPLEADO_HORARIO = :id_empleado_horario
                ";

                $stmtUpdate = $pdo->prepare($baseUpdateSql);
                $stmtUpdate->bindValue(':hora_entrada', $horario['hora_entrada']);
                $stmtUpdate->bindValue(':hora_salida', $horario['hora_salida']);
                $stmtUpdate->bindValue(':tolerancia', $horario['tolerancia']);
                $stmtUpdate->bindValue(':nombre_turno', $horario['nombre_turno']);
                $stmtUpdate->bindValue(':fecha_desde_individual', $horario['fecha_desde_individual']);
                $stmtUpdate->bindValue(':fecha_hasta_individual', $horario['fecha_hasta_individual']);
                $stmtUpdate->bindValue(':orden_turno', $horario['orden_turno']);
                $stmtUpdate->bindValue(':observaciones', $horario['observaciones']);
                $stmtUpdate->bindValue(':es_turno_nocturno', $horario['es_turno_nocturno']);
                $stmtUpdate->bindValue(':id_empleado_horario', $existingRecord['ID_EMPLEADO_HORARIO']);

                $stmtUpdate->execute();

                $insertedSchedules[] = $existingRecord['ID_EMPLEADO_HORARIO'];
            } else {
                // Si no existe, insertar nuevo
                $sqlInsert = "
                    INSERT INTO empleado_horario_personalizado 
                    (ID_EMPLEADO, ID_DIA, HORA_ENTRADA, HORA_SALIDA, TOLERANCIA, 
                     NOMBRE_TURNO, FECHA_DESDE, FECHA_HASTA, ORDEN_TURNO, OBSERVACIONES, 
                     ES_TURNO_NOCTURNO, ACTIVO) 
                    VALUES 
                    (:id_empleado, :id_dia, :hora_entrada, :hora_salida, :tolerancia, 
                     :nombre_turno, :fecha_desde_individual, :fecha_hasta_individual, :orden_turno, :observaciones, 
                     :es_turno_nocturno, 'S')
                ";
                
                $stmtInsert = $pdo->prepare($sqlInsert);
                $stmtInsert->bindValue(':id_empleado', $idEmpleado);
                $stmtInsert->bindValue(':id_dia', $horario['id_dia']);
                $stmtInsert->bindValue(':hora_entrada', $horario['hora_entrada']);
                $stmtInsert->bindValue(':hora_salida', $horario['hora_salida']);
                $stmtInsert->bindValue(':tolerancia', $horario['tolerancia']);
                $stmtInsert->bindValue(':nombre_turno', $horario['nombre_turno']);
                $stmtInsert->bindValue(':fecha_desde_individual', $horario['fecha_desde_individual']);
                $stmtInsert->bindValue(':fecha_hasta_individual', $horario['fecha_hasta_individual']);
                $stmtInsert->bindValue(':orden_turno', $horario['orden_turno']);
                $stmtInsert->bindValue(':observaciones', $horario['observaciones']);
                $stmtInsert->bindValue(':es_turno_nocturno', $horario['es_turno_nocturno']);
                $stmtInsert->execute();
                
                $insertedSchedules[] = $pdo->lastInsertId();
            }
        }

        // Confirmar transacción
        $pdo->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Horarios guardados exitosamente',
            'data' => [
                'id_empleado' => $idEmpleado,
                'horarios_procesados' => count($horariosValidados),
                'fecha_desde' => $fechaDesde,
                'fecha_hasta' => $fechaHasta,
                'inserted_ids' => $insertedSchedules,
                'replace_existing' => $replaceExisting
            ]
        ]);

    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'message' => 'Error de base de datos: ' . $e->getMessage()]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>