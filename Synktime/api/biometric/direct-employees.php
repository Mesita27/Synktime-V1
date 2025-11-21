<?php
/**
 * API para obtener lista directa de empleados con estado biométrico
 * Versión optimizada y corregida para solucionar problemas de conexión
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
error_log("Iniciando direct-employees.php");

require_once __DIR__ . '/../config.php';

try {
    $dbConfig = synktime_db_config();
    error_log("Intentando conectar a la base de datos: {$dbConfig['dbname']} en {$dbConfig['host']}");

    $conn = synktime_get_pdo();
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    error_log("Conexión exitosa a la base de datos");
    
    // Detectar qué tabla de empleados existe
    $empleadosTable = 'empleado'; // Default

    // Verificar si la tabla empleado existe
    try {
        $stmt = $conn->query("SHOW TABLES LIKE '$empleadosTable'");
        if ($stmt->rowCount() == 0) {
            error_log("¡ADVERTENCIA! La tabla $empleadosTable no existe");
        }
    } catch (PDOException $e) {
        error_log("Error al verificar tablas: " . $e->getMessage());
        // Continuar con el valor predeterminado
    }
    
    // Parámetros de búsqueda desde GET
    $searchTerm = isset($_GET['busqueda']) ? $_GET['busqueda'] : '';
    $sede = isset($_GET['sede']) ? $_GET['sede'] : '';
    $establecimiento = isset($_GET['establecimiento']) ? $_GET['establecimiento'] : '';
    $estadoBiometrico = isset($_GET['estado']) ? $_GET['estado'] : '';
    
    // Parámetros de paginación
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $limit = isset($_GET['limit']) ? max(1, min(500, intval($_GET['limit']))) : 50; // Máximo 500 por seguridad
    $offset = ($page - 1) * $limit;
    
    // Log para depuración
    error_log("Buscando empleados con: busqueda=$searchTerm, sede=$sede, establecimiento=$establecimiento, estado=$estadoBiometrico, page=$page, limit=$limit");
    
    // Construir consulta base - adaptada para las tablas reales de la base de datos
    $sql = "SELECT 
                e.ID_EMPLEADO as ID_EMPLEADO,
                e.ID_EMPLEADO as codigo,
                e.NOMBRE as nombre,
                e.APELLIDO as apellido,
                e.DNI as dni,
                e.ID_ESTABLECIMIENTO as id_establecimiento,
                est.NOMBRE AS nombre_establecimiento,
                s.NOMBRE AS nombre_sede,
                s.ID_SEDE as ID_SEDE,
                -- Verificar inscripciones biométricas desde biometric_data
                CASE 
                    WHEN bd_facial.ID IS NOT NULL THEN 1 
                    ELSE 0 
                END as facial_enrolled,
                CASE 
                    WHEN bd_finger.ID IS NOT NULL THEN 1 
                    ELSE 0 
                END as fingerprint_enrolled,
                GREATEST(
                    COALESCE(bd_facial.UPDATED_AT, '1970-01-01'),
                    COALESCE(bd_finger.UPDATED_AT, '1970-01-01')
                ) as last_updated
            FROM 
                $empleadosTable e
            LEFT JOIN establecimiento est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
            LEFT JOIN sede s ON est.ID_SEDE = s.ID_SEDE
            LEFT JOIN biometric_data bd_facial ON e.ID_EMPLEADO = bd_facial.ID_EMPLEADO 
                AND bd_facial.BIOMETRIC_TYPE = 'face' 
                AND bd_facial.ACTIVO = 'S'
            LEFT JOIN biometric_data bd_finger ON e.ID_EMPLEADO = bd_finger.ID_EMPLEADO 
                AND bd_finger.BIOMETRIC_TYPE = 'fingerprint' 
                AND bd_finger.ACTIVO = 'S'
            WHERE 
                e.ESTADO = 'A'";
    
    // Añadir condiciones de filtro
    $params = [];
    
    if (!empty($searchTerm)) {
        $sql .= " AND (e.ID_EMPLEADO LIKE ? OR e.NOMBRE LIKE ? OR e.APELLIDO LIKE ? OR e.DNI LIKE ?)";
        $searchParam = "%$searchTerm%";
        array_push($params, $searchParam, $searchParam, $searchParam, $searchParam);
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
                $sql .= " AND (bd_facial.ID IS NOT NULL AND bd_finger.ID IS NOT NULL)";
                break;
            case 'pending':
                $sql .= " AND (bd_facial.ID IS NULL AND bd_finger.ID IS NULL)";
                break;
            case 'partial':
                $sql .= " AND ((bd_facial.ID IS NOT NULL AND bd_finger.ID IS NULL) OR (bd_facial.ID IS NULL AND bd_finger.ID IS NOT NULL))";
                break;
        }
    }
    
    // Ordenamiento
    $sql .= " ORDER BY e.NOMBRE ASC";
    
    // Contar total de registros (antes de LIMIT)
    $countSql = "SELECT COUNT(*) as total FROM ($sql) as count_table";
    $countStmt = $conn->prepare($countSql);
    $countStmt->execute($params);
    $totalRecords = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Añadir LIMIT y OFFSET para paginación
    $sql .= " LIMIT $limit OFFSET $offset";
    
    // Log de SQL
    error_log("SQL: $sql");
    error_log("Params: " . json_encode($params));
    error_log("Paginación: página $page, límite $limit, offset $offset, total $totalRecords");
    
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
            'codigo' => $row['codigo'], // Ahora es el ID_EMPLEADO
            'CODIGO' => $row['codigo'], // Ahora es el ID_EMPLEADO
            'nombre' => $row['nombre'] . ' ' . $row['apellido'],
            'NOMBRE' => $row['nombre'],
            'APELLIDO' => $row['apellido'],
            'DNI' => $row['dni'],
            'establecimiento' => $row['nombre_establecimiento'],
            'ESTABLECIMIENTO' => $row['nombre_establecimiento'],
            'sede' => $row['nombre_sede'],
            'SEDE' => $row['nombre_sede'],
            'ID_SEDE' => $row['ID_SEDE'],
            'ID_ESTABLECIMIENTO' => $row['id_establecimiento'],
            'biometric_status' => $biometricStatus,
            'facial_enrolled' => !is_null($row['facial_enrolled']) && $row['facial_enrolled'] == 1 ? true : false,
            'fingerprint_enrolled' => !is_null($row['fingerprint_enrolled']) && $row['fingerprint_enrolled'] == 1 ? true : false,
            'last_updated' => $row['last_updated']
        ];
    }
    
    error_log("Encontrados: " . count($employees) . " empleados (contador: $count) de $totalRecords total");
    
    // Responder con los datos reales de la base de datos
    echo json_encode([
        'success' => true,
        'message' => 'Datos recuperados correctamente desde la base de datos',
        'count' => count($employees),
        'data' => $employees,
        'pagination' => [
            'current_page' => $page,
            'per_page' => $limit,
            'total' => $totalRecords,
            'total_pages' => ceil($totalRecords / $limit),
            'has_next' => $page < ceil($totalRecords / $limit),
            'has_prev' => $page > 1
        ],
        'is_real_data' => true // Indicador de que son datos reales
    ]);
    
    error_log("Respuesta enviada correctamente con " . count($employees) . " empleados");
    
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
} catch (Exception $e) {
    // Capturar cualquier otro tipo de excepción
    error_log("Error general en direct-employees.php: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error general: ' . $e->getMessage(),
        'error' => true
    ]);
}

// Log final
error_log("Finalizado direct-employees.php");
error_log("==========================================");
?>
