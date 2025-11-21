<?php
require_once 'config/database.php';

// Crear el procedimiento almacenado correctamente
$procedureSQL = "
CREATE PROCEDURE ReactivarEmpleadosVacaciones()
BEGIN
    DECLARE done INT DEFAULT FALSE;
    DECLARE emp_id INT;
    DECLARE vacacion_id INT;
    
    DECLARE cur CURSOR FOR 
        SELECT ev.ID_EMPLEADO, ev.ID_VACACION
        FROM empleado_vacaciones ev
        WHERE ev.ESTADO = 'ACTIVA' 
        AND ev.FECHA_FIN = CURDATE()
        AND ev.REACTIVACION_AUTOMATICA = 'S';
    
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
    
    OPEN cur;
    
    read_loop: LOOP
        FETCH cur INTO emp_id, vacacion_id;
        IF done THEN
            LEAVE read_loop;
        END IF;
        
        UPDATE empleado 
        SET ACTIVO = 'S', ESTADO = 'A'
        WHERE ID_EMPLEADO = emp_id;
        
        UPDATE empleado_vacaciones 
        SET ESTADO = 'FINALIZADA'
        WHERE ID_VACACION = vacacion_id;
        
    END LOOP;
    
    CLOSE cur;
END";

try {
    $pdo->exec("DROP PROCEDURE IF EXISTS ReactivarEmpleadosVacaciones");
    $pdo->exec($procedureSQL);
    echo "✅ Procedimiento almacenado creado correctamente\n";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

?>
