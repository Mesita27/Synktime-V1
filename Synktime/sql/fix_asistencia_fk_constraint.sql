-- SOLUCIÓN SIMPLIFICADA: Modificar tabla asistencia para usar SOLO empleado_horario_personalizado
-- Eliminar complejidad de dual sistema

-- 1. Verificar constraints existentes
SELECT 
    CONSTRAINT_NAME,
    COLUMN_NAME,
    REFERENCED_TABLE_NAME,
    REFERENCED_COLUMN_NAME
FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = DATABASE()
AND TABLE_NAME = 'asistencia'
AND REFERENCED_TABLE_NAME IS NOT NULL;

-- 2. Eliminar constraint FK existente si existe (verificar nombre primero)
-- ALTER TABLE asistencia DROP FOREIGN KEY constraint_name_aqui;

-- 3. Agregar columna ID_EMPLEADO_HORARIO si no existe
ALTER TABLE asistencia 
ADD COLUMN IF NOT EXISTS ID_EMPLEADO_HORARIO INT NULL 
COMMENT 'FK a empleado_horario_personalizado';

-- 4. Crear índice para la nueva columna
CREATE INDEX IF NOT EXISTS idx_asistencia_empleado_horario 
ON asistencia(ID_EMPLEADO_HORARIO);

-- 5. Crear FK que apunte a empleado_horario_personalizado
ALTER TABLE asistencia 
ADD CONSTRAINT fk_asistencia_empleado_horario 
FOREIGN KEY (ID_EMPLEADO_HORARIO) 
REFERENCES empleado_horario_personalizado(ID_EMPLEADO_HORARIO) 
ON DELETE SET NULL 
ON UPDATE CASCADE;

-- 6. Agregar índices adicionales para optimización
CREATE INDEX IF NOT EXISTS idx_asistencia_empleado_fecha 
ON asistencia(ID_EMPLEADO, FECHA, TIPO);

-- 7. Verificar estructura final
DESCRIBE asistencia;