<?php
try {
    require_once 'config/database.php';
    
    echo "🔧 REFACTORIZANDO TABLA JUSTIFICACIONES - ELIMINANDO COLUMNAS\n";
    echo "============================================================\n\n";
    
    // Lista de columnas a eliminar
    $columnasAEliminar = [
        'fecha_justificacion',
        'estado',
        'aprobada_por',
        'fecha_aprobacion',
        'comentario_aprobacion',
        'documentos_adjuntos',
        'notificado_supervisor',
        'notificado_rrhh',
        'impacto_salario',
        'updated_at',
        'deleted_at'
    ];
    
    // Lista de columnas a agregar
    $columnasAAgregar = [
        'turno_id INT(11) DEFAULT NULL COMMENT "ID del turno específico (ID_EMPLEADO_HORARIO)"',
        'justificar_todos_turnos TINYINT(1) DEFAULT 0 COMMENT "1 si justifica todos los turnos del día"',
        'turnos_ids JSON DEFAULT NULL COMMENT "Array de IDs de turnos cuando justifica múltiples"'
    ];
    
    echo "1. 🗑️ ELIMINANDO COLUMNAS INNECESARIAS:\n";
    foreach ($columnasAEliminar as $columna) {
        try {
            $sql = "ALTER TABLE justificaciones DROP COLUMN `$columna`";
            $pdo->exec($sql);
            echo "   ✅ Eliminada: $columna\n";
        } catch (Exception $e) {
            if (strpos($e->getMessage(), "doesn't exist") !== false) {
                echo "   ℹ️  $columna: No existe\n";
            } else {
                echo "   ⚠️  $columna: " . $e->getMessage() . "\n";
            }
        }
    }
    
    echo "\n2. ➕ AGREGANDO NUEVAS COLUMNAS:\n";
    foreach ($columnasAAgregar as $columna) {
        try {
            $sql = "ALTER TABLE justificaciones ADD COLUMN $columna";
            $pdo->exec($sql);
            $nombreColumna = explode(' ', $columna)[0];
            echo "   ✅ Agregada: $nombreColumna\n";
        } catch (Exception $e) {
            $nombreColumna = explode(' ', $columna)[0];
            if (strpos($e->getMessage(), "Duplicate column") !== false) {
                echo "   ℹ️  $nombreColumna: Ya existe\n";
            } else {
                echo "   ⚠️  $nombreColumna: " . $e->getMessage() . "\n";
            }
        }
    }
    
    echo "\n3. 🔗 AGREGANDO FOREIGN KEY PARA TURNO:\n";
    try {
        $sql = "ALTER TABLE justificaciones ADD CONSTRAINT fk_justificaciones_turno 
                FOREIGN KEY (turno_id) REFERENCES empleado_horario_personalizado(ID_EMPLEADO_HORARIO) 
                ON DELETE SET NULL ON UPDATE CASCADE";
        $pdo->exec($sql);
        echo "   ✅ Foreign key agregada para turno_id\n";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), "already exists") !== false) {
            echo "   ℹ️  Foreign key para turno_id: Ya existe\n";
        } else {
            echo "   ⚠️  Foreign key: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n4. 📊 ESTRUCTURA FINAL DE LA TABLA:\n";
    $stmt = $pdo->query("DESCRIBE justificaciones");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $extra = $row['Extra'] ? " ({$row['Extra']})" : "";
        echo "   - {$row['Field']}: {$row['Type']} {$row['Null']} {$row['Key']}{$extra}\n";
    }
    
    // Contar registros
    $stmt = $pdo->query("SELECT COUNT(*) FROM justificaciones");
    $count = $stmt->fetchColumn();
    echo "\n📈 Total registros: $count\n";
    
    echo "\n🎉 REFACTORIZACIÓN COMPLETADA EXITOSAMENTE\n";
    echo "La tabla justificaciones ahora tiene solo las columnas necesarias.\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>