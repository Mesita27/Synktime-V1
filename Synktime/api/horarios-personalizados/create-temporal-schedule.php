<?php
require_once __DIR__ . '/../../auth/session.php';
requireAuth();
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');

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
    $requiredFields = ['empleados', 'fecha', 'hora_entrada', 'hora_salida'];
    foreach ($requiredFields as $field) {
        if (!isset($data[$field]) || empty($data[$field])) {
            echo json_encode(['success' => false, 'message' => "Campo requerido: $field"]);
            exit;
        }
    }

    $empleados = $data['empleados'];
    $fecha = $data['fecha'];
    $horaEntrada = $data['hora_entrada'];
    $horaSalida = $data['hora_salida'];
    $tolerancia = isset($data['tolerancia']) ? (int)$data['tolerancia'] : 15;
    $observaciones = $data['observaciones'] ?? '';
    $esTemporal = $data['es_temporal'] ?? 'S';

    // Validar que empleados sea un array
    if (!is_array($empleados) || empty($empleados)) {
        echo json_encode(['success' => false, 'message' => 'Debe seleccionar al menos un empleado']);
        exit;
    }

    // Validar formato de fecha
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
        echo json_encode(['success' => false, 'message' => 'Formato de fecha inválido']);
        exit;
    }

    // Validar formato de horas
    if (!preg_match('/^\d{2}:\d{2}$/', $horaEntrada) || !preg_match('/^\d{2}:\d{2}$/', $horaSalida)) {
        echo json_encode(['success' => false, 'message' => 'Formato de hora inválido']);
        exit;
    }

    $esTurnoNocturno = $horaSalida <= $horaEntrada ? 'S' : 'N';

    $nuevoInicio = new DateTime("{$fecha} {$horaEntrada}");
    $nuevoFin = new DateTime("{$fecha} {$horaSalida}");
    if ($esTurnoNocturno === 'S') {
        $nuevoFin->modify('+1 day');
    }

    // Validar que la fecha no sea en el pasado
    $fechaActual = date('Y-m-d');
    if ($fecha < $fechaActual) {
        echo json_encode(['success' => false, 'message' => 'No se pueden crear horarios temporales para fechas pasadas']);
        exit;
    }

    // Iniciar transacción
    $pdo->beginTransaction();

    $successCount = 0;
    $errors = [];

    foreach ($empleados as $idEmpleado) {
        try {
            $idEmpleado = (int)$idEmpleado;

            // Verificar que el empleado existe y pertenece a la empresa
            $stmt = $pdo->prepare("
                SELECT e.ID_EMPLEADO, CONCAT(e.NOMBRE, ' ', e.APELLIDO) as NOMBRE_COMPLETO, e.ID_ESTABLECIMIENTO
                FROM empleado e
                JOIN establecimiento est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
                JOIN sede s ON est.ID_SEDE = s.ID_SEDE
                WHERE e.ID_EMPLEADO = ? AND s.ID_EMPRESA = ?
            ");
            $stmt->execute([$idEmpleado, $empresaId]);
            $empleado = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$empleado) {
                $errors[] = "Empleado ID $idEmpleado no encontrado o no pertenece a la empresa";
                continue;
            }

            // Verificar conflictos de horario con TODOS los horarios existentes para esta fecha (temporales y regulares)
            $stmt = $pdo->prepare("
                SELECT HORA_ENTRADA, HORA_SALIDA, ES_TEMPORAL, FECHA_DESDE, FECHA_HASTA, ES_TURNO_NOCTURNO
                FROM empleado_horario_personalizado
                WHERE ID_EMPLEADO = ? AND FECHA_DESDE = ? AND ACTIVO = 'S'
            ");
            $stmt->execute([$idEmpleado, $fecha]);
            $existingSchedules = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Verificar conflictos de horas
            $hasConflict = false;
            foreach ($existingSchedules as $existing) {
                $existingStart = new DateTime($existing['FECHA_DESDE'] . ' ' . $existing['HORA_ENTRADA']);
                $existingEndDate = $existing['FECHA_HASTA'] ?: $existing['FECHA_DESDE'];
                $existingEnd = new DateTime($existingEndDate . ' ' . $existing['HORA_SALIDA']);

                if ($existing['HORA_SALIDA'] <= $existing['HORA_ENTRADA'] || $existingEnd <= $existingStart) {
                    $existingEnd->modify('+1 day');
                }

                if ($nuevoInicio < $existingEnd && $nuevoFin > $existingStart) {
                    $hasConflict = true;
                    $tipoHorario = $existing['ES_TEMPORAL'] == 'S' ? 'temporal' : 'regular';
                    $errors[] = "Conflicto de horario con horario {$tipoHorario} existente ({$existing['HORA_ENTRADA']} - {$existing['HORA_SALIDA']}) para el empleado {$empleado['NOMBRE_COMPLETO']} en la fecha $fecha";
                    break;
                }
            }

            if ($hasConflict) {
                continue;
            }

            // Calcular el día de la semana para la fecha
            $fechaObj = new DateTime($fecha);
            $diaSemana = (int)$fechaObj->format('N'); // 1 = Lunes, 7 = Domingo

            // Calcular fecha de finalización (un día después)
            $fechaHastaObj = clone $fechaObj;
            $fechaHastaObj->modify('+1 day');
            $fechaHasta = $fechaHastaObj->format('Y-m-d');

            // Insertar el horario temporal
            $stmt = $pdo->prepare("
                INSERT INTO empleado_horario_personalizado (
                    ID_EMPLEADO,
                    ID_DIA,
                    FECHA_DESDE,
                    FECHA_HASTA,
                    HORA_ENTRADA,
                    HORA_SALIDA,
                    TOLERANCIA,
                    OBSERVACIONES,
                    ES_TEMPORAL,
                    ES_TURNO_NOCTURNO,
                    ACTIVO,
                    ORIGEN,
                    CREATED_AT
                ) VALUES (
                    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'S', 'TEMPORAL',
                    NOW()
                )
            ");

            $stmt->execute([
                $idEmpleado,
                $diaSemana,
                $fecha,
                $fechaHasta, // FECHA_HASTA = FECHA_DESDE + 1 día
                $horaEntrada,
                $horaSalida,
                $tolerancia,
                $observaciones,
                $esTemporal,
                $esTurnoNocturno
            ]);

            $successCount++;

        } catch (Exception $e) {
            $errors[] = "Error al crear horario para empleado ID $idEmpleado: " . $e->getMessage();
        }
    }

    // Confirmar transacción si al menos un horario fue creado exitosamente
    if ($successCount > 0) {
        $pdo->commit();

        $message = "Horario temporal creado exitosamente para $successCount empleado(s)";
        if (!empty($errors)) {
            $message .= ". Errores encontrados: " . implode('; ', $errors);
        }

        echo json_encode([
            'success' => true,
            'message' => $message,
            'created_count' => $successCount,
            'errors' => $errors
        ]);
    } else {
        $pdo->rollBack();
        echo json_encode([
            'success' => false,
            'message' => 'No se pudo crear ningún horario temporal: ' . implode('; ', $errors)
        ]);
    }

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log("Error en create-temporal-schedule.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error interno del servidor: ' . $e->getMessage()
    ]);
}
?>