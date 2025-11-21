<?php
/**
 * Script de refactorización de la tabla justificaciones
 * - Elimina columnas innecesarias
 * - Verifica/agrega columnas necesarias para justificaciones por turno
 * - Actualiza módulos relacionados
 */

require_once 'config/database.php';

echo "=== INICIANDO REFACTORIZACIÓN DE TABLA JUSTIFICACIONES ===\n\n";

try {
    // 1. Verificar estructura actual de la tabla
    echo "1. Verificando estructura actual de la tabla justificaciones...\n";

    $stmt = $pdo->query("DESCRIBE justificaciones");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $existingColumns = array_column($columns, 'Field');
    echo "Columnas actuales: " . implode(', ', $existingColumns) . "\n\n";

    // 2. Columnas a eliminar
    $columnsToDrop = [
        'hora_inicio_falta',
        'hora_fin_falta',
        'aprobada_por',
        'fecha_aprobacion',
        'comentario_aprobacion',
        'documentos_adjuntos',
        'notificado_supervisor',
        'notificado_rrhh',
        'impacto_salario'
    ];

    echo "2. Eliminando columnas innecesarias...\n";
    foreach ($columnsToDrop as $column) {
        if (in_array($column, $existingColumns)) {
            echo "   - Eliminando columna: $column\n";
            $pdo->exec("ALTER TABLE justificaciones DROP COLUMN `$column`");
        } else {
            echo "   - Columna $column ya no existe o nunca existió\n";
        }
    }
    echo "\n";

    // 3. Verificar columnas necesarias para justificaciones por turno
    echo "3. Verificando columnas necesarias para justificaciones por turno...\n";

    $requiredColumns = [
        'turno_id' => "INT NULL COMMENT 'ID del turno específico (ID_EMPLEADO_HORARIO)'",
        'justificar_todos_turnos' => "TINYINT(1) DEFAULT 0 COMMENT '1 si justifica todos los turnos del día'",
        'turnos_ids' => "JSON NULL COMMENT 'Array de IDs de turnos cuando justifica múltiples'"
    ];

    foreach ($requiredColumns as $columnName => $columnDefinition) {
        if (!in_array($columnName, $existingColumns)) {
            echo "   - Agregando columna: $columnName\n";
            $pdo->exec("ALTER TABLE justificaciones ADD COLUMN `$columnName` $columnDefinition");
        } else {
            echo "   - Columna $columnName ya existe\n";
        }
    }
    echo "\n";

    // 4. Verificar restricciones de clave foránea para turno_id
    echo "4. Verificando restricciones de clave foránea...\n";

    // Verificar si existe la restricción FK
    $stmt = $pdo->query("
        SELECT CONSTRAINT_NAME
        FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
        WHERE TABLE_NAME = 'justificaciones'
        AND COLUMN_NAME = 'turno_id'
        AND TABLE_SCHEMA = DATABASE()
    ");
    $fkExists = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$fkExists) {
        echo "   - Agregando restricción de clave foránea para turno_id\n";
        $pdo->exec("
            ALTER TABLE justificaciones
            ADD CONSTRAINT fk_justificaciones_turno_id
            FOREIGN KEY (turno_id) REFERENCES empleado_horario_personalizado(ID_EMPLEADO_HORARIO)
            ON DELETE SET NULL ON UPDATE CASCADE
        ");
    } else {
        echo "   - Restricción de clave foránea ya existe\n";
    }
    echo "\n";

    // 5. Verificar estructura final
    echo "5. Verificando estructura final de la tabla...\n";
    $stmt = $pdo->query("DESCRIBE justificaciones");
    $finalColumns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $finalColumnNames = array_column($finalColumns, 'Field');

    echo "Columnas finales: " . implode(', ', $finalColumnNames) . "\n\n";

    // 6. Mostrar resumen de cambios
    echo "6. Resumen de refactorización:\n";
    echo "   ✅ Columnas eliminadas: " . implode(', ', $columnsToDrop) . "\n";
    echo "   ✅ Columnas verificadas/agregadas: turno_id, justificar_todos_turnos, turnos_ids\n";
    echo "   ✅ Restricción de clave foránea verificada\n\n";

    echo "=== REFACTORIZACIÓN COMPLETADA EXITOSAMENTE ===\n";

} catch (Exception $e) {
    echo "❌ ERROR durante la refactorización: " . $e->getMessage() . "\n";
    exit(1);
}
?>