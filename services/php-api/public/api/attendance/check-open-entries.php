<?php
// Evitar cualquier salida previa y asegurar formato JSON consistente
ob_start();

require_once __DIR__ . '/../../config/timezone.php';
require_once __DIR__ . '/../../auth/session.php';
require_once __DIR__ . '/../../config/database.php';

// Limpiar buffer previo
ob_clean();

header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

try {
    requireAuth();

    $currentUser = getCurrentUser();
    if (!$currentUser || empty($currentUser['id_empresa'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Sesión inválida o empresa no asociada'
        ]);
        exit;
    }

    if (!isset($conn)) {
        throw new Exception('Conexión no disponible');
    }

    $empresaId = (int) $currentUser['id_empresa'];

    $baseWhere = "
        FROM asistencia a
        INNER JOIN empleado e ON a.ID_EMPLEADO = e.ID_EMPLEADO
        INNER JOIN establecimiento est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
        INNER JOIN sede s ON est.ID_SEDE = s.ID_SEDE
        WHERE s.ID_EMPRESA = :empresa_id
          AND a.TIPO = 'ENTRADA'
          AND NOT EXISTS (
              SELECT 1 FROM asistencia a2
              WHERE a2.ID_EMPLEADO = a.ID_EMPLEADO
                AND a2.TIPO = 'SALIDA'
                AND CONCAT(a2.FECHA, ' ', a2.HORA) >= CONCAT(a.FECHA, ' ', a.HORA)
          )
    ";

    // Total de entradas abiertas
    $countSql = 'SELECT COUNT(*) AS total ' . $baseWhere;
    $countStmt = $conn->prepare($countSql);
    $countStmt->bindValue(':empresa_id', $empresaId, PDO::PARAM_INT);
    $countStmt->execute();
    $totalOpen = (int) $countStmt->fetchColumn();

    $entries = [];

    if ($totalOpen > 0) {
        $detailsSql = "
            SELECT
                a.ID_ASISTENCIA AS id_asistencia,
                a.ID_EMPLEADO AS id_empleado,
                CONCAT_WS(' ', e.NOMBRE, e.APELLIDO) AS nombre_completo,
                a.FECHA AS fecha,
                a.HORA AS hora,
                est.NOMBRE AS establecimiento,
                s.NOMBRE AS sede
            " . $baseWhere . "
            ORDER BY a.FECHA DESC, a.HORA DESC
            LIMIT 10
        ";

        $detailsStmt = $conn->prepare($detailsSql);
        $detailsStmt->bindValue(':empresa_id', $empresaId, PDO::PARAM_INT);
        $detailsStmt->execute();

        while ($row = $detailsStmt->fetch(PDO::FETCH_ASSOC)) {
            $entries[] = [
                'id_asistencia' => isset($row['id_asistencia']) ? (int) $row['id_asistencia'] : null,
                'id_empleado' => isset($row['id_empleado']) ? (int) $row['id_empleado'] : null,
                'nombre_completo' => $row['nombre_completo'] ?? '',
                'fecha' => $row['fecha'] ?? null,
                'hora' => $row['hora'] ?? null,
                'establecimiento' => $row['establecimiento'] ?? null,
                'sede' => $row['sede'] ?? null,
                'fecha_hora' => ($row['fecha'] ?? '') && ($row['hora'] ?? '')
                    ? trim(($row['fecha'] ?? '') . ' ' . ($row['hora'] ?? ''))
                    : null,
            ];
        }
    }

    echo json_encode([
        'success' => true,
        'open_entries' => [
            'total' => $totalOpen,
            'entries' => $entries,
        ],
        'checked_at' => getBogotaDateTime()
    ], JSON_UNESCAPED_UNICODE);
    exit;
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error al consultar entradas abiertas: ' . $e->getMessage()
    ]);
    exit;
}
