<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/timezone.php';
require_once __DIR__ . '/../utils/attendance_status_utils.php';

session_start();
$empresaId = $_SESSION['id_empresa'] ?? null;

if (!$empresaId) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Sesión expirada']);
    exit;
}

try {
    // Obtener parámetros
    $tipo = isset($_GET['tipo']) ? $_GET['tipo'] : null;
    $establecimientoId = !empty($_GET['establecimiento_id']) ? intval($_GET['establecimiento_id']) : null;
    $sedeId = !empty($_GET['sede_id']) ? intval($_GET['sede_id']) : null;
    $fecha = isset($_GET['fecha']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['fecha']) ? $_GET['fecha'] : getBogotaDate();
    
    error_log("API get-attendance-details-simplified.php - Parámetros: tipo=$tipo, fecha=$fecha, empresa=$empresaId");
    
    // Validar tipo
    $tiposValidos = ['temprano', 'aTiempo', 'tarde', 'faltas'];
    if (!in_array($tipo, $tiposValidos)) {
        throw new Exception('Tipo de asistencia inválido');
    }
    
    // Construir condición WHERE según nivel jerárquico
    $whereConditions = [];
    $params = [':fecha' => $fecha];
    
    if ($establecimientoId) {
        $whereConditions[] = "E.ID_ESTABLECIMIENTO = :establecimiento_id";
        $params[':establecimiento_id'] = $establecimientoId;
    } elseif ($sedeId) {
        $whereConditions[] = "EST.ID_SEDE = :sede_id";
        $params[':sede_id'] = $sedeId;
    } else {
        $whereConditions[] = "S.ID_EMPRESA = :empresa_id";
        $params[':empresa_id'] = $empresaId;
    }
    
    $whereClause = implode(' AND ', $whereConditions);
    
    if ($tipo === 'faltas') {
        // Obtener día de la semana de la fecha consultada (1=Lunes, 7=Domingo)
        $diaSemana = date('N', strtotime($fecha));

        // Obtener empleados que NO asistieron a TODOS sus turnos programados del día
        $query = "
            SELECT DISTINCT E.ID_EMPLEADO, E.NOMBRE, E.APELLIDO,
                   EST.NOMBRE as ESTABLECIMIENTO_NOMBRE, S.NOMBRE as SEDE_NOMBRE,
                   EHP.HORA_ENTRADA as HORA_ENTRADA_PROGRAMADA,
                   EHP.HORA_SALIDA as HORA_SALIDA_PROGRAMADA,
                   EHP.NOMBRE_TURNO,
                   EHP.TOLERANCIA as TOLERANCIA_PROGRAMADA,
                   EHP.ORDEN_TURNO,
                   'Ausente' as estado
            FROM EMPLEADO E
            JOIN ESTABLECIMIENTO EST ON E.ID_ESTABLECIMIENTO = EST.ID_ESTABLECIMIENTO
            JOIN SEDE S ON EST.ID_SEDE = S.ID_SEDE
            JOIN EMPLEADO_HORARIO_PERSONALIZADO EHP ON E.ID_EMPLEADO = EHP.ID_EMPLEADO
                AND :fecha BETWEEN EHP.FECHA_DESDE AND COALESCE(EHP.FECHA_HASTA, '9999-12-31')
                AND EHP.ID_DIA = :dia_semana
                AND EHP.ACTIVO = 'S'
            WHERE E.ACTIVO = 'S'
              AND $whereClause
              AND NOT EXISTS (
                  SELECT 1 FROM asistencia A
                  WHERE A.ID_EMPLEADO = E.ID_EMPLEADO
                    AND A.FECHA = :fecha2
                    AND A.TIPO = 'ENTRADA'
                    AND A.ID_EMPLEADO_HORARIO = EHP.ID_EMPLEADO_HORARIO
              )
            ORDER BY E.APELLIDO, E.NOMBRE, EHP.ORDEN_TURNO
        ";

        $params[':fecha'] = $fecha;
        $params[':fecha2'] = $fecha;
        $params[':dia_semana'] = $diaSemana;    } else {
        // Obtener empleados que SÍ asistieron - mostrar cada turno por separado
        $query = "
            SELECT DISTINCT E.ID_EMPLEADO, E.NOMBRE, E.APELLIDO,
                   EST.NOMBRE as ESTABLECIMIENTO_NOMBRE, S.NOMBRE as SEDE_NOMBRE,
                   A.HORA as HORA_ENTRADA_REAL,
                   EHP.HORA_ENTRADA as HORA_ENTRADA_PROGRAMADA,
                   EHP.HORA_SALIDA as HORA_SALIDA_PROGRAMADA,
                   EHP.NOMBRE_TURNO,
                   EHP.TOLERANCIA as tolerancia,
                   EHP.ORDEN_TURNO
            FROM asistencia A
            JOIN EMPLEADO E ON A.ID_EMPLEADO = E.ID_EMPLEADO
            JOIN ESTABLECIMIENTO EST ON E.ID_ESTABLECIMIENTO = EST.ID_ESTABLECIMIENTO
            JOIN SEDE S ON EST.ID_SEDE = S.ID_SEDE
            JOIN EMPLEADO_HORARIO_PERSONALIZADO EHP ON A.ID_EMPLEADO_HORARIO = EHP.ID_EMPLEADO_HORARIO
                AND A.FECHA BETWEEN EHP.FECHA_DESDE AND COALESCE(EHP.FECHA_HASTA, '9999-12-31')
                AND EHP.ACTIVO = 'S'
            WHERE A.FECHA = :fecha
              AND A.TIPO = 'ENTRADA'
              AND E.ACTIVO = 'S'
              AND $whereClause
            ORDER BY A.HORA, E.APELLIDO, E.NOMBRE, EHP.ORDEN_TURNO
        ";
    }
    
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $empleados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Para empleados que asistieron, filtrar por estado
    if ($tipo !== 'faltas') {
        $empleadosFiltrados = [];
        
        foreach ($empleados as $empleado) {
            $estado = calcularEstadoEntrada(
                $empleado['HORA_ENTRADA_PROGRAMADA'],
                $empleado['HORA_ENTRADA_REAL'],
                (int)($empleado['tolerancia'] ?? 15)
            );
            
            $empleado['estado'] = $estado;
            
            // Filtrar según el tipo solicitado
            $incluir = false;
            switch ($tipo) {
                case 'temprano':
                    $incluir = ($estado === 'Temprano');
                    break;
                case 'aTiempo':
                    $incluir = ($estado === 'Puntual');
                    break;
                case 'tarde':
                    $incluir = ($estado === 'Tardanza');
                    break;
            }
            
            if ($incluir) {
                $empleadosFiltrados[] = $empleado;
            }
        }
        
        $empleados = $empleadosFiltrados;
    }
    
    // Formatear respuesta
    $empleadosFormateados = array_map(function($empleado) use ($tipo, $fecha) {
        $formateado = [
            'id_empleado' => $empleado['ID_EMPLEADO'],
            'nombre_completo' => trim($empleado['NOMBRE'] . ' ' . $empleado['APELLIDO']),
            'cargo' => 'Sin cargo', // Campo CARGO no existe en la tabla
            'establecimiento' => $empleado['ESTABLECIMIENTO_NOMBRE'] ?? 'Sin establecimiento',
            'sede' => $empleado['SEDE_NOMBRE'] ?? 'Sin sede',
            'hora_entrada_programada' => isset($empleado['HORA_ENTRADA_PROGRAMADA']) ? 
                date('H:i', strtotime($empleado['HORA_ENTRADA_PROGRAMADA'])) : 'No definida',
            'hora_entrada_real' => isset($empleado['HORA_ENTRADA_REAL']) ? 
                date('H:i', strtotime($empleado['HORA_ENTRADA_REAL'])) : null,
            'estado' => $empleado['estado']
        ];
        
        // Para faltas, agregar información adicional del horario del día específico
        if ($tipo === 'faltas') {
            $tolerancia = isset($empleado['TOLERANCIA_PROGRAMADA']) ? $empleado['TOLERANCIA_PROGRAMADA'] : 15;
            $nombreTurno = isset($empleado['NOMBRE_TURNO']) ? $empleado['NOMBRE_TURNO'] : 'Horario Estándar';
            $horaSalida = isset($empleado['HORA_SALIDA_PROGRAMADA']) ?
                date('H:i', strtotime($empleado['HORA_SALIDA_PROGRAMADA'])) : 'No definida';
            $ordenTurno = isset($empleado['ORDEN_TURNO']) ? $empleado['ORDEN_TURNO'] : 1;

            $formateado['tolerancia'] = $tolerancia;
            $formateado['nombre_turno'] = $nombreTurno;
            $formateado['hora_salida_programada'] = $horaSalida;
            $formateado['orden_turno'] = $ordenTurno;
            $formateado['horario_info'] = "Horario del día " . date('d/m/Y', strtotime($fecha)) . ": " .
                                         $formateado['hora_entrada_programada'] . " - " . $horaSalida .
                                         " (Tolerancia: " . $tolerancia . " min)";
        } else {
            // Para asistencias, agregar información del turno específico
            $nombreTurno = isset($empleado['NOMBRE_TURNO']) ? $empleado['NOMBRE_TURNO'] : 'Horario Estándar';
            $horaSalida = isset($empleado['HORA_SALIDA_PROGRAMADA']) ?
                date('H:i', strtotime($empleado['HORA_SALIDA_PROGRAMADA'])) : 'No definida';
            $ordenTurno = isset($empleado['ORDEN_TURNO']) ? $empleado['ORDEN_TURNO'] : 1;

            $formateado['nombre_turno'] = $nombreTurno;
            $formateado['hora_salida_programada'] = $horaSalida;
            $formateado['orden_turno'] = $ordenTurno;
        }
        
        return $formateado;
    }, $empleados);
    
    echo json_encode([
        'success' => true,
        'data' => $empleadosFormateados,
        'total' => count($empleadosFormateados),
        'tipo' => $tipo,
        'fecha' => $fecha
    ]);
    
} catch (Exception $e) {
    error_log("Error en get-attendance-details-simplified.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error interno del servidor: ' . $e->getMessage()
    ]);
}
?>