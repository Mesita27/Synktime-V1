<?php
require_once __DIR__ . '/../../auth/session.php';
requireAuth();
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');

// Establecer zona horaria de Colombia
date_default_timezone_set('America/Bogota');

// Verificar el método de la solicitud
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Método no permitido'
    ]);
    exit;
}

// Obtener datos del POST
$id_asistencia = $_POST['id_asistencia'] ?? null;
$observacion = $_POST['observacion'] ?? '';

// Validar datos
if (!$id_asistencia) {
    echo json_encode([
        'success' => false,
        'message' => 'ID de asistencia no proporcionado'
    ]);
    exit;
}

// Limitar la observación a 200 caracteres
$observacion = substr($observacion, 0, 200);

// Actualizar la observación en la base de datos
try {
    $sql = "UPDATE ASISTENCIA SET OBSERVACION = ? WHERE ID_ASISTENCIA = ?";
    $stmt = $conn->prepare($sql);
    $result = $stmt->execute([$observacion, $id_asistencia]);
    
    if ($result) {
        // Obtener los datos actualizados para devolverlos
        $sql_get = "SELECT a.ID_ASISTENCIA, a.TIPO, a.OBSERVACION, a.FECHA, a.HORA, 
                          CONCAT(e.NOMBRE, ' ', e.APELLIDO) AS nombre_empleado
                   FROM ASISTENCIA a
                   JOIN EMPLEADO e ON a.ID_EMPLEADO = e.ID_EMPLEADO
                   WHERE a.ID_ASISTENCIA = ?";
        $stmt_get = $conn->prepare($sql_get);
        $stmt_get->execute([$id_asistencia]);
        $updated_data = $stmt_get->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'message' => 'Observación actualizada correctamente',
            'data' => $updated_data
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Error al actualizar la observación'
        ]);
    }
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error de base de datos: ' . $e->getMessage()
    ]);
}
?>