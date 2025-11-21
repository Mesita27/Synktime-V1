<?php
require_once '../../auth/session.php';  // Ruta corregida
require_once '../../config/database.php';  // Ruta corregida

header('Content-Type: application/json');

function checkTable($conn, $tableName) {
    try {
        $stmt = $conn->query("SHOW TABLES LIKE '$tableName'");
        $exists = $stmt->rowCount() > 0;
        
        $structure = [];
        if ($exists) {
            $stmt = $conn->query("DESCRIBE $tableName");
            $structure = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $stmt = $conn->query("SELECT COUNT(*) FROM $tableName");
            $count = $stmt->fetchColumn();
            
            return [
                'exists' => true,
                'count' => $count,
                'structure' => $structure
            ];
        } else {
            return [
                'exists' => false
            ];
        }
    } catch (PDOException $e) {
        return [
            'error' => $e->getMessage()
        ];
    }
}

try {
    $horarioTable = checkTable($conn, 'HORARIO');
    $horarioDiaTable = checkTable($conn, 'HORARIO_DIA');
    $empleadoTable = checkTable($conn, 'EMPLEADO');
    $empleadoHorarioTable = checkTable($conn, 'EMPLEADO_HORARIO');
    $establecimientoTable = checkTable($conn, 'ESTABLECIMIENTO');
    $sedeTable = checkTable($conn, 'SEDE');
    
    // Probar una consulta básica para ver si hay datos
    $empleadoSample = [];
    try {
        $stmt = $conn->query("SELECT ID_EMPLEADO, NOMBRE, APELLIDO FROM EMPLEADO LIMIT 3");
        $empleadoSample = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $empleadoSample = ['error' => $e->getMessage()];
    }
    
    // Probar una consulta básica para ver si hay horarios
    $horarioSample = [];
    try {
        $stmt = $conn->query("SELECT ID_HORARIO, NOMBRE FROM HORARIO LIMIT 3");
        $horarioSample = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $horarioSample = ['error' => $e->getMessage()];
    }
    
    // Probar una consulta JOIN para ver si las relaciones funcionan
    $joinSample = [];
    try {
        $stmt = $conn->query("
            SELECT h.ID_HORARIO, h.NOMBRE, e.NOMBRE as establecimiento 
            FROM HORARIO h
            JOIN ESTABLECIMIENTO e ON h.ID_ESTABLECIMIENTO = e.ID_ESTABLECIMIENTO
            LIMIT 3
        ");
        $joinSample = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $joinSample = ['error' => $e->getMessage()];
    }
    
    echo json_encode([
        'success' => true,
        'tables' => [
            'HORARIO' => $horarioTable,
            'HORARIO_DIA' => $horarioDiaTable,
            'EMPLEADO' => $empleadoTable,
            'EMPLEADO_HORARIO' => $empleadoHorarioTable,
            'ESTABLECIMIENTO' => $establecimientoTable,
            'SEDE' => $sedeTable,
        ],
        'samples' => [
            'empleado' => $empleadoSample,
            'horario' => $horarioSample,
            'join' => $joinSample
        ],
        'session' => isset($_SESSION) ? [
            'id_empresa' => $_SESSION['id_empresa'] ?? 'No definido',
            'usuario' => $_SESSION['usuario'] ?? 'No definido'
        ] : 'No hay sesión activa'
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error de base de datos: ' . $e->getMessage()
    ]);
}
?>