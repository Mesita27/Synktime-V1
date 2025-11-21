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
    header('Content-Disposition: attachment; filename="empleados_' . date('Y-m-d') . '.xlsx"');
    header('Cache-Control: max-age=0');
    header('Pragma: public');

    // Obtener filtros del request (mismos que usa la API de empleados)
    $filtros = [
        'codigo' => $_GET['codigo'] ?? null,
        'identificacion' => $_GET['identificacion'] ?? null,
        'nombre' => $_GET['nombre'] ?? null,
        'sede' => $_GET['sede'] ?? null,
        'establecimiento' => $_GET['establecimiento'] ?? null,
        'estado' => $_GET['estado'] ?? null
    ];

    // Obtener nombres de sede y establecimiento para el encabezado
    $sedeNombre = 'Todas las sedes';
    $establecimientoNombre = 'Todos los establecimientos';

    if (!empty($filtros['sede'])) {
        $stmtSede = $conn->prepare("SELECT nombre FROM sede WHERE id_sede = :sede");
        $stmtSede->execute(['sede' => $filtros['sede']]);
        $sedeData = $stmtSede->fetch(PDO::FETCH_ASSOC);
        $sedeNombre = $sedeData ? $sedeData['nombre'] : 'Sede no encontrada';
    }

    if (!empty($filtros['establecimiento'])) {
        $stmtEst = $conn->prepare("SELECT nombre FROM establecimiento WHERE id_establecimiento = :establecimiento");
        $stmtEst->execute(['establecimiento' => $filtros['establecimiento']]);
        $estData = $stmtEst->fetch(PDO::FETCH_ASSOC);
        $establecimientoNombre = $estData ? $estData['nombre'] : 'Establecimiento no encontrado';
    }

    // Usar la misma lógica que la API de empleados para obtener datos
    $where = ["s.ID_EMPRESA = :empresa_id"];
    $params = [':empresa_id' => $empresaId];

    // Aplicar filtros (misma lógica que list.php)
    if (!empty($filtros['codigo'])) {
        $where[] = "e.ID_EMPLEADO = :codigo";
        $params[':codigo'] = $filtros['codigo'];
    }

    if (!empty($filtros['identificacion'])) {
        $where[] = "e.DNI LIKE :identificacion";
        $params[':identificacion'] = '%' . $filtros['identificacion'] . '%';
    }

    if (!empty($filtros['nombre'])) {
        $where[] = "(e.NOMBRE LIKE :nombre OR e.APELLIDO LIKE :nombre)";
        $params[':nombre'] = '%' . $filtros['nombre'] . '%';
    }

    if (!empty($filtros['sede'])) {
        $where[] = "s.ID_SEDE = :sede";
        $params[':sede'] = $filtros['sede'];
    }

    if (!empty($filtros['establecimiento'])) {
        $where[] = "est.ID_ESTABLECIMIENTO = :establecimiento";
        $params[':establecimiento'] = $filtros['establecimiento'];
    }

    if (!empty($filtros['estado'])) {
        $where[] = "e.ESTADO = :estado";
        $params[':estado'] = $filtros['estado'];
    }

    // Consulta principal (sin paginación para exportar todo)
    $sql = "
        SELECT
            e.ID_EMPLEADO as id,
            e.DNI as identificacion,
            CONCAT(e.NOMBRE, ' ', e.APELLIDO) as nombre_completo,
            e.CORREO as email,
            e.TELEFONO as telefono,
            est.NOMBRE as establecimiento,
            s.NOMBRE as sede,
            e.FECHA_INGRESO as fecha_contratacion,
            CASE e.ESTADO
                WHEN 'A' THEN 'Activo'
                WHEN 'I' THEN 'Inactivo'
                ELSE e.ESTADO
            END as estado
        FROM empleado e
        JOIN establecimiento est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
        JOIN sede s ON est.ID_SEDE = s.ID_SEDE
        WHERE " . implode(' AND ', $where) . "
        ORDER BY e.NOMBRE ASC, e.APELLIDO ASC
    ";

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $empleados = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Crear el archivo Excel
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Empleados');

    // Estilos
    $headerStyle = [
        'font' => ['bold' => true, 'size' => 14],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E3F2FD']]
    ];

    $columnHeaderStyle = [
        'font' => ['bold' => true],
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFF3E0']],
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
    ];

    $dataStyle = [
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
    ];

    $dataStyleCenter = [
        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
    ];

    // Fila 1: Encabezado centrado con información de empresa, sede, establecimiento y fecha
    $encabezado = $empresa['nombre'] . ' - ' . $sedeNombre . ' - ' . $establecimientoNombre . ' - Exportado: ' . date('d/m/Y H:i');
    $sheet->mergeCells('A1:I1');
    $sheet->setCellValue('A1', $encabezado);
    $sheet->getStyle('A1:I1')->applyFromArray($headerStyle);

    // Fila 2: Encabezados de columnas
    $columnas = [
        'A' => 'Código',
        'B' => 'Identificación',
        'C' => 'Nombre Completo',
        'D' => 'Email',
        'E' => 'Teléfono',
        'F' => 'Establecimiento',
        'G' => 'Sede',
        'H' => 'Fecha Contratación',
        'I' => 'Estado'
    ];

    foreach ($columnas as $col => $titulo) {
        $sheet->setCellValue($col . '2', $titulo);
        $sheet->getStyle($col . '2')->applyFromArray($columnHeaderStyle);
    }

    // Procesar empleados
    $row = 3; // Comenzar desde la fila 3
    foreach ($empleados as $empleado) {
        $sheet->setCellValue('A' . $row, $empleado['id']);
        $sheet->getStyle('A' . $row)->applyFromArray($dataStyleCenter);

        $sheet->setCellValue('B' . $row, $empleado['identificacion']);
        $sheet->getStyle('B' . $row)->applyFromArray($dataStyle);

        $sheet->setCellValue('C' . $row, $empleado['nombre_completo']);
        $sheet->getStyle('C' . $row)->applyFromArray($dataStyle);

        $sheet->setCellValue('D' . $row, $empleado['email']);
        $sheet->getStyle('D' . $row)->applyFromArray($dataStyle);

        $sheet->setCellValue('E' . $row, $empleado['telefono']);
        $sheet->getStyle('E' . $row)->applyFromArray($dataStyle);

        $sheet->setCellValue('F' . $row, $empleado['establecimiento']);
        $sheet->getStyle('F' . $row)->applyFromArray($dataStyle);

        $sheet->setCellValue('G' . $row, $empleado['sede']);
        $sheet->getStyle('G' . $row)->applyFromArray($dataStyle);

        $sheet->setCellValue('H' . $row, $empleado['fecha_contratacion']);
        $sheet->getStyle('H' . $row)->applyFromArray($dataStyleCenter);

        $sheet->setCellValue('I' . $row, $empleado['estado']);
        $sheet->getStyle('I' . $row)->applyFromArray($dataStyleCenter);

        $row++;
    }

    // Ajustar ancho de columnas automáticamente
    foreach (range('A', 'I') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }

    // Configurar página para impresión
    $sheet->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE);
    $sheet->getPageSetup()->setFitToWidth(1);
    $sheet->getPageSetup()->setFitToHeight(0);

    // Crear el writer y enviar el archivo
    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');

} catch (Exception $e) {
    // En caso de error, mostrar mensaje
    ob_clean();
    header('Content-Type: text/plain');
    echo "Error al generar el archivo Excel: " . $e->getMessage();
    error_log("Error en exportación de empleados: " . $e->getMessage());
}
?>