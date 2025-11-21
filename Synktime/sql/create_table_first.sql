-- PASO 1: CREAR TABLA EMPLEADO_HORARIO_PERSONALIZADO
-- Ejecutar antes de hacer cualquier migración

-- Verificar si la tabla ya existe
SELECT 
    'Verificando si tabla existe...' as status,
    COUNT(*) as tabla_existe
FROM INFORMATION_SCHEMA.TABLES
WHERE TABLE_SCHEMA = DATABASE()
AND TABLE_NAME = 'empleado_horario_personalizado';

-- Crear tabla empleado_horario_personalizado
CREATE TABLE IF NOT EXISTS empleado_horario_personalizado (
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

-- Verificar que se creó correctamente
SELECT 'Tabla creada exitosamente' as resultado;

DESCRIBE empleado_horario_personalizado;

-- Ahora verificar horarios personalizados (debería estar vacía inicialmente)
SELECT 
    COUNT(*) as total_horarios,
    COUNT(DISTINCT ID_EMPLEADO) as empleados_con_horarios
FROM empleado_horario_personalizado
WHERE ACTIVO = 'S';