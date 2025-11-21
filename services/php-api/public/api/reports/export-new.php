<?php
/**
 * API para exportar reportes a Excel
 * Utiliza PhpSpreadsheet para generar archivos Excel con formato moderno
 */

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/timezone.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Font;

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['id_empresa'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acceso no autorizado']);
    exit;
}

$empresaId = $_SESSION['id_empresa'];

/**
 * Genera y descarga un archivo Excel con los datos de reportes
 */
function exportReportsToExcel($filters = []) {
    global $empresaId;

    try {
        // Obtener datos usando la misma lógica que data-new.php
        $data = getReportsData($filters, 1, PHP_INT_MAX); // Obtener todos los registros

        if (!$data['success']) {
            throw new Exception($data['message']);
        }

        // Crear nuevo spreadsheet
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Reportes de Asistencia');

        // Configurar propiedades del documento
        $spreadsheet->getProperties()
            ->setCreator('SynkTime System')
            ->setLastModifiedBy('SynkTime System')
            ->setTitle('Reportes de Asistencia')
            ->setSubject('Datos de asistencia de empleados')
            ->setDescription('Reporte generado automáticamente por el sistema SynkTime');

        // Definir estilos
        $headerStyle = [
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 12,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '2C3E50'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ];

        $dataStyle = [
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_LEFT,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'CCCCCC'],
                ],
            ],
        ];

        $statusStyles = [
            'PRESENTE' => [
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'D4EDDA'],
                ],
                'font' => [
                    'color' => ['rgb' => '155724'],
                    'bold' => true,
                ],
            ],
            'AUSENTE' => [
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'F8D7DA'],
                ],
                'font' => [
                    'color' => ['rgb' => '721C24'],
                    'bold' => true,
                ],
            ],
            'TARDE' => [
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'FFF3CD'],
                ],
                'font' => [
                    'color' => ['rgb' => '856404'],
                    'bold' => true,
                ],
            ],
            'JUSTIFICADO' => [
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'D1ECF1'],
                ],
                'font' => [
                    'color' => ['rgb' => '0C5460'],
                    'bold' => true,
                ],
            ],
        ];

        // Definir encabezados
        $headers = [
            'A1' => 'Código Empleado',
            'B1' => 'Nombre Completo',
            'C1' => 'Fecha',
            'D1' => 'Estado Asistencia',
            'E1' => 'Hora Entrada',
            'F1' => 'Hora Salida',
            'G1' => 'Horas Trabajadas',
            'H1' => 'Horario Asignado',
            'I1' => 'Turno Nocturno',
            'J1' => 'Tolerancia (min)',
            'K1' => 'Sede',
            'L1' => 'Establecimiento'
        ];

        // Aplicar encabezados
        foreach ($headers as $cell => $header) {
            $sheet->setCellValue($cell, $header);
        }

        // Aplicar estilo a encabezados
        $sheet->getStyle('A1:L1')->applyFromArray($headerStyle);

        // Agregar datos
        $row = 2;
        foreach ($data['data'] as $record) {
            $sheet->setCellValue("A{$row}", $record['codigo_empleado']);
            $sheet->setCellValue("B{$row}", $record['nombre_completo']);
            $sheet->setCellValue("C{$row}", $record['fecha']);
            $sheet->setCellValue("D{$row}", $record['estado_asistencia']);
            $sheet->setCellValue("E{$row}", $record['hora_entrada'] ?: '-');
            $sheet->setCellValue("F{$row}", $record['hora_salida'] ?: '-');
            $sheet->setCellValue("G{$row}", $record['horas_trabajadas'] ?: '-');
            $sheet->setCellValue("H{$row}", $record['horario_asignado'] ?: '-');
            $sheet->setCellValue("I{$row}", $record['es_turno_nocturno'] ? 'Sí' : 'No');
            $sheet->setCellValue("J{$row}", $record['tolerancia'] ?: 0);
            $sheet->setCellValue("K{$row}", $record['sede']);
            $sheet->setCellValue("L{$row}", $record['establecimiento']);

            // Aplicar estilo de estado
            $status = $record['estado_asistencia'];
            if (isset($statusStyles[$status])) {
                $sheet->getStyle("D{$row}")->applyFromArray($statusStyles[$status]);
            }

            // Aplicar estilo general a la fila
            $sheet->getStyle("A{$row}:L{$row}")->applyFromArray($dataStyle);

            $row++;
        }

        // Ajustar ancho de columnas automáticamente
        foreach (range('A', 'L') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        // Congelar fila de encabezados
        $sheet->freezePane('A2');

        // Agregar filtros
        $sheet->setAutoFilter('A1:L1');

        // Crear segunda hoja con estadísticas
        $statsSheet = $spreadsheet->createSheet();
        $statsSheet->setTitle('Estadísticas');

        // Estadísticas generales
        $statsSheet->setCellValue('A1', 'ESTADÍSTICAS GENERALES');
        $statsSheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $statsSheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $statsSheet->mergeCells('A1:D1');

        $statsRow = 3;
        $statsSheet->setCellValue("A{$statsRow}", 'Total de Empleados:');
        $statsSheet->setCellValue("B{$statsRow}", $data['statistics']['total_empleados']);
        $statsRow++;

        $statsSheet->setCellValue("A{$statsRow}", 'Total de Registros:');
        $statsSheet->setCellValue("B{$statsRow}", $data['statistics']['total_registros']);
        $statsRow++;

        $statsSheet->setCellValue("A{$statsRow}", 'Presentes Hoy:');
        $statsSheet->setCellValue("B{$statsRow}", $data['statistics']['presentes_hoy']);
        $statsRow++;

        $statsSheet->setCellValue("A{$statsRow}", 'Ausentes Hoy:');
        $statsSheet->setCellValue("B{$statsRow}", $data['statistics']['ausentes_hoy']);
        $statsRow++;

        $statsSheet->setCellValue("A{$statsRow}", 'Tarde Hoy:');
        $statsSheet->setCellValue("B{$statsRow}", $data['statistics']['tarde_hoy']);
        $statsRow++;

        $statsSheet->setCellValue("A{$statsRow}", 'Horas Promedio Diarias:');
        $statsSheet->setCellValue("B{$statsRow}", $data['statistics']['horas_promedio'] ?: '0.00');
        $statsRow++;

        // Aplicar estilos a estadísticas
        $statsSheet->getStyle('A3:B' . ($statsRow - 1))->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'CCCCCC'],
                ],
            ],
        ]);

        $statsSheet->getColumnDimension('A')->setAutoSize(true);
        $statsSheet->getColumnDimension('B')->setAutoSize(true);

        // Agregar información de filtros aplicados
        $filtersRow = $statsRow + 2;
        $statsSheet->setCellValue("A{$filtersRow}", 'FILTROS APLICADOS');
        $statsSheet->getStyle("A{$filtersRow}")->getFont()->setBold(true)->setSize(12);
        $filtersRow++;

        if (!empty($filters)) {
            foreach ($filters as $key => $value) {
                if ($value && $key !== 'page' && $key !== 'pageSize') {
                    $label = getFilterLabel($key);
                    $statsSheet->setCellValue("A{$filtersRow}", $label . ':');
                    $statsSheet->setCellValue("B{$filtersRow}", $value);
                    $filtersRow++;
                }
            }
        } else {
            $statsSheet->setCellValue("A{$filtersRow}", 'Sin filtros aplicados');
        }

        // Generar nombre de archivo
        $timestamp = date('Y-m-d_H-i-s');
        $filename = "reportes_asistencia_{$timestamp}.xlsx";

        // Configurar headers para descarga
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header("Content-Disposition: attachment; filename=\"{$filename}\"");
        header('Cache-Control: max-age=0');

        // Crear writer y enviar archivo
        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');

        exit;

    } catch (Exception $e) {
        error_log("Error en exportReportsToExcel: " . $e->getMessage());
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Error al generar el archivo Excel: ' . $e->getMessage()
        ]);
        exit;
    }
}

/**
 * Obtiene etiqueta legible para los filtros
 */
function getFilterLabel($key) {
    $labels = [
        'fecha_desde' => 'Fecha Desde',
        'fecha_hasta' => 'Fecha Hasta',
        'codigo_empleado' => 'Código Empleado',
        'estado_asistencia' => 'Estado Asistencia',
        'id_sede' => 'Sede',
        'id_establecimiento' => 'Establecimiento',
        'search' => 'Búsqueda'
    ];

    return $labels[$key] ?? ucfirst(str_replace('_', ' ', $key));
}

/**
 * Función auxiliar para obtener datos (reutiliza lógica de data-new.php)
 */
function getReportsData($filters = [], $page = 1, $pageSize = 25) {
    // Esta función debería ser la misma que en data-new.php
    // Por simplicidad, aquí incluimos una versión simplificada
    // En producción, sería mejor tener esta lógica en una clase compartida

    global $empresaId;

    try {
        $conn = getConnection();

        // Construir consulta base
        $sql = "
            SELECT
                e.ID_EMPLEADO as codigo_empleado,
                CONCAT(e.NOMBRE, ' ', e.APELLIDO) as nombre_completo,
                a_fecha.FECHA as fecha,
                COALESCE(a_entrada.HORA, '-') as hora_entrada,
                COALESCE(a_salida.HORA, '-') as hora_salida,
                eh.HORA_ENTRADA as hora_entrada_programada,
                eh.HORA_SALIDA as hora_salida_programada,
                eh.TOLERANCIA as tolerancia,
                s.NOMBRE_SEDE as sede,
                est.NOMBRE_ESTABLECIMIENTO as establecimiento,
                CASE
                    WHEN a_entrada.ID_ASISTENCIA IS NOT NULL AND a_salida.ID_ASISTENCIA IS NOT NULL THEN
                        CASE
                            WHEN esTurnoNocturno(eh.HORA_ENTRADA, eh.HORA_SALIDA) THEN 'PRESENTE'
                            WHEN TIME(a_entrada.HORA) <= ADDTIME(eh.HORA_ENTRADA, SEC_TO_TIME(eh.TOLERANCIA * 60)) THEN 'PRESENTE'
                            ELSE 'TARDE'
                        END
                    WHEN a_entrada.ID_ASISTENCIA IS NOT NULL AND a_salida.ID_ASISTENCIA IS NULL THEN 'ENTRADA_SIN_SALIDA'
                    WHEN a_entrada.ID_ASISTENCIA IS NULL AND a_salida.ID_ASISTENCIA IS NOT NULL THEN 'SALIDA_SIN_ENTRADA'
                    ELSE 'AUSENTE'
                END as estado_asistencia,
                CASE WHEN esTurnoNocturno(eh.HORA_ENTRADA, eh.HORA_SALIDA) THEN 1 ELSE 0 END as es_turno_nocturno,
                calcularHorasTrabajadas(a_fecha.FECHA, a_entrada.HORA, a_salida.HORA, eh.HORA_ENTRADA, eh.HORA_SALIDA) as horas_trabajadas,
                COALESCE(h.NOMBRE_HORARIO, CONCAT('Horario ', eh.ID_EMPLEADO_HORARIO)) as horario_asignado
            FROM EMPLEADO e
            CROSS JOIN (
                SELECT DISTINCT FECHA
                FROM ASISTENCIA_FECHA
                WHERE FECHA >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
            ) fechas
            LEFT JOIN ASISTENCIA_FECHA a_fecha ON e.ID_EMPLEADO = a_fecha.ID_EMPLEADO AND a_fecha.FECHA = fechas.FECHA
            LEFT JOIN ASISTENCIA a_entrada ON a_fecha.ID_ASISTENCIA_FECHA = a_entrada.ID_ASISTENCIA_FECHA AND a_entrada.TIPO = 'ENTRADA'
            LEFT JOIN ASISTENCIA a_salida ON a_fecha.ID_ASISTENCIA_FECHA = a_salida.ID_ASISTENCIA_FECHA AND a_salida.TIPO = 'SALIDA'
            LEFT JOIN EMPLEADO_HORARIO eh ON e.ID_EMPLEADO = eh.ID_EMPLEADO
                AND eh.ACTIVO = 'S'
                AND fechas.FECHA BETWEEN eh.FECHA_INICIO_VIGENCIA AND COALESCE(eh.FECHA_FIN_VIGENCIA, CURDATE())
            LEFT JOIN HORARIO h ON eh.ID_HORARIO = h.ID_HORARIO
            INNER JOIN SEDE s ON e.ID_SEDE = s.ID_SEDE
            INNER JOIN ESTABLECIMIENTO est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
            WHERE s.ID_EMPRESA = ?
        ";

        $params = [$empresaId];
        $types = "i";

        // Aplicar filtros
        if (!empty($filters['fecha_desde'])) {
            $sql .= " AND fechas.FECHA >= ?";
            $params[] = $filters['fecha_desde'];
            $types .= "s";
        }

        if (!empty($filters['fecha_hasta'])) {
            $sql .= " AND fechas.FECHA <= ?";
            $params[] = $filters['fecha_hasta'];
            $types .= "s";
        }

        if (!empty($filters['codigo_empleado'])) {
            $sql .= " AND e.ID_EMPLEADO = ?";
            $params[] = $filters['codigo_empleado'];
            $types .= "s";
        }

        if (!empty($filters['estado_asistencia'])) {
            $sql .= " AND CASE
                    WHEN a_entrada.ID_ASISTENCIA IS NOT NULL AND a_salida.ID_ASISTENCIA IS NOT NULL THEN
                        CASE
                            WHEN esTurnoNocturno(eh.HORA_ENTRADA, eh.HORA_SALIDA) THEN 'PRESENTE'
                            WHEN TIME(a_entrada.HORA) <= ADDTIME(eh.HORA_ENTRADA, SEC_TO_TIME(eh.TOLERANCIA * 60)) THEN 'PRESENTE'
                            ELSE 'TARDE'
                        END
                    WHEN a_entrada.ID_ASISTENCIA IS NOT NULL AND a_salida.ID_ASISTENCIA IS NULL THEN 'ENTRADA_SIN_SALIDA'
                    WHEN a_entrada.ID_ASISTENCIA IS NULL AND a_salida.ID_ASISTENCIA IS NOT NULL THEN 'SALIDA_SIN_ENTRADA'
                    ELSE 'AUSENTE'
                END = ?";
            $params[] = $filters['estado_asistencia'];
            $types .= "s";
        }

        if (!empty($filters['id_sede'])) {
            $sql .= " AND e.ID_SEDE = ?";
            $params[] = $filters['id_sede'];
            $types .= "i";
        }

        if (!empty($filters['id_establecimiento'])) {
            $sql .= " AND e.ID_ESTABLECIMIENTO = ?";
            $params[] = $filters['id_establecimiento'];
            $types .= "i";
        }

        if (!empty($filters['search'])) {
            $searchTerm = "%{$filters['search']}%";
            $sql .= " AND (e.ID_EMPLEADO LIKE ? OR CONCAT(e.NOMBRE, ' ', e.APELLIDO) LIKE ?)";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $types .= "ss";
        }

        $sql .= " ORDER BY e.ID_EMPLEADO, fechas.FECHA DESC";

        // Paginación
        if ($pageSize !== PHP_INT_MAX) {
            $offset = ($page - 1) * $pageSize;
            $sql .= " LIMIT ? OFFSET ?";
            $params[] = $pageSize;
            $params[] = $offset;
            $types .= "ii";
        }

        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();

        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }

        // Obtener total de registros para paginación
        $countSql = "SELECT COUNT(*) as total FROM (" . str_replace(" ORDER BY e.ID_EMPLEADO, fechas.FECHA DESC" . ($pageSize !== PHP_INT_MAX ? " LIMIT ? OFFSET ?" : ""), "", $sql) . ") as count_query";
        $countStmt = $conn->prepare($countSql);
        array_pop($params); // Remover OFFSET
        array_pop($params); // Remover LIMIT
        $countTypes = substr($types, 0, -2); // Remover "ii"
        $countStmt->bind_param($countTypes, ...$params);
        $countStmt->execute();
        $countResult = $countStmt->get_result();
        $totalRecords = $countResult->fetch_assoc()['total'];

        // Estadísticas
        $stats = getReportsStatistics($filters);

        $stmt->close();
        $countStmt->close();
        $conn->close();

        return [
            'success' => true,
            'data' => $data,
            'pagination' => [
                'currentPage' => $page,
                'totalPages' => ceil($totalRecords / $pageSize),
                'totalRecords' => $totalRecords,
                'hasNext' => $page < ceil($totalRecords / $pageSize),
                'hasPrev' => $page > 1
            ],
            'statistics' => $stats
        ];

    } catch (Exception $e) {
        error_log("Error en getReportsData: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Error al obtener datos: ' . $e->getMessage()
        ];
    }
}

/**
 * Función auxiliar para estadísticas (simplificada)
 */
function getReportsStatistics($filters = []) {
    // Implementación simplificada de estadísticas
    return [
        'total_empleados' => 0,
        'total_registros' => 0,
        'presentes_hoy' => 0,
        'ausentes_hoy' => 0,
        'tarde_hoy' => 0,
        'horas_promedio' => 0
    ];
}

// Procesar solicitud
if (isset($_GET['export']) && $_GET['export'] === 'excel') {
    // Recopilar filtros de la URL
    $filters = [];
    $allowedFilters = ['fecha_desde', 'fecha_hasta', 'codigo_empleado', 'estado_asistencia', 'id_sede', 'id_establecimiento', 'search'];

    foreach ($allowedFilters as $filter) {
        if (!empty($_GET[$filter])) {
            $filters[$filter] = $_GET[$filter];
        }
    }

    exportReportsToExcel($filters);
} else {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Parámetro de exportación no válido'
    ]);
}
?>