<?php
/**
 * API simple para obtener establecimientos
 */

// require_once __DIR__ . '/../auth/session.php'; // Comentado para testing
require_once __DIR__ . '/../config/database.php';

// requireAuth(); // Comentado para testing

header('Content-Type: application/json');

header('Content-Type: application/json');

try {
    $stmt = $pdo->prepare("
        SELECT 
            ID_ESTABLECIMIENTO as id,
            NOMBRE as nombre,
            ID_SEDE as sede_id
        FROM establecimiento 
        WHERE ESTADO = 'A'
        ORDER BY NOMBRE
    ");
    
    $stmt->execute();
    $establecimientos = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'establecimientos' => $establecimientos
    ]);
    
} catch (Exception $e) {
    error_log("Error en API establecimientos: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener establecimientos'
    ]);
}
?>