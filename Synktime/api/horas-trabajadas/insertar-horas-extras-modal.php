<?php

/**
 * API para insertar horas extras desde el modal de aprobación
 * Recibe un array de horas extras y las inserta verificando duplicados
 */

header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/timezone.php';

// Función para verificar si ya existen horas extras duplicadas
function verificarHorasExtrasDuplicadas($idEmpleado, $idEmpleadoHorario, $fecha, $horaInicio, $horaFin, $tipoHorario, $pdo) {
    $query = "
        SELECT COUNT(*) as count
        FROM horas_extras_aprobacion
        WHERE ID_EMPLEADO = ?
        AND FECHA = ?
        AND HORA_INICIO = ?
        AND HORA_FIN = ?
        AND TIPO_HORARIO = ?
    ";

    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute([$idEmpleado, $fecha, $horaInicio, $horaFin, $tipoHorario]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'] > 0;
    } catch (Exception $e) {
        error_log("Error verificando duplicados: " . $e->getMessage());
        return false;
    }
}

// Función para insertar horas extras verificando duplicados
function insertarHorasExtras($horasExtras, $pdo) {
    $insertadas = 0;
    $existentes = 0;
    $errores = 0;

    foreach ($horasExtras as $horaExtra) {
        try {
            // Verificar si ya existe
            if (verificarHorasExtrasDuplicadas(
                $horaExtra['id_empleado'],
                $horaExtra['id_empleado_horario'],
                $horaExtra['fecha'],
                $horaExtra['hora_inicio'],
                $horaExtra['hora_fin'],
                $horaExtra['tipo_horario'],
                $pdo
            )) {
                $existentes++;
                continue;
            }

            // Insertar nueva hora extra
            $query = "
                INSERT INTO horas_extras_aprobacion
                (ID_EMPLEADO, ID_EMPLEADO_HORARIO, FECHA, HORA_INICIO, HORA_FIN, HORAS_EXTRAS, TIPO_EXTRA, TIPO_HORARIO, ESTADO_APROBACION, CREATED_AT, UPDATED_AT)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pendiente', NOW(), NOW())
            ";

            $stmt = $pdo->prepare($query);
            $stmt->execute([
                $horaExtra['id_empleado'],
                $horaExtra['id_empleado_horario'],
                $horaExtra['fecha'],
                $horaExtra['hora_inicio'],
                $horaExtra['hora_fin'],
                $horaExtra['horas_extras'],
                $horaExtra['tipo_extra'],
                $horaExtra['tipo_horario']
            ]);

            $insertadas++;

        } catch (Exception $e) {
            error_log("Error insertando hora extra: " . $e->getMessage());
            $errores++;
        }
    }

    return [
        'insertadas' => $insertadas,
        'existentes' => $existentes,
        'errores' => $errores
    ];
}

// Procesar la solicitud
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Limpiar cualquier output anterior
    if (ob_get_level()) {
        ob_clean();
    }

    try {
        // Usar la conexión global $pdo desde config/database.php
        global $pdo;

        if (!isset($pdo) || !$pdo) {
            throw new Exception('No se pudo establecer conexión a la base de datos');
        }

        // Obtener datos JSON
        $input = file_get_contents('php://input');

        $data = json_decode($input, true);

        if (!$data || !isset($data['horas_extras']) || !is_array($data['horas_extras'])) {
            echo json_encode([
                'success' => false,
                'message' => 'Datos inválidos. Se esperaba un array de horas_extras.'
            ]);
            exit;
        }

        $horasExtras = $data['horas_extras'];

        error_log("Datos recibidos - Total horas extras: " . count($horasExtras));
        foreach ($horasExtras as $i => $he) {
            error_log("Hora extra $i: " . json_encode($he));
        }

        if (empty($horasExtras)) {
            echo json_encode([
                'success' => false,
                'message' => 'No se recibieron horas extras para procesar.'
            ]);
            exit;
        }

        // Insertar horas extras
        $resultado = insertarHorasExtras($horasExtras, $pdo);

        // Crear mensaje de resultado
        $totalProcesadas = $resultado['insertadas'] + $resultado['existentes'] + $resultado['errores'];

        $mensaje = "✅ Procesamiento de horas extras completado.\n\n";
        $mensaje .= "📊 Resumen del proceso:\n";
        $mensaje .= "• Total de horas extras procesadas: {$totalProcesadas}\n";

        if ($resultado['insertadas'] > 0) {
            $mensaje .= "• ✅ Nuevas insertadas en BD: {$resultado['insertadas']}\n";
        }

        if ($resultado['existentes'] > 0) {
            $mensaje .= "• ℹ️ Ya existían (evitadas duplicados): {$resultado['existentes']}\n";
        }

        if ($resultado['errores'] > 0) {
            $mensaje .= "• ❌ Errores durante el proceso: {$resultado['errores']}\n";
        }

        if ($resultado['insertadas'] > 0) {
            $mensaje .= "\n💡 Las nuevas horas extras están pendientes de aprobación.";
        }

        $response = [
            'success' => true,
            'message' => trim($mensaje),
            'resultado' => $resultado
        ];

        echo json_encode($response);

    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error interno del servidor: ' . $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Método no permitido'
    ]);
}
?>