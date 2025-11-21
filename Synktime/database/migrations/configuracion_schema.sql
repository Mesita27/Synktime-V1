-- Estructura de base de datos para nuevas funcionalidades de configuración
-- SynkTime - Módulo de Configuración

-- 1. Tabla para vacaciones de empleados
CREATE TABLE IF NOT EXISTS `empleado_vacaciones` (
    `ID_VACACION` INT(11) AUTO_INCREMENT PRIMARY KEY,
    `ID_EMPLEADO` INT(11) NOT NULL,
    `FECHA_INICIO` DATE NOT NULL,
    `FECHA_FIN` DATE NOT NULL,
    `MOTIVO` VARCHAR(255) DEFAULT NULL,
    `ESTADO` ENUM('PROGRAMADA', 'ACTIVA', 'FINALIZADA', 'CANCELADA') DEFAULT 'PROGRAMADA',
    `REACTIVACION_AUTOMATICA` CHAR(1) DEFAULT 'S',
    `OBSERVACIONES` TEXT,
    `CREATED_BY` INT(11),
    `CREATED_AT` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `UPDATED_AT` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_empleado` (`ID_EMPLEADO`),
    INDEX `idx_fechas` (`FECHA_INICIO`, `FECHA_FIN`),
    INDEX `idx_estado` (`ESTADO`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- 2. Tabla para plantillas de horarios
CREATE TABLE IF NOT EXISTS `plantillas_horarios` (
    `ID_PLANTILLA` INT(11) AUTO_INCREMENT PRIMARY KEY,
    `NOMBRE` VARCHAR(100) NOT NULL,
    `DESCRIPCION` TEXT,
    `ID_EMPRESA` INT(11) NOT NULL,
    `ACTIVA` CHAR(1) DEFAULT 'S',
    `CREATED_BY` INT(11),
    `CREATED_AT` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `UPDATED_AT` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_empresa` (`ID_EMPRESA`),
    INDEX `idx_activa` (`ACTIVA`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- 3. Tabla para detalles de plantillas de horarios (horarios por día)
CREATE TABLE IF NOT EXISTS `plantilla_horario_detalle` (
    `ID_DETALLE` INT(11) AUTO_INCREMENT PRIMARY KEY,
    `ID_PLANTILLA` INT(11) NOT NULL,
    `ID_DIA` INT(11) NOT NULL,
    `HORA_ENTRADA` TIME NOT NULL,
    `HORA_SALIDA` TIME NOT NULL,
    `TOLERANCIA` INT(11) DEFAULT 15,
    `NOMBRE_TURNO` VARCHAR(50) DEFAULT 'Turno Regular',
    `ACTIVO` CHAR(1) DEFAULT 'S',
    `ORDEN_TURNO` INT(11) DEFAULT 1,
    `OBSERVACIONES` TEXT,
    INDEX `idx_plantilla` (`ID_PLANTILLA`),
    INDEX `idx_dia` (`ID_DIA`),
    FOREIGN KEY (`ID_PLANTILLA`) REFERENCES `plantillas_horarios`(`ID_PLANTILLA`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- 4. Tabla para historial de cambios de contraseña
CREATE TABLE IF NOT EXISTS `password_history` (
    `ID_HISTORY` INT(11) AUTO_INCREMENT PRIMARY KEY,
    `ID_USUARIO` INT(11) NOT NULL,
    `PASSWORD_HASH` VARCHAR(255) NOT NULL,
    `CHANGED_AT` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `CHANGED_BY` INT(11),
    `IP_ADDRESS` VARCHAR(45),
    INDEX `idx_usuario` (`ID_USUARIO`),
    INDEX `idx_fecha` (`CHANGED_AT`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- 5. Modificar tabla empleado_horario_personalizado para vigencia individual
ALTER TABLE `empleado_horario_personalizado` 
ADD COLUMN IF NOT EXISTS `VIGENCIA_INDIVIDUAL` CHAR(1) DEFAULT 'N',
ADD COLUMN IF NOT EXISTS `FECHA_VIGENCIA_DESDE` DATE NULL,
ADD COLUMN IF NOT EXISTS `FECHA_VIGENCIA_HASTA` DATE NULL;

-- 6. Crear índices adicionales para optimización
CREATE INDEX IF NOT EXISTS `idx_empleado_estado` ON `empleado` (`ID_EMPLEADO`, `ESTADO`, `ACTIVO`);
CREATE INDEX IF NOT EXISTS `idx_empleado_vacaciones_estado` ON `empleado_vacaciones` (`ID_EMPLEADO`, `ESTADO`, `FECHA_INICIO`, `FECHA_FIN`);

-- 7. Crear vista para empleados con vacaciones activas
CREATE OR REPLACE VIEW `vw_empleados_en_vacaciones` AS
SELECT 
    e.ID_EMPLEADO,
    e.NOMBRE,
    e.APELLIDO,
    e.DNI,
    ev.ID_VACACION,
    ev.FECHA_INICIO,
    ev.FECHA_FIN,
    ev.MOTIVO,
    ev.OBSERVACIONES,
    DATEDIFF(ev.FECHA_FIN, CURDATE()) AS DIAS_RESTANTES
FROM empleado e
INNER JOIN empleado_vacaciones ev ON e.ID_EMPLEADO = ev.ID_EMPLEADO
WHERE ev.ESTADO = 'ACTIVA'
    AND CURDATE() BETWEEN ev.FECHA_INICIO AND ev.FECHA_FIN
    AND e.ACTIVO = 'S';

-- 8. Crear procedimiento para reactivar empleados automáticamente
DELIMITER //
CREATE OR REPLACE PROCEDURE `ReactivarEmpleadosVacaciones`()
BEGIN
    DECLARE done INT DEFAULT FALSE;
    DECLARE emp_id INT;
    DECLARE vacacion_id INT;
    
    -- Cursor para empleados que terminan vacaciones hoy
    DECLARE cur CURSOR FOR 
        SELECT ev.ID_EMPLEADO, ev.ID_VACACION
        FROM empleado_vacaciones ev
        WHERE ev.ESTADO = 'ACTIVA' 
        AND ev.FECHA_FIN = CURDATE()
        AND ev.REACTIVACION_AUTOMATICA = 'S';
    
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
    
    OPEN cur;
    
    read_loop: LOOP
        FETCH cur INTO emp_id, vacacion_id;
        IF done THEN
            LEAVE read_loop;
        END IF;
        
        -- Reactivar empleado
        UPDATE empleado 
        SET ACTIVO = 'S', ESTADO = 'A'
        WHERE ID_EMPLEADO = emp_id;
        
        -- Marcar vacación como finalizada
        UPDATE empleado_vacaciones 
        SET ESTADO = 'FINALIZADA'
        WHERE ID_VACACION = vacacion_id;
        
    END LOOP;
    
    CLOSE cur;
END //
DELIMITER ;

-- 9. Crear evento para ejecutar reactivación automática diariamente
-- Nota: Esto requiere que MySQL tenga event_scheduler = ON
CREATE EVENT IF NOT EXISTS `evt_reactivar_empleados`
ON SCHEDULE EVERY 1 DAY
STARTS (TIMESTAMP(CURRENT_DATE) + INTERVAL 1 DAY + INTERVAL 1 HOUR)
DO
    CALL ReactivarEmpleadosVacaciones();

-- 10. Insertar datos iniciales para días de la semana si no existen
INSERT IGNORE INTO `dia_semana` (`ID_DIA`, `NOMBRE`) VALUES
(1, 'Lunes'),
(2, 'Martes'),
(3, 'Miércoles'),
(4, 'Jueves'),
(5, 'Viernes'),
(6, 'Sábado'),
(7, 'Domingo');

-- Comentarios sobre la estructura:
-- 
-- empleado_vacaciones: Maneja períodos de vacaciones con estados y reactivación automática
-- plantillas_horarios: Plantillas base para configuración masiva
-- plantilla_horario_detalle: Horarios específicos por día para cada plantilla
-- password_history: Historial de cambios de contraseña para auditoría
-- 
-- La vigencia individual en empleado_horario_personalizado permite tener fechas
-- específicas para cada horario en lugar de una fecha global.
--
-- El procedimiento ReactivarEmpleadosVacaciones se ejecuta automáticamente cada día
-- para reactivar empleados que terminan vacaciones.