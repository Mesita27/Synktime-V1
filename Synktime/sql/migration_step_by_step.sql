-- MIGRACIÓN PASO A PASO: Sistema Unificado de Horarios
-- Ejecutar en orden secuencial

-- =================================================================
-- PASO 1: ANÁLIZAR ESTADO ACTUAL
-- =================================================================

-- Verificar estructura actual de empleados y establecimientos
SELECT 'PASO 1: Análisis de empleados por establecimiento' as PASO;

SELECT 
    est.ID_ESTABLECIMIENTO,
    est.NOMBRE as establecimiento,
    COUNT(e.ID_EMPLEADO) as empleados_activos,
    COUNT(DISTINCT h.ID_HORARIO) as horarios_disponibles,
    GROUP_CONCAT(DISTINCT h.NOMBRE SEPARATOR ', ') as nombres_horarios
FROM establecimiento est
LEFT JOIN empleado e ON est.ID_ESTABLECIMIENTO = e.ID_ESTABLECIMIENTO AND e.ACTIVO = 'S'
LEFT JOIN horario h ON est.ID_ESTABLECIMIENTO = h.ID_ESTABLECIMIENTO
GROUP BY est.ID_ESTABLECIMIENTO, est.NOMBRE
ORDER BY empleados_activos DESC;

-- Verificar qué empleados ya tienen horarios personalizados
SELECT 
    'Empleados con horarios personalizados existentes' as info,
    COUNT(DISTINCT ID_EMPLEADO) as cantidad
FROM empleado_horario_personalizado
WHERE ACTIVO = 'S';

-- =================================================================
-- PASO 2: MIGRAR HORARIOS DE ESTABLECIMIENTO A PERSONALIZADOS
-- =================================================================

SELECT 'PASO 2: Migración de horarios legacy a personalizados' as PASO;

-- Crear horarios personalizados basados en horarios de establecimiento
INSERT INTO empleado_horario_personalizado (
    ID_EMPLEADO,
    ID_DIA,
    HORA_ENTRADA,
    HORA_SALIDA,
    TOLERANCIA,
    NOMBRE_TURNO,
    FECHA_DESDE,
    FECHA_HASTA,
    ACTIVO,
    ORDEN_TURNO,
    OBSERVACIONES
)
SELECT 
    e.ID_EMPLEADO,
    hd.ID_DIA,
    h.HORA_ENTRADA,
    h.HORA_SALIDA,
    COALESCE(h.TOLERANCIA, 15) as TOLERANCIA,
    CONCAT(h.NOMBRE, ' - ', est.NOMBRE) as NOMBRE_TURNO,
    CURDATE() as FECHA_DESDE,
    NULL as FECHA_HASTA,
    'S' as ACTIVO,
    1 as ORDEN_TURNO,
    CONCAT('Migrado desde horario ', h.NOMBRE, ' (ID:', h.ID_HORARIO, ') del establecimiento ', est.NOMBRE, ' el ', NOW()) as OBSERVACIONES
FROM empleado e
INNER JOIN establecimiento est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
INNER JOIN horario h ON est.ID_ESTABLECIMIENTO = h.ID_ESTABLECIMIENTO
INNER JOIN horario_dia hd ON h.ID_HORARIO = hd.ID_HORARIO
WHERE e.ACTIVO = 'S'
-- Solo tomar el primer horario por establecimiento para evitar duplicados
AND h.ID_HORARIO = (
    SELECT MIN(h2.ID_HORARIO) 
    FROM horario h2 
    WHERE h2.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
)
-- Evitar duplicados si ya existe un horario personalizado para este empleado y día
AND NOT EXISTS (
    SELECT 1 FROM empleado_horario_personalizado ehp
    WHERE ehp.ID_EMPLEADO = e.ID_EMPLEADO
    AND ehp.ID_DIA = hd.ID_DIA
    AND ehp.ACTIVO = 'S'
);

-- Verificar resultado de migración
SELECT 
    'Empleados con horarios personalizados después de migración' as info,
    COUNT(DISTINCT ID_EMPLEADO) as cantidad
FROM empleado_horario_personalizado
WHERE ACTIVO = 'S';

-- =================================================================
-- PASO 3: PREPARAR ESTRUCTURA ASISTENCIA
-- =================================================================

SELECT 'PASO 3: Preparando estructura tabla asistencia' as PASO;

-- Verificar si ya existe la columna ID_EMPLEADO_HORARIO
SELECT 
    COLUMN_NAME,
    COLUMN_TYPE,
    IS_NULLABLE,
    COLUMN_KEY
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME = 'asistencia'
AND COLUMN_NAME IN ('ID_HORARIO', 'ID_EMPLEADO_HORARIO');

-- Si no existe ID_EMPLEADO_HORARIO, agregarla
-- ALTER TABLE asistencia ADD COLUMN ID_EMPLEADO_HORARIO INT NULL;

-- =================================================================
-- PASO 4: MIGRAR REGISTROS DE ASISTENCIA
-- =================================================================

SELECT 'PASO 4: Migración de registros de asistencia' as PASO;

-- Verificar registros actuales
SELECT 
    'Registros de asistencia actuales' as info,
    COUNT(*) as total,
    COUNT(CASE WHEN ID_HORARIO IS NOT NULL THEN 1 END) as con_horario_legacy
FROM asistencia;

-- Poblar ID_EMPLEADO_HORARIO basado en empleado + fecha + día semana
UPDATE asistencia a
INNER JOIN empleado_horario_personalizado ehp ON (
    a.ID_EMPLEADO = ehp.ID_EMPLEADO
    AND DAYOFWEEK(a.FECHA) = CASE 
        WHEN ehp.ID_DIA = 1 THEN 2  -- Lunes
        WHEN ehp.ID_DIA = 2 THEN 3  -- Martes
        WHEN ehp.ID_DIA = 3 THEN 4  -- Miércoles
        WHEN ehp.ID_DIA = 4 THEN 5  -- Jueves
        WHEN ehp.ID_DIA = 5 THEN 6  -- Viernes
        WHEN ehp.ID_DIA = 6 THEN 7  -- Sábado
        WHEN ehp.ID_DIA = 7 THEN 1  -- Domingo
    END
    AND ehp.ACTIVO = 'S'
    AND ehp.FECHA_DESDE <= a.FECHA
    AND (ehp.FECHA_HASTA IS NULL OR ehp.FECHA_HASTA >= a.FECHA)
)
SET a.ID_EMPLEADO_HORARIO = ehp.ID_EMPLEADO_HORARIO
WHERE a.ID_EMPLEADO_HORARIO IS NULL;

-- Verificar resultado
SELECT 
    'Registros de asistencia después de migración' as info,
    COUNT(*) as total,
    COUNT(CASE WHEN ID_EMPLEADO_HORARIO IS NOT NULL THEN 1 END) as con_horario_personalizado,
    COUNT(CASE WHEN ID_HORARIO IS NOT NULL THEN 1 END) as con_horario_legacy
FROM asistencia;

-- =================================================================
-- PASO 5: VERIFICACIONES FINALES
-- =================================================================

SELECT 'PASO 5: Verificaciones finales' as PASO;

-- Empleados sin horarios personalizados
SELECT 
    e.ID_EMPLEADO,
    e.NOMBRE,
    e.APELLIDO,
    est.NOMBRE as establecimiento
FROM empleado e
INNER JOIN establecimiento est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
LEFT JOIN empleado_horario_personalizado ehp ON e.ID_EMPLEADO = ehp.ID_EMPLEADO AND ehp.ACTIVO = 'S'
WHERE e.ACTIVO = 'S'
AND ehp.ID_EMPLEADO IS NULL
ORDER BY est.NOMBRE, e.NOMBRE;

-- Registros de asistencia sin horario personalizado
SELECT 
    'Registros de asistencia sin horario personalizado' as problema,
    COUNT(*) as cantidad
FROM asistencia a
WHERE a.ID_EMPLEADO_HORARIO IS NULL;

SELECT 'MIGRACIÓN COMPLETADA' as resultado;