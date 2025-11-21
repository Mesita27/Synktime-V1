<?php
require_once __DIR__ . '/../../config/database.php';
session_start();

header('Content-Type: application/json');

try {
    $empresaId = $_SESSION['id_empresa'] ?? null;
    
    if (!$empresaId) {
        echo json_encode(['success' => false, 'message' => 'Sesión inválida']);
        exit;
    }

    // Verificar parámetro
    if (!isset($_GET['id']) || empty($_GET['id'])) {
        echo json_encode(['success' => false, 'message' => 'ID de asistencia no proporcionado']);
        exit;
    }

    $id_asistencia = intval($_GET['id']);

    // Consultar datos de la asistencia
    $sql = "
        SELECT 
            a.ID_ASISTENCIA,
            a.ID_EMPLEADO,
            a.FECHA,
            a.TIPO,
            a.HORA,
            a.TARDANZA,
            a.OBSERVACION,
            a.FOTO,
            a.REGISTRO_MANUAL,
            a.ID_HORARIO,
            e.DNI,
            e.NOMBRE,
            e.APELLIDO,
            est.NOMBRE AS establecimiento,
            s.NOMBRE AS sede,
            h.NOMBRE AS nombre_horario,
            h.HORA_ENTRADA,
            h.HORA_SALIDA,
            h.TOLERANCIA
        FROM asistencia a
        JOIN empleado e ON a.ID_EMPLEADO = e.ID_EMPLEADO
        JOIN establecimiento est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
        JOIN sede s ON est.ID_SEDE = s.ID_SEDE
        LEFT JOIN horario h ON a.ID_HORARIO = h.ID_HORARIO
        WHERE a.ID_ASISTENCIA = :id_asistencia
        AND s.ID_EMPRESA = :empresa_id
    ";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindValue(':id_asistencia', $id_asistencia);
    $stmt->bindValue(':empresa_id', $empresaId);
    $stmt->execute();
    
    $asistencia = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$asistencia) {
        echo json_encode(['success' => false, 'message' => 'Asistencia no encontrada o sin permisos']);
        exit;
    }
    
    // Consultar entradas y salidas para esta persona, fecha y horario
    $sqlCompleto = "
        SELECT 
            a2.ID_ASISTENCIA,
            a2.TIPO,
            a2.HORA,
            a2.TARDANZA,
            a2.OBSERVACION
        FROM asistencia a2
        WHERE a2.ID_EMPLEADO = :id_empleado
        AND a2.FECHA = :fecha
        AND a2.ID_HORARIO = :id_horario
        ORDER BY a2.TIPO, a2.ID_ASISTENCIA DESC
    ";
    
    $stmtCompleto = $conn->prepare($sqlCompleto);
    $stmtCompleto->bindValue(':id_empleado', $asistencia['ID_EMPLEADO']);
    $stmtCompleto->bindValue(':fecha', $asistencia['FECHA']);
    $stmtCompleto->bindValue(':id_horario', $asistencia['ID_HORARIO']);
    $stmtCompleto->execute();
    
    $registrosCompletos = $stmtCompleto->fetchAll(PDO::FETCH_ASSOC);
    
    // Organizar registros por tipo
    $entradas = [];
    $salidas = [];
    
    foreach ($registrosCompletos as $reg) {
        if ($reg['TIPO'] === 'ENTRADA') {
            $entradas[] = $reg;
        } else if ($reg['TIPO'] === 'SALIDA') {
            $salidas[] = $reg;
        }
    }
    
    // Obtener la entrada y salida más recientes
    $ultimaEntrada = !empty($entradas) ? $entradas[0] : null;
    $ultimaSalida = !empty($salidas) ? $salidas[0] : null;
    
    // Formatear información adicional
    $detalles = [
        'codigo' => $asistencia['ID_EMPLEADO'],
        'dni' => $asistencia['DNI'],
        'nombre' => $asistencia['NOMBRE'],
        'apellido' => $asistencia['APELLIDO'],
        'sede' => $asistencia['sede'],
        'establecimiento' => $asistencia['establecimiento'],
        'fecha' => $asistencia['FECHA'],
        'hora_entrada' => $ultimaEntrada ? $ultimaEntrada['HORA'] : null,
        'hora_salida' => $ultimaSalida ? $ultimaSalida['HORA'] : null,
        'registro_actual' => [
            'tipo' => $asistencia['TIPO'],
            'hora' => $asistencia['HORA'],
            'es_manual' => $asistencia['REGISTRO_MANUAL'] === 'S',
            'observacion' => $asistencia['OBSERVACION']
        ],
        'horario' => [
            'id' => $asistencia['ID_HORARIO'],
            'nombre' => $asistencia['nombre_horario'],
            'hora_entrada' => $asistencia['HORA_ENTRADA'],
            'hora_salida' => $asistencia['HORA_SALIDA'],
            'tolerancia' => $asistencia['TOLERANCIA']
        ],
        'foto' => $asistencia['FOTO'],
        'todos_registros' => [
            'entradas' => $entradas,
            'salidas' => $salidas
        ]
    ];
    
    // Calcular estado de entrada
    $estado_entrada = 'Ausente';
    if ($ultimaEntrada && $asistencia['HORA_ENTRADA']) {
        $ts_entrada_programada = strtotime($asistencia['FECHA'] . ' ' . $asistencia['HORA_ENTRADA']);
        $ts_entrada_real = strtotime($asistencia['FECHA'] . ' ' . $ultimaEntrada['HORA']);
        $tolerancia = (int)($asistencia['TOLERANCIA'] ?? 0);
        
        if ($ts_entrada_real < $ts_entrada_programada - $tolerancia * 60) {
            $estado_entrada = 'Temprano';
        } elseif ($ts_entrada_real <= $ts_entrada_programada + $tolerancia * 60) {
            $estado_entrada = 'A Tiempo';
        } else {
            $estado_entrada = 'Tardanza';
            // Calcular minutos de tardanza
            $minutosTardanza = round(($ts_entrada_real - $ts_entrada_programada) / 60);
            $detalles['minutos_tardanza'] = $minutosTardanza;
        }
    } elseif ($ultimaEntrada) {
        $estado_entrada = 'Presente'; // Si hay registro de entrada pero no hay horario programado
    }
    
    $detalles['estado_entrada'] = $estado_entrada;
    
    // Calcular horas trabajadas si hay entrada y salida
    if ($ultimaEntrada && $ultimaSalida) {
        $ts_entrada = strtotime($asistencia['FECHA'] . ' ' . $ultimaEntrada['HORA']);
        $ts_salida = strtotime($asistencia['FECHA'] . ' ' . $ultimaSalida['HORA']);
        
        // Solo calcular si la salida es posterior a la entrada
        if ($ts_salida > $ts_entrada) {
            $horasTrabajadas = round(($ts_salida - $ts_entrada) / 3600, 2);
            $detalles['horas_trabajadas'] = $horasTrabajadas;
        }
    }
    
    // Obtener los días del horario
    if ($asistencia['ID_HORARIO']) {
        $sqlDias = "
            SELECT 
                GROUP_CONCAT(
                    CASE hd.ID_DIA
                        WHEN 1 THEN 'Lunes'
                        WHEN 2 THEN 'Martes'
                        WHEN 3 THEN 'Miércoles'
                        WHEN 4 THEN 'Jueves'
                        WHEN 5 THEN 'Viernes'
                        WHEN 6 THEN 'Sábado'
                        WHEN 7 THEN 'Domingo'
                    END
                    SEPARATOR ', '
                ) as dias_horario
            FROM horario_dia hd
            WHERE hd.ID_HORARIO = :id_horario
        ";
        
        $stmtDias = $conn->prepare($sqlDias);
        $stmtDias->bindValue(':id_horario', $asistencia['ID_HORARIO']);
        $stmtDias->execute();
        $resultDias = $stmtDias->fetch(PDO::FETCH_ASSOC);
        
        if ($resultDias) {
            $detalles['horario']['dias'] = $resultDias['dias_horario'];
        }
    }
    
    echo json_encode([
        'success' => true,
        'data' => $detalles
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error al cargar detalles de asistencia: ' . $e->getMessage()
    ]);
}
?>