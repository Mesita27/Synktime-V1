<?php

// Configurar zona horaria de Bogotá, Colombia
require_once __DIR__ . '/../../config/timezone.php';
require_once __DIR__ . '/../../auth/session.php';
requireAuth();
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');

try {
    // Obtener información del usuario actual
    $currentUser = getCurrentUser();
    $empresaId = $currentUser['id_empresa'] ?? null;
    
    if (!$empresaId) {
        echo json_encode(['success' => false, 'message' => 'Sesión inválida - empresa no encontrada']);
        exit;
    }

    // Parámetros requeridos
    $idEmpleado = $_GET['id_empleado'] ?? null;
    $fecha = $_GET['fecha'] ?? getBogotaDate();
    $tipoRegistro = $_GET['tipo'] ?? 'ENTRADA'; // ENTRADA o SALIDA

    if (!$idEmpleado) {
        echo json_encode(['success' => false, 'message' => 'ID de empleado requerido']);
        exit;
    }

    // Validar formato de fecha
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
        echo json_encode(['success' => false, 'message' => 'Formato de fecha inválido']);
        exit;
    }

    // Obtener día de la semana (1=Lunes, 7=Domingo)
    $diaSemana = date('N');

    // Verificar que el empleado pertenece a la empresa
    $sqlVerifyEmployee = "
        SELECT 
            e.ID_EMPLEADO,
            e.NOMBRE,
            e.APELLIDO,
            e.DNI
        FROM empleado e
        JOIN establecimiento est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
        JOIN sede s ON est.ID_SEDE = s.ID_SEDE
        WHERE e.ID_EMPLEADO = :id_empleado 
        AND s.ID_EMPRESA = :empresa_id 
        AND e.ACTIVO = 'S'
    ";

    $stmt = $conn->prepare($sqlVerifyEmployee);
    $stmt->bindValue(':id_empleado', $idEmpleado);
    $stmt->bindValue(':empresa_id', $empresaId);
    $stmt->execute();
    $empleado = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$empleado) {
        echo json_encode(['success' => false, 'message' => 'Empleado no encontrado o sin permisos']);
        exit;
    }

    // Buscar horarios personalizados activos para esta fecha y día
    $sqlPersonalizados = "
        SELECT 
            ehp.ID_EMPLEADO_HORARIO,
            ehp.HORA_ENTRADA,
            ehp.HORA_SALIDA,
            ehp.TOLERANCIA,
            ehp.NOMBRE_TURNO,
            ehp.ORDEN_TURNO,
            ehp.OBSERVACIONES,
            'personalizado' as tipo_horario
        FROM empleado_horario_personalizado ehp
        WHERE ehp.ID_EMPLEADO = :id_empleado
        AND ehp.ID_DIA = :dia_semana
        AND ehp.ACTIVO = 'S'
        AND ehp.FECHA_DESDE <= :fecha
        AND (ehp.FECHA_HASTA IS NULL OR ehp.FECHA_HASTA >= :fecha)
        ORDER BY ehp.ORDEN_TURNO
    ";

    $stmtPersonalizados = $conn->prepare($sqlPersonalizados);
    $stmtPersonalizados->bindValue(':id_empleado', $idEmpleado);
    $stmtPersonalizados->bindValue(':dia_semana', $diaSemana);
    $stmtPersonalizados->bindValue(':fecha', $fecha);
    $stmtPersonalizados->execute();
    $horariosPersonalizados = $stmtPersonalizados->fetchAll(PDO::FETCH_ASSOC);

    $horarioAplicable = null;
    $horariosDisponibles = [];
    $tieneHorarioPersonalizado = false;

    if (!empty($horariosPersonalizados)) {
        // Tiene horarios personalizados
        $tieneHorarioPersonalizado = true;
        
        foreach ($horariosPersonalizados as $horario) {
            $horariosDisponibles[] = [
                'id_horario' => $horario['ID_EMPLEADO_HORARIO'],
                'tipo' => 'personalizado',
                'nombre' => $horario['NOMBRE_TURNO'],
                'hora_entrada' => $horario['HORA_ENTRADA'],
                'hora_salida' => $horario['HORA_SALIDA'],
                'tolerancia' => (int)$horario['TOLERANCIA'],
                'orden' => (int)$horario['ORDEN_TURNO'],
                'observaciones' => $horario['OBSERVACIONES']
            ];
        }

        // Determinar horario aplicable según el tipo de registro
        if ($tipoRegistro === 'ENTRADA') {
            // Para entrada, usar el primer turno del día
            $horarioAplicable = $horariosDisponibles[0];
        } else {
            // Para salida, buscar el turno correspondiente
            $horaActual = getBogotaTime();
            
            // Buscar el turno más cercano o activo
            foreach ($horariosDisponibles as $horario) {
                if ($horaActual >= $horario['hora_entrada']) {
                    $horarioAplicable = $horario;
                } else {
                    break;
                }
            }
            
            // Si no se encontró, usar el último turno
            if (!$horarioAplicable) {
                $horarioAplicable = end($horariosDisponibles);
            }
        }
        
    } else {
        // No tiene horarios personalizados, buscar horario tradicional
        $sqlTradicional = "
            SELECT 
                h.ID_HORARIO,
                h.NOMBRE,
                h.HORA_ENTRADA,
                h.HORA_SALIDA,
                h.TOLERANCIA,
                'tradicional' as tipo_horario
            FROM empleado_horario eh
            JOIN horario h ON eh.ID_HORARIO = h.ID_HORARIO
            JOIN horario_dia hd ON h.ID_HORARIO = hd.ID_HORARIO
            WHERE eh.ID_EMPLEADO = :id_empleado
            AND hd.ID_DIA = :dia_semana
            AND (eh.FECHA_HASTA IS NULL OR eh.FECHA_HASTA >= :fecha)
            AND eh.FECHA_DESDE <= :fecha
            ORDER BY eh.FECHA_DESDE DESC
            LIMIT 1
        ";

        $stmtTradicional = $conn->prepare($sqlTradicional);
        $stmtTradicional->bindValue(':id_empleado', $idEmpleado);
        $stmtTradicional->bindValue(':dia_semana', $diaSemana);
        $stmtTradicional->bindValue(':fecha', $fecha);
        $stmtTradicional->execute();
        $horarioTradicional = $stmtTradicional->fetch(PDO::FETCH_ASSOC);

        if ($horarioTradicional) {
            $horarioAplicable = [
                'id_horario' => $horarioTradicional['ID_HORARIO'],
                'tipo' => 'tradicional',
                'nombre' => $horarioTradicional['NOMBRE'],
                'hora_entrada' => $horarioTradicional['HORA_ENTRADA'],
                'hora_salida' => $horarioTradicional['HORA_SALIDA'],
                'tolerancia' => (int)$horarioTradicional['TOLERANCIA'],
                'orden' => 1,
                'observaciones' => null
            ];

            $horariosDisponibles[] = $horarioAplicable;
        }
    }

    // Calcular estado del registro si hay horario aplicable
    $estadoRegistro = null;
    $informacionTardanza = null;

    if ($horarioAplicable) {
        $horaActual = getBogotaTime();
        $horaReferencia = $tipoRegistro === 'ENTRADA' ? 
            $horarioAplicable['hora_entrada'] : 
            $horarioAplicable['hora_salida'];
        
        $toleranciaSegundos = $horarioAplicable['tolerancia'] * 60;
        
        $tsActual = strtotime($fecha . ' ' . $horaActual);
        $tsReferencia = strtotime($fecha . ' ' . $horaReferencia);
        
        $diferencia = $tsActual - $tsReferencia;
        
        if ($tipoRegistro === 'ENTRADA') {
            if ($diferencia <= -$toleranciaSegundos) {
                $estadoRegistro = 'temprano';
                $informacionTardanza = 'T'; // Temprano
            } elseif ($diferencia <= $toleranciaSegundos) {
                $estadoRegistro = 'puntual';
                $informacionTardanza = 'N'; // Normal
            } else {
                $estadoRegistro = 'tardio';
                $informacionTardanza = 'S'; // Tarde
            }
        } else {
            if ($diferencia < -$toleranciaSegundos) {
                $estadoRegistro = 'temprano';
                $informacionTardanza = 'T'; // Salida temprana
            } elseif ($diferencia <= $toleranciaSegundos) {
                $estadoRegistro = 'puntual';
                $informacionTardanza = 'N'; // Normal
            } else {
                $estadoRegistro = 'tardio';
                $informacionTardanza = 'S'; // Salida tardía
            }
        }

        $minutosDesfase = abs($diferencia) / 60;
    }

    // Verificar si ya existe un registro de este tipo para esta fecha
    $sqlCheckExisting = "
        SELECT COUNT(*) as existe
        FROM asistencia 
        WHERE ID_EMPLEADO = :id_empleado 
        AND FECHA = :fecha 
        AND TIPO = :tipo
    ";

    $stmtCheck = $conn->prepare($sqlCheckExisting);
    $stmtCheck->bindValue(':id_empleado', $idEmpleado);
    $stmtCheck->bindValue(':fecha', $fecha);
    $stmtCheck->bindValue(':tipo', $tipoRegistro);
    $stmtCheck->execute();
    $yaExiste = $stmtCheck->fetch(PDO::FETCH_ASSOC)['existe'] > 0;

    // Respuesta
    echo json_encode([
        'success' => true,
        'empleado' => [
            'id' => (int)$empleado['ID_EMPLEADO'],
            'nombre_completo' => trim($empleado['NOMBRE'] . ' ' . $empleado['APELLIDO']),
            'dni' => $empleado['DNI']
        ],
        'fecha_consulta' => $fecha,
        'dia_semana' => $diaSemana,
        'tipo_registro' => $tipoRegistro,
        'tiene_horario_personalizado' => $tieneHorarioPersonalizado,
        'horario_aplicable' => $horarioAplicable,
        'horarios_disponibles' => $horariosDisponibles,
        'total_turnos_dia' => count($horariosDisponibles),
        'registro_info' => [
            'ya_existe' => $yaExiste,
            'estado' => $estadoRegistro,
            'tardanza_codigo' => $informacionTardanza,
            'minutos_desfase' => $horarioAplicable ? round($minutosDesfase, 0) : null,
            'hora_actual' => getBogotaTime(),
            'fecha_actual' => getBogotaDate()
        ],
        'recomendaciones' => $horarioAplicable ? [
            'puede_registrar' => !$yaExiste,
            'mensaje' => !$yaExiste ? 
                "Empleado puede registrar $tipoRegistro" : 
                "Ya existe un registro de $tipoRegistro para esta fecha",
            'horario_sugerido' => $horarioAplicable['nombre'],
            'tolerancia_minutos' => $horarioAplicable['tolerancia']
        ] : [
            'puede_registrar' => false,
            'mensaje' => 'No hay horarios configurados para este día',
            'horario_sugerido' => null,
            'tolerancia_minutos' => 0
        ]
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error de base de datos: ' . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>