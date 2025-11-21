<?php
declare(strict_types=1);

ob_start();

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/timezone.php';

date_default_timezone_set('America/Bogota');

session_start();

if (!isset($_SESSION['id_empresa'])) {
    sendJsonResponse(401, [
        'success' => false,
        'message' => 'La sesión ha expirado. Inicie sesión nuevamente.'
    ]);
}

$empresaId = (int) $_SESSION['id_empresa'];

$payload = getRequestPayload();
$filters = normalizeFilters($payload['filters'] ?? []);
$rawRecords = $payload['processedData'] ?? [];

if (!is_array($rawRecords) || count($rawRecords) === 0) {
    sendJsonResponse(400, [
        'success' => false,
        'message' => 'No se encontraron registros para exportar. Aplique filtros y vuelva a intentarlo.'
    ]);
}

try {
    $records = buildNormalizedRecords($conn, $empresaId, $rawRecords);

    if (count($records) === 0) {
        sendJsonResponse(400, [
            'success' => false,
            'message' => 'No hay información disponible con los filtros seleccionados.'
        ]);
    }

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Horas Trabajadas');

    $headerStyle = [
        'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_CENTER,
            'vertical' => Alignment::VERTICAL_CENTER,
            'wrapText' => true
        ],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2F75B5']],
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '1F4E78']]]
    ];

    $dataStyle = [
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_CENTER,
            'vertical' => Alignment::VERTICAL_CENTER,
            'wrapText' => true
        ],
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'DDDDDD']]]
    ];

    $statusFill = [
        'aprobada' => 'C6EFCE',
        'pendiente' => 'FFF2CC',
        'rechazada' => 'F8CBAD'
    ];

    $summaryRow = 1;
    $sheet->setCellValue('A' . $summaryRow, 'Reporte de Horas Trabajadas');
    $sheet->mergeCells('A' . $summaryRow . ':X' . $summaryRow);
    $sheet->getStyle('A' . $summaryRow)->getFont()->setBold(true)->setSize(16);
    $sheet->getStyle('A' . $summaryRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
    $summaryRow++;

    $periodo = formatPeriodLabel($filters['fechaDesde'], $filters['fechaHasta']);
    $sheet->setCellValue('A' . $summaryRow, 'Período: ' . $periodo);
    $sheet->mergeCells('A' . $summaryRow . ':M' . $summaryRow);
    $sheet->getStyle('A' . $summaryRow)->getFont()->setBold(true);

    $sheet->setCellValue('N' . $summaryRow, 'Generado el: ' . date('d/m/Y H:i:s'));
    $sheet->mergeCells('N' . $summaryRow . ':X' . $summaryRow);
    $sheet->getStyle('N' . $summaryRow)->getFont()->setBold(true);
    $summaryRow++;

    $sheet->setCellValue('A' . $summaryRow, 'Sede: ' . ($filters['sede'] ?? 'Todas'));
    $sheet->mergeCells('A' . $summaryRow . ':M' . $summaryRow);

    $sheet->setCellValue('N' . $summaryRow, 'Establecimiento: ' . ($filters['establecimiento'] ?? 'Todos'));
    $sheet->mergeCells('N' . $summaryRow . ':X' . $summaryRow);
    $summaryRow++;

    $sheet->setCellValue('A' . $summaryRow, buildEmpleadoLabel($filters));
    $sheet->mergeCells('A' . $summaryRow . ':X' . $summaryRow);
    $summaryRow += 2;

    $headers = [
        'Empleado', 'DNI', 'Sede', 'Establecimiento', 'Fecha', 'Día',
        'Horario Programado', 'Entrada Real', 'Salida Real',
        'Horas Regulares', 'Recargo Nocturno', 'Recargo Dominical/Festivo', 'Recargo Nocturno Dom/Fest',
        'Extra Diurna', 'Estado Extra Diurna',
        'Extra Nocturna', 'Estado Extra Nocturna',
        'Extra Diurna Dom/Fest', 'Estado Extra Diurna Dom/Fest',
        'Extra Nocturna Dom/Fest', 'Estado Extra Nocturna Dom/Fest',
        'Total (solo aprobadas)', 'Tipo de Registro', 'Observaciones'
    ];

    $columnIndex = ord('A');
    foreach ($headers as $header) {
        $columnLetter = chr($columnIndex);
        $sheet->setCellValue($columnLetter . $summaryRow, $header);
        $sheet->getStyle($columnLetter . $summaryRow)->applyFromArray($headerStyle);
        $sheet->getColumnDimension($columnLetter)->setWidth(match ($header) {
            'Empleado' => 28,
            'Observaciones' => 40,
            'Horario Programado' => 24,
            'Estado Registro' => 22,
            default => 18,
        });
        $columnIndex++;
    }

    $sheet->getRowDimension($summaryRow)->setRowHeight(28);
    $rowPointer = $summaryRow + 1;

    $totals = [
        'horasRegulares' => 0.0,
        'recargoNocturno' => 0.0,
        'recargoDominical' => 0.0,
        'recargoNocturnoDom' => 0.0,
        'extraDiurna' => 0.0,
        'extraNocturna' => 0.0,
        'extraDiurnaDom' => 0.0,
        'extraNocturnaDom' => 0.0,
        'totalAprobado' => 0.0,
    ];

    foreach ($records as $record) {
        $sheet->setCellValue('A' . $rowPointer, $record['empleado']);
        $sheet->setCellValue('B' . $rowPointer, $record['dni']);
        $sheet->setCellValue('C' . $rowPointer, $record['sede']);
        $sheet->setCellValue('D' . $rowPointer, $record['establecimiento']);
        $sheet->setCellValue('E' . $rowPointer, $record['fecha']);
        $sheet->setCellValue('F' . $rowPointer, $record['dia']);
        $sheet->setCellValue('G' . $rowPointer, $record['horario']);
        $sheet->setCellValue('H' . $rowPointer, $record['entrada']);
        $sheet->setCellValue('I' . $rowPointer, $record['salida']);
        $sheet->setCellValue('J' . $rowPointer, decimalToHoursMinutes($record['horasRegulares']));
        $sheet->setCellValue('K' . $rowPointer, decimalToHoursMinutes($record['recargoNocturno']));
        $sheet->setCellValue('L' . $rowPointer, decimalToHoursMinutes($record['recargoDominical']));
        $sheet->setCellValue('M' . $rowPointer, decimalToHoursMinutes($record['recargoNocturnoDom']));
        $sheet->setCellValue('N' . $rowPointer, decimalToHoursMinutes($record['extraDiurna']));
        $sheet->setCellValue('O' . $rowPointer, $record['estadoExtraDiurna']);
        $sheet->setCellValue('P' . $rowPointer, decimalToHoursMinutes($record['extraNocturna']));
        $sheet->setCellValue('Q' . $rowPointer, $record['estadoExtraNocturna']);
        $sheet->setCellValue('R' . $rowPointer, decimalToHoursMinutes($record['extraDiurnaDom']));
        $sheet->setCellValue('S' . $rowPointer, $record['estadoExtraDiurnaDom']);
        $sheet->setCellValue('T' . $rowPointer, decimalToHoursMinutes($record['extraNocturnaDom']));
        $sheet->setCellValue('U' . $rowPointer, $record['estadoExtraNocturnaDom']);
        $sheet->setCellValue('V' . $rowPointer, decimalToHoursMinutes($record['totalAprobado']));
    $sheet->setCellValue('W' . $rowPointer, $record['tipoRegistro']);
        $sheet->setCellValue('X' . $rowPointer, $record['observaciones']);

        $sheet->getStyle('A' . $rowPointer . ':X' . $rowPointer)->applyFromArray($dataStyle);
        $sheet->getStyle('G' . $rowPointer)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('X' . $rowPointer)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

        applyStatusStyle($sheet, 'O' . $rowPointer, $record['estadoExtraDiurna'], $statusFill);
        applyStatusStyle($sheet, 'Q' . $rowPointer, $record['estadoExtraNocturna'], $statusFill);
        applyStatusStyle($sheet, 'S' . $rowPointer, $record['estadoExtraDiurnaDom'], $statusFill);
        applyStatusStyle($sheet, 'U' . $rowPointer, $record['estadoExtraNocturnaDom'], $statusFill);

        if ($record['tipo'] === 'justificacion') {
            $sheet->getStyle('A' . $rowPointer . ':X' . $rowPointer)
                ->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F2F2F2');
        }

        $totals['horasRegulares'] += $record['horasRegulares'];
        $totals['recargoNocturno'] += $record['recargoNocturno'];
        $totals['recargoDominical'] += $record['recargoDominical'];
        $totals['recargoNocturnoDom'] += $record['recargoNocturnoDom'];
        if (($record['estadoExtraDiurnaRaw'] ?? null) === 'aprobada') {
            $totals['extraDiurna'] += $record['extraDiurna'];
        }
        if (($record['estadoExtraNocturnaRaw'] ?? null) === 'aprobada') {
            $totals['extraNocturna'] += $record['extraNocturna'];
        }
        if (($record['estadoExtraDiurnaDomRaw'] ?? null) === 'aprobada') {
            $totals['extraDiurnaDom'] += $record['extraDiurnaDom'];
        }
        if (($record['estadoExtraNocturnaDomRaw'] ?? null) === 'aprobada') {
            $totals['extraNocturnaDom'] += $record['extraNocturnaDom'];
        }
        $totals['totalAprobado'] += $record['totalAprobado'];

        $rowPointer++;
    }

    $sheet->setCellValue('A' . $rowPointer, 'TOTAL GENERAL (solo horas aprobadas)');
    $sheet->mergeCells('A' . $rowPointer . ':I' . $rowPointer);
    $sheet->getStyle('A' . $rowPointer . ':X' . $rowPointer)->applyFromArray([
        'font' => ['bold' => true],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E2EFDA']],
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'A9D08E']]],
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_CENTER,
            'vertical' => Alignment::VERTICAL_CENTER,
            'wrapText' => true
        ]
    ]);

    $sheet->setCellValue('J' . $rowPointer, decimalToHoursMinutes($totals['horasRegulares']));
    $sheet->setCellValue('K' . $rowPointer, decimalToHoursMinutes($totals['recargoNocturno']));
    $sheet->setCellValue('L' . $rowPointer, decimalToHoursMinutes($totals['recargoDominical']));
    $sheet->setCellValue('M' . $rowPointer, decimalToHoursMinutes($totals['recargoNocturnoDom']));
    $sheet->setCellValue('N' . $rowPointer, decimalToHoursMinutes($totals['extraDiurna']));
    $sheet->setCellValue('P' . $rowPointer, decimalToHoursMinutes($totals['extraNocturna']));
    $sheet->setCellValue('R' . $rowPointer, decimalToHoursMinutes($totals['extraDiurnaDom']));
    $sheet->setCellValue('T' . $rowPointer, decimalToHoursMinutes($totals['extraNocturnaDom']));
    $sheet->setCellValue('V' . $rowPointer, decimalToHoursMinutes($totals['totalAprobado']));

    $sheet->getRowDimension($rowPointer)->setRowHeight(24);

    $sheet->getStyle('A1:X' . $rowPointer)->getAlignment()->setWrapText(true);

    $sheet->freezePane('A' . ($summaryRow + 1));

    if (ob_get_length() > 0) {
        ob_end_clean();
    }

    $fileName = sprintf('horas_trabajadas_%s.xlsx', date('Ymd_His'));

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $fileName . '"');
    header('Cache-Control: max-age=0');
    header('Pragma: public');

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
} catch (Throwable $exception) {
    if (ob_get_length() > 0) {
        ob_end_clean();
    }

    error_log('[EXPORT HORAS TRABAJADAS] ' . $exception->getMessage());
    sendJsonResponse(500, [
        'success' => false,
        'message' => 'Ocurrió un error al generar el archivo Excel. Intente nuevamente.',
        'error' => $exception->getMessage()
    ]);
}

function getRequestPayload(): array
{
    $input = file_get_contents('php://input');
    if ($input !== false && trim($input) !== '') {
        $decoded = json_decode($input, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }
    }

    if (!empty($_POST)) {
        return $_POST;
    }

    return [];
}

function normalizeFilters(array $filters): array
{
    $empleadosRaw = $filters['empleados'] ?? [];
    if (!is_array($empleadosRaw)) {
        $empleadosRaw = [$empleadosRaw];
    }

    $empleados = array_values(array_filter(
        array_map(static fn($value) => (int) $value, $empleadosRaw),
        static fn($value) => $value > 0
    ));

    return [
        'sede' => $filters['sede'] ?? null,
        'establecimiento' => $filters['establecimiento'] ?? null,
        'fechaDesde' => $filters['fechaDesde'] ?? null,
        'fechaHasta' => $filters['fechaHasta'] ?? null,
        'empleados' => $empleados
    ];
}

function buildEmpleadoLabel(array $filters): string
{
    $empleados = $filters['empleados'];
    if (empty($empleados)) {
        return 'Empleados: Todos los empleados filtrados en la vista.';
    }

    $cantidad = count($empleados);
    return sprintf('Empleados seleccionados: %d', $cantidad);
}

function buildNormalizedRecords(PDO $conn, int $empresaId, array $rawRecords): array
{
    $records = [];
    $cache = [];

    foreach ($rawRecords as $record) {
        if (!is_array($record)) {
            continue;
        }

        $employeeId = isset($record['ID_EMPLEADO']) ? (int) $record['ID_EMPLEADO'] : null;
        if (!$employeeId) {
            continue;
        }

        $employeeInfo = getEmployeeInfo($conn, $empresaId, $employeeId, $cache);

        $nombre = sanitizeString(($record['NOMBRE'] ?? '') . ' ' . ($record['APELLIDO'] ?? ''));
        if ($nombre === '') {
            $nombre = trim($employeeInfo['nombreCompleto']);
        }

        $fecha = sanitizeString($record['FECHA'] ?? '');
        if ($fecha === '') {
            continue;
        }

        $dia = getDayName($fecha);
        $horario = sanitizeString($record['HORARIO_ASIGNADO'] ?? '');
        $entrada = sanitizeString($record['ENTRADA_HORA'] ?? '--');
        $salida = sanitizeString($record['SALIDA_HORA'] ?? '--');
        $observaciones = sanitizeString($record['OBSERVACIONES'] ?? '');
        $tipo = $record['tipo'] ?? 'horas';

        $horasRegulares = toFloat($record['HORAS_REGULARES'] ?? 0);
        $recargoNocturno = toFloat($record['RECARGO_NOCTURNO'] ?? 0);
        $recargoDominical = toFloat($record['RECARGO_DOMINICAL_FESTIVO'] ?? 0);
        $recargoNocturnoDom = toFloat($record['RECARGO_NOCTURNO_DOMINICAL_FESTIVO'] ?? 0);
        $extraDiurna = toFloat($record['EXTRA_DIURNA'] ?? 0);
        $extraNocturna = toFloat($record['EXTRA_NOCTURNA'] ?? 0);
        $extraDiurnaDom = toFloat($record['EXTRA_DIURNA_DOMINICAL_FESTIVA'] ?? 0);
        $extraNocturnaDom = toFloat($record['EXTRA_NOCTURNA_DOMINICAL_FESTIVA'] ?? 0);

        $estadoExtraDiurna = normalizeStatus($record['EXTRA_DIURNA_ESTADO'] ?? null);
        $estadoExtraNocturna = normalizeStatus($record['EXTRA_NOCTURNA_ESTADO'] ?? null);
        $estadoExtraDiurnaDom = normalizeStatus($record['EXTRA_DIURNA_DOMINICAL_ESTADO'] ?? null);
        $estadoExtraNocturnaDom = normalizeStatus($record['EXTRA_NOCTURNA_DOMINICAL_ESTADO'] ?? null);

        $totalAprobado = isset($record['TOTAL_HORAS']) ? toFloat($record['TOTAL_HORAS']) : calculateApprovedTotal(
            $horasRegulares,
            $recargoNocturno,
            $recargoDominical,
            $recargoNocturnoDom,
            $extraDiurna,
            $extraNocturna,
            $extraDiurnaDom,
            $extraNocturnaDom,
            $estadoExtraDiurna,
            $estadoExtraNocturna,
            $estadoExtraDiurnaDom,
            $estadoExtraNocturnaDom
        );

        $estadoGeneral = resolveEstadoGeneral($tipo, $extraDiurna, $extraNocturna, $extraDiurnaDom, $extraNocturnaDom, [
            $estadoExtraDiurna,
            $estadoExtraNocturna,
            $estadoExtraDiurnaDom,
            $estadoExtraNocturnaDom
        ]);

        $records[] = [
            'tipo' => $tipo,
            'empleado' => $nombre,
            'dni' => $employeeInfo['dni'],
            'sede' => $employeeInfo['sede'],
            'establecimiento' => $employeeInfo['establecimiento'],
            'fecha' => formatDate($fecha),
            'dia' => $dia,
            'horario' => $horario !== '' ? $horario : '--',
            'entrada' => $entrada !== '' ? $entrada : '--',
            'salida' => $salida !== '' ? $salida : '--',
            'horasRegulares' => $horasRegulares,
            'recargoNocturno' => $recargoNocturno,
            'recargoDominical' => $recargoDominical,
            'recargoNocturnoDom' => $recargoNocturnoDom,
            'extraDiurna' => $extraDiurna,
            'extraNocturna' => $extraNocturna,
            'extraDiurnaDom' => $extraDiurnaDom,
            'extraNocturnaDom' => $extraNocturnaDom,
            'estadoExtraDiurna' => statusLabel($estadoExtraDiurna),
            'estadoExtraNocturna' => statusLabel($estadoExtraNocturna),
            'estadoExtraDiurnaDom' => statusLabel($estadoExtraDiurnaDom),
            'estadoExtraNocturnaDom' => statusLabel($estadoExtraNocturnaDom),
            'estadoExtraDiurnaRaw' => $estadoExtraDiurna,
            'estadoExtraNocturnaRaw' => $estadoExtraNocturna,
            'estadoExtraDiurnaDomRaw' => $estadoExtraDiurnaDom,
            'estadoExtraNocturnaDomRaw' => $estadoExtraNocturnaDom,
            'totalAprobado' => $totalAprobado,
            'tipoRegistro' => $tipo === 'justificacion' ? 'Justificación' : 'Asistencia',
            'estadoGeneral' => $estadoGeneral,
            'observaciones' => $observaciones !== '' ? $observaciones : '--'
        ];
    }

    usort($records, static function (array $a, array $b) {
        return strcmp($a['fecha'] . $a['empleado'], $b['fecha'] . $b['empleado']);
    });

    return $records;
}

function getEmployeeInfo(PDO $conn, int $empresaId, int $employeeId, array &$cache): array
{
    if (isset($cache[$employeeId])) {
        return $cache[$employeeId];
    }

    $stmt = $conn->prepare('
        SELECT 
            e.NOMBRE AS nombre,
            e.APELLIDO AS apellido,
            e.DNI AS dni,
            COALESCE(est.NOMBRE, "") AS establecimiento,
            COALESCE(s.NOMBRE, "") AS sede
    FROM empleado e
        LEFT JOIN ESTABLECIMIENTO est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
        LEFT JOIN SEDE s ON est.ID_SEDE = s.ID_SEDE
        WHERE e.ID_EMPLEADO = :empleado
          AND (:empresa = 0 OR s.ID_EMPRESA = :empresa)
        LIMIT 1
    ');
    $stmt->execute([
        ':empleado' => $employeeId,
        ':empresa' => $empresaId,
    ]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [
        'nombre' => '',
        'apellido' => '',
        'dni' => '',
        'establecimiento' => '',
        'sede' => ''
    ];

    $row['nombreCompleto'] = trim(($row['nombre'] ?? '') . ' ' . ($row['apellido'] ?? ''));

    return $cache[$employeeId] = $row;
}

function sanitizeString(?string $value): string
{
    $value = $value ?? '';
    $value = strip_tags($value);
    $value = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    return trim($value);
}

function toFloat($value): float
{
    if (is_string($value)) {
        $value = str_replace(',', '.', $value);
    }
    return (float) $value;
}

function decimalToHoursMinutes(float $decimalHour): string
{
    $hours = (int) floor($decimalHour);
    $minutes = (int) round(($decimalHour - $hours) * 60);

    if ($minutes === 60) {
        $hours++;
        $minutes = 0;
    }

    return sprintf('%02dh %02dm', $hours, $minutes);
}

function formatDate(string $date): string
{
    $timestamp = strtotime($date);
    return $timestamp ? date('d/m/Y', $timestamp) : $date;
}

function formatPeriodLabel(?string $from, ?string $to): string
{
    if (!$from && !$to) {
        return 'Sin filtros de fecha';
    }

    if ($from && !$to) {
        return formatDate($from) . ' en adelante';
    }

    if (!$from && $to) {
        return 'Hasta ' . formatDate($to);
    }

    return formatDate($from) . ' - ' . formatDate($to);
}

function getDayName(string $date): string
{
    static $days = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
    $timestamp = strtotime($date);
    return $timestamp ? $days[(int) date('w', $timestamp)] : '';
}

function normalizeStatus($status): ?string
{
    if ($status === null) {
        return null;
    }

    $normalized = strtolower(trim((string) $status));
    return match ($normalized) {
        'aprobado', 'aprobada' => 'aprobada',
        'rechazado', 'rechazada' => 'rechazada',
        'pendiente' => 'pendiente',
        default => null,
    };
}

function statusLabel(?string $status): string
{
    return match ($status) {
        'aprobada' => 'Aprobada',
        'rechazada' => 'Rechazada',
        'pendiente' => 'Pendiente',
        default => '--',
    };
}

function calculateApprovedTotal(
    float $horasRegulares,
    float $recargoNocturno,
    float $recargoDominical,
    float $recargoNocturnoDom,
    float $extraDiurna,
    float $extraNocturna,
    float $extraDiurnaDom,
    float $extraNocturnaDom,
    ?string $estadoExtraDiurna,
    ?string $estadoExtraNocturna,
    ?string $estadoExtraDiurnaDom,
    ?string $estadoExtraNocturnaDom
): float {
    $total = $horasRegulares + $recargoNocturno + $recargoDominical + $recargoNocturnoDom;

    if ($estadoExtraDiurna === 'aprobada') {
        $total += $extraDiurna;
    }
    if ($estadoExtraNocturna === 'aprobada') {
        $total += $extraNocturna;
    }
    if ($estadoExtraDiurnaDom === 'aprobada') {
        $total += $extraDiurnaDom;
    }
    if ($estadoExtraNocturnaDom === 'aprobada') {
        $total += $extraNocturnaDom;
    }

    return $total;
}

function resolveEstadoGeneral(string $tipo, float $extraDiurna, float $extraNocturna, float $extraDiurnaDom, float $extraNocturnaDom, array $statusList): string
{
    if ($tipo === 'justificacion') {
        return 'Justificación';
    }

    $totalExtras = $extraDiurna + $extraNocturna + $extraDiurnaDom + $extraNocturnaDom;
    $statuses = array_filter($statusList);

    if ($totalExtras <= 0) {
        return 'Registro sin extras';
    }

    if (in_array('rechazada', $statuses, true)) {
        return 'Extras rechazadas';
    }

    if (in_array('pendiente', $statuses, true)) {
        return 'Extras pendientes de aprobación';
    }

    if (in_array('aprobada', $statuses, true)) {
        return 'Extras aprobadas';
    }

    return 'Extras registradas';
}

function applyStatusStyle(Worksheet $sheet, string $cell, string $statusLabel, array $statusFill): void
{
    $statusKey = match ($statusLabel) {
        'Aprobada' => 'aprobada',
        'Pendiente' => 'pendiente',
        'Rechazada' => 'rechazada',
        default => null,
    };

    if ($statusKey && isset($statusFill[$statusKey])) {
        $sheet->getStyle($cell)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($statusFill[$statusKey]);
    }
}

function sendJsonResponse(int $statusCode, array $payload): void
{
    if (ob_get_length() > 0) {
        ob_end_clean();
    }

    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}
