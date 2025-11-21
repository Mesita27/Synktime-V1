<?php
/**
 * API para obtener detalles completos de un empleado
 * Incluye información personal y todos sus horarios asignados
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Incluir archivos necesarios
require_once '../../config/database.php';
require_once '../../includes/attendance_status_utils.php';

try {
    // Verificar método de solicitud
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        throw new Exception('Método no permitido');
    }

    // Obtener código del empleado
    $codigo = isset($_GET['codigo']) ? trim($_GET['codigo']) : null;

    if (!$codigo) {
        throw new Exception('Código de empleado requerido');
    }

    // Conectar a la base de datos
    $conn = getConnection();

    // Obtener información básica del empleado
    $queryEmpleado = "
        SELECT
            e.ID_EMPLEADO,
            e.DNI,
            e.NOMBRE,
            e.APELLIDO,
            s.NOMBRE_SEDE as SEDE,
            est.NOMBRE_ESTABLECIMIENTO as ESTABLECIMIENTO
    FROM empleado e
        LEFT JOIN SEDE s ON e.ID_SEDE = s.ID_SEDE
        LEFT JOIN ESTABLECIMIENTO est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
        WHERE e.ID_EMPLEADO = ?
    ";

    $stmt = $conn->prepare($queryEmpleado);
    $stmt->bind_param("s", $codigo);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception('Empleado no encontrado');
    }

    $empleado = $result->fetch_assoc();

    // Obtener horarios del empleado
    $queryHorarios = "
        SELECT
            eh.ID_EMPLEADO_HORARIO,
            eh.HORA_ENTRADA,
            eh.HORA_SALIDA,
            eh.TOLERANCIA,
            eh.DIAS_SEMANA,
            eh.FECHA_INICIO_VIGENCIA,
            eh.FECHA_FIN_VIGENCIA,
            eh.ACTIVO,
            eh.ID_HORARIO
    FROM empleado_horario eh
        WHERE eh.ID_EMPLEADO = ?
        ORDER BY eh.FECHA_INICIO_VIGENCIA DESC, eh.ID_EMPLEADO_HORARIO DESC
    ";

    $stmtHorarios = $conn->prepare($queryHorarios);
    $stmtHorarios->bind_param("s", $codigo);
    $stmtHorarios->execute();
    $resultHorarios = $stmtHorarios->get_result();

    $horarios = [];
    while ($row = $resultHorarios->fetch_assoc()) {
        // Formatear fechas
        if ($row['FECHA_INICIO_VIGENCIA']) {
            $row['FECHA_INICIO_VIGENCIA'] = date('d/m/Y', strtotime($row['FECHA_INICIO_VIGENCIA']));
        }
        if ($row['FECHA_FIN_VIGENCIA']) {
            $row['FECHA_FIN_VIGENCIA'] = date('d/m/Y', strtotime($row['FECHA_FIN_VIGENCIA']));
        }

        $horarios[] = $row;
    }

    $empleado['horarios'] = $horarios;

    // Cerrar conexiones
    $stmt->close();
    $stmtHorarios->close();
    $conn->close();

    // Respuesta exitosa
    echo json_encode([
        'success' => true,
        'empleado' => $empleado
    ]);

} catch (Exception $e) {
    // Respuesta de error
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>