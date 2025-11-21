<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../auth/session.php';

// Verificar autenticación
requireAuth();

try {
    $currentUser = getCurrentUser();
    $empresaId = $currentUser['id_empresa'] ?? null;

    if (!$empresaId) {
        echo json_encode(['success' => false, 'message' => 'Empresa no válida', 'data' => [], 'sedes' => []]);
        exit;
    }

    $stmt = $conn->prepare("SELECT ID_SEDE, NOMBRE FROM sede WHERE ID_EMPRESA = ? AND ESTADO = 'A' ORDER BY NOMBRE");
    $stmt->execute([$empresaId]);
    $sedes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'data' => $sedes, 'sedes' => $sedes]);

} catch (Exception $e) {
    error_log("Error en get-sedes.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error al cargar sedes', 'data' => [], 'sedes' => []]);
}
?>