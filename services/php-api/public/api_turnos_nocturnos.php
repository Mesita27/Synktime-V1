<?php
/**
 * API MEJORADA PARA TURNOS NOCTURNOS
 * Sistema de Asistencia Synktime
 * Implementa registros de entrada/salida con jornadas laborales
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Manejo de preflight requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

require_once 'db_connection.php';

class ApiTurnosNocturnos {
    private $conn;
    
    public function __construct($db_connection) {
        $this->conn = $db_connection;
    }
    
    /**
     * Registra entrada de empleado con gestión de jornadas
     */
    public function registrarEntrada($data) {
        try {
            $id_empleado = $data['ID_EMPLEADO'];
            $fecha = $data['FECHA'] ?? date('Y-m-d');
            $hora = $data['HORA'] ?? date('H:i:s');
            $id_empleado_horario = $data['ID_EMPLEADO_HORARIO'];
            
            // Verificar si ya tiene una jornada activa
            $stmt = $this->conn->prepare("
                SELECT ID_JORNADA, FECHA_INICIO, ES_TURNO_NOCTURNO
                FROM jornadas_trabajo 
                WHERE ID_EMPLEADO = ? AND ESTADO = 'INICIADA'
                ORDER BY CREATED_AT DESC LIMIT 1
            ");
            $stmt->bind_param("i", $id_empleado);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                return [
                    'success' => false,
                    'error' => 'Ya tiene una jornada laboral activa',
                    'data' => $result->fetch_assoc()
                ];
            }
            
            // Usar procedimiento almacenado para registrar entrada
            $stmt = $this->conn->prepare("CALL sp_registrar_entrada(?, ?, ?, ?, @id_jornada, @es_nocturno)");
            $stmt->bind_param("issi", $id_empleado, $fecha, $hora, $id_empleado_horario);
            $stmt->execute();
            
            // Obtener resultados del procedimiento
            $result = $this->conn->query("SELECT @id_jornada as id_jornada, @es_nocturno as es_nocturno");
            $output = $result->fetch_assoc();
            
            return [
                'success' => true,
                'action' => 'ENTRADA',
                'data' => [
                    'ID_JORNADA' => $output['id_jornada'],
                    'ID_EMPLEADO' => $id_empleado,
                    'FECHA' => $fecha,
                    'HORA' => $hora,
                    'ES_TURNO_NOCTURNO' => $output['es_nocturno'],
                    'TIMESTAMP' => date('Y-m-d H:i:s')
                ]
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Error al registrar entrada: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Registra salida de empleado
     */
    public function registrarSalida($data) {
        try {
            $id_empleado = $data['ID_EMPLEADO'];
            $fecha = $data['FECHA'] ?? date('Y-m-d');
            $hora = $data['HORA'] ?? date('H:i:s');
            
            // Usar procedimiento almacenado para registrar salida
            $stmt = $this->conn->prepare("CALL sp_registrar_salida(?, ?, ?, @id_jornada, @horas_trabajadas)");
            $stmt->bind_param("iss", $id_empleado, $fecha, $hora);
            $stmt->execute();
            
            // Obtener resultados del procedimiento
            $result = $this->conn->query("SELECT @id_jornada as id_jornada, @horas_trabajadas as horas_trabajadas");
            $output = $result->fetch_assoc();
            
            if (!$output['id_jornada']) {
                return [
                    'success' => false,
                    'error' => 'No se encontró jornada activa para este empleado'
                ];
            }
            
            return [
                'success' => true,
                'action' => 'SALIDA',
                'data' => [
                    'ID_JORNADA' => $output['id_jornada'],
                    'ID_EMPLEADO' => $id_empleado,
                    'FECHA' => $fecha,
                    'HORA' => $hora,
                    'HORAS_TRABAJADAS' => round($output['horas_trabajadas'], 2),
                    'TIMESTAMP' => date('Y-m-d H:i:s')
                ]
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Error al registrar salida: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Obtiene información de jornada actual del empleado
     */
    public function consultarJornadaActual($id_empleado) {
        try {
            $stmt = $this->conn->prepare("
                SELECT 
                    j.*,
                    CONCAT(e.NOMBRE, ' ', e.APELLIDO) as NOMBRE_EMPLEADO,
                    ehp.NOMBRE_TURNO,
                    a_entrada.HORA as HORA_ENTRADA_REAL,
                    CASE 
                        WHEN j.ES_TURNO_NOCTURNO = 'S' THEN 'Nocturno'
                        ELSE 'Diurno'
                    END as TIPO_TURNO
                FROM jornadas_trabajo j
                JOIN empleado e ON j.ID_EMPLEADO = e.ID_EMPLEADO
                JOIN empleado_horario_personalizado ehp ON j.ID_EMPLEADO_HORARIO = ehp.ID_EMPLEADO_HORARIO
                LEFT JOIN asistencia a_entrada ON j.ID_JORNADA = a_entrada.ID_JORNADA_TRABAJO 
                    AND a_entrada.TIPO = 'ENTRADA'
                WHERE j.ID_EMPLEADO = ? AND j.ESTADO = 'INICIADA'
                ORDER BY j.CREATED_AT DESC LIMIT 1
            ");
            $stmt->bind_param("i", $id_empleado);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $jornada = $result->fetch_assoc();
                
                // Calcular horas transcurridas si está en jornada
                if ($jornada['HORA_ENTRADA_REAL']) {
                    $inicio = new DateTime($jornada['FECHA_INICIO'] . ' ' . $jornada['HORA_ENTRADA_REAL']);
                    $ahora = new DateTime();
                    $diferencia = $inicio->diff($ahora);
                    $horas_transcurridas = $diferencia->h + ($diferencia->i / 60);
                    $jornada['HORAS_TRANSCURRIDAS'] = round($horas_transcurridas, 2);
                }
                
                return [
                    'success' => true,
                    'data' => $jornada
                ];
            }
            
            return [
                'success' => false,
                'error' => 'No hay jornada activa'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Error al consultar jornada: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Obtiene historial de jornadas del empleado
     */
    public function consultarHistorialJornadas($id_empleado, $fecha_desde = null, $fecha_hasta = null) {
        try {
            $fecha_desde = $fecha_desde ?? date('Y-m-01'); // Primer día del mes actual
            $fecha_hasta = $fecha_hasta ?? date('Y-m-d');   // Hoy
            
            $stmt = $this->conn->prepare("
                SELECT * FROM vista_jornadas_completas
                WHERE ID_EMPLEADO = ? 
                AND FECHA_INICIO BETWEEN ? AND ?
                ORDER BY FECHA_INICIO DESC, HORA_ENTRADA DESC
            ");
            $stmt->bind_param("iss", $id_empleado, $fecha_desde, $fecha_hasta);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $jornadas = [];
            while ($row = $result->fetch_assoc()) {
                $jornadas[] = $row;
            }
            
            // Calcular estadísticas
            $total_jornadas = count($jornadas);
            $jornadas_completadas = count(array_filter($jornadas, function($j) { 
                return $j['ESTADO'] == 'COMPLETADA'; 
            }));
            $total_horas = array_sum(array_column($jornadas, 'HORAS_TRABAJADAS'));
            $total_extras = array_sum(array_column($jornadas, 'HORAS_EXTRAS'));
            
            return [
                'success' => true,
                'data' => [
                    'jornadas' => $jornadas,
                    'estadisticas' => [
                        'total_jornadas' => $total_jornadas,
                        'jornadas_completadas' => $jornadas_completadas,
                        'total_horas_trabajadas' => round($total_horas, 2),
                        'total_horas_extras' => round($total_extras, 2),
                        'promedio_horas_dia' => $total_jornadas > 0 ? round($total_horas / $total_jornadas, 2) : 0
                    ]
                ]
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Error al consultar historial: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Obtiene horarios disponibles para un empleado
     */
    public function consultarHorarios($id_empleado) {
        try {
            $stmt = $this->conn->prepare("
                SELECT 
                    ID_EMPLEADO_HORARIO,
                    ID_DIA,
                    CASE ID_DIA
                        WHEN 1 THEN 'Lunes'
                        WHEN 2 THEN 'Martes'
                        WHEN 3 THEN 'Miércoles'
                        WHEN 4 THEN 'Jueves'
                        WHEN 5 THEN 'Viernes'
                        WHEN 6 THEN 'Sábado'
                        WHEN 7 THEN 'Domingo'
                    END as DIA_NOMBRE,
                    HORA_ENTRADA,
                    HORA_SALIDA,
                    NOMBRE_TURNO,
                    ES_TURNO_NOCTURNO,
                    ACTIVO
                FROM empleado_horario_personalizado 
                WHERE ID_EMPLEADO = ? AND ACTIVO = 'S'
                ORDER BY ID_DIA
            ");
            $stmt->bind_param("i", $id_empleado);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $horarios = [];
            while ($row = $result->fetch_assoc()) {
                $horarios[] = $row;
            }
            
            return [
                'success' => true,
                'data' => $horarios
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Error al consultar horarios: ' . $e->getMessage()
            ];
        }
    }
}

// Inicializar API
try {
    $api = new ApiTurnosNocturnos($conn);
    
    // Obtener datos de la petición
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $_GET['action'] ?? $input['action'] ?? '';
    
    switch ($action) {
        case 'registrar_entrada':
            $response = $api->registrarEntrada($input);
            break;
            
        case 'registrar_salida':
            $response = $api->registrarSalida($input);
            break;
            
        case 'consultar_jornada_actual':
            $id_empleado = $_GET['id_empleado'] ?? $input['ID_EMPLEADO'];
            $response = $api->consultarJornadaActual($id_empleado);
            break;
            
        case 'consultar_historial':
            $id_empleado = $_GET['id_empleado'] ?? $input['ID_EMPLEADO'];
            $fecha_desde = $_GET['fecha_desde'] ?? $input['fecha_desde'] ?? null;
            $fecha_hasta = $_GET['fecha_hasta'] ?? $input['fecha_hasta'] ?? null;
            $response = $api->consultarHistorialJornadas($id_empleado, $fecha_desde, $fecha_hasta);
            break;
            
        case 'consultar_horarios':
            $id_empleado = $_GET['id_empleado'] ?? $input['ID_EMPLEADO'];
            $response = $api->consultarHorarios($id_empleado);
            break;
            
        default:
            $response = [
                'success' => false,
                'error' => 'Acción no válida',
                'actions_disponibles' => [
                    'registrar_entrada',
                    'registrar_salida', 
                    'consultar_jornada_actual',
                    'consultar_historial',
                    'consultar_horarios'
                ]
            ];
    }
    
} catch (Exception $e) {
    $response = [
        'success' => false,
        'error' => 'Error interno del servidor: ' . $e->getMessage()
    ];
}

// Responder
echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
?>