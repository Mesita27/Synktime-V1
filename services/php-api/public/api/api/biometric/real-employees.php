<?php
/**
 * API para obtener lista directa de empleados con estado biométrico sin datos simulados
 * Versión optimizada que solo devuelve datos reales de la base de datos
 */

// Encabezados necesarios
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

// Activar reporte de errores para depuración
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Log para depuración
error_log("==========================================");
error_log("Iniciando real-employees.php");

try {
    // Incluir configuración de base de datos
    require_once '../../config/database.php';
    
    // Conectar a la base de datos (usando las variables ya definidas en database.php)
    error_log("Intentando conectar a la base de datos: $dbname en $host");
    $conn = $pdo; // Usar la conexión ya establecida
    
    // Verificar conexión
    error_log("Conexión exitosa a la base de datos");
    
    // Detectar qué tabla de empleados existe
    $empleadosTable = 'empleado'; // Default
    
    // Verificar si la tabla empleado existe
    try {
        $stmt = $conn->query("SHOW TABLES LIKE '$empleadosTable'");
        $tableExists = $stmt->rowCount() > 0;
        if (!$tableExists) {
            throw new Exception("La tabla $empleadosTable no existe en la base de datos");
        }
    } catch (PDOException $e) {
        throw new Exception("Error al verificar tabla: " . $e->getMessage());
    }
    
    // Parámetros de búsqueda desde GET
    $searchTerm = isset($_GET['busqueda']) ? $_GET['busqueda'] : '';
    $sede = isset($_GET['sede']) ? $_GET['sede'] : '';
    $establecimiento = isset($_GET['establecimiento']) ? $_GET['establecimiento'] : '';
    $estadoBiometrico = isset($_GET['estado']) ? $_GET['estado'] : '';
    
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
    error_log("Params: " . json_encode($params));
    
    // Preparar y ejecutar consulta
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    
    // Obtener resultados
    $employees = [];
    $count = 0;
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $count++;
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
    
    error_log("Encontrados: " . count($employees) . " empleados reales (contador: $count)");
    
    // Verificar si no se encontraron empleados
    if (count($employees) === 0) {
        throw new Exception("No se encontraron empleados reales en la base de datos");
    }
    
    // Responder con los datos
    echo json_encode([
        'success' => true,
        'message' => 'Datos recuperados correctamente de la base de datos',
        'count' => count($employees),
        'data' => $employees,
        'real_data' => true
    ]);
    
    error_log("Respuesta enviada correctamente con " . count($employees) . " empleados reales");
    
} catch (PDOException $e) {
    // Log del error
    error_log("Error en real-employees.php (PDO): " . $e->getMessage());
    
    // Responder con error
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error en la base de datos: ' . $e->getMessage(),
        'error' => true
    ]);
} catch (Exception $e) {
    // Capturar cualquier otro tipo de excepción
    error_log("Error general en real-employees.php: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error general: ' . $e->getMessage(),
        'error' => true
    ]);
}

// Log final
error_log("Finalizado real-employees.php");
error_log("==========================================");
?>
