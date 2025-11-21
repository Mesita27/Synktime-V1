-- MIGRACIÓN COMPLETA: De horarios legacy a empleado_horario_personalizado
-- Este script migra horarios basados en establecimiento a horarios personalizados por empleado

-- 1. VERIFICAR DATOS ANTES DE LA MIGRACIÓN
SELECT 'ANTES DE MIGRACIÓN - Análisis de horarios por establecimiento' as estado;

SELECT 
    est.NOMBRE as ESTABLECIMIENTO,
    COUNT(e.ID_EMPLEADO) as EMPLEADOS_ACTIVOS,
    GROUP_CONCAT(DISTINCT h.NOMBRE SEPARATOR ', ') as HORARIOS_DISPONIBLES
FROM empleado e
INNER JOIN establecimiento est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
LEFT JOIN horario h ON est.ID_ESTABLECIMIENTO = h.ID_ESTABLECIMIENTO
WHERE e.ACTIVO = 'S'
GROUP BY est.ID_ESTABLECIMIENTO, est.NOMBRE
ORDER BY est.NOMBRE;

SELECT 
    'Total empleados activos' as info,
    COUNT(*) as cantidad
FROM empleado e
WHERE e.ACTIVO = 'S';

SELECT 
    'Total horarios legacy disponibles' as info,
    COUNT(*) as cantidad
FROM horario;

-- 2. MIGRAR HORARIOS LEGACY A EMPLEADO_HORARIO_PERSONALIZADO
-- Para cada empleado, usar el primer horario de su establecimiento para crear horarios L-V

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
    dias.dia_numero,
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
CROSS JOIN (
    SELECT 1 as dia_numero UNION ALL  -- Lunes
    SELECT 2 as dia_numero UNION ALL  -- Martes
    SELECT 3 as dia_numero UNION ALL  -- Miércoles
    SELECT 4 as dia_numero UNION ALL  -- Jueves
    SELECT 5 as dia_numero UNION ALL  -- Viernes
    SELECT 6 as dia_numero UNION ALL  -- Sábado
    SELECT 7 as dia_numero            -- Domingo
) as dias
INNER JOIN horario_dia hd ON h.ID_HORARIO = hd.ID_HORARIO AND hd.ID_DIA = dias.dia_numero
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
    AND ehp.ID_DIA = dias.dia_numero
    AND ehp.ACTIVO = 'S'
);

-- 3. VERIFICAR MIGRACIÓN
SELECT 'DESPUÉS DE MIGRACIÓN' as estado;

SELECT 
    'Empleados con horarios personalizados' as info,
    COUNT(DISTINCT ID_EMPLEADO) as cantidad
FROM empleado_horario_personalizado
WHERE ACTIVO = 'S';

SELECT 
    'Total horarios personalizados creados' as info,
    COUNT(*) as cantidad
FROM empleado_horario_personalizado
WHERE ACTIVO = 'S';

-- 4. MOSTRAR EMPLEADOS MIGRADOS
SELECT 
    e.ID_EMPLEADO,
    e.NOMBRE,
    e.APELLIDO,
    est.NOMBRE as establecimiento,
    h.NOMBRE as horario_legacy_base,
    COUNT(ehp.ID_EMPLEADO_HORARIO) as horarios_personalizados_creados
FROM empleado e
INNER JOIN establecimiento est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
LEFT JOIN horario h ON est.ID_ESTABLECIMIENTO = h.ID_ESTABLECIMIENTO
    AND h.ID_HORARIO = (
        SELECT MIN(h2.ID_HORARIO) 
        FROM horario h2 
        WHERE h2.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
    )
LEFT JOIN empleado_horario_personalizado ehp ON e.ID_EMPLEADO = ehp.ID_EMPLEADO AND ehp.ACTIVO = 'S'
WHERE e.ACTIVO = 'S'
GROUP BY e.ID_EMPLEADO, e.NOMBRE, e.APELLIDO, est.NOMBRE, h.NOMBRE
ORDER BY est.NOMBRE, e.NOMBRE, e.APELLIDO;

-- 5. RESUMEN POR ESTABLECIMIENTO
SELECT 
    est.NOMBRE as establecimiento,
    COUNT(DISTINCT e.ID_EMPLEADO) as empleados_migrados,
    COUNT(ehp.ID_EMPLEADO_HORARIO) as total_horarios_personalizados,
    MIN(h.NOMBRE) as horario_base_utilizado
FROM empleado e
INNER JOIN establecimiento est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
LEFT JOIN horario h ON est.ID_ESTABLECIMIENTO = h.ID_ESTABLECIMIENTO
    AND h.ID_HORARIO = (
        SELECT MIN(h2.ID_HORARIO) 
        FROM horario h2 
        WHERE h2.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
    )
LEFT JOIN empleado_horario_personalizado ehp ON e.ID_EMPLEADO = ehp.ID_EMPLEADO AND ehp.ACTIVO = 'S'
WHERE e.ACTIVO = 'S'
GROUP BY est.ID_ESTABLECIMIENTO, est.NOMBRE
ORDER BY est.NOMBRE;