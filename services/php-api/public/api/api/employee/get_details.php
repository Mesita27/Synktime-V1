<?php
/**
 * API para obtener detalles del empleado
 * Permite obtener la información completa de un empleado para el modal biométrico
 */

// Incluir configuración de base de datos y sesión
require_once '../../config/database.php';
require_once '../../auth/session.php';

// Verificar autenticación
requireAuth();

header('Content-Type: application/json');

try {
    // Obtener información del usuario actual
    $currentUser = getCurrentUser();
    $empresaId = $currentUser['id_empresa'] ?? null;
    
    if (!$empresaId) {
        throw new Exception('Sesión inválida - empresa no encontrada');
    }
    
    // Verificar que se proporcionó un ID de empleado
    if (!isset($_GET['id']) || empty($_GET['id'])) {
        throw new Exception('Se requiere ID de empleado');
    }
    
    $employeeId = $_GET['id'];
    
    // Consulta para obtener los datos del empleado con información de establecimiento
    // IMPORTANTE: Filtrar por empresa para seguridad
    $query = "SELECT 
                e.ID_EMPLEADO,
                e.CODIGO,
                e.NOMBRES,
                e.APELLIDOS,
                e.ESTADO,
                est.ID_ESTABLECIMIENTO,
                est.NOMBRE AS NOMBRE_ESTABLECIMIENTO,
                s.ID_SEDE,
                s.NOMBRE AS NOMBRE_SEDE
              FROM 
                empleado e
              LEFT JOIN 
                establecimiento est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
              LEFT JOIN 
                sede s ON est.ID_SEDE = s.ID_SEDE
              WHERE 
                (e.ID_EMPLEADO = ? OR e.CODIGO = ?)
                AND s.ID_EMPRESA = ?";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([$employeeId, $employeeId, $empresaId]);
    
    $employee = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$employee) {
        throw new Exception('Empleado no encontrado');
    }
    
    // Obtener estado biométrico del empleado
    $bioQuery = "SELECT COUNT(*) AS count, biometric_type 
                 FROM employee_biometrics 
                 WHERE employee_id = ?
                 GROUP BY biometric_type";
    
    $bioStmt = $pdo->prepare($bioQuery);
    $bioStmt->execute([$employeeId]);
    $biometrics = $bioStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Formatear estado biométrico
    $biometricStatus = [
        'facial' => false,
        'fingerprint' => false
    ];
    
    foreach ($biometrics as $bio) {
        if ($bio['biometric_type'] == 'face' && $bio['count'] > 0) {
            $biometricStatus['facial'] = true;
        }
        if ($bio['biometric_type'] == 'fingerprint' && $bio['count'] > 0) {
            $biometricStatus['fingerprint'] = true;
        }
    }
    
    // Formatear el nombre completo
    $fullName = trim($employee['NOMBRES'] . ' ' . $employee['APELLIDOS']);
    
    // Preparar respuesta
    $response = [
        'success' => true,
        'employee' => [
            'id' => $employee['ID_EMPLEADO'],
            'codigo' => $employee['CODIGO'],
            'nombres' => $employee['NOMBRES'],
            'apellidos' => $employee['APELLIDOS'],
            'nombre_completo' => $fullName,
            'estado' => $employee['ESTADO'],
            'establecimiento' => [
                'id' => $employee['ID_ESTABLECIMIENTO'],
                'nombre' => $employee['NOMBRE_ESTABLECIMIENTO']
            ],
            'sede' => [
                'id' => $employee['ID_SEDE'],
                'nombre' => $employee['NOMBRE_SEDE']
            ]
        ],
        'biometric_status' => $biometricStatus
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
