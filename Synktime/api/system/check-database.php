<?php
/**
 * API para verificar el estado de la base de datos
 * Verifica la conexión y la existencia de tablas principales
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Manejar solicitudes preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

try {
    require_once '../../config/database.php';
    
    $response = [
        'success' => false,
        'connection' => false,
        'tables' => [],
        'message' => '',
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    // Verificar conexión
    if ($conn) {
        $response['connection'] = true;
        $response['message'] = 'Conexión exitosa a la base de datos';
        
        // Verificar tablas principales
        $tables_to_check = [
            'asistencia' => 'Registros de asistencia',
            'empleado' => 'Información de empleados',
            'biometric_data' => 'Datos biométricos',
            'biometric_logs' => 'Logs de verificación biométrica',
            'horario' => 'Horarios de trabajo',
            'establecimiento' => 'Sedes y establecimientos'
        ];
        
        foreach ($tables_to_check as $table => $description) {
            try {
                $query = "SHOW TABLES LIKE '$table'";
                $stmt = $conn->query($query);
                $result = $stmt->fetchAll();
                
                $response['tables'][$table] = [
                    'exists' => count($result) > 0,
                    'description' => $description
                ];
                
                if (count($result) > 0) {
                    // Obtener información adicional de la tabla
                    $count_query = "SELECT COUNT(*) as total FROM `$table`";
                    $count_stmt = $conn->query($count_query);
                    $count_result = $count_stmt->fetch(PDO::FETCH_ASSOC);
                    $response['tables'][$table]['record_count'] = $count_result['total'];
                }
                
            } catch (Exception $e) {
                $response['tables'][$table] = [
                    'exists' => false,
                    'description' => $description,
                    'error' => $e->getMessage()
                ];
            }
        }
        
        // Verificar si todas las tablas esenciales existen
        $essential_tables = ['asistencia', 'empleado', 'biometric_data'];
        $all_essential_exist = true;
        
        foreach ($essential_tables as $table) {
            if (!isset($response['tables'][$table]) || !$response['tables'][$table]['exists']) {
                $all_essential_exist = false;
                break;
            }
        }
        
        $response['success'] = $all_essential_exist;
        
        if (!$all_essential_exist) {
            $response['message'] = 'Algunas tablas esenciales no existen. Importa el archivo SQL.';
        } else {
            $response['message'] = 'Sistema de base de datos configurado correctamente';
        }
        
    } else {
        $response['message'] = 'No se pudo establecer conexión con la base de datos';
    }
    
} catch (Exception $e) {
    $response = [
        'success' => false,
        'connection' => false,
        'message' => 'Error: ' . $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ];
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>
