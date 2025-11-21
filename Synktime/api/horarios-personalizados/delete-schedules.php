<?php
require_once __DIR__ . '/../../auth/session.php';
requireAuth();
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');

// Verificar método DELETE o POST
if (!in_array($_SERVER['REQUEST_METHOD'], ['DELETE', 'POST'])) {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

try {
    // Obtener información del usuario actual
    $currentUser = getCurrentUser();
    $empresaId = $currentUser['id_empresa'] ?? null;
    
    if (!$empresaId) {
        echo json_encode(['success' => false, 'message' => 'Sesión inválida - empresa no encontrada']);
        exit;
    }

    // Obtener datos de la petición
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data) {
        echo json_encode(['success' => false, 'message' => 'Datos JSON inválidos']);
        exit;
    }

    // Validar datos requeridos
    if (!isset($data['horario_ids']) || !is_array($data['horario_ids']) || empty($data['horario_ids'])) {
        echo json_encode(['success' => false, 'message' => 'IDs de horarios requeridos']);
        exit;
    }

    $horarioIds = array_map('intval', $data['horario_ids']);
    $deleteType = $data['delete_type'] ?? 'soft'; // soft (desactivar) o hard (eliminar permanentemente)

    // Verificar que todos los horarios pertenecen a empleados de la empresa del usuario
    $placeholders = implode(',', array_fill(0, count($horarioIds), '?'));
    $sqlVerify = "
        SELECT 
            ehp.ID_EMPLEADO_HORARIO,
            ehp.ID_EMPLEADO,
            ehp.NOMBRE_TURNO,
            ehp.ID_DIA,
            e.NOMBRE,
            e.APELLIDO,
            ds.NOMBRE as dia_nombre
        FROM empleado_horario_personalizado ehp
        JOIN empleado e ON ehp.ID_EMPLEADO = e.ID_EMPLEADO
        JOIN establecimiento est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
        JOIN sede s ON est.ID_SEDE = s.ID_SEDE
        JOIN dia_semana ds ON ehp.ID_DIA = ds.ID_DIA
        WHERE ehp.ID_EMPLEADO_HORARIO IN ($placeholders)
        AND s.ID_EMPRESA = ?
    ";

    $params = array_merge($horarioIds, [$empresaId]);
    $stmt = $conn->prepare($sqlVerify);
    $stmt->execute($params);
    $horariosEncontrados = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($horariosEncontrados) !== count($horarioIds)) {
        echo json_encode(['success' => false, 'message' => 'Algunos horarios no fueron encontrados o no tiene permisos']);
        exit;
    }

    // Iniciar transacción
    $conn->beginTransaction();

    try {
        $horariosEliminados = [];
        
        if ($deleteType === 'hard') {
            // Eliminación permanente
            $sqlDelete = "
                DELETE FROM empleado_horario_personalizado 
                WHERE ID_EMPLEADO_HORARIO IN ($placeholders)
            ";
            $stmtDelete = $conn->prepare($sqlDelete);
            $stmtDelete->execute($horarioIds);
            
            $horariosEliminados = $horariosEncontrados;
            $mensaje = 'Horarios eliminados permanentemente';
            
        } else {
            // Eliminación suave (desactivar) - Establecer fecha_hasta al día anterior
            $sqlDeactivate = "
                UPDATE empleado_horario_personalizado
                SET ACTIVO = 'N',
                    FECHA_HASTA = DATE_SUB(CURDATE(), INTERVAL 1 DAY),
                    UPDATED_AT = NOW()
                WHERE ID_EMPLEADO_HORARIO IN ($placeholders)
            ";
            $stmtDeactivate = $conn->prepare($sqlDeactivate);
            $stmtDeactivate->execute($horarioIds);

            $horariosEliminados = $horariosEncontrados;
            $mensaje = 'Horarios desactivados exitosamente (vigencia terminada ayer)';
        }

        // Registrar en log de cambios (opcional - puedes crear esta tabla si quieres auditoría)
        foreach ($horariosEliminados as $horario) {
            // Aquí podrías insertar en una tabla de auditoría si la tienes
            // Por ahora solo guardamos la información para la respuesta
        }

        $conn->commit();

        // Preparar resumen de eliminaciones por empleado
        $resumenPorEmpleado = [];
        foreach ($horariosEliminados as $horario) {
            $empleadoId = $horario['ID_EMPLEADO'];
            $empleadoNombre = trim($horario['NOMBRE'] . ' ' . $horario['APELLIDO']);
            
            if (!isset($resumenPorEmpleado[$empleadoId])) {
                $resumenPorEmpleado[$empleadoId] = [
                    'empleado_id' => $empleadoId,
                    'empleado_nombre' => $empleadoNombre,
                    'horarios_eliminados' => []
                ];
            }
            
            $resumenPorEmpleado[$empleadoId]['horarios_eliminados'][] = [
                'horario_id' => $horario['ID_EMPLEADO_HORARIO'],
                'nombre_turno' => $horario['NOMBRE_TURNO'],
                'dia' => $horario['dia_nombre']
            ];
        }

        echo json_encode([
            'success' => true,
            'message' => $mensaje,
            'data' => [
                'total_eliminados' => count($horariosEliminados),
                'delete_type' => $deleteType,
                'horarios_eliminados' => $horariosEliminados,
                'resumen_por_empleado' => array_values($resumenPorEmpleado)
            ]
        ]);

    } catch (Exception $e) {
        $conn->rollBack();
        throw $e;
    }

} catch (PDOException $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    echo json_encode(['success' => false, 'message' => 'Error de base de datos: ' . $e->getMessage()]);
} catch (Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>