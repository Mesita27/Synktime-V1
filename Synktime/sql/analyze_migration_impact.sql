-- ANÁLISIS: Verificar qué módulos usan horarios legacy vs personalizados
-- para evaluar el impacto de migrar completamente a empleado_horario_personalizado

-- 1. Verificar registros actuales en asistencia
SELECT 
    'Registros en asistencia' as tabla,
    COUNT(*) as total_registros,
    COUNT(CASE WHEN ID_HORARIO IS NOT NULL THEN 1 END) as con_id_horario,
    COUNT(CASE WHEN ID_HORARIO IS NULL THEN 1 END) as sin_id_horario
FROM asistencia;

-- 2. Verificar horarios legacy en uso
SELECT 
    'Horarios legacy en uso' as info,
    COUNT(DISTINCT h.ID_HORARIO) as horarios_legacy_usados,
    COUNT(DISTINCT a.ID_HORARIO) as ids_en_asistencia
FROM horario h
LEFT JOIN asistencia a ON h.ID_HORARIO = a.ID_HORARIO;

-- 3. Verificar establecimientos y sus horarios
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
ORDER BY est.NOMBRE;

-- 4. Verificar horarios personalizados disponibles
SELECT 
    'Horarios personalizados' as info,
    COUNT(*) as total_horarios_personalizados,
    COUNT(DISTINCT ID_EMPLEADO) as empleados_con_horarios_personalizados
FROM empleado_horario_personalizado
WHERE ACTIVO = 'S';

-- 5. Identificar empleados SIN horarios personalizados
SELECT 
    e.ID_EMPLEADO,
    e.NOMBRE,
    e.APELLIDO,
    est.NOMBRE as establecimiento,
    COUNT(h.ID_HORARIO) as horarios_disponibles_establecimiento
FROM empleado e
INNER JOIN establecimiento est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
LEFT JOIN empleado_horario_personalizado ehp ON e.ID_EMPLEADO = ehp.ID_EMPLEADO AND ehp.ACTIVO = 'S'
LEFT JOIN horario h ON est.ID_ESTABLECIMIENTO = h.ID_ESTABLECIMIENTO
WHERE ehp.ID_EMPLEADO IS NULL
AND e.ACTIVO = 'S'
GROUP BY e.ID_EMPLEADO, e.NOMBRE, e.APELLIDO, est.NOMBRE
ORDER BY est.NOMBRE, e.NOMBRE;