<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

// Configurar zona horaria para Colombia
date_default_timezone_set('America/Bogota');

require_once '../config/database.php';
require_once '../utils/attendance_status_utils.php';
require_once '../utils/horario_utils.php';

// Iniciar sesión
session_start();
$empresaId = $_SESSION['id_empresa'] ?? 1; // Valor por defecto 1 si no hay sesión

try {
    // Obtener los parámetros de la solicitud
    $tipo = isset($_GET['tipo']) ? $_GET['tipo'] : null;
    $establecimientoId = !empty($_GET['establecimiento_id']) ? intval($_GET['establecimiento_id']) : null;
    $sedeId = !empty($_GET['sede_id']) ? intval($_GET['sede_id']) : null;
    
    // Fecha - usar la fecha enviada o la fecha actual en Colombia
    $fecha = isset($_GET['fecha']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['fecha']) 
        ? $_GET['fecha'] 
        : date('Y-m-d'); // Ya usa la zona horaria de Colombia gracias al date_default_timezone_set
    
    // Registrar para depuración
    error_log("API get-attendance-details.php - Parámetros recibidos: tipo=$tipo, fecha=$fecha, empresa=$empresaId");
    
    // Validar tipo
    $tiposValidos = ['temprano', 'aTiempo', 'tarde', 'faltas'];
    if (!in_array($tipo, $tiposValidos)) {
        throw new Exception('Tipo de asistencia inválido');
    }
    
    // Construir condición WHERE según filtros
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
    
    // Construir la cláusula WHERE
    $whereClause = implode(" AND ", $whereConditions);
    
    // Obtener el nombre de la ubicación (empresa, sede o establecimiento)
    $locationName = "Todas las ubicaciones";
    if ($establecimientoId) {
        $queryLoc = "SELECT NOMBRE FROM ESTABLECIMIENTO WHERE ID_ESTABLECIMIENTO = :id";
        $stmtLoc = $conn->prepare($queryLoc);
        $stmtLoc->bindValue(':id', $establecimientoId);
        $stmtLoc->execute();
        $locationName = $stmtLoc->fetchColumn() ?: $locationName;
    } elseif ($sedeId) {
        $queryLoc = "SELECT NOMBRE FROM SEDE WHERE ID_SEDE = :id";
        $stmtLoc = $conn->prepare($queryLoc);
        $stmtLoc->bindValue(':id', $sedeId);
        $stmtLoc->execute();
        $locationName = $stmtLoc->fetchColumn() ?: $locationName;
    } else {
        $queryLoc = "SELECT NOMBRE FROM EMPRESA WHERE ID_EMPRESA = :id";
        $stmtLoc = $conn->prepare($queryLoc);
        $stmtLoc->bindValue(':id', $empresaId);
        $stmtLoc->execute();
        $locationName = $stmtLoc->fetchColumn() ?: $locationName;
    }
    
    // Dependiendo del tipo, construir la consulta apropiada
    if ($tipo === 'faltas') {
        // Consulta para faltas: Empleados activos que no tienen registro de entrada para la fecha
        $query = "
            SELECT
                E.ID_EMPLEADO AS CODIGO,
                E.NOMBRE,
                E.APELLIDO,
                S.NOMBRE AS SEDE,
                EST.NOMBRE AS ESTABLECIMIENTO
            FROM EMPLEADO E
            INNER JOIN ESTABLECIMIENTO EST ON E.ID_ESTABLECIMIENTO = EST.ID_ESTABLECIMIENTO
            INNER JOIN SEDE S ON EST.ID_SEDE = S.ID_SEDE
            LEFT JOIN ASISTENCIA A ON E.ID_EMPLEADO = A.ID_EMPLEADO AND A.FECHA = :fecha AND A.TIPO = 'ENTRADA'
            WHERE E.ACTIVO = 'S'
            AND $whereClause
            AND A.ID_ASISTENCIA IS NULL  -- No registró asistencia
            ORDER BY E.NOMBRE, E.APELLIDO
        ";
    } else {
        // Para los otros tipos - obtener TODOS los registros de asistencia y filtrar con lógica PHP consistente
        $query = "
            SELECT
                E.ID_EMPLEADO AS CODIGO,
                E.NOMBRE,
                E.APELLIDO,
                S.NOMBRE AS SEDE,
                EST.NOMBRE AS ESTABLECIMIENTO,
                A.HORA AS ENTRADA_HORA,
                A.TIPO AS TIPO_REGISTRO,
                A.ID_ASISTENCIA,
                A.ID_EMPLEADO_HORARIO
            FROM ASISTENCIA A
            INNER JOIN EMPLEADO E ON A.ID_EMPLEADO = E.ID_EMPLEADO
            INNER JOIN ESTABLECIMIENTO EST ON E.ID_ESTABLECIMIENTO = EST.ID_ESTABLECIMIENTO
            INNER JOIN SEDE S ON EST.ID_SEDE = S.ID_SEDE
            WHERE A.FECHA = :fecha
            AND A.TIPO = 'ENTRADA'
            AND $whereClause
            ORDER BY E.NOMBRE, E.APELLIDO, A.HORA
        ";
    }
    
    // Registrar la consulta para depuración
    error_log("Query: $query");
    
    // Preparar y ejecutar la consulta
    $stmt = $conn->prepare($query);
    foreach ($params as $key => $val) {
        $stmt->bindValue($key, $val);
        error_log("Param $key: $val");
    }
    $stmt->execute();

    // Obtener resultados
    $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Procesar y filtrar los resultados según el tipo usando lógica consistente
    $data = [];

    if ($tipo === 'faltas') {
        // Para faltas, agregar información del horario usando la función centralizada
        foreach ($registros as &$registro) {
            $horarioInfo = obtenerHorarioEmpleadoSimplificado($registro['CODIGO'], $fecha, $conn);
            $registro['HORARIO_NOMBRE'] = $horarioInfo['horario_nombre'];
            $registro['HORA_ENTRADA'] = $horarioInfo['HORA_ENTRADA'];
        }
        $data = $registros;
    } else {
        // Para otros tipos, filtrar usando lógica consistente (solo entradas)
        foreach ($registros as $registro) {
            // Obtener horario correcto usando el ID específico del registro de asistencia
            // Si no hay ID_EMPLEADO_HORARIO, usar la función general
            if (!empty($registro['ID_EMPLEADO_HORARIO'])) {
                $horarioInfo = obtenerHorarioPorId($registro['ID_EMPLEADO_HORARIO'], $conn);
            } else {
                $horarioInfo = obtenerHorarioEmpleadoSimplificado($registro['CODIGO'], $fecha, $conn);
            }

            // Calcular estado de entrada
            $horaEntradaProgramada = $horarioInfo['HORA_ENTRADA'];
            $horaEntradaReal = $registro['ENTRADA_HORA'];
            $tolerancia = (int)($horarioInfo['TOLERANCIA'] ?? 15);

            $estadoEntrada = calcularEstadoEntrada($horaEntradaProgramada, $horaEntradaReal, $tolerancia);

            // Agregar información del horario al registro
            $registro['HORARIO_NOMBRE'] = $horarioInfo['horario_nombre'];
            $registro['HORARIO_ENTRADA'] = $horarioInfo['HORA_ENTRADA'];
            $registro['HORARIO_SALIDA'] = $horarioInfo['HORA_SALIDA'];
            $registro['TOLERANCIA'] = $horarioInfo['TOLERANCIA'];
            $registro['TIPO_HORARIO'] = $horarioInfo['tipo_horario'];

            // Verificar si coincide con el filtro
            $incluirRegistro = false;
            switch ($tipo) {
                case 'temprano':
                    $incluirRegistro = ($estadoEntrada === 'Temprano');
                    break;
                case 'aTiempo':
                    $incluirRegistro = ($estadoEntrada === 'Puntual');
                    break;
                case 'tarde':
                    $incluirRegistro = ($estadoEntrada === 'Tardanza');
                    break;
            }

            if ($incluirRegistro) {
                // Agregar información adicional del estado calculado
                $registro['ESTADO_CALCULADO'] = $estadoEntrada;
                $data[] = $registro;
            }
        }
    }

    // Registrar resultados para depuración
    error_log("Resultados encontrados después del filtrado: " . count($data));
    
    // Devolver resultados
    echo json_encode([
        'success' => true,
        'data' => $data,
        'tipo' => $tipo,
        'fecha' => $fecha,
        'locationName' => $locationName
    ]);
    
} catch (Exception $e) {
    // Registrar el error
    error_log("Error en get-attendance-details.php: " . $e->getMessage());
    
    // Devolver respuesta de error
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error interno del servidor: ' . $e->getMessage()
    ]);
}
?>