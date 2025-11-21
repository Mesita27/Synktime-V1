<?php
require_once __DIR__ . '/../../auth/session.php';
requireAuth();
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');

// Verificar método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
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
    $requiredFields = ['empleado_origen_id', 'empleados_destino_ids', 'fecha_desde'];
    foreach ($requiredFields as $field) {
        if (!isset($data[$field]) || empty($data[$field])) {
            echo json_encode(['success' => false, 'message' => "Campo requerido: $field"]);
            exit;
        }
    }

    $empleadoOrigenId = (int)$data['empleado_origen_id'];
    $empleadosDestinoIds = array_map('intval', $data['empleados_destino_ids']);
    $fechaDesde = $data['fecha_desde'];
    $fechaHasta = !empty($data['fecha_hasta']) ? $data['fecha_hasta'] : null;
    $replaceExisting = $data['replace_existing'] ?? false;
    $copyOnlyActiveDays = $data['copy_only_active_days'] ?? false;

    // Validar formato de fechas
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaDesde)) {
        echo json_encode(['success' => false, 'message' => 'Formato de fecha desde inválido']);
        exit;
    }

    if ($fechaHasta && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaHasta)) {
        echo json_encode(['success' => false, 'message' => 'Formato de fecha hasta inválido']);
        exit;
    }

    // Verificar que el empleado origen y destinos pertenecen a la empresa
    $allEmployeeIds = array_merge([$empleadoOrigenId], $empleadosDestinoIds);
    $placeholders = implode(',', array_fill(0, count($allEmployeeIds), '?'));
    
    $sqlVerifyEmployees = "
        SELECT 
            e.ID_EMPLEADO,
            e.NOMBRE,
            e.APELLIDO
        FROM empleado e
        JOIN establecimiento est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
        JOIN sede s ON est.ID_SEDE = s.ID_SEDE
        WHERE e.ID_EMPLEADO IN ($placeholders)
        AND s.ID_EMPRESA = ?
        AND e.ACTIVO = 'S'
    ";

    $params = array_merge($allEmployeeIds, [$empresaId]);
    $stmt = $conn->prepare($sqlVerifyEmployees);
    $stmt->execute($params);
    $empleadosEncontrados = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($empleadosEncontrados) !== count($allEmployeeIds)) {
        echo json_encode(['success' => false, 'message' => 'Algunos empleados no fueron encontrados o no tiene permisos']);
        exit;
    }

    // Obtener horarios del empleado origen
    $sqlOrigen = "
        SELECT 
            ehp.ID_DIA,
            ehp.HORA_ENTRADA,
            ehp.HORA_SALIDA,
            ehp.TOLERANCIA,
            ehp.NOMBRE_TURNO,
            ehp.ORDEN_TURNO,
            ehp.OBSERVACIONES
        FROM empleado_horario_personalizado ehp
        WHERE ehp.ID_EMPLEADO = ?
        AND ehp.ACTIVO = 'S'
        AND (ehp.FECHA_HASTA IS NULL OR ehp.FECHA_HASTA >= CURDATE())
    ";

    if ($copyOnlyActiveDays) {
        $sqlOrigen .= " AND ehp.FECHA_DESDE <= CURDATE()";
    }

    $sqlOrigen .= " ORDER BY ehp.ID_DIA, ehp.ORDEN_TURNO";

    $stmtOrigen = $conn->prepare($sqlOrigen);
    $stmtOrigen->execute([$empleadoOrigenId]);
    $horariosOrigen = $stmtOrigen->fetchAll(PDO::FETCH_ASSOC);

    if (empty($horariosOrigen)) {
        echo json_encode(['success' => false, 'message' => 'El empleado origen no tiene horarios activos para copiar']);
        exit;
    }

    // Iniciar transacción
    $conn->beginTransaction();

    try {
        $resultados = [];
        
        foreach ($empleadosDestinoIds as $empleadoDestinoId) {
            $empleadoDestino = array_filter($empleadosEncontrados, function($emp) use ($empleadoDestinoId) {
                return $emp['ID_EMPLEADO'] == $empleadoDestinoId;
            });
            $empleadoDestino = reset($empleadoDestino);
            
            if (!$empleadoDestino) {
                continue;
            }

            $horariosCopiados = 0;
            $horariosActualizados = 0;

            // Si se solicita reemplazar, desactivar horarios existentes
            if ($replaceExisting) {
                $sqlDeactivate = "
                    UPDATE empleado_horario_personalizado 
                    SET ACTIVO = 'N', UPDATED_AT = NOW() 
                    WHERE ID_EMPLEADO = ? 
                    AND ACTIVO = 'S'
                    AND (FECHA_HASTA IS NULL OR FECHA_HASTA >= CURDATE())
                ";
                $stmtDeactivate = $conn->prepare($sqlDeactivate);
                $stmtDeactivate->execute([$empleadoDestinoId]);
            }

            // Copiar cada horario
            foreach ($horariosOrigen as $horario) {
                // Verificar si ya existe un horario similar
                $sqlCheckExisting = "
                    SELECT ID_EMPLEADO_HORARIO 
                    FROM empleado_horario_personalizado 
                    WHERE ID_EMPLEADO = ? 
                    AND ID_DIA = ? 
                    AND ORDEN_TURNO = ?
                    AND FECHA_DESDE = ?
                    AND ACTIVO = 'S'
                ";
                
                $stmtCheck = $conn->prepare($sqlCheckExisting);
                $stmtCheck->execute([
                    $empleadoDestinoId,
                    $horario['ID_DIA'],
                    $horario['ORDEN_TURNO'],
                    $fechaDesde
                ]);
                
                if ($stmtCheck->fetch()) {
                    // Actualizar existente
                    $sqlUpdate = "
                        UPDATE empleado_horario_personalizado 
                        SET HORA_ENTRADA = ?,
                            HORA_SALIDA = ?,
                            TOLERANCIA = ?,
                            NOMBRE_TURNO = ?,
                            FECHA_HASTA = ?,
                            OBSERVACIONES = ?,
                            UPDATED_AT = NOW()
                        WHERE ID_EMPLEADO = ? 
                        AND ID_DIA = ? 
                        AND ORDEN_TURNO = ?
                        AND FECHA_DESDE = ?
                        AND ACTIVO = 'S'
                    ";
                    
                    $stmtUpdate = $conn->prepare($sqlUpdate);
                    $stmtUpdate->execute([
                        $horario['HORA_ENTRADA'],
                        $horario['HORA_SALIDA'],
                        $horario['TOLERANCIA'],
                        $horario['NOMBRE_TURNO'],
                        $fechaHasta,
                        $horario['OBSERVACIONES'],
                        $empleadoDestinoId,
                        $horario['ID_DIA'],
                        $horario['ORDEN_TURNO'],
                        $fechaDesde
                    ]);
                    
                    $horariosActualizados++;
                } else {
                    // Insertar nuevo
                    $sqlInsert = "
                        INSERT INTO empleado_horario_personalizado 
                        (ID_EMPLEADO, ID_DIA, HORA_ENTRADA, HORA_SALIDA, TOLERANCIA, 
                         NOMBRE_TURNO, FECHA_DESDE, FECHA_HASTA, ORDEN_TURNO, OBSERVACIONES) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ";
                    
                    $stmtInsert = $conn->prepare($sqlInsert);
                    $stmtInsert->execute([
                        $empleadoDestinoId,
                        $horario['ID_DIA'],
                        $horario['HORA_ENTRADA'],
                        $horario['HORA_SALIDA'],
                        $horario['TOLERANCIA'],
                        $horario['NOMBRE_TURNO'],
                        $fechaDesde,
                        $fechaHasta,
                        $horario['ORDEN_TURNO'],
                        $horario['OBSERVACIONES']
                    ]);
                    
                    $horariosCopiados++;
                }
            }

            $resultados[] = [
                'empleado_id' => $empleadoDestinoId,
                'empleado_nombre' => trim($empleadoDestino['NOMBRE'] . ' ' . $empleadoDestino['APELLIDO']),
                'horarios_copiados' => $horariosCopiados,
                'horarios_actualizados' => $horariosActualizados,
                'total_procesados' => $horariosCopiados + $horariosActualizados
            ];
        }

        $conn->commit();

        $empleadoOrigen = array_filter($empleadosEncontrados, function($emp) use ($empleadoOrigenId) {
            return $emp['ID_EMPLEADO'] == $empleadoOrigenId;
        });
        $empleadoOrigen = reset($empleadoOrigen);

        echo json_encode([
            'success' => true,
            'message' => 'Horarios copiados exitosamente',
            'data' => [
                'empleado_origen' => [
                    'id' => $empleadoOrigenId,
                    'nombre' => trim($empleadoOrigen['NOMBRE'] . ' ' . $empleadoOrigen['APELLIDO']),
                    'total_horarios_origen' => count($horariosOrigen)
                ],
                'fecha_desde' => $fechaDesde,
                'fecha_hasta' => $fechaHasta,
                'replace_existing' => $replaceExisting,
                'copy_only_active_days' => $copyOnlyActiveDays,
                'resultados_por_empleado' => $resultados,
                'resumen' => [
                    'empleados_procesados' => count($resultados),
                    'total_horarios_copiados' => array_sum(array_column($resultados, 'horarios_copiados')),
                    'total_horarios_actualizados' => array_sum(array_column($resultados, 'horarios_actualizados'))
                ]
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