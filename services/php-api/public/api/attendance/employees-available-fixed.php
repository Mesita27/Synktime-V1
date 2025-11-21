<?php
require_once __DIR__ . '/../../auth/session.php';
requireAuth();
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');

// Establecer zona horaria de Colombia
// Zona horaria configurada en config/timezone.php

try {
    $currentUser = getCurrentUser();
    $userRole = $currentUser['rol'] ?? null;
    $empresaId = $currentUser['id_empresa'] ?? null;

    // Capturar filtros
    $sede = $_GET['sede'] ?? '';
    $establecimiento = $_GET['establecimiento'] ?? '';
    $codigo = $_GET['codigo'] ?? '';

    // Construir la consulta base para obtener todos los empleados activos
    $where = ['e.ACTIVO = "S"'];
    $params = [];

    // Solo filtrar por empresa para roles específicos (más permisivo)
    if (in_array($userRole, ['ASISTENCIA', 'EMPLEADO']) && $empresaId) {
        $where[] = 's.ID_EMPRESA = ?';
        $params[] = $empresaId;
    }

    if ($sede) {
        $where[] = 's.ID_SEDE = ?';
        $params[] = $sede;
    }
    if ($establecimiento) {
        $where[] = 'est.ID_ESTABLECIMIENTO = ?';
        $params[] = $establecimiento;
    }
    if ($codigo) {
        $where[] = 'e.ID_EMPLEADO = ?';
        $params[] = $codigo;
    }

    $whereClause = implode(' AND ', $where);

    // Consulta para obtener todos los empleados (nombres de tabla corregidos)
    $sql = "
        SELECT 
            e.ID_EMPLEADO,
            e.NOMBRE,
            e.APELLIDO,
            e.ID_ESTABLECIMIENTO,
            est.NOMBRE AS ESTABLECIMIENTO,
            s.NOMBRE AS SEDE,
            s.ID_EMPRESA
        FROM empleado e
        JOIN establecimiento est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
        JOIN sede s ON est.ID_SEDE = s.ID_SEDE
        WHERE $whereClause
        ORDER BY s.NOMBRE, est.NOMBRE, e.APELLIDO, e.NOMBRE
        LIMIT 200
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $empleados = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Para cada empleado, verificar sus horarios y asistencias
    $result = [];
    $fecha_actual = getBogotaDate();

    foreach ($empleados as $empleado) {
        // Obtener el día de la semana (1=Lunes ... 7=Domingo)
        $dia_semana = date('N', strtotime($fecha_actual));
        
        // **CORREGIDO: Buscar SOLO horarios personalizados basándose en fechas de vigencia**
        $sqlPersonalizados = "
            SELECT 
                ehp.ID_EMPLEADO_HORARIO,
                ehp.NOMBRE_TURNO AS HORARIO_NOMBRE, 
                ehp.HORA_ENTRADA, 
                ehp.HORA_SALIDA,
                'personalizado' as tipo_horario
            FROM empleado_horario_personalizado ehp
            WHERE ehp.ID_EMPLEADO = ?
              AND ehp.ID_DIA = ?
              AND ehp.FECHA_DESDE <= ?
              AND (ehp.FECHA_HASTA IS NULL OR ehp.FECHA_HASTA >= ?)
              AND ehp.ACTIVO = 'S'
            ORDER BY ehp.FECHA_DESDE DESC, ehp.ORDEN_TURNO, ehp.HORA_ENTRADA
        ";
        
        $stmtPersonalizados = $pdo->prepare($sqlPersonalizados);
        $stmtPersonalizados->execute([$empleado['ID_EMPLEADO'], $dia_semana, $fecha_actual, $fecha_actual]);
        $horarios = $stmtPersonalizados->fetchAll(PDO::FETCH_ASSOC);
        
        // CAMBIO: Mostrar empleados aunque no tengan horarios asignados
        // Para debug y registro manual
        
        // Para cada horario, verificar si ya se registró entrada y salida
        $horarios_disponibles = [];
        $tiene_horarios_disponibles = false;
        
        if (!empty($horarios)) {
            foreach ($horarios as $horario) {
                // **CORREGIDO: Consultar registros de asistencia usando ID_EMPLEADO_HORARIO**
                $sqlAsistencias = "
                    SELECT TIPO
                    FROM asistencia
                    WHERE ID_EMPLEADO = ?
                      AND FECHA = ?
                      AND ID_EMPLEADO_HORARIO = ?
                    ORDER BY HORA
                ";
                
                $stmtAsistencias = $pdo->prepare($sqlAsistencias);
                $stmtAsistencias->execute([$empleado['ID_EMPLEADO'], $fecha_actual, $horario['ID_EMPLEADO_HORARIO']]);
                $asistencias = $stmtAsistencias->fetchAll(PDO::FETCH_ASSOC);
                
                $tiene_entrada = false;
                $tiene_salida = false;
                
                foreach ($asistencias as $asistencia) {
                    if ($asistencia['TIPO'] === 'ENTRADA') $tiene_entrada = true;
                    if ($asistencia['TIPO'] === 'SALIDA') $tiene_salida = true;
                }
                
                // Determinar el estado del horario
                $estado_horario = [];
                if (!$tiene_entrada && !$tiene_salida) {
                    $estado_horario = [
                        'estado' => 'disponible',
                        'proximo_registro' => 'ENTRADA',
                        'mensaje' => 'Registrar entrada'
                    ];
                    $tiene_horarios_disponibles = true;
                } else if ($tiene_entrada && !$tiene_salida) {
                    $estado_horario = [
                        'estado' => 'disponible',
                        'proximo_registro' => 'SALIDA',
                        'mensaje' => 'Registrar salida'
                    ];
                    $tiene_horarios_disponibles = true;
                } else {
                    $estado_horario = [
                        'estado' => 'completado',
                        'mensaje' => 'Entrada y salida registradas'
                    ];
                }
                
                $horarios_disponibles[] = [
                    'id_empleado_horario' => $horario['ID_EMPLEADO_HORARIO'],  // **CORREGIDO: usar ID_EMPLEADO_HORARIO**
                    'nombre' => $horario['HORARIO_NOMBRE'],
                    'hora_entrada' => $horario['HORA_ENTRADA'],
                    'hora_salida' => $horario['HORA_SALIDA'],
                    'estado' => $estado_horario
                ];
            }
        } else {
            // Empleado sin horarios - permitir registro manual
            $tiene_horarios_disponibles = true;
            $horarios_disponibles[] = [
                'id_horario' => null,
                'nombre' => 'Registro Manual',
                'hora_entrada' => '00:00',
                'hora_salida' => '23:59',
                'estado' => [
                    'estado' => 'disponible',
                    'proximo_registro' => 'ENTRADA/SALIDA',
                    'mensaje' => 'Registro manual disponible'
                ]
            ];
        }
        
        // Agregar información de horarios disponibles
        $empleado['HORARIOS'] = $horarios_disponibles;
        $empleado['TIENE_HORARIOS_DISPONIBLES'] = $tiene_horarios_disponibles;
        
        // CAMBIO: Incluir TODOS los empleados para facilitar el registro
        $result[] = $empleado;
    }

    echo json_encode([
        'success' => true,
        'data' => $result,
        'total' => count($result),
        'filtros_aplicados' => [
            'sede' => $sede,
            'establecimiento' => $establecimiento,
            'codigo' => $codigo,
            'empresa_filtro' => in_array($userRole, ['ASISTENCIA', 'EMPLEADO']) ? $empresaId : 'Sin filtro'
        ],
        'debug_info' => [
            'fecha_actual' => $fecha_actual,
            'dia_semana' => date('N', strtotime($fecha_actual)),
            'user_role' => $userRole,
            'empresa_id' => $empresaId
        ]
    ]);

} catch (Exception $e) {
    error_log("Error en employees-available.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error al cargar empleados: ' . $e->getMessage(),
        'data' => []
    ]);
}
?>
