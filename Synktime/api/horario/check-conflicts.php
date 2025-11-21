<?php
require_once '../../auth/session.php';
require_once '../../config/database.php';

header('Content-Type: application/json');

// Verificar método
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

// Obtener datos
$data = json_decode(file_get_contents('php://input'), true);
if (!$data || !isset($data['id_empleado']) || !isset($data['horarios']) || !isset($data['fecha_desde'])) {
    echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
    exit;
}

$id_empleado = intval($data['id_empleado']);
$horarios = $data['horarios'];
$fecha_desde = $data['fecha_desde'];
$fecha_hasta = !empty($data['fecha_hasta']) ? $data['fecha_hasta'] : null;

try {
    // Obtener información de los horarios a asignar
    $placeholders = implode(',', array_fill(0, count($horarios), '?'));
    $sql = "
        SELECT 
            h.ID_HORARIO, 
            h.NOMBRE, 
            h.HORA_ENTRADA, 
            h.HORA_SALIDA,
            GROUP_CONCAT(hd.ID_DIA) as dias
        FROM horario h
        LEFT JOIN horario_dia hd ON h.ID_HORARIO = hd.ID_HORARIO
        WHERE h.ID_HORARIO IN ($placeholders)
        GROUP BY h.ID_HORARIO
    ";
    
    $stmt = $conn->prepare($sql);
    foreach ($horarios as $i => $id) {
        $stmt->bindValue($i+1, $id);
    }
    $stmt->execute();
    
    $nuevosHorarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Obtener horarios actuales del empleado en el período especificado
    $sqlActuales = "
        SELECT 
            eh.ID_EMPLEADO_HORARIO,
            h.ID_HORARIO, 
            h.NOMBRE, 
            h.HORA_ENTRADA, 
            h.HORA_SALIDA,
            GROUP_CONCAT(hd.ID_DIA) as dias,
            eh.FECHA_DESDE,
            eh.FECHA_HASTA
        FROM EMPLEADO_HORARIO eh
        JOIN HORARIO h ON eh.ID_HORARIO = h.ID_HORARIO
        LEFT JOIN HORARIO_DIA hd ON h.ID_HORARIO = hd.ID_HORARIO
        WHERE eh.ID_EMPLEADO = :id_empleado
        AND (
            (eh.FECHA_HASTA IS NULL) OR
            (:fecha_hasta IS NULL AND eh.FECHA_DESDE <= :fecha_desde AND eh.FECHA_HASTA IS NULL) OR
            (:fecha_hasta IS NULL AND eh.FECHA_DESDE >= :fecha_desde) OR
            (:fecha_hasta IS NOT NULL AND eh.FECHA_DESDE <= :fecha_hasta AND (eh.FECHA_HASTA IS NULL OR eh.FECHA_HASTA >= :fecha_desde))
        )
        GROUP BY eh.ID_EMPLEADO_HORARIO
    ";
    
    $stmt = $conn->prepare($sqlActuales);
    $stmt->bindValue(':id_empleado', $id_empleado);
    $stmt->bindValue(':fecha_desde', $fecha_desde);
    $stmt->bindValue(':fecha_hasta', $fecha_hasta, $fecha_hasta ? PDO::PARAM_STR : PDO::PARAM_NULL);
    $stmt->execute();
    $horariosActuales = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Verificar conflictos
    $conflicts = [];
    
    foreach ($nuevosHorarios as $nuevo) {
        $nuevosDias = explode(',', $nuevo['dias']);
        
        foreach ($horariosActuales as $actual) {
            // Evitar comparar el mismo horario
            if ($nuevo['ID_HORARIO'] == $actual['ID_HORARIO']) {
                continue;
            }
            
            $actualesDias = explode(',', $actual['dias']);
            
            // Verificar si hay días en común
            $diasComunes = array_intersect($nuevosDias, $actualesDias);
            
            if (!empty($diasComunes)) {
                // Convertir horas a timestamps para comparar
                $nuevoInicio = strtotime("1970-01-01 " . $nuevo['HORA_ENTRADA']);
                $nuevoFin = strtotime("1970-01-01 " . $nuevo['HORA_SALIDA']);
                $actualInicio = strtotime("1970-01-01 " . $actual['HORA_ENTRADA']);
                $actualFin = strtotime("1970-01-01 " . $actual['HORA_SALIDA']);
                
                // Ajustar si el horario termina al día siguiente
                if ($nuevoFin < $nuevoInicio) {
                    $nuevoFin += 86400; // Añadir 24 horas en segundos
                }
                if ($actualFin < $actualInicio) {
                    $actualFin += 86400;
                }
                
                // Verificar solapamiento
                $overlap = ($nuevoInicio < $actualFin && $nuevoFin > $actualInicio);
                
                if ($overlap) {
                    // Convertir IDs de días a nombres
                    $diasNombres = [];
                    foreach ($diasComunes as $diaId) {
                        $diasNombres[] = getDayName(intval($diaId));
                    }
                    
                    $conflicts[] = [
                        'nuevoHorario' => [
                            'id' => $nuevo['ID_HORARIO'],
                            'nombre' => $nuevo['NOMBRE'],
                            'horas' => $nuevo['HORA_ENTRADA'] . ' - ' . $nuevo['HORA_SALIDA']
                        ],
                        'horarioExistente' => [
                            'id' => $actual['ID_HORARIO'],
                            'nombre' => $actual['NOMBRE'],
                            'horas' => $actual['HORA_ENTRADA'] . ' - ' . $actual['HORA_SALIDA'],
                            'fecha_desde' => $actual['FECHA_DESDE'],
                            'fecha_hasta' => $actual['FECHA_HASTA']
                        ],
                        'dias' => $diasNombres,
                        'dias_ids' => $diasComunes
                    ];
                }
            }
        }
    }
    
    echo json_encode([
        'success' => true,
        'hasConflicts' => !empty($conflicts),
        'conflicts' => $conflicts
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error de base de datos: ' . $e->getMessage()
    ]);
}

// Función para obtener nombre del día
function getDayName($day) {
    $days = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];
    return $days[$day - 1] ?? "Día $day";
}
?>