<?php
/**
 * API para obtener lista directa de empleados con estado biométrico
// Incluir configuración de base de datos
require_once __DIR__ . '/../../config/database.php';
$dbCfg = synktime_db_config();
$servername = $dbCfg['host'];
$username = $dbCfg['username'];
$password = $dbCfg['password'];
$dbname = $dbCfg['dbname'];
 */

// Encabezados necesarios
    $conn = synktime_get_pdo();
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

// Incluir configuración de base de datos
require_once '../../config/database.php';

try {
    // Crear conexión
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Detectar qué tabla de empleados existe
    $empleadosTable = 'empleado'; // Default
    
    error_log("Usando tabla de empleados: $empleadosTable");
    
    // Parámetros de búsqueda desde GET
    $searchTerm = isset($_GET['busqueda']) ? $_GET['busqueda'] : '';
    $sede = isset($_GET['sede']) ? $_GET['sede'] : '';
    $establecimiento = isset($_GET['establecimiento']) ? $_GET['establecimiento'] : '';
    $estadoBiometrico = isset($_GET['estado']) ? $_GET['estado'] : '';
    
    // Log para depuración
    error_log("Buscando empleados con: busqueda=$searchTerm, sede=$sede, establecimiento=$establecimiento, estado=$estadoBiometrico");
    
    // Construir consulta base
    $sql = "SELECT 
                e.id as ID_EMPLEADO,
                e.codigo,
                e.nombre,
                e.apellido,
                e.id_establecimiento,
                est.nombre AS nombre_establecimiento,
                s.nombre AS nombre_sede,
                b.facial_enrolled,
                b.fingerprint_enrolled,
                b.last_updated
            FROM 
                $empleadosTable e
            LEFT JOIN establecimiento est ON e.id_establecimiento = est.ID_ESTABLECIMIENTO
            LEFT JOIN sede s ON est.ID_SEDE = s.ID_SEDE
            LEFT JOIN biometric_enrollment b ON e.id = b.id_empleado
            WHERE 
                e.estado = 'A'";
    
    // Añadir condiciones de filtro
    $params = [];
    
    if (!empty($searchTerm)) {
        $sql .= " AND (e.codigo LIKE ? OR e.nombre LIKE ? OR e.apellido LIKE ?)";
        $searchParam = "%$searchTerm%";
        array_push($params, $searchParam, $searchParam, $searchParam);
    }
    
    if (!empty($sede)) {
        $sql .= " AND s.ID_SEDE = ?";
        array_push($params, $sede);
    }
    
    if (!empty($establecimiento)) {
        $sql .= " AND est.ID_ESTABLECIMIENTO = ?";
        array_push($params, $establecimiento);
    }
    
    if (!empty($estadoBiometrico)) {
        switch ($estadoBiometrico) {
            case 'enrolled':
                $sql .= " AND (b.facial_enrolled = 1 AND b.fingerprint_enrolled = 1)";
                break;
            case 'pending':
                $sql .= " AND (b.facial_enrolled = 0 OR b.fingerprint_enrolled = 0 OR b.facial_enrolled IS NULL)";
                break;
            case 'partial':
                $sql .= " AND ((b.facial_enrolled = 1 AND b.fingerprint_enrolled = 0) OR (b.facial_enrolled = 0 AND b.fingerprint_enrolled = 1))";
                break;
        }
    }
    
    // Ordenamiento
    $sql .= " ORDER BY e.nombre ASC";
    
    // Log de SQL
    error_log("SQL: $sql");
    
    // Preparar y ejecutar consulta
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    
    // Obtener resultados
    $employees = [];
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Determinar estado biométrico
        $biometricStatus = 'pending';
        
        if (!is_null($row['facial_enrolled']) && $row['facial_enrolled'] == 1 && 
            !is_null($row['fingerprint_enrolled']) && $row['fingerprint_enrolled'] == 1) {
            $biometricStatus = 'enrolled';
        } elseif ((!is_null($row['facial_enrolled']) && $row['facial_enrolled'] == 1) || 
                 (!is_null($row['fingerprint_enrolled']) && $row['fingerprint_enrolled'] == 1)) {
            $biometricStatus = 'partial';
        }
        
        // Dar formato a los datos
        $employees[] = [
            'id' => $row['ID_EMPLEADO'],
            'ID_EMPLEADO' => $row['ID_EMPLEADO'],
            'codigo' => $row['codigo'],
            'nombre' => $row['nombre'] . ' ' . $row['apellido'],
            'NOMBRE' => $row['nombre'],
            'APELLIDO' => $row['apellido'],
            'establecimiento' => $row['nombre_establecimiento'],
            'ESTABLECIMIENTO' => $row['nombre_establecimiento'],
            'sede' => $row['nombre_sede'],
            'SEDE' => $row['nombre_sede'],
            'biometric_status' => $biometricStatus,
            'facial_enrolled' => !is_null($row['facial_enrolled']) && $row['facial_enrolled'] == 1 ? true : false,
            'fingerprint_enrolled' => !is_null($row['fingerprint_enrolled']) && $row['fingerprint_enrolled'] == 1 ? true : false,
            'last_updated' => $row['last_updated']
        ];
    }
    
    // Responder con los datos
    echo json_encode([
        'success' => true,
        'message' => 'Datos recuperados correctamente',
        'count' => count($employees),
        'data' => $employees
    ]);
    
} catch (PDOException $e) {
    // Log del error
    error_log("Error en direct-employees.php: " . $e->getMessage());
    
    // Responder con error
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error en la base de datos: ' . $e->getMessage(),
        'error' => true
    ]);
}
?>
