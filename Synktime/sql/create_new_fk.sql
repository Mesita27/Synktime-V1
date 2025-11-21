-- ============================================================================
-- CREAR NUEVO FK CONSTRAINT HACIA empleado_horario_personalizado
-- ============================================================================
-- Este script crea el nuevo FK constraint después de haber eliminado el anterior

-- Paso 1: Crear el nuevo FK constraint
ALTER TABLE asistencia 
ADD CONSTRAINT fk_asistencia_empleado_horario 
FOREIGN KEY (ID_EMPLEADO_HORARIO) 
REFERENCES empleado_horario_personalizado(ID_EMPLEADO_HORARIO) 
ON DELETE SET NULL ON UPDATE CASCADE;

-- Paso 2: Crear índices para optimización
CREATE INDEX idx_asistencia_empleado_horario ON asistencia(ID_EMPLEADO_HORARIO);
CREATE INDEX idx_asistencia_empleado_fecha ON asistencia(ID_EMPLEADO, FECHA, TIPO);

-- Paso 3: Verificar el resultado
SHOW CREATE TABLE asistencia;

-- Paso 4: Verificar índices
SHOW INDEX FROM asistencia;

SELECT 'FK Migration completed successfully!' as STATUS;