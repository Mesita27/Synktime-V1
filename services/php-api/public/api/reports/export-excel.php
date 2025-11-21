<?php
// Iniciar output buffering para evitar cualquier output accidental
ob_start();

// Limpiar cualquier output previo
ob_clean();

// Verificar que no haya output previo
if (headers_sent($file, $line)) {
    die("Headers already sent in $file on line $line");
}

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../utils/attendance_status_utils.php';
require_once __DIR__ . '/../../utils/horario_utils.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

session_start();

// Verificar autenticación
if (!isset($_SESSION['id_empresa'])) {
    header('HTTP/1.1 401 Unauthorized');
    exit('Acceso no autorizado');
}

$empresaId = $_SESSION['id_empresa'];

try {
    // Establecer zona horaria de Colombia
    date_default_timezone_set('America/Bogota');

    // Limpiar cualquier output que pueda haber sido generado
    ob_clean();

    // Obtener información de la empresa
    $stmtEmpresa = $conn->prepare('SELECT nombre FROM empresa WHERE id_empresa = :empresa_id');
    $stmtEmpresa->execute(['empresa_id' => $empresaId]);
    $empresa = $stmtEmpresa->fetch(PDO::FETCH_ASSOC);

    if (!$empresa) {
        throw new Exception('Empresa no encontrada');
    }

    // Ahora que hemos hecho todas las validaciones, enviar los headers
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="reporte_asistencia_' . date('Y-m-d') . '.xlsx"');
    header('Cache-Control: max-age=0');
    header('Pragma: public');

    // Parámetros de filtro
    $filtros = [
        'codigo' => $_GET['codigo'] ?? null,
        'nombre' => $_GET['nombre'] ?? null,
        'sede' => $_GET['sede'] ?? null,
        'establecimiento' => $_GET['establecimiento'] ?? null,
        'estado_entrada' => $_GET['estado_entrada'] ?? null,
        'fecha_desde' => $_GET['fecha_desde'] ?? null,
        'fecha_hasta' => $_GET['fecha_hasta'] ?? null,
        'tipo_reporte' => $_GET['tipo_reporte'] ?? null
    ];

    // Construir consulta base (igual que combined.php)
    $where = [];
    $params = [];

    // Aplicar filtro de empresa
    $where[] = 'id_sede IN (SELECT id_sede FROM sede WHERE id_empresa = :empresa_id)';
    $params[':empresa_id'] = $empresaId;

    // Aplicar filtros de fecha
    if ($filtros['fecha_desde']) {
        $where[] = 'fecha_evento >= :fecha_desde';
        $params[':fecha_desde'] = $filtros['fecha_desde'];
    }

    if ($filtros['fecha_hasta']) {
        $where[] = 'fecha_evento <= :fecha_hasta';
        $params[':fecha_hasta'] = $filtros['fecha_hasta'];
    }

    // Filtros para reporte del día, semana o mes actual
    if ($filtros['tipo_reporte']) {
        switch ($filtros['tipo_reporte']) {
            case 'dia':
                $where[] = 'fecha_evento = CURDATE()';
                break;
            case 'semana':
                $where[] = 'YEARWEEK(fecha_evento, 1) = YEARWEEK(CURDATE(), 1)';
                break;
            case 'mes':
                $where[] = 'YEAR(fecha_evento) = YEAR(CURDATE()) AND MONTH(fecha_evento) = MONTH(CURDATE())';
                break;
        }
    }

    // Filtros adicionales
    if ($filtros['codigo']) {
        $where[] = 'codigo_empleado = :codigo';
        $params[':codigo'] = $filtros['codigo'];
    }

    if ($filtros['nombre']) {
        $where[] = '(nombre_empleado LIKE :nombre OR apellido_empleado LIKE :nombre)';
        $params[':nombre'] = '%' . $filtros['nombre'] . '%';
    }

    if ($filtros['sede'] && $filtros['sede'] !== 'Todas') {
        $where[] = 'id_sede = :sede';
        $params[':sede'] = $filtros['sede'];
    }

    if ($filtros['establecimiento'] && $filtros['establecimiento'] !== 'Todos') {
        $where[] = 'id_establecimiento = :establecimiento';
        $params[':establecimiento'] = $filtros['establecimiento'];
    }

    $filtro_estado = null;
    if ($filtros['estado_entrada'] && $filtros['estado_entrada'] !== 'Todos') {
        $filtro_estado = $filtros['estado_entrada'];
    }

    $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

    // Consulta principal: combinar asistencia y justificaciones (igual que combined.php)
    $sql = "
        SELECT *
        FROM (
            SELECT
                'asistencia' as tipo_registro,
                e.ID_EMPLEADO as codigo_empleado,
                e.DNI,
                e.NOMBRE as nombre_empleado,
                e.APELLIDO as apellido_empleado,
                s.ID_SEDE as id_sede,
                s.NOMBRE as sede,
                est.ID_ESTABLECIMIENTO as id_establecimiento,
                est.NOMBRE as establecimiento,
                a.FECHA as fecha_evento,
                a.HORA as hora_entrada,
                NULL as hora_salida,
                ehp.ID_EMPLEADO_HORARIO,
                ehp.HORA_ENTRADA as hora_entrada_programada,
                ehp.HORA_SALIDA as hora_salida_programada,
                ehp.TOLERANCIA,
                ehp.NOMBRE_TURNO,
                ehp.ES_TURNO_NOCTURNO,
                a.OBSERVACION,
                a.ID_ASISTENCIA as id_registro,
                0 as justificar_todos_turnos,
                NULL as detalle_adicional
            FROM asistencia a
            JOIN EMPLEADO e ON a.ID_EMPLEADO = e.ID_EMPLEADO
            JOIN ESTABLECIMIENTO est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
            JOIN SEDE s ON est.ID_SEDE = s.ID_SEDE
            LEFT JOIN empleado_horario_personalizado ehp ON a.ID_EMPLEADO_HORARIO = ehp.ID_EMPLEADO_HORARIO
                AND a.FECHA BETWEEN ehp.FECHA_DESDE AND COALESCE(ehp.FECHA_HASTA, '9999-12-31')
                AND ehp.ACTIVO = 'S'
            WHERE a.TIPO = 'ENTRADA'

            UNION ALL

            SELECT
                'justificacion' as tipo_registro,
                e.ID_EMPLEADO as codigo_empleado,
                e.DNI,
                e.NOMBRE as nombre_empleado,
                e.APELLIDO as apellido_empleado,
                s.ID_SEDE as id_sede,
                s.NOMBRE as sede,
                est.ID_ESTABLECIMIENTO as id_establecimiento,
                est.NOMBRE as establecimiento,
                j.fecha_falta as fecha_evento,
                NULL as hora_entrada,
                NULL as hora_salida,
                j.turno_id as ID_EMPLEADO_HORARIO,
                ehp.HORA_ENTRADA as hora_entrada_programada,
                ehp.HORA_SALIDA as hora_salida_programada,
                ehp.TOLERANCIA,
                ehp.NOMBRE_TURNO,
                ehp.ES_TURNO_NOCTURNO,
                j.motivo as OBSERVACION,
                j.id as id_registro,
                j.justificar_todos_turnos,
                j.detalle_adicional
            FROM justificaciones j
            JOIN EMPLEADO e ON j.empleado_id = e.ID_EMPLEADO
            JOIN ESTABLECIMIENTO est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
            JOIN SEDE s ON est.ID_SEDE = s.ID_SEDE
            LEFT JOIN empleado_horario_personalizado ehp ON j.turno_id = ehp.ID_EMPLEADO_HORARIO
                AND j.fecha_falta BETWEEN ehp.FECHA_DESDE AND COALESCE(ehp.FECHA_HASTA, '9999-12-31')
                AND ehp.ACTIVO = 'S'
            WHERE j.estado = 'aprobada'
        ) AS combined_data
        {$whereClause}
        ORDER BY fecha_evento DESC, codigo_empleado ASC
    ";

    $stmt = $conn->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Procesar registros para buscar salidas y calcular estados (igual que combined.php)
    $result = [];

    foreach ($registros as $registro) {
        $hora_entrada = $registro['hora_entrada'];
        $fecha = $registro['fecha_evento'];
        $id_empleado = $registro['codigo_empleado'];
        $tipo_registro = $registro['tipo_registro'];
        $id_empleado_horario = $registro['ID_EMPLEADO_HORARIO'];

        if ($id_empleado_horario) {
            $horarioInfo = obtenerHorarioPorId($id_empleado_horario, $conn) ?: [];
        } else {
            $horarioInfo = obtenerHorarioEmpleadoSimplificado($id_empleado, $fecha, $conn) ?: [];
        }

        $horaEntradaProgramada = $registro['hora_entrada_programada'] ?? null;
        $horaSalidaProgramada = $registro['hora_salida_programada'] ?? null;
        $nombreTurnoRegistro = $registro['NOMBRE_TURNO'] ?? null;

        if (!$horaEntradaProgramada && !empty($horarioInfo['HORA_ENTRADA'])) {
            $horaEntradaProgramada = $horarioInfo['HORA_ENTRADA'];
        }
        if (!$horaSalidaProgramada && !empty($horarioInfo['HORA_SALIDA'])) {
            $horaSalidaProgramada = $horarioInfo['HORA_SALIDA'];
        }

    $tolerancia = normalizarToleranciaMinutos($registro['TOLERANCIA'] ?? $horarioInfo['TOLERANCIA'] ?? 15);
        $justificarTodosTurnos = (int)($registro['justificar_todos_turnos'] ?? 0) === 1;
        $detalleAdicional = $registro['detalle_adicional'] ?? null;

        // Buscar salida correspondiente (solo para asistencia)
        $hora_salida = null;
        if ($tipo_registro === 'asistencia' && $id_empleado_horario) {
            $salidaSql = "
                SELECT HORA
                FROM asistencia
                WHERE ID_EMPLEADO = :id_empleado
                AND TIPO = 'SALIDA'
                AND ID_EMPLEADO_HORARIO = :id_horario
                AND CONCAT(FECHA, ' ', :hora_entrada) <= CONCAT(FECHA, ' ', HORA)
                AND CONCAT(FECHA, ' ', HORA) <= DATE_ADD(CONCAT(:fecha, ' ', :hora_entrada), INTERVAL 24 HOUR)
                ORDER BY CONCAT(FECHA, ' ', HORA) ASC
                LIMIT 1
            ";
            $salidaStmt = $conn->prepare($salidaSql);
            $salidaStmt->bindValue(':id_empleado', $id_empleado);
            $salidaStmt->bindValue(':id_horario', $id_empleado_horario);
            $salidaStmt->bindValue(':fecha', $fecha);
            $salidaStmt->bindValue(':hora_entrada', $hora_entrada);
            $salidaStmt->execute();
            $salidaResult = $salidaStmt->fetch(PDO::FETCH_ASSOC);
            if ($salidaResult) {
                $hora_salida = $salidaResult['HORA'];
            }
        }

        // Calcular estado de entrada usando las utilidades
        $estado_entrada = 'Ausente';

        if ($tipo_registro === 'justificacion') {
            $estado_entrada = 'Justificado';
        } elseif ($hora_entrada && $horaEntradaProgramada) {
            $estado = calcularEstadoEntrada($horaEntradaProgramada, $hora_entrada, $tolerancia);
            switch ($estado) {
                case 'Temprano':
                    $estado_entrada = 'Temprano';
                    break;
                case 'Puntual':
                    $estado_entrada = 'A Tiempo';
                    break;
                case 'Tardanza':
                    $estado_entrada = 'Tardanza';
                    break;
                default:
                    $estado_entrada = 'Ausente';
            }
        } elseif ($hora_entrada) {
            $estado_entrada = 'Presente';
        }

        // Calcular horas trabajadas (con manejo de turnos nocturnos)
        $horas_trabajadas = null;
        if ($hora_entrada && $hora_salida) {
            $hora_inicio = strtotime($fecha . ' ' . $hora_entrada);
            $hora_fin = strtotime($fecha . ' ' . $hora_salida);

            if ($hora_salida < $hora_entrada) {
                $hora_fin = strtotime($fecha . ' ' . $hora_salida . ' +1 day');
            }

            $diferencia_segundos = $hora_fin - $hora_inicio;
            $horas_trabajadas = round($diferencia_segundos / 3600, 2);
        }

        // Estado de salida usando las utilidades
        $estado_salida = '--';
        if ($hora_salida && $horaSalidaProgramada) {
            $estado_salida = calcularEstadoSalida($horaSalidaProgramada, $hora_salida, $tolerancia);
        } elseif ($hora_salida) {
            $estado_salida = 'Registrada';
        }

        // Filtrar por estado si necesario
        if ($filtro_estado && $estado_entrada !== $filtro_estado) {
            continue;
        }

        // Formatear fecha
        $fecha_formateada = date('d/m/Y', strtotime($fecha));

        // Ajustar campos de horario y observación para justificaciones de jornada completa
        $horarioNombre = $nombreTurnoRegistro ?: ($horarioInfo['horario_nombre'] ?? null);
        if ($tipo_registro === 'justificacion' && $justificarTodosTurnos) {
            $horarioNombre = $horarioNombre ?: 'Jornada completa';
        }

        $observacion = $registro['OBSERVACION'] ?: ($tipo_registro === 'justificacion' ? 'Justificación' : '');
        if ($tipo_registro === 'justificacion') {
            if ($detalleAdicional) {
                $observacion = trim($observacion . ' - ' . $detalleAdicional);
            }
            if ($justificarTodosTurnos) {
                $observacion = 'Jornada completa: ' . $observacion;
            }
        }

        if ($tipo_registro === 'justificacion' && $justificarTodosTurnos && !$horaEntradaProgramada && !$horaSalidaProgramada) {
            $horaEntradaProgramada = 'Jornada completa';
            $horaSalidaProgramada = '';
        }

        $tipoLabel = $tipo_registro === 'justificacion'
            ? ($justificarTodosTurnos ? 'Justificación (Jornada completa)' : 'Justificación')
            : 'Asistencia';

        $result[] = [
            'ID_EMPLEADO' => $registro['codigo_empleado'],
            'DNI' => $registro['DNI'],
            'NOMBRE' => $registro['nombre_empleado'],
            'APELLIDO' => $registro['apellido_empleado'],
            'sede' => $registro['sede'],
            'establecimiento' => $registro['establecimiento'],
            'FECHA_FORMATEADA' => $fecha_formateada,
            'ENTRADA_HORA' => $hora_entrada,
            'SALIDA_HORA' => $hora_salida,
            'ENTRADA_ESTADO' => $estado_entrada,
            'SALIDA_ESTADO' => $estado_salida,
            'HORAS_TRABAJADAS' => $horas_trabajadas,
            'HORARIO_NOMBRE' => $horarioNombre ?: 'Sin horario',
            'HORA_ENTRADA_PROGRAMADA' => $horaEntradaProgramada,
            'HORA_SALIDA_PROGRAMADA' => $horaSalidaProgramada,
            'OBSERVACION' => $observacion,
            'tipo_registro' => $tipoLabel,
            'justificacion_jornada_completa' => $justificarTodosTurnos
        ];
    }

    // Crear el archivo Excel
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Reporte de Asistencia');

    $headerStyle = [
        'font' => ['bold' => true, 'size' => 14],
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_CENTER,
            'wrapText' => true
        ],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E3F2FD']]
    ];

    $subHeaderStyle = [
        'font' => ['bold' => true, 'size' => 12],
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_LEFT,
            'wrapText' => true
        ],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F5F5F5']]
    ];

    $columnHeaderStyle = [
        'font' => ['bold' => true],
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_CENTER,
            'wrapText' => true
        ],
        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFF3E0']],
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
    ];

    $dataStyle = [
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_LEFT,
            'wrapText' => true
        ],
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
    ];

    $dataCenterStyle = [
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_CENTER,
            'wrapText' => true
        ],
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
    ];

    $estadoTempranoStyle = [
        'font' => ['color' => ['rgb' => '1976D2']],
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_CENTER,
            'wrapText' => true
        ],
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
    ];

    $estadoTiempoStyle = [
        'font' => ['color' => ['rgb' => '388E3C']],
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_CENTER,
            'wrapText' => true
        ],
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
    ];

    $estadoTardanzaStyle = [
        'font' => ['color' => ['rgb' => 'F57C00']],
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_CENTER,
            'wrapText' => true
        ],
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
    ];

    $estadoJustificadoStyle = [
        'font' => ['color' => ['rgb' => '9C27B0']],
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_CENTER,
            'wrapText' => true
        ],
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
    ];

    $estadoAusenteStyle = [
        'font' => ['color' => ['rgb' => '9E9E9E']],
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_CENTER,
            'wrapText' => true
        ],
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
    ];

    // Fila 1: Encabezado centrado con información de empresa
    $encabezado = $empresa['nombre'] . ' - Reporte de Asistencia';
    $sheet->mergeCells('A1:P1');
    $sheet->setCellValue('A1', $encabezado);
    $sheet->getStyle('A1:P1')->applyFromArray($headerStyle);

    // Fila 2: Información de filtros aplicados
    $filtrosTexto = 'Filtros aplicados: ';
    $filtrosAplicados = [];

    if ($filtros['fecha_desde'] || $filtros['fecha_hasta']) {
        $fechaTexto = 'Fecha: ' . ($filtros['fecha_desde'] ? date('d/m/Y', strtotime($filtros['fecha_desde'])) : 'Inicio') . ' - ' . ($filtros['fecha_hasta'] ? date('d/m/Y', strtotime($filtros['fecha_hasta'])) : 'Fin');
        $filtrosAplicados[] = $fechaTexto;
    }

    if ($filtros['tipo_reporte']) {
        $tipoTexto = '';
        switch ($filtros['tipo_reporte']) {
            case 'dia': $tipoTexto = 'Día actual'; break;
            case 'semana': $tipoTexto = 'Semana actual'; break;
            case 'mes': $tipoTexto = 'Mes actual'; break;
        }
        $filtrosAplicados[] = 'Tipo: ' . $tipoTexto;
    }

    if ($filtros['codigo']) {
        $filtrosAplicados[] = 'Código: ' . $filtros['codigo'];
    }

    if ($filtros['nombre']) {
        $filtrosAplicados[] = 'Nombre: ' . $filtros['nombre'];
    }

    if ($filtros['sede'] && $filtros['sede'] !== 'Todas') {
        // Obtener nombre de la sede
        $sedeNombre = '';
    $stmtSede = $conn->prepare("SELECT nombre FROM sede WHERE id_sede = :id_sede");
        $stmtSede->bindValue(':id_sede', $filtros['sede']);
        $stmtSede->execute();
        $sedeData = $stmtSede->fetch(PDO::FETCH_ASSOC);
        if ($sedeData) {
            $sedeNombre = $sedeData['NOMBRE'];
        }
        $filtrosAplicados[] = 'Sede: ' . $sedeNombre;
    }

    if ($filtros['establecimiento'] && $filtros['establecimiento'] !== 'Todos') {
        // Obtener nombre del establecimiento
        $estNombre = '';
    $stmtEst = $conn->prepare("SELECT nombre FROM establecimiento WHERE id_establecimiento = :id_establecimiento");
        $stmtEst->bindValue(':id_establecimiento', $filtros['establecimiento']);
        $stmtEst->execute();
        $estData = $stmtEst->fetch(PDO::FETCH_ASSOC);
        if ($estData) {
            $estNombre = $estData['NOMBRE'];
        }
        $filtrosAplicados[] = 'Establecimiento: ' . $estNombre;
    }

    if ($filtros['estado_entrada'] && $filtros['estado_entrada'] !== 'Todos') {
        $estadoTexto = '';
        switch ($filtros['estado_entrada']) {
            case 'Puntual': $estadoTexto = 'Puntual'; break;
            case 'Tarde': $estadoTexto = 'Tarde'; break;
            case 'Ausente': $estadoTexto = 'Ausente'; break;
            case 'Justificado': $estadoTexto = 'Justificado'; break;
        }
        $filtrosAplicados[] = 'Estado Entrada: ' . $estadoTexto;
    }

    if (empty($filtrosAplicados)) {
        $filtrosTexto .= 'Sin filtros aplicados';
    } else {
        $filtrosTexto .= implode(' | ', $filtrosAplicados);
    }

    $filtrosTexto .= ' | Exportado: ' . date('d/m/Y H:i');

    $sheet->mergeCells('A2:P2');
    $sheet->setCellValue('A2', $filtrosTexto);
    $sheet->getStyle('A2:P2')->applyFromArray($subHeaderStyle);

    // Fila 3: Encabezados de columna
    $headers = [
        'Código', 'DNI', 'Nombre', 'Apellido', 'Sede', 'Establecimiento',
        'Fecha', 'Hora Entrada', 'Hora Salida', 'Estado Entrada', 'Estado Salida',
        'Horas Trabajadas', 'Tipo Registro', 'Horario Asignado', 'Hora Entrada (Horario)', 'Hora Salida (Horario)', 'Observación'
    ];

    $col = 'A';
    foreach ($headers as $header) {
        $sheet->setCellValue($col . '3', $header);
        $sheet->getStyle($col . '3')->applyFromArray($columnHeaderStyle);
        $col++;
    }
    $sheet->getRowDimension(1)->setRowHeight(-1);
    $sheet->getRowDimension(2)->setRowHeight(-1);
    $sheet->getRowDimension(3)->setRowHeight(-1);

    // Datos
    $row = 4; // Comenzar desde la fila 4
    foreach ($result as $asistencia) {
        $col = 'A';

        // Código
        $sheet->setCellValue($col++ . $row, $asistencia['ID_EMPLEADO'] ?? '');
        $sheet->getStyle(chr(ord($col)-1) . $row)->applyFromArray($dataStyle);

        // DNI
        $sheet->setCellValue($col++ . $row, $asistencia['DNI'] ?? '');
        $sheet->getStyle(chr(ord($col)-1) . $row)->applyFromArray($dataStyle);

        // Nombre
        $sheet->setCellValue($col++ . $row, $asistencia['NOMBRE'] ?? '');
        $sheet->getStyle(chr(ord($col)-1) . $row)->applyFromArray($dataStyle);

        // Apellido
        $sheet->setCellValue($col++ . $row, $asistencia['APELLIDO'] ?? '');
        $sheet->getStyle(chr(ord($col)-1) . $row)->applyFromArray($dataStyle);

        // Sede
        $sheet->setCellValue($col++ . $row, $asistencia['sede'] ?? '');
        $sheet->getStyle(chr(ord($col)-1) . $row)->applyFromArray($dataStyle);

        // Establecimiento
        $sheet->setCellValue($col++ . $row, $asistencia['establecimiento'] ?? '');
        $sheet->getStyle(chr(ord($col)-1) . $row)->applyFromArray($dataStyle);

        // Fecha
        $sheet->setCellValue($col++ . $row, $asistencia['FECHA_FORMATEADA'] ?? '');
        $sheet->getStyle(chr(ord($col)-1) . $row)->applyFromArray($dataCenterStyle);

        // Hora Entrada
        $sheet->setCellValue($col++ . $row, $asistencia['ENTRADA_HORA'] ?? '');
        $sheet->getStyle(chr(ord($col)-1) . $row)->applyFromArray($dataCenterStyle);

        // Hora Salida
        $sheet->setCellValue($col++ . $row, $asistencia['SALIDA_HORA'] ?? '');
        $sheet->getStyle(chr(ord($col)-1) . $row)->applyFromArray($dataCenterStyle);

        // Estado Entrada
        $estadoEntrada = $asistencia['ENTRADA_ESTADO'] ?? '';
        $sheet->setCellValue($col++ . $row, $estadoEntrada);
        $styleEstado = $dataCenterStyle;
        switch ($estadoEntrada) {
            case 'Temprano':
                $styleEstado = $estadoTempranoStyle;
                break;
            case 'A Tiempo':
                $styleEstado = $estadoTiempoStyle;
                break;
            case 'Tardanza':
                $styleEstado = $estadoTardanzaStyle;
                break;
            case 'Ausente':
                $styleEstado = $estadoAusenteStyle;
                break;
            case 'Justificado':
                $styleEstado = $estadoJustificadoStyle;
                break;
            case 'Presente':
                $styleEstado = $estadoTiempoStyle; // Verde para presente sin horario
                break;
        }
        $sheet->getStyle(chr(ord($col)-1) . $row)->applyFromArray($styleEstado);

        // Estado Salida
        $estadoSalida = $asistencia['SALIDA_ESTADO'] ?? '';
        $sheet->setCellValue($col++ . $row, $estadoSalida);
        $sheet->getStyle(chr(ord($col)-1) . $row)->applyFromArray($dataCenterStyle);

        // Horas Trabajadas
        $horas = $asistencia['HORAS_TRABAJADAS'] ? $asistencia['HORAS_TRABAJADAS'] . ' h' : '';
        $sheet->setCellValue($col++ . $row, $horas);
        $sheet->getStyle(chr(ord($col)-1) . $row)->applyFromArray($dataCenterStyle);

        // Tipo de registro
        $sheet->setCellValue($col++ . $row, $asistencia['tipo_registro'] ?? '');
        $sheet->getStyle(chr(ord($col)-1) . $row)->applyFromArray($dataStyle);

        // Horario Asignado
        $sheet->setCellValue($col++ . $row, $asistencia['HORARIO_NOMBRE'] ?? '');
        $sheet->getStyle(chr(ord($col)-1) . $row)->applyFromArray($dataStyle);

        // Hora Entrada (Horario)
        $sheet->setCellValue($col++ . $row, $asistencia['HORA_ENTRADA_PROGRAMADA'] ?? '');
        $sheet->getStyle(chr(ord($col)-1) . $row)->applyFromArray($dataCenterStyle);

        // Hora Salida (Horario)
        $sheet->setCellValue($col++ . $row, $asistencia['HORA_SALIDA_PROGRAMADA'] ?? '');
        $sheet->getStyle(chr(ord($col)-1) . $row)->applyFromArray($dataCenterStyle);

        // Observación
        $sheet->setCellValue($col++ . $row, $asistencia['OBSERVACION'] ?? '');
        $sheet->getStyle(chr(ord($col)-1) . $row)->applyFromArray($dataStyle);

        $sheet->getRowDimension($row)->setRowHeight(-1);
        $row++;
    }

    $lastRow = $row - 1;
    if ($lastRow >= 1) {
        $sheet->getStyle('A1:P' . $lastRow)->getAlignment()->setWrapText(true);
    }

    // Ajustar anchos de columna
    $columnWidths = [12, 12, 20, 20, 15, 20, 12, 12, 12, 15, 15, 15, 18, 20, 15, 15, 25];
    $col = 'A';
    foreach ($columnWidths as $width) {
        $sheet->getColumnDimension($col)->setWidth($width);
        $col++;
    }

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