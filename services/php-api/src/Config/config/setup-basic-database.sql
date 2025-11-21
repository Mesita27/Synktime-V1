-- SCRIPT DE INICIALIZACIÓN BÁSICA PARA SYNKTIME BIOMÉTRICO
-- Este script crea la estructura mínima necesaria para el funcionamiento

-- Crear base de datos si no existe
CREATE DATABASE IF NOT EXISTS `synktime` CHARACTER SET utf8 COLLATE utf8_general_ci;
USE `synktime`;

-- Tabla principal de empleados (estructura básica)
CREATE TABLE IF NOT EXISTS `empleado` (
  `ID_EMPLEADO` int(11) NOT NULL AUTO_INCREMENT,
  `CODIGO` varchar(20) DEFAULT NULL,
  `NOMBRES` varchar(100) NOT NULL,
  `APELLIDOS` varchar(100) NOT NULL,
  `EMAIL` varchar(100) DEFAULT NULL,
  `TELEFONO` varchar(20) DEFAULT NULL,
  `ACTIVO` char(1) DEFAULT 'Y',
  `FECHA_CREACION` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `FECHA_MODIFICACION` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`ID_EMPLEADO`),
  UNIQUE KEY `CODIGO_UNIQUE` (`CODIGO`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Tabla de datos biométricos
CREATE TABLE IF NOT EXISTS `biometric_data` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `ID_EMPLEADO` int(11) NOT NULL,
  `BIOMETRIC_TYPE` enum('facial','fingerprint') NOT NULL,
  `FINGER_TYPE` varchar(20) DEFAULT NULL COMMENT 'thumb_right, index_right, etc.',
  `BIOMETRIC_DATA` longtext NOT NULL COMMENT 'Datos codificados del biométrico',
  `QUALITY_SCORE` decimal(5,2) DEFAULT NULL COMMENT 'Puntuación de calidad 0-100',
  `ACTIVO` tinyint(1) DEFAULT '1',
  `CREATED_AT` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `UPDATED_AT` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`ID`),
  KEY `FK_biometric_empleado` (`ID_EMPLEADO`),
  KEY `IDX_biometric_type` (`BIOMETRIC_TYPE`),
  KEY `IDX_active` (`ACTIVO`),
  CONSTRAINT `FK_biometric_empleado` FOREIGN KEY (`ID_EMPLEADO`) REFERENCES `empleado` (`ID_EMPLEADO`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Tabla de logs biométricos
CREATE TABLE IF NOT EXISTS `biometric_logs` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `ID_EMPLEADO` int(11) NOT NULL,
  `ACTION` enum('enroll','verify','update','delete') NOT NULL,
  `BIOMETRIC_TYPE` enum('facial','fingerprint') NOT NULL,
  `SUCCESS` tinyint(1) NOT NULL,
  `ERROR_MESSAGE` text,
  `IP_ADDRESS` varchar(45) DEFAULT NULL,
  `USER_AGENT` text,
  `CREATED_AT` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`ID`),
  KEY `FK_logs_empleado` (`ID_EMPLEADO`),
  KEY `IDX_action` (`ACTION`),
  KEY `IDX_created` (`CREATED_AT`),
  CONSTRAINT `FK_logs_empleado` FOREIGN KEY (`ID_EMPLEADO`) REFERENCES `empleado` (`ID_EMPLEADO`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Insertar empleados de prueba si no existen
INSERT IGNORE INTO `empleado` (`ID_EMPLEADO`, `CODIGO`, `NOMBRES`, `APELLIDOS`, `EMAIL`, `ACTIVO`) VALUES
(1, 'EMP0001', 'Juan Carlos', 'Pérez López', 'juan.perez@synktime.com', 'Y'),
(2, 'EMP0002', 'María Elena', 'García Martín', 'maria.garcia@synktime.com', 'Y'),
(3, 'EMP0003', 'Pedro Antonio', 'Rodríguez Silva', 'pedro.rodriguez@synktime.com', 'Y'),
(4, 'EMP0004', 'Ana Sofía', 'López Herrera', 'ana.lopez@synktime.com', 'Y'),
(5, 'EMP0005', 'Carlos Eduardo', 'Martínez Ruiz', 'carlos.martinez@synktime.com', 'Y');

-- Configuración de timezone
SET time_zone = '+00:00';

-- Crear índices adicionales para optimización
CREATE INDEX IF NOT EXISTS `IDX_empleado_activo` ON `empleado` (`ACTIVO`);
CREATE INDEX IF NOT EXISTS `IDX_empleado_nombres` ON `empleado` (`NOMBRES`, `APELLIDOS`);

-- Vista para datos biométricos con información de empleado
CREATE OR REPLACE VIEW `v_biometric_data` AS
SELECT 
    bd.ID,
    bd.ID_EMPLEADO,
    e.CODIGO,
    e.NOMBRES,
    e.APELLIDOS,
    e.EMAIL,
    bd.BIOMETRIC_TYPE,
    bd.FINGER_TYPE,
    bd.QUALITY_SCORE,
    bd.ACTIVO as BIOMETRIC_ACTIVO,
    bd.CREATED_AT,
    bd.UPDATED_AT
FROM `biometric_data` bd
INNER JOIN `empleado` e ON bd.ID_EMPLEADO = e.ID_EMPLEADO
WHERE e.ACTIVO = 'Y';

-- Procedimiento para limpiar logs antiguos (opcional)
DELIMITER //
CREATE PROCEDURE IF NOT EXISTS `CleanOldBiometricLogs`()
BEGIN
    DELETE FROM `biometric_logs` 
    WHERE CREATED_AT < DATE_SUB(NOW(), INTERVAL 90 DAY);
END //
DELIMITER ;

-- Verificar estructura
SELECT 'Estructura de base de datos creada exitosamente' as status;

-- Mostrar resumen de tablas
SELECT 
    TABLE_NAME as 'Tabla',
    TABLE_ROWS as 'Registros',
    CREATE_TIME as 'Creada'
FROM INFORMATION_SCHEMA.TABLES 
WHERE TABLE_SCHEMA = 'synktime' 
AND TABLE_NAME IN ('empleado', 'biometric_data', 'biometric_logs')
ORDER BY TABLE_NAME;
