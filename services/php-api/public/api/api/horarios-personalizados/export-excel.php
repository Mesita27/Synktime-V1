<?php
// Iniciar output buffering para evitar cualquier output accidental
ob_start();

// Limpiar cualquier output previo
ob_clean();

// Verificar que no haya output previo
if (headers_sent($file, $line)) {
    die("Headers already sent in $file on line $line");
}

require_once '../../auth/session.php';
require_once '../../config/database.php';
require_once '../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

// Verificar autenticación
requireAuth();

if (!function_exists('formatHoursToHoursMinutes')) {
    function formatHoursToHoursMinutes($hours)
    {
        $totalMinutes = (int) round($hours * 60);
        $sign = $totalMinutes < 0 ? '-' : '';
        $totalMinutes = abs($totalMinutes);
        $hoursPart = intdiv($totalMinutes, 60);
        $minutesPart = $totalMinutes % 60;

        return sprintf('%s%02d:%02d', $sign, $hoursPart, $minutesPart);
    }
}

if (!function_exists('hoursToExcelDuration')) {
    function hoursToExcelDuration($hours)
    {
        if ($hours === null) {
            return 0;
        }

        $totalMinutes = round($hours * 60);
        return $totalMinutes / (24 * 60);
    }
}

try {
    $currentUser = getCurrentUser();
    $empresaId = $currentUser['id_empresa'] ?? null;

    if (!$empresaId) {
        throw new Exception('Empresa no válida');
    }

    // Limpiar cualquier output que pueda haber sido generado
    ob_clean();

    // Obtener información de la empresa
    $stmtEmpresa = $conn->prepare("SELECT nombre FROM empresa WHERE id_empresa = :empresa_id");
    $stmtEmpresa->execute(['empresa_id' => $empresaId]);
    $empresa = $stmtEmpresa->fetch(PDO::FETCH_ASSOC);

    if (!$empresa) {
        throw new Exception('Empresa no encontrada');
    }

    // Ahora que hemos hecho todas las validaciones, enviar los headers
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="horarios_personalizados_' . date('Y-m-d') . '.xlsx"');
    header('Cache-Control: max-age=0');
    header('Pragma: public');

    // Obtener filtros de la consulta
    $filtros = [];
    $sede = $_GET['sede'] ?? null;
    $establecimiento = $_GET['establecimiento'] ?? null;
    $estado = $_GET['estado'] ?? null;
    $search = $_GET['search'] ?? null;
    $fechaVigenciaDesde = $_GET['fecha_vigencia_desde'] ?? null;
    $fechaVigenciaHasta = $_GET['fecha_vigencia_hasta'] ?? null;
    $empleadosSeleccionados = $_GET['empleados'] ?? null; // Array de IDs de empleados
    $soloActivos = isset($_GET['solo_activos']) ? (bool)$_GET['solo_activos'] : false;

    // Obtener nombres de sede y establecimiento para el encabezado
    $sedeNombre = 'Todas las sedes';
    $establecimientoNombre = 'Todos los establecimientos';

    if ($sede) {
    $stmtSede = $conn->prepare("SELECT nombre FROM sede WHERE id_sede = :sede");
        $stmtSede->execute(['sede' => $sede]);
        $sedeData = $stmtSede->fetch(PDO::FETCH_ASSOC);
        $sedeNombre = $sedeData ? $sedeData['nombre'] : 'Sede no encontrada';
    }

    if ($establecimiento) {
    $stmtEst = $conn->prepare("SELECT nombre FROM establecimiento WHERE id_establecimiento = :establecimiento");
        $stmtEst->execute(['establecimiento' => $establecimiento]);
        $estData = $stmtEst->fetch(PDO::FETCH_ASSOC);
        $establecimientoNombre = $estData ? $estData['nombre'] : 'Establecimiento no encontrado';
    }

    // Construir consulta SQL para obtener empleados con sus horarios
    $sql = "
        SELECT DISTINCT
            e.ID_EMPLEADO,
            CONCAT(e.NOMBRE, ' ', e.APELLIDO) as nombre_completo,
            s.NOMBRE as sede_nombre,
            est.NOMBRE as establecimiento_nombre
    FROM empleado e
        JOIN establecimiento est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
        JOIN sede s ON est.ID_SEDE = s.ID_SEDE
        WHERE s.ID_EMPRESA = :empresa_id
    ";

    // Condición de empleados activos (configurable)
    if ($soloActivos) {
        $sql .= " AND e.ACTIVO = 'S'";
    }

    $params = ['empresa_id' => $empresaId];

    if ($sede) {
        $sql .= " AND s.ID_SEDE = :sede";
        $params['sede'] = $sede;
    }

    if ($establecimiento) {
        $sql .= " AND est.ID_ESTABLECIMIENTO = :establecimiento";
        $params['establecimiento'] = $establecimiento;
    }

    if ($search) {
        $sql .= " AND (CONCAT(e.NOMBRE, ' ', e.APELLIDO) LIKE :search OR e.DNI LIKE :search)";
        $params['search'] = "%$search%";
    }

    // Filtro de empleados específicos
    if ($empleadosSeleccionados && is_array($empleadosSeleccionados)) {
        $placeholders = [];
        foreach ($empleadosSeleccionados as $index => $idEmpleado) {
            $placeholders[] = ":empleado_$index";
            $params["empleado_$index"] = $idEmpleado;
        }
        $sql .= " AND e.ID_EMPLEADO IN (" . implode(',', $placeholders) . ")";
    }

    $sql .= " ORDER BY e.NOMBRE, e.APELLIDO";

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $empleados = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Crear el archivo Excel
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Horarios Personalizados');

    // Estilos
    $headerStyle = [
        'font' => ['bold' => true, 'size' => 14],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E3F2FD']]
    ];

    $dayHeaderStyle = [
        'font' => ['bold' => true],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFF3E0']],
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
    ];

    $employeeStyle = [
        'font' => ['bold' => true],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F5F5F5']],
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
    ];

    $scheduleStyle = [
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_CENTER,
            'vertical' => Alignment::VERTICAL_TOP,
            'wrapText' => true
        ],
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
    ];

    $totalStyle = [
        'font' => ['bold' => true],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E8F5E8']],
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
    ];

    // Fila 1: Encabezado centrado con información de empresa, sede, establecimiento y fecha
    $encabezado = $empresa['nombre'] . ' - ' . $sedeNombre . ' - ' . $establecimientoNombre . ' - Exportado: ' . date('d/m/Y H:i');
    $sheet->mergeCells('A1:I1');
    $sheet->setCellValue('A1', $encabezado);
    $sheet->getStyle('A1:I1')->applyFromArray($headerStyle);

    // Fila 2: Días de la semana y "Horas semanales"
    $diasSemana = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];
    $sheet->setCellValue('A2', 'Empleado');
    $sheet->getStyle('A2')->applyFromArray($dayHeaderStyle);

    foreach ($diasSemana as $index => $dia) {
        $col = chr(66 + $index); // B, C, D, E, F, G, H
        $sheet->setCellValue($col . '2', $dia);
        $sheet->getStyle($col . '2')->applyFromArray($dayHeaderStyle);
    }
    $sheet->setCellValue('I2', 'Horas semanales');
    $sheet->getStyle('I2')->applyFromArray($dayHeaderStyle);

    // Procesar empleados
    $row = 3; // Comenzar desde la fila 3
    $fechaActual = date('Y-m-d'); // Fecha actual para filtrar vigencias

    foreach ($empleados as $empleado) {
        $idEmpleado = $empleado['ID_EMPLEADO'];

        // Obtener todos los horarios activos para este empleado según filtros de fecha
        $sqlHorarios = "
            SELECT
                ehp.ID_DIA,
                ehp.NOMBRE_TURNO,
                ehp.HORA_ENTRADA,
                ehp.HORA_SALIDA,
                ehp.TOLERANCIA,
                ehp.FECHA_DESDE,
                ehp.FECHA_HASTA,
                ehp.ACTIVO
            FROM empleado_horario_personalizado ehp
            WHERE ehp.ID_EMPLEADO = :id_empleado
            AND ehp.ACTIVO = 'S'
        ";

        $paramsHorarios = ['id_empleado' => $idEmpleado];

        // Aplicar filtros de fecha de vigencia
        if ($fechaVigenciaDesde) {
            $sqlHorarios .= " AND ehp.FECHA_DESDE <= :fecha_desde";
            $paramsHorarios['fecha_desde'] = $fechaVigenciaDesde;
        }

        if ($fechaVigenciaHasta) {
            $sqlHorarios .= " AND (ehp.FECHA_HASTA >= :fecha_hasta OR ehp.FECHA_HASTA IS NULL)";
            $paramsHorarios['fecha_hasta'] = $fechaVigenciaHasta;
        } else {
            // Si no se especifica fecha hasta, usar fecha actual como fallback
            $sqlHorarios .= " AND (ehp.FECHA_HASTA >= :fecha_actual OR ehp.FECHA_HASTA IS NULL)";
            $paramsHorarios['fecha_actual'] = date('Y-m-d');
        }

        $sqlHorarios .= " ORDER BY ehp.ID_DIA, ehp.ORDEN_TURNO";

        $stmtHorarios = $conn->prepare($sqlHorarios);
        $stmtHorarios->execute($paramsHorarios);
        $horarios = $stmtHorarios->fetchAll(PDO::FETCH_ASSOC);

        // Organizar horarios por día
        $horariosPorDia = [];
        $totalHorasSemanales = 0;

        foreach ($horarios as $horario) {
            $diaId = $horario['ID_DIA'];
            if (!isset($horariosPorDia[$diaId])) {
                $horariosPorDia[$diaId] = [];
            }
            $horariosPorDia[$diaId][] = $horario;
        }

        // Calcular horas por día y total semanal
        $horasPorDia = [];
        for ($diaId = 1; $diaId <= 7; $diaId++) {
            if (isset($horariosPorDia[$diaId])) {
                $horasDia = 0;
                $detalleHorarios = [];

                foreach ($horariosPorDia[$diaId] as $horario) {
                    if ($horario['HORA_ENTRADA'] && $horario['HORA_SALIDA']) {
                        $horaEntrada = strtotime($horario['HORA_ENTRADA']);
                        $horaSalida = strtotime($horario['HORA_SALIDA']);

                        $horasTurno = 0;
                        $horaSalidaDisplay = date('H:i', $horaSalida);

                        // Verificar si es horario nocturno (hora salida < hora entrada)
                        if ($horaSalida < $horaEntrada) {
                            // Horario nocturno: calcular desde entrada hasta medianoche + desde medianoche hasta salida
                            $medianoche = strtotime(date('Y-m-d', $horaEntrada) . ' 23:59:59') + 1; // Inicio del día siguiente
                            $horasTurno = (($medianoche - $horaEntrada) + ($horaSalida - strtotime(date('Y-m-d', $horaSalida) . ' 00:00:00'))) / 3600;
                            $horaSalidaDisplay = date('H:i', $horaSalida) . ' (día siguiente)';
                        } else {
                            // Horario normal
                            $horasTurno = ($horaSalida - $horaEntrada) / 3600;
                        }

                        if ($horasTurno > 0) {
                            $horasDia += $horasTurno;

                            $turnoNombre = $horario['NOMBRE_TURNO'] ?: 'Turno';
                            $detalleHorarios[] = $turnoNombre . ': ' .
                                               date('H:i', $horaEntrada) . '-' . $horaSalidaDisplay .
                                               ' (' . formatHoursToHoursMinutes($horasTurno) . ' h)';
                        }
                    }
                }

                if (!empty($detalleHorarios)) {
                    $horasPorDia[$diaId] = implode("\n", $detalleHorarios) . "\nTotal: " . formatHoursToHoursMinutes($horasDia) . ' h';
                    $totalHorasSemanales += $horasDia;
                } else {
                    $horasPorDia[$diaId] = 'Día de descanso';
                }
            } else {
                $horasPorDia[$diaId] = 'Día de descanso';
            }
        }

        // Escribir fila del empleado
        $sheet->setCellValue('A' . $row, $empleado['nombre_completo']);
        $sheet->getStyle('A' . $row)->applyFromArray($employeeStyle);

        // Escribir horarios por día
        foreach ($diasSemana as $index => $dia) {
            $diaId = $index + 1; // 1=Lunes, 2=Martes, etc.
            $col = chr(66 + $index); // B, C, D, E, F, G, H
            $valor = $horasPorDia[$diaId] ?? 'Día de descanso';

            $sheet->setCellValue($col . $row, $valor);
            $sheet->getStyle($col . $row)->applyFromArray($scheduleStyle);
        }

        // Ajustar altura de fila automáticamente para todas las filas de empleados
        // ya que el contenido puede tener múltiples líneas con ajuste de texto
        $sheet->getRowDimension($row)->setRowHeight(-1);

        // Total horas semanales
        $sheet->setCellValue('I' . $row, hoursToExcelDuration($totalHorasSemanales));
        $sheet->getStyle('I' . $row)->applyFromArray($totalStyle);

        $row++;
    }

    if ($row > 3) {
        $sheet->getStyle('I3:I' . ($row - 1))->getNumberFormat()->setFormatCode('[h]:mm');
    }

    // Ajustar anchos de columna
    $sheet->getColumnDimension('A')->setWidth(25);
    for ($i = 0; $i < 7; $i++) {
        $col = chr(66 + $i);
        $sheet->getColumnDimension($col)->setWidth(20);
    }
    $sheet->getColumnDimension('I')->setWidth(15);

    // Configurar impresión
    $sheet->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE);
    $sheet->getPageSetup()->setFitToWidth(1);
    $sheet->getPageSetup()->setFitToHeight(0);

    // Crear el writer y enviar el archivo
    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');

    // Limpiar el buffer de output
    ob_end_flush();

} catch (Exception $e) {
    // Limpiar cualquier output que se haya generado
    ob_clean();

    // En caso de error, enviar respuesta JSON con headers apropiados
    header('Content-Type: application/json');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    echo json_encode(['success' => false, 'message' => 'Error al exportar: ' . $e->getMessage()]);
    ob_end_flush();
}
?>