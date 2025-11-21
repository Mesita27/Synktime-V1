-- SCRIPT PARA MIGRAR FK DE ASISTENCIA: De horario legacy a empleado_horario_personalizado
-- ==================================================================================

-- PASO 1: VERIFICAR FK CONSTRAINTS ACTUALES
SELECT 'PASO 1: Verificando FK constraints actuales en asistencia' as status;

SELECT 
    CONSTRAINT_NAME,
    COLUMN_NAME,
    REFERENCED_TABLE_NAME,
    REFERENCED_COLUMN_NAME,
    DELETE_RULE,
    UPDATE_RULE
FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE k
INNER JOIN INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS r 
    ON k.CONSTRAINT_NAME = r.CONSTRAINT_NAME
WHERE k.TABLE_SCHEMA = DATABASE()
AND k.TABLE_NAME = 'asistencia'
AND k.REFERENCED_TABLE_NAME IS NOT NULL;

-- PASO 2: CREAR TABLA EMPLEADO_HORARIO_PERSONALIZADO SI NO EXISTE
SELECT 'PASO 2: Creando tabla empleado_horario_personalizado si no existe' as status;

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

-- PASO 3: AGREGAR COLUMNA ID_EMPLEADO_HORARIO A ASISTENCIA SI NO EXISTE
SELECT 'PASO 3: Agregando columna ID_EMPLEADO_HORARIO a asistencia' as status;

-- Verificar si la columna ya existe
SELECT 
    COLUMN_NAME,
    COLUMN_TYPE,
    IS_NULLABLE
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME = 'asistencia'
AND COLUMN_NAME = 'ID_EMPLEADO_HORARIO';

-- Agregar columna si no existe
SET @sql = CONCAT('ALTER TABLE asistencia ADD COLUMN IF NOT EXISTS ID_EMPLEADO_HORARIO INT NULL COMMENT "FK a empleado_horario_personalizado"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- PASO 4: ELIMINAR FK CONSTRAINT DE ID_HORARIO (SI EXISTE)
SELECT 'PASO 4: Eliminando FK constraint de ID_HORARIO hacia tabla horario' as status;

-- Buscar el nombre del constraint FK hacia tabla horario
SET @fk_name = (
    SELECT CONSTRAINT_NAME
    FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'asistencia'
    AND COLUMN_NAME = 'ID_HORARIO'
    AND REFERENCED_TABLE_NAME = 'horario'
    LIMIT 1
);

-- Eliminar FK si existe
SET @sql = IF(@fk_name IS NOT NULL, 
    CONCAT('ALTER TABLE asistencia DROP FOREIGN KEY ', @fk_name), 
    'SELECT "No FK constraint found for ID_HORARIO" as info'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- PASO 5: CREAR NUEVO FK CONSTRAINT HACIA EMPLEADO_HORARIO_PERSONALIZADO
SELECT 'PASO 5: Creando nuevo FK constraint hacia empleado_horario_personalizado' as status;

-- Verificar si el FK ya existe
SET @existing_fk = (
    SELECT CONSTRAINT_NAME
    FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'asistencia'
    AND COLUMN_NAME = 'ID_EMPLEADO_HORARIO'
    AND REFERENCED_TABLE_NAME = 'empleado_horario_personalizado'
    LIMIT 1
);

-- Crear FK solo si no existe
SET @sql = IF(@existing_fk IS NULL, 
    'ALTER TABLE asistencia ADD CONSTRAINT fk_asistencia_empleado_horario FOREIGN KEY (ID_EMPLEADO_HORARIO) REFERENCES empleado_horario_personalizado(ID_EMPLEADO_HORARIO) ON DELETE SET NULL ON UPDATE CASCADE',
    'SELECT "FK constraint ya existe" as info'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- PASO 6: CREAR ÍNDICES PARA OPTIMIZACIÓN
SELECT 'PASO 6: Creando índices para optimización' as status;

-- Índice para ID_EMPLEADO_HORARIO
CREATE INDEX IF NOT EXISTS idx_asistencia_empleado_horario ON asistencia(ID_EMPLEADO_HORARIO);

-- Índice compuesto para búsquedas frecuentes
CREATE INDEX IF NOT EXISTS idx_asistencia_empleado_fecha ON asistencia(ID_EMPLEADO, FECHA, TIPO);

-- PASO 7: VERIFICAR RESULTADO FINAL
SELECT 'PASO 7: Verificando configuración final' as status;

-- Estructura actualizada
DESCRIBE asistencia;

-- FK Constraints finales
SELECT 
    CONSTRAINT_NAME,
    COLUMN_NAME,
    REFERENCED_TABLE_NAME,
    REFERENCED_COLUMN_NAME,
    DELETE_RULE,
    UPDATE_RULE
FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE k
INNER JOIN INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS r 
    ON k.CONSTRAINT_NAME = r.CONSTRAINT_NAME
WHERE k.TABLE_SCHEMA = DATABASE()
AND k.TABLE_NAME = 'asistencia'
AND k.REFERENCED_TABLE_NAME IS NOT NULL;

-- PASO 8: NOTAS PARA ENDPOINTS
SELECT 'CONFIGURACIÓN COMPLETADA PARA ENDPOINTS' as resultado;
SELECT 'Ahora puedes usar:' as info;
SELECT '1. asistencia.ID_EMPLEADO_HORARIO apunta a empleado_horario_personalizado' as nota1;
SELECT '2. asistencia.ID_HORARIO sigue existiendo pero sin FK (legacy)' as nota2; 
SELECT '3. Nuevos registros deben usar ID_EMPLEADO_HORARIO' as nota3;
SELECT '4. Registros legacy mantienen ID_HORARIO hasta migración completa' as nota4;