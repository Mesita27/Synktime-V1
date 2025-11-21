-- RESUMEN DE MIGRACIÓN: Sistema Unificado de Horarios
-- =====================================================

-- SITUACIÓN ACTUAL:
-- ✅ Tabla empleado: vinculada a establecimiento (NO tiene ID_HORARIO)
-- ✅ Tabla horario: vinculada a establecimiento (contiene horarios disponibles)
-- ✅ Tabla horario_dia: define qué días aplica cada horario
-- ✅ Tabla asistencia: tiene ID_HORARIO (apunta a tabla horario legacy)
-- ✅ Tabla empleado_horario_personalizado: ya existe, sistema moderno

-- OBJETIVO:
-- Migrar completamente al sistema empleado_horario_personalizado
-- Eliminar dependencia de tabla horario legacy en asistencia

-- =====================================================
-- ESTRATEGIA DE MIGRACIÓN
-- =====================================================

-- PASO 1: Análisis actual
SELECT 'ANÁLISIS SISTEMA ACTUAL' as fase;

-- Empleados por establecimiento
SELECT 
    est.NOMBRE as establecimiento,
    COUNT(e.ID_EMPLEADO) as empleados_activos
FROM establecimiento est
LEFT JOIN empleado e ON est.ID_ESTABLECIMIENTO = e.ID_ESTABLECIMIENTO AND e.ACTIVO = 'S'
GROUP BY est.ID_ESTABLECIMIENTO, est.NOMBRE
ORDER BY empleados_activos DESC;

-- Horarios legacy disponibles
SELECT 
    h.ID_HORARIO,
    h.NOMBRE,
    est.NOMBRE as establecimiento,
    h.HORA_ENTRADA,
    h.HORA_SALIDA,
    COUNT(hd.ID_DIA) as dias_aplicables
FROM horario h
INNER JOIN establecimiento est ON h.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
LEFT JOIN horario_dia hd ON h.ID_HORARIO = hd.ID_HORARIO
GROUP BY h.ID_HORARIO, h.NOMBRE, est.NOMBRE, h.HORA_ENTRADA, h.HORA_SALIDA
ORDER BY est.NOMBRE, h.NOMBRE;

-- Registros de asistencia con horarios legacy
SELECT 
    'Registros asistencia con horario legacy' as info,
    COUNT(*) as cantidad,
    COUNT(DISTINCT ID_EMPLEADO) as empleados_únicos,
    COUNT(DISTINCT ID_HORARIO) as horarios_legacy_usados
FROM asistencia 
WHERE ID_HORARIO IS NOT NULL;

-- =====================================================
-- PASO 2: Migración de horarios
-- =====================================================

SELECT 'MIGRACIÓN DE HORARIOS' as fase;

-- Para cada empleado activo, crear horarios personalizados basados en:
-- 1. Su establecimiento
-- 2. El primer horario disponible en ese establecimiento
-- 3. Los días que ese horario aplica

-- Query para ver qué se migrará:
SELECT 
    e.ID_EMPLEADO,
    e.NOMBRE,
    e.APELLIDO,
    est.NOMBRE as establecimiento,
    h.NOMBRE as horario_a_usar,
    h.HORA_ENTRADA,
    h.HORA_SALIDA,
    GROUP_CONCAT(ds.NOMBRE ORDER BY hd.ID_DIA) as dias_aplicables
FROM empleado e
INNER JOIN establecimiento est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
INNER JOIN horario h ON est.ID_ESTABLECIMIENTO = h.ID_ESTABLECIMIENTO
INNER JOIN horario_dia hd ON h.ID_HORARIO = hd.ID_HORARIO
INNER JOIN dia_semana ds ON hd.ID_DIA = ds.ID_DIA
WHERE e.ACTIVO = 'S'
-- Solo primer horario por establecimiento
AND h.ID_HORARIO = (
    SELECT MIN(h2.ID_HORARIO) 
    FROM horario h2 
    WHERE h2.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
)
-- Solo empleados sin horarios personalizados
AND NOT EXISTS (
    SELECT 1 FROM empleado_horario_personalizado ehp
    WHERE ehp.ID_EMPLEADO = e.ID_EMPLEADO AND ehp.ACTIVO = 'S'
)
GROUP BY e.ID_EMPLEADO, e.NOMBRE, e.APELLIDO, est.NOMBRE, h.NOMBRE, h.HORA_ENTRADA, h.HORA_SALIDA
ORDER BY est.NOMBRE, e.NOMBRE;

-- =====================================================
-- PASO 3: Migración de asistencia
-- =====================================================

SELECT 'MIGRACIÓN DE ASISTENCIA' as fase;

-- Verificar qué registros de asistencia se pueden migrar
SELECT 
    a.ID_ASISTENCIA,
    a.ID_EMPLEADO,
    e.NOMBRE,
    a.FECHA,
    a.TIPO,
    DAYNAME(a.FECHA) as dia_semana,
    a.ID_HORARIO as horario_legacy,
    hl.NOMBRE as nombre_horario_legacy,
    ehp.ID_EMPLEADO_HORARIO as horario_personalizado_destino,
    ehp.NOMBRE_TURNO
FROM asistencia a
INNER JOIN empleado e ON a.ID_EMPLEADO = e.ID_EMPLEADO
LEFT JOIN horario hl ON a.ID_HORARIO = hl.ID_HORARIO
LEFT JOIN empleado_horario_personalizado ehp ON (
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
ORDER BY a.FECHA DESC, a.ID_EMPLEADO
LIMIT 20;

-- =====================================================
-- ARCHIVOS DE MIGRACIÓN CREADOS
-- =====================================================

SELECT 'ARCHIVOS DE MIGRACIÓN DISPONIBLES' as info;
SELECT '1. sql/migration_step_by_step.sql - Migración completa paso a paso' as archivo;
SELECT '2. sql/analyze_migration_impact.sql - Análisis de impacto' as archivo;
SELECT '3. sql/migrate_legacy_to_personalized.sql - Migración de horarios' as archivo;
SELECT '4. sql/fix_asistencia_fk_constraint.sql - Actualizar FK constraints' as archivo;
SELECT '5. sql/migrate_asistencia_records.sql - Migrar registros asistencia' as archivo;
SELECT '6. sql/final_cleanup_validation.sql - Validación final' as archivo;

-- =====================================================
-- ORDEN RECOMENDADO DE EJECUCIÓN
-- =====================================================

SELECT 'ORDEN DE EJECUCIÓN RECOMENDADO' as proceso;
SELECT '1. Ejecutar analyze_migration_impact.sql' as paso;
SELECT '2. Ejecutar migrate_legacy_to_personalized.sql' as paso;
SELECT '3. Ejecutar fix_asistencia_fk_constraint.sql' as paso;
SELECT '4. Ejecutar migrate_asistencia_records.sql' as paso;
SELECT '5. Ejecutar final_cleanup_validation.sql' as paso;
SELECT 'ALTERNATIVA: Ejecutar migration_step_by_step.sql (todo junto)' as paso;