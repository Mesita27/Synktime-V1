-- Migración para mejorar la tabla justificacion
-- Agregando campos necesarios para el sistema de justificaciones por falta

-- Verificar si las columnas ya existen antes de agregarlas
ALTER TABLE `justificacion` 
ADD COLUMN IF NOT EXISTS `ID_HORARIO_PERSONALIZADO` int(11) DEFAULT NULL COMMENT 'ID del horario personalizado que le tocaba ese día',
ADD COLUMN IF NOT EXISTS `TURNO_PROGRAMADO` varchar(50) DEFAULT NULL COMMENT 'Descripción del turno que tenía programado',
ADD COLUMN IF NOT EXISTS `HORA_ENTRADA_PROGRAMADA` time DEFAULT NULL COMMENT 'Hora de entrada programada',
ADD COLUMN IF NOT EXISTS `HORA_SALIDA_PROGRAMADA` time DEFAULT NULL COMMENT 'Hora de salida programada',
ADD COLUMN IF NOT EXISTS `OBSERVACION` text DEFAULT NULL COMMENT 'Observación detallada de la justificación',
ADD COLUMN IF NOT EXISTS `JUSTIFICADO_POR` int(11) DEFAULT NULL COMMENT 'ID del usuario que registró la justificación',
ADD COLUMN IF NOT EXISTS `FECHA_JUSTIFICACION` datetime DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha y hora cuando se registró la justificación',
ADD COLUMN IF NOT EXISTS `ESTADO` enum('PENDIENTE', 'APROBADO', 'RECHAZADO') DEFAULT 'APROBADO' COMMENT 'Estado de la justificación',
ADD COLUMN IF NOT EXISTS `HORAS_JUSTIFICADAS` decimal(4,2) DEFAULT 0.00 COMMENT 'Horas que se justifican (siempre 0 para faltas)',
ADD COLUMN IF NOT EXISTS `TIPO_JUSTIFICACION` enum('FALTA', 'TARDANZA', 'SALIDA_TEMPRANA') DEFAULT 'FALTA' COMMENT 'Tipo de justificación';

-- Modificar la columna MOTIVO para que sea más descriptiva
ALTER TABLE `justificacion` 
MODIFY COLUMN `MOTIVO` varchar(500) DEFAULT NULL COMMENT 'Motivo de la justificación';

-- Agregar índices para mejorar el rendimiento
ALTER TABLE `justificacion`
ADD INDEX IF NOT EXISTS `IDX_JUSTIFICACION_FECHA` (`FECHA`),
ADD INDEX IF NOT EXISTS `IDX_JUSTIFICACION_ESTADO` (`ESTADO`),
ADD INDEX IF NOT EXISTS `IDX_JUSTIFICACION_TIPO` (`TIPO_JUSTIFICACION`),
ADD INDEX IF NOT EXISTS `IDX_JUSTIFICACION_HORARIO` (`ID_HORARIO_PERSONALIZADO`),
ADD INDEX IF NOT EXISTS `IDX_JUSTIFICACION_USUARIO` (`JUSTIFICADO_POR`);

-- Agregar foreign key para el usuario que justifica
ALTER TABLE `justificacion`
ADD CONSTRAINT IF NOT EXISTS `FK_JUSTIFICACION_USUARIO` 
FOREIGN KEY (`JUSTIFICADO_POR`) REFERENCES `usuario` (`ID_USUARIO`) ON DELETE SET NULL;

-- Agregar foreign key para el horario personalizado
ALTER TABLE `justificacion`
ADD CONSTRAINT IF NOT EXISTS `FK_JUSTIFICACION_HORARIO` 
FOREIGN KEY (`ID_HORARIO_PERSONALIZADO`) REFERENCES `empleado_horario_personalizado` (`id`) ON DELETE SET NULL;