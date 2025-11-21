-- ACTUALIZAR ESQUEMA TABLA ASISTENCIA PARA FK EMPLEADO_HORARIO_PERSONALIZADO
-- Ejecutar solo si la columna ID_EMPLEADO_HORARIO no existe o no tiene FK

-- 1. VERIFICAR ESTRUCTURA ACTUAL
SELECT 'Verificando estructura actual...' as status;

-- Verificar si existe la columna
SELECT 
    COLUMN_NAME,
    COLUMN_TYPE,
    IS_NULLABLE,
    COLUMN_KEY
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME = 'asistencia'
AND COLUMN_NAME = 'ID_EMPLEADO_HORARIO';

-- 2. AGREGAR COLUMNA SI NO EXISTE
-- Ejecutar solo si la query anterior no devuelve resultados
/*
ALTER TABLE asistencia 
ADD COLUMN ID_EMPLEADO_HORARIO INT NULL 
COMMENT 'FK a empleado_horario_personalizado'
AFTER ID_HORARIO;
*/

-- 3. CREAR ÍNDICE PARA LA NUEVA COLUMNA
-- Mejorar performance en JOINs
/*
CREATE INDEX idx_asistencia_empleado_horario 
ON asistencia(ID_EMPLEADO_HORARIO);
*/

-- 4. AGREGAR FK CONSTRAINT
-- Conectar con empleado_horario_personalizado
/*
ALTER TABLE asistencia 
ADD CONSTRAINT fk_asistencia_empleado_horario 
FOREIGN KEY (ID_EMPLEADO_HORARIO) 
REFERENCES empleado_horario_personalizado(ID_EMPLEADO_HORARIO) 
ON DELETE SET NULL 
ON UPDATE CASCADE;
*/

-- 5. VERIFICAR FK CREADO
SELECT 'Verificando FK constraint...' as status;

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
AND k.REFERENCED_TABLE_NAME = 'empleado_horario_personalizado';

-- 6. ÍNDICES ADICIONALES RECOMENDADOS
/*
-- Índice compuesto para búsquedas por empleado y fecha
CREATE INDEX idx_asistencia_empleado_fecha 
ON asistencia(ID_EMPLEADO, FECHA, TIPO);

-- Índice para búsquedas por fecha
CREATE INDEX idx_asistencia_fecha 
ON asistencia(FECHA);
*/

-- 7. ESTRUCTURA FINAL ESPERADA
SELECT 'Estructura final esperada:' as info;

DESCRIBE asistencia;