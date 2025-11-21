-- COMANDOS ESPECÍFICOS PARA MIGRAR FK
-- Ejecutar uno por uno en phpMyAdmin

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
-- PASO 2: Verificar FK actual en asistencia (mostrar constraints)
-- ============================================================================
SHOW CREATE TABLE asistencia;

-- También puedes verificar con:
-- SHOW INDEX FROM asistencia;

-- ============================================================================
-- PASO 3: Agregar columna ID_EMPLEADO_HORARIO (solo si no existe)
-- ============================================================================
ALTER TABLE asistencia ADD COLUMN ID_EMPLEADO_HORARIO INT NULL;

-- ============================================================================
-- PASO 4: Eliminar FK de ID_HORARIO 
-- USAR EL NOMBRE QUE OBTUVISTE EN PASO 2
-- ============================================================================
-- ALTER TABLE asistencia DROP FOREIGN KEY nombre_del_constraint_aqui;

-- Ejemplos comunes de nombres de constraints:
-- ALTER TABLE asistencia DROP FOREIGN KEY asistencia_ibfk_1;
-- ALTER TABLE asistencia DROP FOREIGN KEY fk_asistencia_horario;

-- ============================================================================
-- PASO 5: Crear nuevo FK hacia empleado_horario_personalizado
-- ============================================================================
ALTER TABLE asistencia 
ADD CONSTRAINT fk_asistencia_empleado_horario 
FOREIGN KEY (ID_EMPLEADO_HORARIO) 
REFERENCES empleado_horario_personalizado(ID_EMPLEADO_HORARIO) 
ON DELETE SET NULL ON UPDATE CASCADE;

-- ============================================================================
-- PASO 6: Crear índices para optimización
-- ============================================================================
CREATE INDEX idx_asistencia_empleado_horario ON asistencia(ID_EMPLEADO_HORARIO);
CREATE INDEX idx_asistencia_empleado_fecha ON asistencia(ID_EMPLEADO, FECHA, TIPO);

-- ============================================================================
-- PASO 7: Verificar resultado final
-- ============================================================================
DESCRIBE asistencia;

SHOW CREATE TABLE asistencia;