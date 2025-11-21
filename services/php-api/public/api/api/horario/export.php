<?php
require_once __DIR__ . '/../../config/database.php';
session_start();

// Verificar autenticación
if (!isset($_SESSION['id_empresa'])) {
    header('HTTP/1.1 401 Unauthorized');
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Sesión inválida']);
    exit;
}

$empresaId = $_SESSION['id_empresa'];

try {
    // Parámetros de filtro
    $filtros = [
        'id' => $_GET['id'] ?? null,
        'nombre' => $_GET['nombre'] ?? null,
        'sede' => $_GET['sede'] ?? null,
        'establecimiento' => $_GET['establecimiento'] ?? null,
        'dia' => $_GET['dia'] ?? null
    ];

    // Construcción de la consulta
    $where = ["s.ID_EMPRESA = :empresa_id"];
    $params = [':empresa_id' => $empresaId];

    if ($filtros['id']) {
        $where[] = "h.ID_HORARIO = :id";
        $params[':id'] = $filtros['id'];
    }

    if ($filtros['nombre']) {
        $where[] = "h.NOMBRE LIKE :nombre";
        $params[':nombre'] = '%' . $filtros['nombre'] . '%';
    }

    if ($filtros['sede']) {
        $where[] = "s.ID_SEDE = :sede";
        $params[':sede'] = $filtros['sede'];
    }

    if ($filtros['establecimiento']) {
        $where[] = "e.ID_ESTABLECIMIENTO = :establecimiento";
        $params[':establecimiento'] = $filtros['establecimiento'];
    }

    if ($filtros['dia']) {
        $where[] = "EXISTS (SELECT 1 FROM HORARIO_DIA hd WHERE hd.ID_HORARIO = h.ID_HORARIO AND hd.ID_DIA = :dia)";
        $params[':dia'] = $filtros['dia'];
    }

    $whereClause = implode(' AND ', $where);

    // Consulta para obtener los horarios
    $sql = "
        SELECT 
            h.ID_HORARIO,
            h.NOMBRE,
            s.NOMBRE as sede,
            e.NOMBRE as establecimiento,
            h.HORA_ENTRADA,
            h.HORA_SALIDA,
            h.TOLERANCIA,
            (
                SELECT GROUP_CONCAT(
                    CASE hd.ID_DIA
                        WHEN 1 THEN 'Lunes'
                        WHEN 2 THEN 'Martes'
                        WHEN 3 THEN 'Miércoles'
                        WHEN 4 THEN 'Jueves'
                        WHEN 5 THEN 'Viernes'
                        WHEN 6 THEN 'Sábado'
                        WHEN 7 THEN 'Domingo'
                    END
                    ORDER BY hd.ID_DIA SEPARATOR ', '
                )
                FROM HORARIO_DIA hd
                WHERE hd.ID_HORARIO = h.ID_HORARIO
            ) as dias,
            (SELECT COUNT(*) FROM EMPLEADO_HORARIO eh WHERE eh.ID_HORARIO = h.ID_HORARIO) as empleados_count
        FROM horario h
        JOIN establecimiento e ON h.ID_ESTABLECIMIENTO = e.ID_ESTABLECIMIENTO
        JOIN sede s ON e.ID_SEDE = s.ID_SEDE
        WHERE {$whereClause}
        GROUP BY h.ID_HORARIO
        ORDER BY h.ID_HORARIO DESC
    ";

    $stmt = $conn->prepare($sql);
    
    // Bind parámetros de filtro
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    
    $stmt->execute();
    $horarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Nombre del archivo
    $fileName = 'Horarios_' . date('Y-m-d_H-i-s') . '.xls';

    // Encabezados para descarga
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="' . $fileName . '"');
    header('Cache-Control: max-age=0');

    // Crear documento Excel como XML
    echo '<?xml version="1.0" encoding="UTF-8"?>';
    echo '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" xmlns:x="urn:schemas-microsoft-com:office:excel">';
    echo '<Worksheet ss:Name="Horarios">';
    echo '<Table>';
    
    // Encabezados de columna
    echo '<Row>';
    echo '<Cell><Data ss:Type="String">ID</Data></Cell>';
    echo '<Cell><Data ss:Type="String">Nombre</Data></Cell>';
    echo '<Cell><Data ss:Type="String">Sede</Data></Cell>';
    echo '<Cell><Data ss:Type="String">Establecimiento</Data></Cell>';
    echo '<Cell><Data ss:Type="String">Días</Data></Cell>';
    echo '<Cell><Data ss:Type="String">Hora Entrada</Data></Cell>';
    echo '<Cell><Data ss:Type="String">Hora Salida</Data></Cell>';
    echo '<Cell><Data ss:Type="String">Tolerancia (min)</Data></Cell>';
    echo '<Cell><Data ss:Type="String">Empleados asignados</Data></Cell>';
    echo '</Row>';
    
    // Datos
    foreach ($horarios as $horario) {
        echo '<Row>';
        echo '<Cell><Data ss:Type="Number">' . $horario['ID_HORARIO'] . '</Data></Cell>';
        echo '<Cell><Data ss:Type="String">' . htmlspecialchars($horario['NOMBRE']) . '</Data></Cell>';
        echo '<Cell><Data ss:Type="String">' . htmlspecialchars($horario['sede']) . '</Data></Cell>';
        echo '<Cell><Data ss:Type="String">' . htmlspecialchars($horario['establecimiento']) . '</Data></Cell>';
        echo '<Cell><Data ss:Type="String">' . htmlspecialchars($horario['dias'] ?? '') . '</Data></Cell>';
        echo '<Cell><Data ss:Type="String">' . htmlspecialchars($horario['HORA_ENTRADA']) . '</Data></Cell>';
        echo '<Cell><Data ss:Type="String">' . htmlspecialchars($horario['HORA_SALIDA']) . '</Data></Cell>';
        echo '<Cell><Data ss:Type="Number">' . $horario['TOLERANCIA'] . '</Data></Cell>';
        echo '<Cell><Data ss:Type="Number">' . $horario['empleados_count'] . '</Data></Cell>';
        echo '</Row>';
    }
    
    echo '</Table>';
    echo '</Worksheet>';
    echo '</Workbook>';
    
} catch (PDOException $e) {
    header('HTTP/1.1 500 Internal Server Error');
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Error al exportar horarios: ' . $e->getMessage()
    ]);
}
?>
