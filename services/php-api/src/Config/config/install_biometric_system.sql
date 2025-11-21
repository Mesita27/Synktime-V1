-- =====================================================
-- Script de Instalación del Sistema Biométrico SynkTime
-- =====================================================

-- Verificar si las tablas ya existen
SET @biometric_data_exists = (
    SELECT COUNT(*) 
    FROM information_schema.tables 
    WHERE table_schema = DATABASE() 
    AND table_name = 'biometric_data'
);

SET @biometric_logs_exists = (
    SELECT COUNT(*) 
    FROM information_schema.tables 
    WHERE table_schema = DATABASE() 
    AND table_name = 'biometric_logs'
);

-- Crear tabla biometric_data si no existe
SET @sql = IF(@biometric_data_exists = 0,
    'CREATE TABLE `biometric_data` (
        `ID` int(11) NOT NULL AUTO_INCREMENT,
        `ID_EMPLEADO` int(11) NOT NULL,
        `BIOMETRIC_TYPE` enum(\'fingerprint\',\'facial\') NOT NULL,
        `FINGER_TYPE` varchar(20) DEFAULT NULL COMMENT \'Tipo de dedo para huellas: thumb_right, index_right, etc.\',
        `BIOMETRIC_DATA` longtext DEFAULT NULL COMMENT \'Datos biométricos procesados en formato JSON\',
        `CREATED_AT` timestamp NOT NULL DEFAULT current_timestamp(),
        `UPDATED_AT` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
        `ACTIVO` tinyint(1) DEFAULT 1,
        PRIMARY KEY (`ID`),
        KEY `idx_empleado_type` (`ID_EMPLEADO`, `BIOMETRIC_TYPE`),
        KEY `idx_empleado_finger` (`ID_EMPLEADO`, `FINGER_TYPE`),
        KEY `idx_activo` (`ACTIVO`),
        FOREIGN KEY (`ID_EMPLEADO`) REFERENCES `empleado`(`ID_EMPLEADO`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT=\'Almacena datos biométricos enrollados de empleados\';',
    'SELECT "biometric_data table already exists" AS message;'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Crear tabla biometric_logs si no existe
SET @sql = IF(@biometric_logs_exists = 0,
    'CREATE TABLE `biometric_logs` (
        `ID` int(11) NOT NULL AUTO_INCREMENT,
        `ID_EMPLEADO` int(11) NOT NULL,
        `VERIFICATION_METHOD` enum(\'fingerprint\',\'facial\',\'traditional\') NOT NULL,
        `VERIFICATION_SUCCESS` tinyint(1) DEFAULT 0 COMMENT \'1 = exitoso, 0 = fallido\',
        `CONFIDENCE_SCORE` decimal(5,4) DEFAULT NULL COMMENT \'Score de confianza entre 0.0000 y 1.0000\',
        `API_SOURCE` varchar(50) DEFAULT NULL COMMENT \'Fuente de la verificación: internal_api, attendance_api, etc.\',
        `OPERATION_TYPE` enum(\'enrollment\',\'verification\') DEFAULT \'verification\',
        `FECHA` date DEFAULT NULL,
        `HORA` time DEFAULT NULL,
        `CREATED_AT` timestamp NOT NULL DEFAULT current_timestamp(),
        PRIMARY KEY (`ID`),
        KEY `idx_empleado_method` (`ID_EMPLEADO`, `VERIFICATION_METHOD`),
        KEY `idx_fecha_hora` (`FECHA`, `HORA`),
        KEY `idx_success` (`VERIFICATION_SUCCESS`),
        KEY `idx_operation` (`OPERATION_TYPE`),
        FOREIGN KEY (`ID_EMPLEADO`) REFERENCES `empleado`(`ID_EMPLEADO`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT=\'Registra logs de operaciones biométricas\';',
    'SELECT "biometric_logs table already exists" AS message;'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Verificar si la columna VERIFICATION_METHOD existe en la tabla asistencia
SET @verification_column_exists = (
    SELECT COUNT(*) 
    FROM information_schema.columns 
    WHERE table_schema = DATABASE() 
    AND table_name = 'asistencia' 
    AND column_name = 'VERIFICATION_METHOD'
);

-- Añadir columna VERIFICATION_METHOD a la tabla asistencia si no existe
SET @sql = IF(@verification_column_exists = 0,
    'ALTER TABLE `asistencia` 
     ADD COLUMN `VERIFICATION_METHOD` enum(\'fingerprint\',\'facial\',\'traditional\') DEFAULT \'traditional\' 
     AFTER `ID_HORARIO`;',
    'SELECT "VERIFICATION_METHOD column already exists in asistencia table" AS message;'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Crear índices adicionales para optimización
CREATE INDEX IF NOT EXISTS `idx_asistencia_verification` ON `asistencia`(`VERIFICATION_METHOD`);
CREATE INDEX IF NOT EXISTS `idx_asistencia_empleado_fecha` ON `asistencia`(`ID_EMPLEADO`, `FECHA`);

-- Insertar datos de ejemplo para pruebas (opcional)
-- Descomentar las siguientes líneas si desea datos de prueba

/*
-- Insertar datos biométricos de ejemplo para empleado ID 2
INSERT IGNORE INTO `biometric_data` 
(`ID_EMPLEADO`, `BIOMETRIC_TYPE`, `FINGER_TYPE`, `BIOMETRIC_DATA`, `ACTIVO`) 
VALUES 
(2, 'fingerprint', 'index_right', '{"minutiae":[{"x":45.2,"y":67.8,"angle":120,"type":"ending"},{"x":78.1,"y":23.4,"angle":45,"type":"bifurcation"}],"quality":0.87}', 1),
(2, 'facial', NULL, '{"features":[0.1234,-0.5678,0.9012,-0.3456],"quality":0.92}', 1);

-- Insertar logs de ejemplo
INSERT IGNORE INTO `biometric_logs` 
(`ID_EMPLEADO`, `VERIFICATION_METHOD`, `VERIFICATION_SUCCESS`, `CONFIDENCE_SCORE`, `API_SOURCE`, `OPERATION_TYPE`, `FECHA`, `HORA`) 
VALUES 
(2, 'fingerprint', 1, 0.8750, 'internal_api', 'enrollment', CURDATE(), CURTIME()),
(2, 'facial', 1, 0.9200, 'internal_api', 'enrollment', CURDATE(), CURTIME());
*/

-- Crear vista para empleados con estado biométrico
CREATE OR REPLACE VIEW `vw_empleados_biometric_status` AS
SELECT 
    e.ID_EMPLEADO,
    e.NOMBRE,
    e.APELLIDO,
    e.DNI,
    -- Conteo de huellas registradas
    COUNT(CASE WHEN bd.BIOMETRIC_TYPE = 'fingerprint' AND bd.ACTIVO = 1 THEN 1 END) as HUELLAS_REGISTRADAS,
    -- Conteo de datos faciales registrados
    COUNT(CASE WHEN bd.BIOMETRIC_TYPE = 'facial' AND bd.ACTIVO = 1 THEN 1 END) as FACIAL_REGISTRADO,
    -- Estado general de enrolamiento
    CASE 
        WHEN COUNT(CASE WHEN bd.BIOMETRIC_TYPE = 'fingerprint' AND bd.ACTIVO = 1 THEN 1 END) > 0 
         AND COUNT(CASE WHEN bd.BIOMETRIC_TYPE = 'facial' AND bd.ACTIVO = 1 THEN 1 END) > 0 
        THEN 'COMPLETO'
        WHEN COUNT(CASE WHEN bd.BIOMETRIC_TYPE = 'fingerprint' AND bd.ACTIVO = 1 THEN 1 END) > 0 
         OR COUNT(CASE WHEN bd.BIOMETRIC_TYPE = 'facial' AND bd.ACTIVO = 1 THEN 1 END) > 0 
        THEN 'PARCIAL'
        ELSE 'PENDIENTE'
    END as ESTADO_ENROLAMIENTO,
    -- Última fecha de enrolamiento
    MAX(bd.CREATED_AT) as ULTIMO_ENROLAMIENTO
FROM empleado e
LEFT JOIN biometric_data bd ON e.ID_EMPLEADO = bd.ID_EMPLEADO
WHERE e.ESTADO = 'A' AND e.ACTIVO = 'S'
GROUP BY e.ID_EMPLEADO, e.NOMBRE, e.APELLIDO, e.DNI;

-- Crear vista para estadísticas de uso biométrico
CREATE OR REPLACE VIEW `vw_biometric_usage_stats` AS
SELECT 
    bl.VERIFICATION_METHOD,
    bl.OPERATION_TYPE,
    DATE(bl.FECHA) as FECHA,
    COUNT(*) as TOTAL_INTENTOS,
    SUM(bl.VERIFICATION_SUCCESS) as INTENTOS_EXITOSOS,
    ROUND(AVG(bl.CONFIDENCE_SCORE), 4) as CONFIDENCE_PROMEDIO,
    ROUND((SUM(bl.VERIFICATION_SUCCESS) * 100.0 / COUNT(*)), 2) as TASA_EXITO
FROM biometric_logs bl
WHERE bl.FECHA >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
GROUP BY bl.VERIFICATION_METHOD, bl.OPERATION_TYPE, DATE(bl.FECHA)
ORDER BY FECHA DESC, bl.VERIFICATION_METHOD;

-- Crear procedimiento almacenado para limpiar logs antiguos
DELIMITER //

CREATE PROCEDURE IF NOT EXISTS `sp_cleanup_biometric_logs`(IN days_to_keep INT)
BEGIN
    DECLARE rows_deleted INT DEFAULT 0;
    
    -- Eliminar logs más antiguos que el número de días especificado
    DELETE FROM biometric_logs 
    WHERE CREATED_AT < DATE_SUB(NOW(), INTERVAL days_to_keep DAY);
    
    -- Obtener número de filas eliminadas
    SET rows_deleted = ROW_COUNT();
    
    -- Retornar resultado
    SELECT CONCAT('Se eliminaron ', rows_deleted, ' registros de logs biométricos') as resultado;
END //

DELIMITER ;

-- Crear evento para limpieza automática (ejecutar mensualmente)
-- Nota: Requiere que el scheduler de eventos esté habilitado
/*
CREATE EVENT IF NOT EXISTS `evt_cleanup_biometric_logs`
ON SCHEDULE EVERY 1 MONTH
STARTS CURRENT_TIMESTAMP
DO
    CALL sp_cleanup_biometric_logs(90);
*/

-- Insertar configuraciones iniciales en tabla de configuración (si existe)
-- Ajustar según la estructura de tu tabla de configuración
/*
INSERT IGNORE INTO configuracion (clave, valor, descripcion) VALUES
('biometric_fingerprint_threshold', '0.75', 'Umbral mínimo de confianza para verificación de huellas'),
('biometric_facial_threshold', '0.70', 'Umbral mínimo de confianza para verificación facial'),
('biometric_max_attempts', '3', 'Número máximo de intentos de verificación por sesión'),
('biometric_log_retention_days', '90', 'Días de retención para logs biométricos');
*/

-- Mostrar resumen de instalación
SELECT 
    'Instalación del Sistema Biométrico Completada' as status,
    NOW() as timestamp,
    (SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'biometric_data') as biometric_data_created,
    (SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'biometric_logs') as biometric_logs_created,
    (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'asistencia' AND column_name = 'VERIFICATION_METHOD') as verification_column_added;

-- Verificar integridad de las tablas
SHOW TABLE STATUS LIKE 'biometric_%';

-- Mostrar estructura de las nuevas tablas
DESCRIBE biometric_data;
DESCRIBE biometric_logs;

-- Instrucciones finales
SELECT 
'INSTALACIÓN COMPLETADA' as '=== SISTEMA BIOMÉTRICO ===',
'1. Verifique que las tablas se crearon correctamente' as instruccion_1,
'2. Configure permisos de directorio uploads/' as instruccion_2,  
'3. Acceda a biometric-enrollment.php para comenzar enrolamiento' as instruccion_3,
'4. Consulte BIOMETRIC_SYSTEM_DOCS.md para documentación completa' as instruccion_4;
