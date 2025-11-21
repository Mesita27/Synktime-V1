        -- =========================================================================
-- SCRIPT SQL: LIMPIEZA AUTOMÁTICA DE USUARIOS INACTIVOS
-- =========================================================================
-- Este script crea un procedimiento almacenado para eliminar usuarios
-- inactivos y un evento programado para ejecutarlo semanalmente
-- =========================================================================

-- Asegurarse de que el event scheduler esté habilitado
SET GLOBAL event_scheduler = ON;

-- Crear procedimiento para limpiar usuarios inactivos
DELIMITER //

DROP PROCEDURE IF EXISTS LimpiarUsuariosInactivos//

CREATE PROCEDURE LimpiarUsuariosInactivos()
BEGIN
    DECLARE done INT DEFAULT FALSE;
    DECLARE usuario_id INT;
    DECLARE usuario_nombre VARCHAR(255);
    DECLARE usuarios_eliminados INT DEFAULT 0;
    DECLARE error_count INT DEFAULT 0;
    
    -- Cursor para obtener usuarios inactivos con más de 7 días
    DECLARE cur CURSOR FOR 
        SELECT ID_USUARIO, NOMBRE_COMPLETO 
        FROM usuario 
        WHERE ESTADO = 'I' 
        AND TIMESTAMPDIFF(DAY, FECHA_ACTUALIZACION, NOW()) >= 7;
    
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
    DECLARE CONTINUE HANDLER FOR SQLEXCEPTION 
    BEGIN
        GET DIAGNOSTICS CONDITION 1
            @sqlstate = RETURNED_SQLSTATE, 
            @errno = MYSQL_ERRNO, 
            @text = MESSAGE_TEXT;
        SET error_count = error_count + 1;
        -- Log del error
        INSERT INTO log_limpieza_usuarios (
            fecha, 
            tipo, 
            mensaje, 
            usuario_id
        ) VALUES (
            NOW(), 
            'ERROR', 
            CONCAT('Error al eliminar usuario ID ', usuario_id, ': ', @text),
            usuario_id
        );
    END;
    
    -- Crear tabla de log si no existe
    CREATE TABLE IF NOT EXISTS log_limpieza_usuarios (
        id INT AUTO_INCREMENT PRIMARY KEY,
        fecha DATETIME NOT NULL,
        tipo ENUM('INFO', 'ERROR', 'WARNING') NOT NULL,
        mensaje TEXT NOT NULL,
        usuario_id INT NULL,
        usuarios_procesados INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );
    
    -- Log de inicio del proceso
    INSERT INTO log_limpieza_usuarios (fecha, tipo, mensaje, usuarios_procesados) 
    VALUES (NOW(), 'INFO', 'Iniciando limpieza de usuarios inactivos', 0);
    
    -- Abrir cursor y procesar usuarios
    OPEN cur;
    
    read_loop: LOOP
        FETCH cur INTO usuario_id, usuario_nombre;
        IF done THEN
            LEAVE read_loop;
        END IF;
        
        -- Intentar eliminar el usuario
        BEGIN
            -- Eliminar registros relacionados si existen (ajustar según tu esquema)
            -- DELETE FROM user_sessions WHERE user_id = usuario_id;
            -- DELETE FROM user_logs WHERE user_id = usuario_id;
            
            -- Eliminar el usuario principal
            DELETE FROM usuario WHERE ID_USUARIO = usuario_id;
            
            SET usuarios_eliminados = usuarios_eliminados + 1;
            
            -- Log de usuario eliminado exitosamente
            INSERT INTO log_limpieza_usuarios (fecha, tipo, mensaje, usuario_id) 
            VALUES (NOW(), 'INFO', CONCAT('Usuario eliminado: ', usuario_nombre), usuario_id);
            
        END;
        
    END LOOP;
    
    CLOSE cur;
    
    -- Log final del proceso
    INSERT INTO log_limpieza_usuarios (fecha, tipo, mensaje, usuarios_procesados) 
    VALUES (
        NOW(), 
        'INFO', 
        CONCAT('Limpieza completada. Usuarios eliminados: ', usuarios_eliminados, ', Errores: ', error_count),
        usuarios_eliminados
    );
    
    -- Resultado del procedimiento
    SELECT 
        usuarios_eliminados as usuarios_eliminados,
        error_count as errores,
        'Proceso completado' as estado;
        
END//

DELIMITER ;

-- =========================================================================
-- CREAR EVENTO PARA EJECUTAR EL PROCEDIMIENTO SEMANALMENTE
-- =========================================================================

-- Eliminar evento existente si existe
DROP EVENT IF EXISTS evento_limpieza_usuarios_semanal;

-- Crear evento que se ejecuta todos los lunes a las 2:00 AM
CREATE EVENT evento_limpieza_usuarios_semanal
ON SCHEDULE 
    EVERY 1 WEEK 
    STARTS DATE_ADD(DATE_ADD(CURDATE(), INTERVAL(2-WEEKDAY(CURDATE())) DAY), INTERVAL '02:00:00' HOUR_SECOND)
ON COMPLETION PRESERVE
ENABLE
COMMENT 'Limpieza automática de usuarios inactivos cada lunes a las 2:00 AM'
DO
    CALL LimpiarUsuariosInactivos();

-- =========================================================================
-- PROCEDIMIENTO PARA EJECUTAR LIMPIEZA MANUAL (OPCIONAL)
-- =========================================================================

DELIMITER //

DROP PROCEDURE IF EXISTS EjecutarLimpiezaManual//

CREATE PROCEDURE EjecutarLimpiezaManual()
BEGIN
    DECLARE resultado VARCHAR(500);
    
    -- Mostrar usuarios que serán eliminados
    SELECT 
        COUNT(*) as usuarios_a_eliminar,
        GROUP_CONCAT(NOMBRE_COMPLETO SEPARATOR ', ') as nombres
    FROM usuario 
    WHERE ESTADO = 'I' 
    AND TIMESTAMPDIFF(DAY, FECHA_ACTUALIZACION, NOW()) >= 7;
    
    -- Ejecutar limpieza
    CALL LimpiarUsuariosInactivos();
    
END//

DELIMITER ;

-- =========================================================================
-- CONSULTAS ÚTILES PARA MONITOREO
-- =========================================================================

-- Ver usuarios inactivos pendientes de eliminación
-- SELECT 
--     ID_USUARIO,
--     NOMBRE_COMPLETO,
--     USERNAME,
--     ESTADO,
--     FECHA_ACTUALIZACION,
--     TIMESTAMPDIFF(DAY, FECHA_ACTUALIZACION, NOW()) as dias_inactivo
-- FROM usuario 
-- WHERE ESTADO = 'I' 
-- ORDER BY FECHA_ACTUALIZACION ASC;

-- Ver log de limpiezas realizadas
-- SELECT * FROM log_limpieza_usuarios ORDER BY fecha DESC LIMIT 20;

-- Ver estadísticas de limpieza
-- SELECT 
--     DATE(fecha) as fecha_limpieza,
--     SUM(usuarios_procesados) as total_eliminados,
--     COUNT(*) as ejecuciones
-- FROM log_limpieza_usuarios 
-- WHERE tipo = 'INFO' AND usuarios_procesados > 0
-- GROUP BY DATE(fecha)
-- ORDER BY fecha_limpieza DESC;

-- =========================================================================
-- INSTRUCCIONES DE USO:
-- =========================================================================
-- 1. Ejecutar este script completo en MySQL
-- 2. El evento se ejecutará automáticamente cada lunes a las 2:00 AM
-- 3. Para ejecutar limpieza manual: CALL EjecutarLimpiezaManual();
-- 4. Para ver logs: SELECT * FROM log_limpieza_usuarios ORDER BY fecha DESC;
-- 5. Para deshabilitar el evento: ALTER EVENT evento_limpieza_usuarios_semanal DISABLE;
-- 6. Para habilitar el evento: ALTER EVENT evento_limpieza_usuarios_semanal ENABLE;
-- =========================================================================