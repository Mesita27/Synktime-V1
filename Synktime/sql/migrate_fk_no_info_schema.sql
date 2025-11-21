-- MIGRACIÓN FK SIN USAR INFORMATION_SCHEMA
-- Para casos donde hay restricciones de permisos

-- ============================================================================
-- PASO 1: Crear tabla empleado_horario_personalizado
-- ============================================================================
CREATE TABLE IF NOT EXISTS empleado_horario_personalizado (
    ID_EMPLEADO_HORARIO INT AUTO_INCREMENT PRIMARY KEY,
    ID_EMPLEADO INT NOT NULL,
    ID_DIA INT NOT NULL COMMENT '1=Lunes, 2=Martes, 3=Miércoles, 4=Jueves, 5=Viernes, 6=Sábado, 7=Domingo',
    HORA_ENTRADA TIME NOT NULL,
    HORA_SALIDA TIME NOT NULL,
    TOLERANCIA INT DEFAULT 15,
    NOMBRE_TURNO VARCHAR(50) DEFAULT 'Turno Principal',
    FECHA_DESDE DATE NOT NULL,
    FECHA_HASTA DATE NULL,
    ACTIVO CHAR(1) DEFAULT 'S',
    ORDEN_TURNO INT DEFAULT 1,
    OBSERVACIONES TEXT NULL,
    CREATED_AT TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UPDATED_AT TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (ID_EMPLEADO) REFERENCES empleado(ID_EMPLEADO) ON DELETE CASCADE,
    FOREIGN KEY (ID_DIA) REFERENCES dia_semana(ID_DIA),
    INDEX idx_empleado_dia_activo (ID_EMPLEADO, ID_DIA, ACTIVO)
);

-- ============================================================================
-- PASO 2: Ver estructura actual de asistencia
-- ============================================================================
DESCRIBE asistencia;

-- ============================================================================
-- PASO 3: Ver CREATE TABLE de asistencia para encontrar FK constraints
-- ============================================================================
SHOW CREATE TABLE asistencia;

-- ============================================================================
-- PASO 4: Agregar columna ID_EMPLEADO_HORARIO
-- ============================================================================
ALTER TABLE asistencia ADD COLUMN ID_EMPLEADO_HORARIO INT NULL;

-- ============================================================================
-- PASO 5: Eliminar FK de ID_HORARIO
-- Basado en el SHOW CREATE TABLE, usar uno de estos comandos:
-- ============================================================================

-- Opción A: Si el constraint se llama asistencia_ibfk_1
-- ALTER TABLE asistencia DROP FOREIGN KEY asistencia_ibfk_1;

-- Opción B: Si el constraint se llama fk_asistencia_horario  
-- ALTER TABLE asistencia DROP FOREIGN KEY fk_asistencia_horario;

-- Opción C: Si hay múltiples constraints, prueba con números secuenciales
-- ALTER TABLE asistencia DROP FOREIGN KEY asistencia_ibfk_2;
-- ALTER TABLE asistencia DROP FOREIGN KEY asistencia_ibfk_3;

-- COMANDO GENÉRICO PARA INTENTAR (ejecutar hasta que funcione):
ALTER TABLE asistencia DROP FOREIGN KEY asistencia_ibfk_1;

-- ============================================================================
-- PASO 6: Crear nuevo FK hacia empleado_horario_personalizado
-- ============================================================================
ALTER TABLE asistencia 
ADD CONSTRAINT fk_asistencia_empleado_horario 
FOREIGN KEY (ID_EMPLEADO_HORARIO) 
REFERENCES empleado_horario_personalizado(ID_EMPLEADO_HORARIO) 
ON DELETE SET NULL ON UPDATE CASCADE;

-- ============================================================================
-- PASO 7: Crear índices
-- ============================================================================
CREATE INDEX idx_asistencia_empleado_horario ON asistencia(ID_EMPLEADO_HORARIO);
CREATE INDEX idx_asistencia_empleado_fecha ON asistencia(ID_EMPLEADO, FECHA, TIPO);

-- ============================================================================
-- PASO 8: Verificar resultado
-- ============================================================================
DESCRIBE asistencia;
SHOW CREATE TABLE asistencia;

-- ============================================================================
-- PASO 9: Poblar algunos horarios de ejemplo para testing
-- ============================================================================
-- Ejemplo: Crear horario personalizado para empleado 100
INSERT INTO empleado_horario_personalizado 
(ID_EMPLEADO, ID_DIA, HORA_ENTRADA, HORA_SALIDA, TOLERANCIA, NOMBRE_TURNO, FECHA_DESDE, ACTIVO, ORDEN_TURNO, OBSERVACIONES)
VALUES 
(100, 1, '08:00', '16:00', 15, 'Horario Regular - Lunes', CURDATE(), 'S', 1, 'Horario de prueba'),
(100, 2, '08:00', '16:00', 15, 'Horario Regular - Martes', CURDATE(), 'S', 1, 'Horario de prueba'),
(100, 3, '08:00', '16:00', 15, 'Horario Regular - Miércoles', CURDATE(), 'S', 1, 'Horario de prueba'),
(100, 4, '08:00', '16:00', 15, 'Horario Regular - Jueves', CURDATE(), 'S', 1, 'Horario de prueba'),
(100, 5, '08:00', '16:00', 15, 'Horario Regular - Viernes', CURDATE(), 'S', 1, 'Horario de prueba');

-- Verificar horarios creados
SELECT * FROM empleado_horario_personalizado WHERE ID_EMPLEADO = 100;