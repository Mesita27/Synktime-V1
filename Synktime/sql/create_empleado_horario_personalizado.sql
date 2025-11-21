-- Crear tabla para horarios personalizados por empleado
-- Esta tabla permite múltiples horarios por día por empleado

CREATE TABLE empleado_horario_personalizado (
    ID_EMPLEADO_HORARIO INT AUTO_INCREMENT PRIMARY KEY,
    ID_EMPLEADO INT NOT NULL,
    ID_DIA INT NOT NULL COMMENT '1=Lunes, 2=Martes, 3=Miércoles, 4=Jueves, 5=Viernes, 6=Sábado, 7=Domingo',
    HORA_ENTRADA TIME NOT NULL,
    HORA_SALIDA TIME NOT NULL,
    TOLERANCIA INT DEFAULT 15 COMMENT 'Tolerancia en minutos',
    NOMBRE_TURNO VARCHAR(50) DEFAULT 'Turno Principal' COMMENT 'Nombre descriptivo del turno',
    FECHA_DESDE DATE NOT NULL COMMENT 'Fecha de inicio de vigencia',
    FECHA_HASTA DATE NULL COMMENT 'Fecha de fin de vigencia (NULL = indefinido)',
    ACTIVO CHAR(1) DEFAULT 'S' COMMENT 'S=Activo, N=Inactivo',
    ORDEN_TURNO INT DEFAULT 1 COMMENT 'Orden cuando hay múltiples turnos en el mismo día',
    OBSERVACIONES TEXT NULL COMMENT 'Observaciones adicionales',
    CREATED_AT TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UPDATED_AT TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_emp_horario_empleado FOREIGN KEY (ID_EMPLEADO) REFERENCES empleado(ID_EMPLEADO) ON DELETE CASCADE,
    CONSTRAINT fk_emp_horario_dia FOREIGN KEY (ID_DIA) REFERENCES dia_semana(ID_DIA),
    CONSTRAINT chk_horario_activo CHECK (ACTIVO IN ('S', 'N')),
    CONSTRAINT chk_horario_tolerancia CHECK (TOLERANCIA >= 0 AND TOLERANCIA <= 120),
    CONSTRAINT chk_horario_orden CHECK (ORDEN_TURNO >= 1 AND ORDEN_TURNO <= 10),
    CONSTRAINT chk_fecha_vigencia CHECK (FECHA_HASTA IS NULL OR FECHA_HASTA >= FECHA_DESDE),
    INDEX idx_empleado_dia_activo (ID_EMPLEADO, ID_DIA, ACTIVO),
    INDEX idx_fechas_vigencia (FECHA_DESDE, FECHA_HASTA),
    INDEX idx_activo (ACTIVO),
    UNIQUE KEY uk_empleado_dia_turno_vigencia (ID_EMPLEADO, ID_DIA, ORDEN_TURNO, FECHA_DESDE)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Comentarios sobre la tabla
ALTER TABLE empleado_horario_personalizado COMMENT = 'Tabla para gestionar horarios personalizados por empleado, permitiendo múltiples turnos por día';

-- Insertar algunos datos de ejemplo (basados en empleados existentes)
INSERT INTO empleado_horario_personalizado 
(ID_EMPLEADO, ID_DIA, HORA_ENTRADA, HORA_SALIDA, TOLERANCIA, NOMBRE_TURNO, FECHA_DESDE, ORDEN_TURNO, OBSERVACIONES) 
VALUES 
-- Empleado 100 (Cristian Meza) - Horario de lunes a viernes
(100, 1, '08:00', '16:00', 15, 'Horario Regular', CURDATE(), 1, 'Horario estándar de oficina'),
(100, 2, '08:00', '16:00', 15, 'Horario Regular', CURDATE(), 1, 'Horario estándar de oficina'),
(100, 3, '08:00', '16:00', 15, 'Horario Regular', CURDATE(), 1, 'Horario estándar de oficina'),
(100, 4, '08:00', '16:00', 15, 'Horario Regular', CURDATE(), 1, 'Horario estándar de oficina'),
(100, 5, '08:00', '16:00', 15, 'Horario Regular', CURDATE(), 1, 'Horario estándar de oficina'),

-- Empleado 14 (Paula Castro) - Horario partido
(14, 1, '08:00', '12:00', 10, 'Turno Mañana', CURDATE(), 1, 'Primera parte del día'),
(14, 1, '14:00', '18:00', 10, 'Turno Tarde', CURDATE(), 2, 'Segunda parte del día'),
(14, 2, '08:00', '12:00', 10, 'Turno Mañana', CURDATE(), 1, 'Primera parte del día'),
(14, 2, '14:00', '18:00', 10, 'Turno Tarde', CURDATE(), 2, 'Segunda parte del día'),
(14, 3, '08:00', '12:00', 10, 'Turno Mañana', CURDATE(), 1, 'Primera parte del día'),
(14, 3, '14:00', '18:00', 10, 'Turno Tarde', CURDATE(), 2, 'Segunda parte del día'),

-- Empleado 15 (Andrés Díaz) - Horario flexible
(15, 1, '09:00', '17:00', 20, 'Horario Flexible', CURDATE(), 1, 'Horario con mayor tolerancia'),
(15, 2, '09:00', '17:00', 20, 'Horario Flexible', CURDATE(), 1, 'Horario con mayor tolerancia'),
(15, 3, '09:00', '17:00', 20, 'Horario Flexible', CURDATE(), 1, 'Horario con mayor tolerancia'),
(15, 4, '09:00', '17:00', 20, 'Horario Flexible', CURDATE(), 1, 'Horario con mayor tolerancia'),
(15, 5, '10:00', '16:00', 20, 'Viernes Reducido', CURDATE(), 1, 'Horario reducido los viernes');