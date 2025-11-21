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
        echo json_encode(['success' => false, 'message' => 'Empresa no válida', 'establecimientos' => []]);
        exit;
    }
    
    // Verificar si se especifica una sede
    $sedeId = isset($_GET['sede_id']) && !empty($_GET['sede_id']) ? (int)$_GET['sede_id'] : null;
    
    if ($sedeId) {
        // Filtrar por sede específica
        $stmt = $conn->prepare("
            SELECT 
                e.ID_ESTABLECIMIENTO,
                e.NOMBRE,
                e.DIRECCION,
                e.ID_SEDE
            FROM establecimiento e
            JOIN sede s ON e.ID_SEDE = s.ID_SEDE
            WHERE e.ID_SEDE = ? 
            AND s.ID_EMPRESA = ?
            AND e.ESTADO = 'A'
            ORDER BY e.NOMBRE
        ");
        $stmt->execute([$sedeId, $empresaId]);
    } else {
        // Todos los establecimientos de la empresa
        $stmt = $conn->prepare("
            SELECT 
                e.ID_ESTABLECIMIENTO,
                e.NOMBRE,
                e.DIRECCION,
                e.ID_SEDE
            FROM establecimiento e
            JOIN sede s ON e.ID_SEDE = s.ID_SEDE
            WHERE s.ID_EMPRESA = ?
            AND e.ESTADO = 'A'
            ORDER BY e.NOMBRE
        ");
        $stmt->execute([$empresaId]);
    }
    
    $establecimientos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'establecimientos' => $establecimientos,
        'total' => count($establecimientos)
    ]);
    
} catch (Exception $e) {
    error_log("Error en get-establecimientos.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error al cargar establecimientos',
        'establecimientos' => []
    ]);
}
?>