-- =====================================================
-- SNKTIME Database Migration: Enhanced Biometric System
-- Version: 2.0.0
-- Date: 2024-08-23
-- =====================================================

-- Add FOTO_URL column to empleado table
ALTER TABLE `empleado` 
ADD COLUMN `FOTO_URL` VARCHAR(500) NULL COMMENT 'URL or path to employee photo' 
AFTER `ACTIVO`;

-- Create employee_vacations table
CREATE TABLE `employee_vacations` (
    `ID` INT(11) NOT NULL AUTO_INCREMENT,
    `ID_EMPLEADO` INT(11) NOT NULL,
    `FECHA_INICIO` DATE NOT NULL,
    `FECHA_FIN` DATE NOT NULL,
    `TIPO_VACACION` ENUM('vacation', 'sick_leave', 'personal', 'maternity', 'paternity', 'other') NOT NULL DEFAULT 'vacation',
    `DIAS_SOLICITADOS` INT(3) NOT NULL,
    `DIAS_HABILES` INT(3) NOT NULL COMMENT 'Working days only',
    `MOTIVO` TEXT NULL,
    `ESTADO` ENUM('pending', 'approved', 'rejected', 'cancelled') NOT NULL DEFAULT 'pending',
    `FECHA_SOLICITUD` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `APROBADO_POR` INT(11) NULL COMMENT 'ID_USUARIO who approved',
    `FECHA_APROBACION` DATETIME NULL,
    `COMENTARIOS_APROBACION` TEXT NULL,
    `ARCHIVO_ADJUNTO` VARCHAR(500) NULL COMMENT 'Supporting document path',
    `ACTIVO` TINYINT(1) NOT NULL DEFAULT 1,
    `CREATED_AT` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `UPDATED_AT` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`ID`),
    KEY `idx_empleado` (`ID_EMPLEADO`),
    KEY `idx_fechas` (`FECHA_INICIO`, `FECHA_FIN`),
    KEY `idx_estado` (`ESTADO`),
    KEY `idx_tipo` (`TIPO_VACACION`),
    KEY `idx_aprobador` (`APROBADO_POR`),
    FOREIGN KEY (`ID_EMPLEADO`) REFERENCES `empleado`(`ID_EMPLEADO`) ON DELETE CASCADE,
    FOREIGN KEY (`APROBADO_POR`) REFERENCES `usuario`(`ID_USUARIO`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci 
COMMENT='Employee vacation and leave requests';

-- Create roles table for RBAC system
CREATE TABLE `roles` (
    `ID` INT(11) NOT NULL AUTO_INCREMENT,
    `NOMBRE` VARCHAR(50) NOT NULL UNIQUE,
    `DESCRIPCION` TEXT NULL,
    `NIVEL_ACCESO` INT(2) NOT NULL DEFAULT 1 COMMENT '1=Basic, 2=Intermediate, 3=Advanced, 4=Admin, 5=SuperAdmin',
    `ACTIVO` TINYINT(1) NOT NULL DEFAULT 1,
    `CREATED_AT` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `UPDATED_AT` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`ID`),
    KEY `idx_nombre` (`NOMBRE`),
    KEY `idx_nivel` (`NIVEL_ACCESO`),
    KEY `idx_activo` (`ACTIVO`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='System roles for RBAC';

-- Create permissions table
CREATE TABLE `permissions` (
    `ID` INT(11) NOT NULL AUTO_INCREMENT,
    `NOMBRE` VARCHAR(100) NOT NULL UNIQUE,
    `DESCRIPCION` TEXT NULL,
    `MODULO` VARCHAR(50) NOT NULL COMMENT 'Module: attendance, employees, reports, etc.',
    `ACCION` VARCHAR(50) NOT NULL COMMENT 'Action: create, read, update, delete, export',
    `RECURSO` VARCHAR(100) NOT NULL COMMENT 'Resource: user, employee, report, etc.',
    `ACTIVO` TINYINT(1) NOT NULL DEFAULT 1,
    `CREATED_AT` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`ID`),
    KEY `idx_nombre` (`NOMBRE`),
    KEY `idx_modulo` (`MODULO`),
    KEY `idx_accion` (`ACCION`),
    KEY `idx_activo` (`ACTIVO`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='System permissions for RBAC';

-- Create role_permissions junction table
CREATE TABLE `role_permissions` (
    `ID` INT(11) NOT NULL AUTO_INCREMENT,
    `ID_ROL` INT(11) NOT NULL,
    `ID_PERMISO` INT(11) NOT NULL,
    `CREATED_AT` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`ID`),
    UNIQUE KEY `unique_role_permission` (`ID_ROL`, `ID_PERMISO`),
    KEY `idx_rol` (`ID_ROL`),
    KEY `idx_permiso` (`ID_PERMISO`),
    FOREIGN KEY (`ID_ROL`) REFERENCES `roles`(`ID`) ON DELETE CASCADE,
    FOREIGN KEY (`ID_PERMISO`) REFERENCES `permissions`(`ID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='Role-Permission assignments';

-- Create user_roles junction table
CREATE TABLE `user_roles` (
    `ID` INT(11) NOT NULL AUTO_INCREMENT,
    `ID_USUARIO` INT(11) NOT NULL,
    `ID_ROL` INT(11) NOT NULL,
    `ASIGNADO_POR` INT(11) NULL,
    `FECHA_ASIGNACION` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `ACTIVO` TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (`ID`),
    UNIQUE KEY `unique_user_role` (`ID_USUARIO`, `ID_ROL`),
    KEY `idx_usuario` (`ID_USUARIO`),
    KEY `idx_rol` (`ID_ROL`),
    KEY `idx_asignado_por` (`ASIGNADO_POR`),
    KEY `idx_activo` (`ACTIVO`),
    FOREIGN KEY (`ID_USUARIO`) REFERENCES `usuario`(`ID_USUARIO`) ON DELETE CASCADE,
    FOREIGN KEY (`ID_ROL`) REFERENCES `roles`(`ID`) ON DELETE CASCADE,
    FOREIGN KEY (`ASIGNADO_POR`) REFERENCES `usuario`(`ID_USUARIO`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
COMMENT='User-Role assignments';

-- Extend biometric_data table with new fields for Python service
ALTER TABLE `biometric_data`
ADD COLUMN `EMBEDDING_DATA` LONGTEXT NULL COMMENT 'Facial embeddings or fingerprint templates (JSON)' AFTER `BIOMETRIC_DATA`,
ADD COLUMN `TEMPLATE_VERSION` VARCHAR(20) NULL COMMENT 'Template format version' AFTER `EMBEDDING_DATA`,
ADD COLUMN `DEVICE_ID` VARCHAR(100) NULL COMMENT 'Device used for enrollment' AFTER `TEMPLATE_VERSION`,
ADD COLUMN `QUALITY_SCORE` DECIMAL(5,4) NULL COMMENT 'Biometric quality score 0.0000-1.0000' AFTER `DEVICE_ID`,
ADD COLUMN `PYTHON_SERVICE_ID` VARCHAR(100) NULL COMMENT 'Python service template ID' AFTER `QUALITY_SCORE`;

-- Extend biometric_logs table with new fields
ALTER TABLE `biometric_logs`
ADD COLUMN `DEVICE_ID` VARCHAR(100) NULL COMMENT 'Device used for verification' AFTER `API_SOURCE`,
ADD COLUMN `PROCESSING_TIME_MS` INT(5) NULL COMMENT 'Processing time in milliseconds' AFTER `DEVICE_ID`,
ADD COLUMN `ERROR_MESSAGE` TEXT NULL COMMENT 'Error details if verification failed' AFTER `PROCESSING_TIME_MS`;

-- Insert default roles
INSERT INTO `roles` (`NOMBRE`, `DESCRIPCION`, `NIVEL_ACCESO`) VALUES
('SuperAdmin', 'Super Administrator with full system access', 5),
('Administrador', 'System Administrator', 4),
('Gerente', 'Manager with advanced permissions', 3),
('Supervisor', 'Supervisor with intermediate permissions', 2),
('Empleado', 'Basic employee permissions', 1);

-- Insert default permissions
INSERT INTO `permissions` (`NOMBRE`, `DESCRIPCION`, `MODULO`, `ACCION`, `RECURSO`) VALUES
-- User management
('users.create', 'Create new users', 'users', 'create', 'user'),
('users.read', 'View users', 'users', 'read', 'user'),
('users.update', 'Edit users', 'users', 'update', 'user'),
('users.delete', 'Delete users', 'users', 'delete', 'user'),
('users.assign_roles', 'Assign roles to users', 'users', 'assign', 'role'),

-- Employee management
('employees.create', 'Create employees', 'employees', 'create', 'employee'),
('employees.read', 'View employees', 'employees', 'read', 'employee'),
('employees.update', 'Edit employees', 'employees', 'update', 'employee'),
('employees.delete', 'Delete employees', 'employees', 'delete', 'employee'),
('employees.manage_photos', 'Manage employee photos', 'employees', 'manage', 'photo'),

-- Attendance management
('attendance.view_own', 'View own attendance', 'attendance', 'read', 'own_attendance'),
('attendance.view_all', 'View all attendance records', 'attendance', 'read', 'attendance'),
('attendance.edit', 'Edit attendance records', 'attendance', 'update', 'attendance'),
('attendance.export', 'Export attendance reports', 'attendance', 'export', 'attendance'),

-- Biometric management
('biometric.enroll_own', 'Enroll own biometric data', 'biometric', 'enroll', 'own_biometric'),
('biometric.enroll_all', 'Enroll biometric data for any employee', 'biometric', 'enroll', 'biometric'),
('biometric.manage', 'Manage biometric system', 'biometric', 'manage', 'biometric'),

-- Vacation management
('vacations.create', 'Request vacations', 'vacations', 'create', 'vacation'),
('vacations.view_own', 'View own vacation requests', 'vacations', 'read', 'own_vacation'),
('vacations.view_all', 'View all vacation requests', 'vacations', 'read', 'vacation'),
('vacations.approve', 'Approve vacation requests', 'vacations', 'approve', 'vacation'),
('vacations.manage', 'Full vacation management', 'vacations', 'manage', 'vacation'),

-- Reports
('reports.attendance', 'View attendance reports', 'reports', 'read', 'attendance_report'),
('reports.biometric', 'View biometric reports', 'reports', 'read', 'biometric_report'),
('reports.vacation', 'View vacation reports', 'reports', 'read', 'vacation_report'),
('reports.export', 'Export reports', 'reports', 'export', 'report'),

-- System settings
('settings.view', 'View system settings', 'settings', 'read', 'setting'),
('settings.edit', 'Edit system settings', 'settings', 'update', 'setting');

-- Assign permissions to roles
-- SuperAdmin gets all permissions
INSERT INTO `role_permissions` (`ID_ROL`, `ID_PERMISO`)
SELECT r.ID, p.ID 
FROM `roles` r 
CROSS JOIN `permissions` p 
WHERE r.NOMBRE = 'SuperAdmin';

-- Administrador gets most permissions except user deletion and role assignment
INSERT INTO `role_permissions` (`ID_ROL`, `ID_PERMISO`)
SELECT r.ID, p.ID 
FROM `roles` r 
CROSS JOIN `permissions` p 
WHERE r.NOMBRE = 'Administrador' 
AND p.NOMBRE NOT IN ('users.delete', 'users.assign_roles');

-- Gerente gets employee and attendance management, vacation approval
INSERT INTO `role_permissions` (`ID_ROL`, `ID_PERMISO`)
SELECT r.ID, p.ID 
FROM `roles` r 
CROSS JOIN `permissions` p 
WHERE r.NOMBRE = 'Gerente' 
AND p.NOMBRE IN (
    'employees.read', 'employees.update', 'employees.manage_photos',
    'attendance.view_all', 'attendance.edit', 'attendance.export',
    'biometric.enroll_all', 'biometric.manage',
    'vacations.view_all', 'vacations.approve', 'vacations.manage',
    'reports.attendance', 'reports.biometric', 'reports.vacation', 'reports.export'
);

-- Supervisor gets basic management permissions
INSERT INTO `role_permissions` (`ID_ROL`, `ID_PERMISO`)
SELECT r.ID, p.ID 
FROM `roles` r 
CROSS JOIN `permissions` p 
WHERE r.NOMBRE = 'Supervisor' 
AND p.NOMBRE IN (
    'employees.read', 
    'attendance.view_all', 'attendance.export',
    'biometric.enroll_all',
    'vacations.view_all', 'vacations.approve',
    'reports.attendance', 'reports.vacation'
);

-- Empleado gets basic self-service permissions
INSERT INTO `role_permissions` (`ID_ROL`, `ID_PERMISO`)
SELECT r.ID, p.ID 
FROM `roles` r 
CROSS JOIN `permissions` p 
WHERE r.NOMBRE = 'Empleado' 
AND p.NOMBRE IN (
    'employees.read',
    'attendance.view_own',
    'biometric.enroll_own',
    'vacations.create', 'vacations.view_own'
);

-- Create materialized view for biometric usage statistics
CREATE OR REPLACE VIEW `vw_biometric_usage_stats_detailed` AS
SELECT 
    bl.VERIFICATION_METHOD,
    bl.OPERATION_TYPE,
    DATE(bl.FECHA) as FECHA,
    HOUR(bl.CREATED_AT) as HORA,
    bl.DEVICE_ID,
    COUNT(*) as TOTAL_INTENTOS,
    SUM(bl.VERIFICATION_SUCCESS) as INTENTOS_EXITOSOS,
    ROUND(AVG(bl.CONFIDENCE_SCORE), 4) as CONFIDENCE_PROMEDIO,
    ROUND(AVG(bl.PROCESSING_TIME_MS), 2) as TIEMPO_PROMEDIO_MS,
    ROUND((SUM(bl.VERIFICATION_SUCCESS) * 100.0 / COUNT(*)), 2) as TASA_EXITO,
    -- Additional metrics
    MIN(bl.CONFIDENCE_SCORE) as MIN_CONFIDENCE,
    MAX(bl.CONFIDENCE_SCORE) as MAX_CONFIDENCE,
    COUNT(DISTINCT bl.ID_EMPLEADO) as EMPLEADOS_UNICOS
FROM biometric_logs bl
WHERE bl.FECHA >= DATE_SUB(CURDATE(), INTERVAL 90 DAY)
GROUP BY 
    bl.VERIFICATION_METHOD, 
    bl.OPERATION_TYPE, 
    DATE(bl.FECHA),
    HOUR(bl.CREATED_AT),
    bl.DEVICE_ID
ORDER BY FECHA DESC, HORA DESC;

-- Create vacation balance view
CREATE OR REPLACE VIEW `vw_employee_vacation_balance` AS
SELECT 
    e.ID_EMPLEADO,
    e.NOMBRE,
    e.APELLIDO,
    e.FECHA_INGRESO,
    -- Calculate years of service
    TIMESTAMPDIFF(YEAR, e.FECHA_INGRESO, CURDATE()) as ANOS_SERVICIO,
    -- Base vacation days (can be configured)
    CASE 
        WHEN TIMESTAMPDIFF(YEAR, e.FECHA_INGRESO, CURDATE()) < 1 THEN 15
        WHEN TIMESTAMPDIFF(YEAR, e.FECHA_INGRESO, CURDATE()) < 5 THEN 20
        WHEN TIMESTAMPDIFF(YEAR, e.FECHA_INGRESO, CURDATE()) < 10 THEN 25
        ELSE 30
    END as DIAS_ANUALES,
    -- Used vacation days this year
    COALESCE(SUM(
        CASE 
            WHEN ev.ESTADO = 'approved' 
            AND YEAR(ev.FECHA_INICIO) = YEAR(CURDATE())
            AND ev.TIPO_VACACION = 'vacation'
            THEN ev.DIAS_HABILES 
            ELSE 0 
        END
    ), 0) as DIAS_USADOS,
    -- Pending requests
    COALESCE(SUM(
        CASE 
            WHEN ev.ESTADO = 'pending' 
            AND YEAR(ev.FECHA_INICIO) = YEAR(CURDATE())
            AND ev.TIPO_VACACION = 'vacation'
            THEN ev.DIAS_HABILES 
            ELSE 0 
        END
    ), 0) as DIAS_PENDIENTES
FROM empleado e
LEFT JOIN employee_vacations ev ON e.ID_EMPLEADO = ev.ID_EMPLEADO
WHERE e.ESTADO = 'A' AND e.ACTIVO = 'S'
GROUP BY e.ID_EMPLEADO, e.NOMBRE, e.APELLIDO, e.FECHA_INGRESO;

-- Create indexes for performance
CREATE INDEX idx_biometric_data_embedding ON biometric_data(PYTHON_SERVICE_ID);
CREATE INDEX idx_biometric_logs_device ON biometric_logs(DEVICE_ID);
CREATE INDEX idx_biometric_logs_processing_time ON biometric_logs(PROCESSING_TIME_MS);
CREATE INDEX idx_employee_vacations_dates ON employee_vacations(FECHA_INICIO, FECHA_FIN);
CREATE INDEX idx_employee_vacations_status ON employee_vacations(ESTADO, TIPO_VACACION);

-- Migration completion log
INSERT INTO `configuracion` (`clave`, `valor`, `descripcion`) VALUES
('migration_enhanced_biometric', '2.0.0', 'Enhanced biometric system migration completed'),
('rbac_system_enabled', '1', 'Role-based access control system enabled'),
('vacation_system_enabled', '1', 'Vacation management system enabled'),
('employee_photos_enabled', '1', 'Employee photo management enabled')
ON DUPLICATE KEY UPDATE 
    valor = VALUES(valor),
    descripcion = VALUES(descripcion);

SELECT 
    'Enhanced Biometric System Migration Completed' as status,
    NOW() as timestamp,
    'Tables created: employee_vacations, roles, permissions, role_permissions, user_roles' as tables_created,
    'Columns added: empleado.FOTO_URL, biometric_data extensions, biometric_logs extensions' as columns_added,
    'Views created: vw_biometric_usage_stats_detailed, vw_employee_vacation_balance' as views_created;