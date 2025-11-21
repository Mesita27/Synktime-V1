-- ENCONTRAR NOMBRE EXACTO DEL FK CONSTRAINT
-- Ejecutar estos comandos para ver el nombre real

-- Opción 1: Ver la estructura completa de la tabla
SHOW CREATE TABLE asistencia;

-- Opción 2: Ver solo los índices 
SHOW INDEX FROM asistencia WHERE Key_name LIKE '%horario%';

-- Opción 3: Intentar con nombres comunes de constraints
-- (Ejecutar uno por uno hasta que funcione)

-- Intento 1: Nombre genérico auto-generado
-- ALTER TABLE asistencia DROP FOREIGN KEY asistencia_ibfk_1;

-- Intento 2: Si hay múltiples constraints
-- ALTER TABLE asistencia DROP FOREIGN KEY asistencia_ibfk_2;

-- Intento 3: Si hay múltiples constraints  
-- ALTER TABLE asistencia DROP FOREIGN KEY asistencia_ibfk_3;

-- Intento 4: Otro nombre común
-- ALTER TABLE asistencia DROP FOREIGN KEY asistencia_horario_fk;

-- DESPUÉS DE EJECUTAR SHOW CREATE TABLE, verás algo como:
-- CONSTRAINT `nombre_real_del_constraint` FOREIGN KEY (`ID_HORARIO`) REFERENCES `horario` (`ID_HORARIO`)
-- Usa ese nombre_real_del_constraint en el comando DROP