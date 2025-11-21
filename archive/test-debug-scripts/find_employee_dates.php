<?php

/**
 * Script para encontrar fechas con registros de asistencia para el empleado 909
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/timezone.php';

$employeeId = 909;

try {
    // Obtener todas las fechas con registros para este empleado
    $stmt = $pdo->prepare("
        SELECT DISTINCT
            a.FECHA,
            COUNT(CASE WHEN a.TIPO = 'ENTRADA' THEN 1 END) as entradas,
            COUNT(CASE WHEN a.TIPO = 'SALIDA' THEN 1 END) as salidas,
            MIN(CASE WHEN a.TIPO = 'ENTRADA' THEN a.HORA END) as primera_entrada,
            MAX(CASE WHEN a.TIPO = 'SALIDA' THEN a.HORA END) as ultima_salida
        FROM ASISTENCIA a
        WHERE a.ID_EMPLEADO = ?
        GROUP BY a.FECHA
        ORDER BY a.FECHA DESC
        LIMIT 10
    ");
    $stmt->execute([$employeeId]);
    $fechas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "=== FECHAS CON REGISTROS PARA EMPLEADO 909 ===\n";
    echo "Total de fechas encontradas: " . count($fechas) . "\n\n";

    foreach ($fechas as $fecha) {
        echo "FECHA: {$fecha['FECHA']}\n";
        echo "  Entradas: {$fecha['entradas']}\n";
        echo "  Salidas: {$fecha['salidas']}\n";
        echo "  Primera entrada: {$fecha['primera_entrada']}\n";
        echo "  Última salida: {$fecha['ultima_salida']}\n";

        // Calcular horas trabajadas aproximadas
        if ($fecha['primera_entrada'] && $fecha['ultima_salida']) {
            $entrada = strtotime($fecha['primera_entrada']);
            $salida = strtotime($fecha['ultima_salida']);
            if ($salida < $entrada) {
                $salida += 86400; // Agregar 24 horas si cruza medianoche
            }
            $horas = ($salida - $entrada) / 3600;
            echo "  Horas aproximadas: " . round($horas, 2) . "\n";
        }
        echo "\n";
    }

    if (count($fechas) > 0) {
        echo "=== PRUEBA CON LA FECHA MÁS RECIENTE ===\n";
        $fechaReciente = $fechas[0]['FECHA'];
        echo "Usando fecha: $fechaReciente\n";

        // Simular llamada al API con esta fecha
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['empleados'] = [909];
        $_POST['fechaDesde'] = $fechaReciente;
        $_POST['fechaHasta'] = $fechaReciente;

        ob_start();
        include __DIR__ . '/api/horas-trabajadas/get-horas.php';
        $output = ob_get_clean();

        $response = json_decode($output, true);

        echo "\nRESPUESTA DEL API:\n";
        echo "Success: " . ($response['success'] ? 'true' : 'false') . "\n";

        if ($response['success'] && isset($response['data']['horas'])) {
            echo "Días encontrados: " . count($response['data']['horas']) . "\n";

            foreach ($response['data']['horas'] as $dia) {
                echo "DÍA: {$dia['fecha']}\n";
                echo "  Horas trabajadas: {$dia['horas_trabajadas']}\n";

                if (isset($dia['detalle_horas']) && is_array($dia['detalle_horas'])) {
                    echo "  Detalle de horas:\n";
                    foreach ($dia['detalle_horas'] as $detalle) {
                        if (is_array($detalle)) {
                            // Nueva estructura compatible con JavaScript
                            if (isset($detalle['categoria'])) {
                                echo "    - {$detalle['hora_entrada']} - {$detalle['hora_salida']}: {$detalle['horas_trabajadas']} horas ({$detalle['categoria']})\n";
                                if (isset($detalle['segmentos']) && is_array($detalle['segmentos'])) {
                                    echo "      Segmentos:\n";
                                    foreach ($detalle['segmentos'] as $segmento) {
                                        echo "        * {$segmento['hora_inicio']} - {$segmento['hora_fin']}: {$segmento['horas']} horas ({$segmento['tipo']})\n";
                                    }
                                }
                            } else {
                                // Estructura antigua (por compatibilidad)
                                $categoria = $detalle['categoria'] ?? 'N/A';
                                echo "    - {$detalle['hora_entrada']} - {$detalle['hora_salida']}: {$detalle['horas_trabajadas']} horas ({$categoria})\n";
                            }
                        }
                    }
                }
                echo "\n";
            }
        }
    }

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

?>