-- PLAN COMPLETO DE CONFIGURACIÓN DEL SISTEMA DE HORARIOS PERSONALIZADOS
-- ========================================================================

-- EJECUTAR EN ORDEN:

-- 1. CREAR TABLA EMPLEADO_HORARIO_PERSONALIZADO
-- Archivo: create_table_first.sql

-- 2. MIGRAR HORARIOS LEGACY A PERSONALIZADOS  
-- Archivo: migrate_legacy_to_personalized.sql

-- 3. ACTUALIZAR TABLA ASISTENCIA PARA FK
-- Archivo: update_asistencia_schema.sql

-- 4. VERIFICAR TODO FUNCIONANDO
-- Archivo: quick_schema_check.sql (actualizado)


-- ========================================================================
-- VERIFICACIÓN PREVIA: ¿Qué necesitamos?
-- ========================================================================

SELECT 'VERIFICACIÓN PREVIA' as fase;

-- 1. Verificar tablas base existen
SELECT 
    'Verificando tablas base...' as check_name,
    CASE 
        WHEN EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'empleado') THEN '✅ empleado existe'
        ELSE '❌ empleado NO existe'
    END as empleado_table,
    CASE 
        WHEN EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'establecimiento') THEN '✅ establecimiento existe'
        ELSE '❌ establecimiento NO existe'
    END as establecimiento_table,
    CASE 
        WHEN EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'horario') THEN '✅ horario existe'
        ELSE '❌ horario NO existe'
    END as horario_table,
    CASE 
        WHEN EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'dia_semana') THEN '✅ dia_semana existe'
        ELSE '❌ dia_semana NO existe'
    END as dia_semana_table,
    CASE 
        WHEN EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'asistencia') THEN '✅ asistencia existe'
        ELSE '❌ asistencia NO existe'
    END as asistencia_table;

-- 2. Verificar empleados activos
SELECT 
    'Empleados activos encontrados' as info,
    COUNT(*) as cantidad
FROM empleado 
WHERE ACTIVO = 'S';

-- 3. Verificar horarios legacy disponibles
SELECT 
    'Horarios legacy disponibles' as info,
    COUNT(*) as cantidad
FROM horario;

-- 4. Verificar si empleado_horario_personalizado ya existe
SELECT 
    'Tabla empleado_horario_personalizado' as tabla,
    CASE 
        WHEN EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'empleado_horario_personalizado') 
        THEN '✅ YA EXISTE'
        ELSE '❌ NECESITA CREARSE'
    END as estado;

-- ========================================================================
-- SIGUIENTE PASO: Ejecutar create_table_first.sql
-- ========================================================================

SELECT 'SIGUIENTE PASO: Ejecutar sql/create_table_first.sql' as instruccion;