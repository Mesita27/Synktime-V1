<?php

// Configurar zona horaria de Bogotá, Colombia
require_once __DIR__ . '/../config/timezone.php';
header('Content-Type: application/json');
require_once '../config/database.php';
require_once '../auth/session.php';
require_once '../utils/justificaciones_utils.php';

// Verificar autenticación
requireAuth();

try {
    $currentUser = getCurrentUser();
    $empresaId = $currentUser['id_empresa'] ?? null;

    if (!$empresaId) {
        echo json_encode(['success' => false, 'message' => 'Empresa no válida']);
        exit;
    }

    // CORREGIR: Aceptar tanto 'employee_id' como 'empleado_id'
    $employeeId = isset($_GET['employee_id']) ? intval($_GET['employee_id']) : 
                  (isset($_GET['empleado_id']) ? intval($_GET['empleado_id']) : null);
    $fecha = isset($_GET['fecha']) ? $_GET['fecha'] : getBogotaDate();
    $tipoRegistro = isset($_GET['tipo']) ? strtoupper($_GET['tipo']) : 'ENTRADA';
    
    // AGREGAR: Calcular día de la semana automáticamente si no se proporciona
    $diaSemana = isset($_GET['dia_semana']) ? intval($_GET['dia_semana']) : null;
    if (!$diaSemana) {
        $diaSemana = date('N'); // 1=Lunes, 7=Domingo
    }

    if (!$employeeId) {
        echo json_encode(['success' => false, 'message' => 'ID de empleado requerido']);
        exit;
    }

    error_log("DEBUGGING check-employee-schedule.php:");
    error_log("- Empleado ID: $employeeId");
    error_log("- Fecha: $fecha");
    error_log("- Día semana: $diaSemana");
    error_log("- Tipo registro: $tipoRegistro");

    // Convertir día de la semana si es necesario (0=domingo, 1=lunes, etc.)
    $diaSemanaBD = $diaSemana === 0 ? 7 : $diaSemana;

    // **CORREGIDO: Buscar SOLO horarios personalizados basándose en vigencia**
    $stmtPersonalizado = $pdo->prepare("
        SELECT
            ehp.ID_EMPLEADO_HORARIO,
            ehp.NOMBRE_TURNO as horario_nombre,
            ehp.HORA_ENTRADA,
            ehp.HORA_SALIDA,
            ehp.TOLERANCIA,
            ehp.ORDEN_TURNO,
            'personalizado' as tipo_horario
        FROM empleado_horario_personalizado ehp
        WHERE ehp.ID_EMPLEADO = ?
        AND ehp.ID_DIA = ?
        AND ehp.FECHA_DESDE <= ?
        AND (ehp.FECHA_HASTA IS NULL OR ehp.FECHA_HASTA >= ?)
        AND ehp.ACTIVO = 'S'
        ORDER BY ehp.FECHA_DESDE DESC, ehp.ORDEN_TURNO
    ");

    $stmtPersonalizado->execute([$employeeId, $diaSemanaBD, $fecha, $fecha]);
    $horariosPersonalizados = $stmtPersonalizado->fetchAll(PDO::FETCH_ASSOC);

    // **NUEVO: Filtrar horarios justificados**
    $horariosFiltrados = filtrarHorariosPorJustificaciones($employeeId, $fecha, $horariosPersonalizados, $pdo);
    $horariosPersonalizados = $horariosFiltrados['horarios_disponibles'];

    $horario = null;

    if (!empty($horariosPersonalizados)) {
        error_log("Encontrados " . count($horariosPersonalizados) . " horarios personalizados");
        
        // MEJORAR: Lógica para seleccionar horario según tipo de registro y hora actual
        $horaActual = getBogotaTime();
        
        if ($tipoRegistro === 'ENTRADA') {
            // Para entrada: buscar el primer turno que aún no ha terminado o está en tolerancia
            foreach ($horariosPersonalizados as $hp) {
                $horaSalidaTurno = $hp['HORA_SALIDA'];
                if ($horaActual <= $horaSalidaTurno) {
                    $horario = $hp;
                    break;
                }
            }
            // Si no encontró ninguno, usar el primer turno
            if (!$horario) {
                $horario = $horariosPersonalizados[0];
            }
        } else {
            // Para salida: buscar el turno correspondiente según la hora actual
            foreach ($horariosPersonalizados as $hp) {
                $horaEntradaTurno = $hp['HORA_ENTRADA'];
                $horaSalidaTurno = $hp['HORA_SALIDA'];
                
                // Si la hora actual está dentro del rango del turno (o después de la entrada)
                if ($horaActual >= $horaEntradaTurno) {
                    $horario = $hp;
                    // Continuar para encontrar el último turno aplicable
                }
            }
            // Si no encontró ninguno, usar el último turno del día
            if (!$horario) {
                $horario = end($horariosPersonalizados);
            }
        }
        
        error_log("Horario personalizado seleccionado: " . json_encode($horario));
    } else {
        // **CAMBIO FUNDAMENTAL: Solo usar horarios personalizados, no tradicionales**
        error_log("No hay horarios personalizados asignados para el empleado en este día");
        
        echo json_encode([
            'success' => false, 
            'message' => 'No se encontraron horarios personalizados asignados para el empleado en este día',
            'debug' => [
                'empleado_id' => $employeeId,
                'fecha' => $fecha,
                'dia_semana' => $diaSemanaBD
            ]
        ]);
        exit;
    }

    // MEJORAR: Lógica de validación según el tipo de registro
    if ($horario) {
        // 1. Contar cuántos turnos NO JUSTIFICADOS tiene configurados el empleado para este día
        $totalTurnosNoJustificados = count($horariosPersonalizados);

        // 2. Verificar registros previos SOLO para turnos NO JUSTIFICADOS
        $stmtRegistros = $pdo->prepare("
            SELECT a.TIPO, a.HORA, DATE(a.FECHA) as FECHA_REG, a.ID_EMPLEADO_HORARIO
            FROM asistencia a
            LEFT JOIN empleado_horario_personalizado ehp ON a.ID_EMPLEADO_HORARIO = ehp.ID_EMPLEADO_HORARIO
            LEFT JOIN justificaciones j ON ehp.ID_EMPLEADO_HORARIO = j.turno_id 
                AND j.fecha_falta = DATE(a.FECHA)
            WHERE a.ID_EMPLEADO = ?
            AND DATE(a.FECHA) = ?
            AND j.id IS NULL
            ORDER BY a.FECHA, a.HORA
        ");
        $stmtRegistros->execute([$employeeId, $fecha]);
        $registrosPrevios = $stmtRegistros->fetchAll(PDO::FETCH_ASSOC);

        // 3. Analizar registros para determinar estado actual
        $turnosCompletos = 0;
        $entradaSinSalida = false;
        $ultimoTipo = null;
        
        // Contar turnos completos (pares entrada-salida)
        $entradas = 0;
        $salidas = 0;
        foreach ($registrosPrevios as $reg) {
            if ($reg['TIPO'] === 'ENTRADA') {
                $entradas++;
            } elseif ($reg['TIPO'] === 'SALIDA') {
                $salidas++;
            }
            $ultimoTipo = $reg['TIPO'];
        }
        
        $turnosCompletos = min($entradas, $salidas);
        $entradaSinSalida = ($entradas > $salidas);

        error_log("VALIDACIÓN TURNOS:");
        error_log("- Total turnos NO justificados disponibles: $totalTurnosNoJustificados");
        error_log("- Entradas registradas: $entradas");
        error_log("- Salidas registradas: $salidas");
        error_log("- Turnos completos: $turnosCompletos");
        error_log("- Entrada sin salida: " . ($entradaSinSalida ? 'SI' : 'NO'));
        error_log("- Último tipo: $ultimoTipo");

        $puedeRegistrar = true;
        $mensaje = 'Horario encontrado y listo para registro';
        
        if ($tipoRegistro === 'ENTRADA') {
            // Para entrada: verificar que no existan entradas pendientes de salida
            if ($entradaSinSalida) {
                $puedeRegistrar = false;
                $mensaje = 'Existe una entrada previa sin salida registrada. Debe registrar la salida antes de una nueva entrada.';
            } elseif ($entradas >= $totalTurnosNoJustificados) {
                $puedeRegistrar = false;
                $mensaje = "Ya registró entrada en todos los turnos disponibles para hoy ($entradas de $totalTurnosNoJustificados turnos). No puede registrar más entradas.";
            } else {
                $mensaje = "Entrada permitida (turno " . ($entradas + 1) . " de $totalTurnosNoJustificados)";
            }
        } else {
            // Para salida: verificar que haya al menos una entrada sin salida
            if (!$entradaSinSalida) {
                $puedeRegistrar = false;
                $mensaje = 'No hay entradas pendientes de salida. Debe registrar primero una entrada.';
            } else {
                $mensaje = 'Salida registrada correctamente para turno pendiente';
            }
            // SIEMPRE permitir salidas (pueden ser correcciones)
            $puedeRegistrar = true;
        }

        echo json_encode([
            'success' => true,
            'tiene_horario' => true,
            'puede_registrar' => $puedeRegistrar,
            'horario' => [
                'ID_HORARIO' => $horario['ID_HORARIO'] ?? null,
                'ID_EMPLEADO_HORARIO' => $horario['ID_EMPLEADO_HORARIO'] ?? null,
                'horario_nombre' => $horario['horario_nombre'],
                'HORA_ENTRADA' => $horario['HORA_ENTRADA'],
                'HORA_SALIDA' => $horario['HORA_SALIDA'],
                'TOLERANCIA' => $horario['TOLERANCIA'],
                'tipo_horario' => $horario['tipo_horario'],
                'ORDEN_TURNO' => $horario['ORDEN_TURNO'] ?? 1
            ],
            'registros_previos' => $registrosPrevios,
            'message' => $mensaje
        ]);
        
        error_log("Respuesta enviada - Puede registrar: " . ($puedeRegistrar ? 'SI' : 'NO') . " - " . $mensaje);
        
    } else {
        // IMPORTANTE: Para salidas, permitir registro aunque no haya horario configurado
        if ($tipoRegistro === 'SALIDA') {
            echo json_encode([
                'success' => true,
                'tiene_horario' => false,
                'puede_registrar' => true, // PERMITIR salida sin horario
                'horario' => null,
                'message' => 'Salida permitida sin horario específico'
            ]);
            error_log("Salida permitida sin horario configurado");
        } else {
            // Para entradas, requerir horario
            echo json_encode([
                'success' => true,
                'tiene_horario' => false,
                'puede_registrar' => false,
                'horario' => null,
                'message' => 'El empleado no tiene horario asignado para este día (' . date('l', strtotime($fecha)) . ')'
            ]);
            error_log("Entrada bloqueada - sin horario configurado");
        }
    }

} catch (Exception $e) {
    error_log("Error en check-employee-schedule.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error al verificar horario del empleado',
        'error' => $e->getMessage()
    ]);
}
?>
