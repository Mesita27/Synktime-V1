-- SOLUCIÓN PARA PROBLEMA DE PERMISOS EN INFORMATION_SCHEMA
-- Ejecutar como administrador o root

-- ============================================================================
-- OPCIÓN 1: Verificar permisos actuales del usuario
-- ============================================================================
SHOW GRANTS FOR CURRENT_USER();

-- ============================================================================
-- OPCIÓN 2: Conceder permisos necesarios (ejecutar como root con privilegios)
-- ============================================================================
-- GRANT SELECT ON information_schema.* TO 'root'@'localhost';
-- FLUSH PRIVILEGES;

-- ============================================================================
-- OPCIÓN 3: Usar comandos alternativos sin INFORMATION_SCHEMA
-- ============================================================================

-- En lugar de consultar INFORMATION_SCHEMA, usar:

-- Para ver estructura de tabla:
DESCRIBE tabla_name;
SHOW CREATE TABLE tabla_name;

-- Para ver índices:
SHOW INDEX FROM tabla_name;

-- Para ver tablas:
SHOW TABLES;

-- Para ver bases de datos:
SHOW DATABASES;

-- ============================================================================
-- OPCIÓN 4: Crear usuario alternativo con permisos completos
-- ============================================================================
-- CREATE USER 'synktime_admin'@'localhost' IDENTIFIED BY 'password_seguro';
-- GRANT ALL PRIVILEGES ON synktime.* TO 'synktime_admin'@'localhost';
-- GRANT SELECT ON information_schema.* TO 'synktime_admin'@'localhost';
-- FLUSH PRIVILEGES;

-- ============================================================================
-- VERIFICACIÓN DE CONEXIÓN MYSQL
-- ============================================================================
SELECT 
    USER() as usuario_actual,
    DATABASE() as base_datos_actual,
    VERSION() as version_mysql;