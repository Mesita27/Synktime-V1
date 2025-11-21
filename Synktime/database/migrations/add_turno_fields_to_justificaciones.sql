-- =====================================================
-- MIGRACIÓN: Agregar campos de turno a tabla justificaciones
-- Fecha: 2025-09-17
-- Descripción: Agregar campos turno_id y justificar_todos_turnos para compatibilidad con horarios personalizados
-- =====================================================

-- 1. Agregar campos de turno a la tabla justificaciones
ALTER TABLE `justificaciones` 
ADD COLUMN `turno_id` int(11) DEFAULT NULL COMMENT 'ID del turno específico del empleado_horario_personalizado',
ADD COLUMN `justificar_todos_turnos` tinyint(1) DEFAULT 0 COMMENT '1 si justifica todos los turnos del día, 0 si es un turno específico',
ADD COLUMN `turnos_ids` json DEFAULT NULL COMMENT 'Array de IDs de turnos cuando se justifican múltiples turnos específicos';

-- 2. Agregar índices para los nuevos campos
CREATE INDEX `idx_justificaciones_turno` ON `justificaciones` (`turno_id`);
CREATE INDEX `idx_justificaciones_todos_turnos` ON `justificaciones` (`justificar_todos_turnos`);

-- 3. Agregar constraint para el turno_id
ALTER TABLE `justificaciones`
ADD CONSTRAINT `fk_justificaciones_turno` 
FOREIGN KEY (`turno_id`) REFERENCES `empleado_horario_personalizado` (`ID_EMPLEADO_HORARIO`) 
ON DELETE SET NULL ON UPDATE CASCADE;

-- 4. Actualizar la vista para incluir los nuevos campos
DROP VIEW IF EXISTS `vw_justificaciones_completa`;

CREATE VIEW `vw_justificaciones_completa` AS
SELECT 
    j.id,
    j.empleado_id,
    e.NOMBRE as empleado_nombre,
    e.APELLIDO as empleado_apellido,
    e.DNI as empleado_dni,
    CONCAT(e.NOMBRE, ' ', e.APELLIDO) as empleado_nombre_completo,
    j.fecha_falta,
    j.fecha_justificacion,
    j.motivo,
    j.detalle_adicional,
    j.horas_programadas,
    j.tipo_falta,
    j.hora_inicio_falta,
    j.hora_fin_falta,
    j.estado,
    j.turno_id,
    j.justificar_todos_turnos,
    j.turnos_ids,
    ehp.NOMBRE_TURNO as turno_nombre,
    ehp.HORA_ENTRADA as turno_hora_entrada,
    ehp.HORA_SALIDA as turno_hora_salida,
    ap.username as aprobado_por_usuario,
    j.fecha_aprobacion,
    j.comentario_aprobacion,
    cr.username as creado_por_usuario,
    j.notificado_supervisor,
    j.notificado_rrhh,
    j.impacto_salario,
    j.created_at,
    j.updated_at,
    est.NOMBRE as establecimiento_nombre,
    sed.NOMBRE as sede_nombre,
    CASE 
        WHEN j.estado = 'aprobada' THEN 'Aprobada'
        WHEN j.estado = 'rechazada' THEN 'Rechazada'
        WHEN j.estado = 'revision' THEN 'En Revisión'
        ELSE 'Pendiente'
    END as estado_texto,
    CASE 
        WHEN j.tipo_falta = 'completa' THEN 'Día Completo'
        WHEN j.tipo_falta = 'parcial' THEN 'Parcial'
        WHEN j.tipo_falta = 'tardanza' THEN 'Tardanza'
        ELSE j.tipo_falta
    END as tipo_falta_texto,
    CASE 
        WHEN j.justificar_todos_turnos = 1 THEN 'Todos los turnos'
        WHEN j.turno_id IS NOT NULL THEN CONCAT('Turno: ', ehp.NOMBRE_TURNO)
        ELSE 'Sin turno específico'
    END as turno_descripcion
FROM justificaciones j
INNER JOIN empleado e ON j.empleado_id = e.ID_EMPLEADO
LEFT JOIN establecimiento est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
LEFT JOIN sede sed ON est.ID_SEDE = sed.ID_SEDE
LEFT JOIN empleado_horario_personalizado ehp ON j.turno_id = ehp.ID_EMPLEADO_HORARIO
LEFT JOIN usuario ap ON j.aprobada_por = ap.ID_USUARIO
LEFT JOIN usuario cr ON j.justificado_por = cr.ID_USUARIO
WHERE j.deleted_at IS NULL;

-- 5. MENSAJE DE FINALIZACIÓN
SELECT 'Migración completada: Campos de turno agregados a tabla justificaciones' as mensaje;