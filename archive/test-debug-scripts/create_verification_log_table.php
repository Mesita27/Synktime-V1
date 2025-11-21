<?php
/**
 * Script para crear la tabla de log de verificaciones biométricas
 * SNKTIME Biometric System
 */

// Incluir configuración de base de datos
require_once 'config/database.php';

try {
    // Crear tabla de log de verificaciones biométricas
    $sql = "
        CREATE TABLE IF NOT EXISTS biometric_verification_log (
            id INT AUTO_INCREMENT PRIMARY KEY,
            employee_id INT NOT NULL,
            verification_type VARCHAR(50) NOT NULL,
            success TINYINT(1) NOT NULL DEFAULT 0,
            confidence DECIMAL(3,2) DEFAULT NULL,
            attendance_id INT DEFAULT NULL,
            ip_address VARCHAR(45) DEFAULT NULL,
            user_agent TEXT DEFAULT NULL,
            additional_data JSON DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

            INDEX idx_employee_id (employee_id),
            INDEX idx_verification_type (verification_type),
            INDEX idx_success (success),
            INDEX idx_created_at (created_at),
            INDEX idx_attendance_id (attendance_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";

    $pdo->exec($sql);

    // Verificar si la tabla fue creada
    $stmt = $pdo->query("SHOW TABLES LIKE 'biometric_verification_log'");
    $tableExists = $stmt->fetch();

    if ($tableExists) {
        echo "✅ Tabla 'biometric_verification_log' creada exitosamente\n";

        // Verificar estructura de la tabla
        $stmt = $pdo->query("DESCRIBE biometric_verification_log");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo "📋 Estructura de la tabla:\n";
        foreach ($columns as $column) {
            echo "  - {$column['Field']}: {$column['Type']} {$column['Key']}\n";
        }

    } else {
        echo "❌ Error: No se pudo crear la tabla 'biometric_verification_log'\n";
    }

} catch (Exception $e) {
    echo "❌ Error al crear la tabla: " . $e->getMessage() . "\n";
    error_log('Error creando tabla biometric_verification_log: ' . $e->getMessage());
}
?>
