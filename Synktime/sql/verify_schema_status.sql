-- VERIFICACIÓN DE ESQUEMA DESPUÉS DE MIGRACIÓN
-- Revisar estructura y FK constraints actuales

-- 1. VERIFICAR ESTRUCTURA DE TABLA ASISTENCIA
SELECT 'PASO 1: Estructura tabla asistencia' as VERIFICACION;

DESCRIBE asistencia;

-- 2. VERIFICAR FK CONSTRAINTS EXISTENTES
SELECT 'PASO 2: FK Constraints en asistencia' as VERIFICACION;

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

-- 3. VERIFICAR ÍNDICES EN ASISTENCIA
SELECT 'PASO 3: Índices en tabla asistencia' as VERIFICACION;

SHOW INDEX FROM asistencia;

-- 4. VERIFICAR DATOS EN EMPLEADO_HORARIO_PERSONALIZADO
SELECT 'PASO 4: Horarios personalizados disponibles' as VERIFICACION;

SELECT 
    COUNT(*) as total_horarios_personalizados,
    COUNT(DISTINCT ID_EMPLEADO) as empleados_con_horarios,
    MIN(FECHA_DESDE) as fecha_desde_mas_antigua,
    MAX(FECHA_HASTA) as fecha_hasta_mas_reciente
FROM empleado_horario_personalizado
WHERE ACTIVO = 'S';

-- 5. MOSTRAR ALGUNOS HORARIOS PERSONALIZADOS DE EJEMPLO
SELECT 'PASO 5: Ejemplos de horarios personalizados' as VERIFICACION;

SELECT 
    ehp.ID_EMPLEADO_HORARIO,
    e.NOMBRE,
    e.APELLIDO,
    ds.NOMBRE as dia,
    ehp.HORA_ENTRADA,
    ehp.HORA_SALIDA,
    ehp.NOMBRE_TURNO,
    ehp.FECHA_DESDE,
    ehp.FECHA_HASTA
FROM empleado_horario_personalizado ehp
INNER JOIN empleado e ON ehp.ID_EMPLEADO = e.ID_EMPLEADO
INNER JOIN dia_semana ds ON ehp.ID_DIA = ds.ID_DIA
WHERE ehp.ACTIVO = 'S'
ORDER BY e.NOMBRE, ehp.ID_DIA, ehp.ORDEN_TURNO
LIMIT 20;

-- 6. VERIFICAR ESTADO ACTUAL ASISTENCIA VS HORARIOS PERSONALIZADOS
SELECT 'PASO 6: Estado asistencia con horarios personalizados' as VERIFICACION;

SELECT 
    'Registros en asistencia' as info,
    COUNT(*) as total,
    COUNT(CASE WHEN ID_HORARIO IS NOT NULL THEN 1 END) as con_id_horario_legacy,
    COUNT(CASE WHEN ID_EMPLEADO_HORARIO IS NOT NULL THEN 1 END) as con_id_empleado_horario
FROM asistencia;

-- 7. VERIFICAR SI NECESITAMOS CREAR/ACTUALIZAR FK
SELECT 'PASO 7: Verificación FK necesaria' as VERIFICACION;

-- Verificar si existe la columna ID_EMPLEADO_HORARIO
SELECT 
    COLUMN_NAME,
    COLUMN_TYPE,
    IS_NULLABLE,
    COLUMN_KEY,
    COLUMN_DEFAULT
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME = 'asistencia'
AND COLUMN_NAME IN ('ID_HORARIO', 'ID_EMPLEADO_HORARIO');

-- 8. PLAN PARA ENDPOINTS
SELECT 'PASO 8: Cómo funcionarían los endpoints' as PLAN;

SELECT 'Para registrar asistencia, el endpoint necesita:' as info;
SELECT '1. Buscar empleado por ID' as paso;
SELECT '2. Buscar horario personalizado vigente para empleado + día de semana' as paso;
SELECT '3. Usar ehp.ID_EMPLEADO_HORARIO para el campo asistencia.ID_EMPLEADO_HORARIO' as paso;
SELECT '4. Validar horarios de entrada/salida contra ehp.HORA_ENTRADA/HORA_SALIDA' as paso;

-- Query ejemplo para encontrar horario de un empleado en una fecha específica
SELECT 'Query ejemplo para endpoint:' as ejemplo;

SELECT 
    e.ID_EMPLEADO,
    e.NOMBRE,
    ehp.ID_EMPLEADO_HORARIO,
    ehp.HORA_ENTRADA,
    ehp.HORA_SALIDA,
    ehp.TOLERANCIA,
    ehp.NOMBRE_TURNO
FROM empleado e
INNER JOIN empleado_horario_personalizado ehp ON e.ID_EMPLEADO = ehp.ID_EMPLEADO
WHERE e.ID_EMPLEADO = 100  -- Ejemplo: empleado 100
AND ehp.ID_DIA = DAYOFWEEK('2025-09-16') - 1  -- Lunes = 1
AND ehp.ACTIVO = 'S'
AND ehp.FECHA_DESDE <= '2025-09-16'
AND (ehp.FECHA_HASTA IS NULL OR ehp.FECHA_HASTA >= '2025-09-16')
ORDER BY ehp.ORDEN_TURNO
LIMIT 1;