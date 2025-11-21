<?php
require_once __DIR__ . '/../../auth/session.php';
requireAuth();
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');

try {
    // Obtener información del usuario actual
    $currentUser = getCurrentUser();
    $empresaId = $currentUser['id_empresa'] ?? null;
    
    if (!$empresaId) {
        echo json_encode(['success' => false, 'message' => 'Sesión inválida - empresa no encontrada']);
        exit;
    }

    // Verificar parámetro ID del empleado
    if (!isset($_GET['id_empleado']) || empty($_GET['id_empleado'])) {
        echo json_encode(['success' => false, 'message' => 'ID de empleado no proporcionado']);
        exit;
    }

    $idEmpleado = (int)$_GET['id_empleado'];

    // Verificar que el empleado pertenece a la empresa del usuario
    $sqlVerifyEmployee = "
        SELECT 
            e.ID_EMPLEADO,
            e.NOMBRE,
            e.APELLIDO,
            e.DNI,
            e.CORREO,
            e.TELEFONO,
            e.FECHA_INGRESO,
            est.NOMBRE as establecimiento_nombre,
            s.NOMBRE as sede_nombre,
            s.ID_SEDE,
            est.ID_ESTABLECIMIENTO
        FROM empleado e
        JOIN establecimiento est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
        JOIN sede s ON est.ID_SEDE = s.ID_SEDE
        WHERE e.ID_EMPLEADO = :id_empleado 
        AND s.ID_EMPRESA = :empresa_id 
        AND e.ACTIVO = 'S'
    ";

    $stmt = $conn->prepare($sqlVerifyEmployee);
    $stmt->bindValue(':id_empleado', $idEmpleado);
    $stmt->bindValue(':empresa_id', $empresaId);
    $stmt->execute();
    $empleado = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$empleado) {
        echo json_encode(['success' => false, 'message' => 'Empleado no encontrado o sin permisos']);
        exit;
    }

    // Obtener horarios personalizados del empleado
    // Incluir horarios activos, próximos a vencer y próximos a iniciar vigencia
    $fechaHoy = date('Y-m-d');
    $fechaLimiteFutura = date('Y-m-d', strtotime('+30 days')); // Mostrar horarios que inicien en los próximos 30 días

    $sqlSchedules = "
        SELECT
            ehp.ID_EMPLEADO_HORARIO,
            ehp.ID_DIA,
            ehp.HORA_ENTRADA,
            ehp.HORA_SALIDA,
            ehp.TOLERANCIA,
            ehp.NOMBRE_TURNO,
            ehp.FECHA_DESDE,
            ehp.FECHA_HASTA,
            ehp.ACTIVO,
            ehp.ORDEN_TURNO,
            ehp.OBSERVACIONES,
            ehp.ES_TURNO_NOCTURNO,
            ehp.HORA_CORTE_NOCTURNO,
            ehp.CREATED_AT,
            ehp.UPDATED_AT,
            ds.NOMBRE as dia_nombre
        FROM empleado_horario_personalizado ehp
        JOIN dia_semana ds ON ehp.ID_DIA = ds.ID_DIA
        WHERE ehp.ID_EMPLEADO = :id_empleado
        AND ehp.ACTIVO = 'S'
        AND (
            -- Horarios actualmente vigentes (no vencidos)
            (ehp.FECHA_DESDE <= :fecha_hoy AND (ehp.FECHA_HASTA IS NULL OR ehp.FECHA_HASTA >= :fecha_hoy))
            OR
            -- Horarios próximos a entrar en vigencia (próximos 30 días)
            (ehp.FECHA_DESDE > :fecha_hoy AND ehp.FECHA_DESDE <= :fecha_limite_futura)
        )
        ORDER BY ehp.ID_DIA, ehp.ORDEN_TURNO, ehp.FECHA_DESDE DESC
    ";

    $stmtSchedules = $conn->prepare($sqlSchedules);
    $stmtSchedules->bindValue(':id_empleado', $idEmpleado);
    $stmtSchedules->bindValue(':fecha_hoy', $fechaHoy);
    $stmtSchedules->bindValue(':fecha_limite_futura', $fechaLimiteFutura);
    $stmtSchedules->execute();
    $schedules = $stmtSchedules->fetchAll(PDO::FETCH_ASSOC);

    // Organizar horarios por día
    $schedulesByDay = [];
    $dayNames = [
        1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles', 4 => 'Jueves',
        5 => 'Viernes', 6 => 'Sábado', 7 => 'Domingo'
    ];

    // Inicializar todos los días
    for ($i = 1; $i <= 7; $i++) {
        $schedulesByDay[$i] = [
            'dia_id' => $i,
            'dia_nombre' => $dayNames[$i],
            'turnos' => []
        ];
    }

    // Llenar con los horarios existentes
    foreach ($schedules as $schedule) {
        $diaId = (int)$schedule['ID_DIA'];
        
        // Calcular duración del turno
        $entrada = new DateTime($schedule['HORA_ENTRADA']);
        $salida = new DateTime($schedule['HORA_SALIDA']);
        $duracion = $entrada->diff($salida);
        $duracionHoras = $duracion->h + ($duracion->i / 60);

        // Determinar estado del horario (todos son activos según la consulta)
        $fechaHoy = new DateTime();
        $fechaDesde = new DateTime($schedule['FECHA_DESDE']);
        $fechaHasta = $schedule['FECHA_HASTA'] ? new DateTime($schedule['FECHA_HASTA']) : null;

        $estado = 'activo';
        if ($fechaDesde > $fechaHoy) {
            $estado = 'futuro';
        } elseif ($fechaHasta && $fechaHasta < $fechaHoy) {
            $estado = 'vencido';
        }

        $schedulesByDay[$diaId]['turnos'][] = [
            'id' => (int)$schedule['ID_EMPLEADO_HORARIO'],
            'nombre_turno' => $schedule['NOMBRE_TURNO'],
            'hora_entrada' => $schedule['HORA_ENTRADA'],
            'hora_salida' => $schedule['HORA_SALIDA'],
            'tolerancia' => (int)$schedule['TOLERANCIA'],
            'fecha_desde' => $schedule['FECHA_DESDE'],
            'fecha_hasta' => $schedule['FECHA_HASTA'],
            'activo' => $schedule['ACTIVO'] === 'S',
            'orden_turno' => (int)$schedule['ORDEN_TURNO'],
            'observaciones' => $schedule['OBSERVACIONES'],
            'duracion_horas' => round($duracionHoras, 2),
            'estado' => $estado,
            'created_at' => $schedule['CREATED_AT'],
            'updated_at' => $schedule['UPDATED_AT']
        ];
    }

    // Estadísticas del empleado
    $totalTurnos = count($schedules);
    $turnosActivos = 0;
    $turnosFuturos = 0;
    $turnosVencidos = 0;

    foreach ($schedules as $schedule) {
        $fechaHoy = new DateTime();
        $fechaDesde = new DateTime($schedule['FECHA_DESDE']);
        $fechaHasta = $schedule['FECHA_HASTA'] ? new DateTime($schedule['FECHA_HASTA']) : null;

        if ($fechaDesde > $fechaHoy) {
            $turnosFuturos++;
        } elseif ($fechaHasta && $fechaHasta < $fechaHoy) {
            $turnosVencidos++;
        } else {
            $turnosActivos++;
        }
    }

    $diasConHorarios = count(array_filter($schedulesByDay, function($day) {
        return count($day['turnos']) > 0;
    }));

    // Respuesta
    echo json_encode([
        'success' => true,
        'empleado' => [
            'id' => (int)$empleado['ID_EMPLEADO'],
            'nombre_completo' => trim($empleado['NOMBRE'] . ' ' . $empleado['APELLIDO']),
            'nombre' => $empleado['NOMBRE'],
            'apellido' => $empleado['APELLIDO'],
            'dni' => $empleado['DNI'],
            'correo' => $empleado['CORREO'],
            'telefono' => $empleado['TELEFONO'],
            'fecha_ingreso' => $empleado['FECHA_INGRESO'],
            'sede' => [
                'id' => (int)$empleado['ID_SEDE'],
                'nombre' => $empleado['sede_nombre']
            ],
            'establecimiento' => [
                'id' => (int)$empleado['ID_ESTABLECIMIENTO'],
                'nombre' => $empleado['establecimiento_nombre']
            ]
        ],
        'horarios_por_dia' => array_values($schedulesByDay),
        'estadisticas' => [
            'total_turnos' => $totalTurnos,
            'turnos_activos' => $turnosActivos,
            'turnos_futuros' => $turnosFuturos,
            'turnos_vencidos' => $turnosVencidos,
            'dias_con_horarios' => $diasConHorarios,
            'dias_sin_horarios' => 7 - $diasConHorarios
        ],
        'fecha_consulta' => date('Y-m-d H:i:s')
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error de base de datos: ' . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>