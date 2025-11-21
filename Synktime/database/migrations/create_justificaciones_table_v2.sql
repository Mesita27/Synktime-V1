    -- =====================================================
-- MIGRACIÓN: Tabla justificaciones completa v2.0
-- Fecha: 2025-09-17
-- Descripción: Crear tabla justificaciones con estructura completa
-- =====================================================

-- 1. ELIMINAR tabla existente (si existe)
DROP TABLE IF EXISTS `justificacion`;

-- 2. CREAR nueva tabla con estructura completa
CREATE TABLE `justificaciones` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `empleado_id` int(11) NOT NULL,
  `fecha_falta` date NOT NULL,
  `fecha_justificacion` datetime NOT NULL DEFAULT current_timestamp(),
  `motivo` varchar(500) NOT NULL,
  `detalle_adicional` text DEFAULT NULL,
  `horas_programadas` decimal(4,2) DEFAULT 8.00,
  `tipo_falta` enum('completa','parcial','tardanza') DEFAULT 'completa',
  `hora_inicio_falta` time DEFAULT NULL,
  `hora_fin_falta` time DEFAULT NULL,
  `estado` enum('pendiente','aprobada','rechazada','revision') DEFAULT 'pendiente',
  `aprobada_por` int(11) DEFAULT NULL,
  `fecha_aprobacion` datetime DEFAULT NULL,
  `comentario_aprobacion` text DEFAULT NULL,
  `justificado_por` int(11) DEFAULT NULL COMMENT 'Usuario que creó la justificación',
  `documentos_adjuntos` json DEFAULT NULL COMMENT 'Array de rutas de documentos',
  `notificado_supervisor` tinyint(1) DEFAULT 0,
  `notificado_rrhh` tinyint(1) DEFAULT 0,
  `impacto_salario` tinyint(1) DEFAULT 0 COMMENT '1 si afecta el salario, 0 si no',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL COMMENT 'Soft delete',
  
  PRIMARY KEY (`id`),
  KEY `idx_empleado_fecha` (`empleado_id`, `fecha_falta`),
  KEY `idx_estado` (`estado`),
  KEY `idx_fecha_falta` (`fecha_falta`),
  KEY `idx_aprobada_por` (`aprobada_por`),
  KEY `idx_justificado_por` (`justificado_por`),
  KEY `idx_created_at` (`created_at`),
  
  CONSTRAINT `fk_justificaciones_empleado` 
    FOREIGN KEY (`empleado_id`) REFERENCES `empleado` (`ID_EMPLEADO`) 
    ON DELETE CASCADE ON UPDATE CASCADE,
    
  CONSTRAINT `fk_justificaciones_aprobador` 
    FOREIGN KEY (`aprobada_por`) REFERENCES `usuario` (`ID_USUARIO`) 
    ON DELETE SET NULL ON UPDATE CASCADE,
    
  CONSTRAINT `fk_justificaciones_creador` 
    FOREIGN KEY (`justificado_por`) REFERENCES `usuario` (`ID_USUARIO`) 
    ON DELETE SET NULL ON UPDATE CASCADE,
    
  CONSTRAINT `chk_horas_validas` 
    CHECK (`horas_programadas` >= 0 AND `horas_programadas` <= 24),
    
  CONSTRAINT `chk_falta_parcial` 
    CHECK ((`tipo_falta` != 'parcial') OR (`hora_inicio_falta` IS NOT NULL AND `hora_fin_falta` IS NOT NULL))
    
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 3. CREAR índices adicionales para rendimiento
CREATE INDEX `idx_justificaciones_busqueda` 
ON `justificaciones` (`empleado_id`, `fecha_falta`, `estado`);

CREATE INDEX `idx_justificaciones_aprobacion` 
ON `justificaciones` (`estado`, `fecha_justificacion`);

-- 5. CREAR tabla de configuración de justificaciones
CREATE TABLE `justificaciones_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `clave` varchar(100) NOT NULL UNIQUE,
  `valor` text NOT NULL,
  `descripcion` text DEFAULT NULL,
  `tipo` enum('string','number','boolean','json') DEFAULT 'string',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_config_clave` (`clave`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 6. INSERTAR configuraciones por defecto
INSERT INTO `justificaciones_config` (`clave`, `valor`, `descripcion`, `tipo`) VALUES
('horas_limite_justificacion', '16', 'Horas límite para justificar una falta después de ocurrida', 'number'),
('requiere_aprobacion', 'true', 'Si las justificaciones requieren aprobación', 'boolean'),
('notificar_supervisor', 'true', 'Notificar automáticamente al supervisor', 'boolean'),
('notificar_rrhh', 'false', 'Notificar automáticamente a RRHH', 'boolean'),
('horas_trabajo_default', '8.00', 'Horas de trabajo por defecto para justificaciones', 'number'),
('tipos_motivo_permitidos', '["Enfermedad","Cita médica","Emergencia familiar","Trámite personal","Capacitación","Otro"]', 'Tipos de motivos permitidos', 'json'),
('impacta_salario_default', 'false', 'Si por defecto las justificaciones impactan el salario', 'boolean');

-- 7. CREAR tabla de log de cambios para auditoría
CREATE TABLE `justificaciones_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `justificacion_id` int(11) NOT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `accion` varchar(50) NOT NULL,
  `estado_anterior` varchar(50) DEFAULT NULL,
  `estado_nuevo` varchar(50) DEFAULT NULL,
  `comentario` text DEFAULT NULL,
  `metadatos` json DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  
  PRIMARY KEY (`id`),
  KEY `idx_justificacion_log` (`justificacion_id`),
  KEY `idx_usuario_log` (`usuario_id`),
  KEY `idx_accion_log` (`accion`),
  KEY `idx_fecha_log` (`created_at`),
  
  CONSTRAINT `fk_log_justificacion` 
    FOREIGN KEY (`justificacion_id`) REFERENCES `justificaciones` (`id`) 
    ON DELETE CASCADE ON UPDATE CASCADE,
    
  CONSTRAINT `fk_log_usuario` 
    FOREIGN KEY (`usuario_id`) REFERENCES `usuario` (`ID_USUARIO`) 
    ON DELETE SET NULL ON UPDATE CASCADE
    
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 8. CREAR vista para facilitar consultas
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
    END as tipo_falta_texto
FROM justificaciones j
INNER JOIN empleado e ON j.empleado_id = e.ID_EMPLEADO
LEFT JOIN establecimiento est ON e.ID_ESTABLECIMIENTO = est.ID_ESTABLECIMIENTO
LEFT JOIN sede sed ON est.ID_SEDE = sed.ID_SEDE
LEFT JOIN usuario ap ON j.aprobada_por = ap.ID_USUARIO
LEFT JOIN usuario cr ON j.justificado_por = cr.ID_USUARIO
WHERE j.deleted_at IS NULL;

-- 9. MENSAJE DE FINALIZACIÓN
SELECT 'Migración completada exitosamente. Tabla justificaciones creada con estructura completa.' as mensaje;

-- 10. MOSTRAR ESTADÍSTICAS
SELECT 
    'justificaciones' as tabla,
    COUNT(*) as total_registros,
    COUNT(CASE WHEN estado = 'pendiente' THEN 1 END) as pendientes,
    COUNT(CASE WHEN estado = 'aprobada' THEN 1 END) as aprobadas,
    COUNT(CASE WHEN estado = 'rechazada' THEN 1 END) as rechazadas
FROM justificaciones;