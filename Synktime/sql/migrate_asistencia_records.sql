-- MIGRAR REGISTROS DE ASISTENCIA EXISTENTES
-- Convertir asistencia.ID_HORARIO (que apunta a horario legacy) 
-- a asistencia.ID_EMPLEADO_HORARIO (que apuntará a empleado_horario_personalizado)

-- 1. VERIFICAR ESTADO ACTUAL
SELECT 'ESTADO ACTUAL ASISTENCIA' as estado;

SELECT 
    COUNT(*) as total_registros_asistencia,
    COUNT(CASE WHEN ID_HORARIO IS NOT NULL THEN 1 END) as con_horario_legacy_id,
    COUNT(CASE WHEN ID_HORARIO IS NULL THEN 1 END) as sin_horario_id
FROM asistencia;

-- Verificar qué horarios legacy están siendo usados
SELECT 
    h.ID_HORARIO,
    h.NOMBRE as horario_nombre,
    est.NOMBRE as establecimiento,
    COUNT(a.ID_ASISTENCIA) as registros_asistencia
FROM asistencia a
INNER JOIN horario h ON a.ID_HORARIO = h.ID_HORARIO
INNER JOIN establecimiento est ON h.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
GROUP BY h.ID_HORARIO, h.NOMBRE, est.NOMBRE
ORDER BY registros_asistencia DESC;

-- 2. MIGRAR REGISTROS DE ASISTENCIA CON ID_HORARIO
-- Actualizar estos registros para que usen empleado_horario_personalizado

-- Primero, crear un mapeo entre registros de asistencia y horarios personalizados
SELECT 
    a.ID_ASISTENCIA,
    a.ID_EMPLEADO,
    a.FECHA,
    a.ID_HORARIO as horario_legacy_id,
    ehp.ID_EMPLEADO_HORARIO as nuevo_horario_personalizado_id,
    ehp.NOMBRE_TURNO,
    DAYNAME(a.FECHA) as dia_semana
FROM asistencia a
INNER JOIN horario h ON a.ID_HORARIO = h.ID_HORARIO
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
WHERE a.ID_HORARIO IS NOT NULL
LIMIT 10;

-- 3. EJECUTAR MIGRACIÓN
-- IMPORTANTE: Este UPDATE cambia ID_HORARIO por ID_EMPLEADO_HORARIO
-- Solo ejecutar después de confirmar el FK constraint está actualizado

/*
UPDATE asistencia a
INNER JOIN horario h ON a.ID_HORARIO = h.ID_HORARIO
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
WHERE a.ID_HORARIO IS NOT NULL;
*/

-- 4. VERIFICAR MIGRACIÓN (ejecutar después del UPDATE)
/*
SELECT 'DESPUÉS DE MIGRACIÓN ASISTENCIA' as estado;

SELECT 
    COUNT(*) as total_registros,
    COUNT(CASE WHEN ID_EMPLEADO_HORARIO IS NOT NULL THEN 1 END) as registros_migrados,
    COUNT(CASE WHEN ID_EMPLEADO_HORARIO IS NULL THEN 1 END) as registros_sin_horario
FROM asistencia;

-- 5. MOSTRAR REGISTROS NO MIGRADOS (para revisar)
SELECT 
    a.ID_ASISTENCIA,
    a.ID_EMPLEADO,
    e.NOMBRE,
    e.APELLIDO,
    a.FECHA,
    a.TIPO,
    DAYNAME(a.FECHA) as dia_semana,
    'Sin horario personalizado para este día' as razon
FROM asistencia a
INNER JOIN empleado e ON a.ID_EMPLEADO = e.ID_EMPLEADO
WHERE a.ID_EMPLEADO_HORARIO IS NULL
AND a.ID_HORARIO IS NULL
LIMIT 10;

-- 6. ESTADÍSTICAS DE MIGRACIÓN POR EMPLEADO
SELECT 
    e.ID_EMPLEADO,
    e.NOMBRE,
    e.APELLIDO,
    COUNT(a.ID_ASISTENCIA) as total_asistencias,
    COUNT(CASE WHEN a.ID_EMPLEADO_HORARIO IS NOT NULL THEN 1 END) as asistencias_migradas
FROM empleado e
LEFT JOIN asistencia a ON e.ID_EMPLEADO = a.ID_EMPLEADO
WHERE e.ACTIVO = 'S'
GROUP BY e.ID_EMPLEADO, e.NOMBRE, e.APELLIDO
HAVING total_asistencias > 0
ORDER BY total_asistencias DESC;
*/