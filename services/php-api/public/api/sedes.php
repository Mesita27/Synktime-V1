<?php
/**
 * API simple para obtener sedes
 */

require_once 'auth/session.php';
require_once 'config/database.php';

requireAuth();

header('Content-Type: application/json');

try {
    $establecimiento_id = $_GET['establecimiento_id'] ?? null;
    
    if ($establecimiento_id) {
        // Obtener sedes del establecimiento específico
        $stmt = $pdo->prepare("
            SELECT DISTINCT
                s.ID_SEDE as id,
                s.NOMBRE as nombre
            FROM sede s
            INNER JOIN establecimiento e ON s.ID_SEDE = e.ID_SEDE
            WHERE e.ID_ESTABLECIMIENTO = ? AND s.ESTADO = 'A'
            ORDER BY s.NOMBRE
        ");
        $stmt->execute([$establecimiento_id]);
    } else {
        // Obtener todas las sedes activas
        $stmt = $pdo->prepare("
            SELECT 
                ID_SEDE as id,
                NOMBRE as nombre
            FROM sede 
            WHERE ESTADO = 'A'
            ORDER BY NOMBRE
        ");
        $stmt->execute();
    }
    
    $sedes = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'sedes' => $sedes
    ]);
    
} catch (Exception $e) {
    error_log("Error en API sedes: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener sedes'
    ]);
}
?>