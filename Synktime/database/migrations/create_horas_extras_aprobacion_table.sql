-- Migration: Create overtime approval table
-- Date: 2025-01-25
-- Description: Creates the horas_extras_aprobacion table to manage overtime approval workflow

CREATE TABLE IF NOT EXISTS `horas_extras_aprobacion` (
  `ID_HORAS_EXTRAS` int(11) NOT NULL AUTO_INCREMENT,
  `ID_EMPLEADO` int(11) NOT NULL,
  `ID_EMPLEADO_HORARIO` int(11) DEFAULT NULL,
  `FECHA` date NOT NULL,
  `HORA_INICIO` time NOT NULL,
  `HORA_FIN` time NOT NULL,
  `HORAS_EXTRAS` decimal(5,2) NOT NULL,
  `TIPO_EXTRA` enum('antes','despues') NOT NULL COMMENT 'antes=before schedule, despues=after schedule',
  `TIPO_HORARIO` enum('diurna','nocturna','diurna_dominical','nocturna_dominical') NOT NULL,
  `ESTADO_APROBACION` enum('pendiente','aprobada','rechazada') NOT NULL DEFAULT 'pendiente',
  `ID_USUARIO_APROBACION` int(11) DEFAULT NULL,
  `FECHA_APROBACION` datetime DEFAULT NULL,
  `OBSERVACIONES` text,
  `CREATED_AT` timestamp NOT NULL DEFAULT current_timestamp(),
  `UPDATED_AT` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`ID_HORAS_EXTRAS`),
  KEY `IDX_EMPLEADO_FECHA` (`ID_EMPLEADO`, `FECHA`),
  KEY `IDX_ESTADO` (`ESTADO_APROBACION`),
  KEY `IDX_USUARIO_APROBACION` (`ID_USUARIO_APROBACION`),
  KEY `FK_EMPLEADO` (`ID_EMPLEADO`),
  KEY `FK_EMPLEADO_HORARIO` (`ID_EMPLEADO_HORARIO`),
  KEY `FK_USUARIO_APROBACION` (`ID_USUARIO_APROBACION`),
  CONSTRAINT `FK_HORAS_EXTRAS_EMPLEADO` FOREIGN KEY (`ID_EMPLEADO`) REFERENCES `empleado` (`ID_EMPLEADO`) ON DELETE CASCADE,
  CONSTRAINT `FK_HORAS_EXTRAS_EMPLEADO_HORARIO` FOREIGN KEY (`ID_EMPLEADO_HORARIO`) REFERENCES `empleado_horario_personalizado` (`ID_EMPLEADO_HORARIO`) ON DELETE SET NULL,
  CONSTRAINT `FK_HORAS_EXTRAS_USUARIO` FOREIGN KEY (`ID_USUARIO_APROBACION`) REFERENCES `usuario` (`ID_USUARIO`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Tabla para gestionar la aprobación de horas extras trabajadas';

-- Insert sample data for testing (optional - remove in production)
-- INSERT INTO `horas_extras_aprobacion` (`ID_EMPLEADO`, `ID_EMPLEADO_HORARIO`, `FECHA`, `HORA_INICIO`, `HORA_FIN`, `HORAS_EXTRAS`, `TIPO_EXTRA`, `TIPO_HORARIO`, `ESTADO_APROBACION`) VALUES
-- (100, NULL, '2025-01-20', '16:00:00', '18:00:00', 2.00, 'despues', 'diurna', 'pendiente'),
-- (100, NULL, '2025-01-21', '07:00:00', '08:00:00', 1.00, 'antes', 'diurna', 'aprobada');