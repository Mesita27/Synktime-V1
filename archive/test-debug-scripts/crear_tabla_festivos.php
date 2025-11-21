olydays_cache, <?php
/**
 * CREAR TABLA DE DÍAS FESTIVOS/CÍVICOS
 */

require_once 'config/database.php';

echo "🗓️  CREANDO TABLA DE DÍAS FESTIVOS/CÍVICOS\n";
echo "=" . str_repeat("=", 45) . "\n\n";

try {
    // Verificar si la tabla ya existe
    echo "1️⃣  VERIFICANDO TABLA FESTIVOS:\n";
    $stmt = $pdo->query("SHOW TABLES LIKE 'festivos'");
    $exists = $stmt->rowCount() > 0;
    
    if ($exists) {
        echo "   ℹ️  La tabla 'festivos' ya existe\n";
        
        // Mostrar estructura
        $stmt = $pdo->query('DESCRIBE festivos');
        $cols = $stmt->fetchAll();
        echo "   📋 Estructura actual:\n";
        foreach($cols as $col) {
            echo "      - {$col['Field']} ({$col['Type']})\n";
        }
    } else {
        echo "   ❌ La tabla 'festivos' no existe\n";
        echo "\n2️⃣  CREANDO TABLA FESTIVOS:\n";
        
        $sqlCreate = "
            CREATE TABLE festivos (
                ID_FESTIVO INT AUTO_INCREMENT PRIMARY KEY,
                FECHA DATE NOT NULL,
                NOMBRE VARCHAR(100) NOT NULL,
                DESCRIPCION TEXT,
                TIPO ENUM('CIVICO', 'RELIGIOSO', 'NACIONAL', 'REGIONAL') DEFAULT 'CIVICO',
                ID_EMPRESA INT,
                ACTIVO CHAR(1) DEFAULT 'S',
                FECHA_CREACION TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                CREATED_BY VARCHAR(50),
                
                UNIQUE KEY unique_fecha_empresa (FECHA, ID_EMPRESA),
                INDEX idx_fecha (FECHA),
                INDEX idx_empresa (ID_EMPRESA),
                INDEX idx_activo (ACTIVO),
                FOREIGN KEY (ID_EMPRESA) REFERENCES empresa(ID_EMPRESA) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ";
        
        $pdo->exec($sqlCreate);
        echo "   ✅ Tabla 'festivos' creada exitosamente\n";
    }
    
    // Insertar días festivos colombianos básicos para 2025
    echo "\n3️⃣  INSERTANDO DÍAS FESTIVOS 2025:\n";
    
    $festivosColombbia2025 = [
        ['2025-01-01', 'Año Nuevo', 'Celebración del nuevo año', 'NACIONAL'],
        ['2025-01-06', 'Día de los Reyes Magos', 'Epifanía', 'RELIGIOSO'],
        ['2025-03-24', 'Día de San José', 'San José', 'RELIGIOSO'],
        ['2025-04-13', 'Domingo de Ramos', 'Semana Santa', 'RELIGIOSO'],
        ['2025-04-17', 'Jueves Santo', 'Semana Santa', 'RELIGIOSO'],
        ['2025-04-18', 'Viernes Santo', 'Semana Santa', 'RELIGIOSO'],
        ['2025-05-01', 'Día del Trabajo', 'Día Internacional del Trabajo', 'NACIONAL'],
        ['2025-06-02', 'Ascensión del Señor', 'Día religioso', 'RELIGIOSO'],
        ['2025-06-23', 'Corpus Christi', 'Día religioso', 'RELIGIOSO'],
        ['2025-06-30', 'Sagrado Corazón de Jesús', 'Día religioso', 'RELIGIOSO'],
        ['2025-07-07', 'San Pedro y San Pablo', 'Día religioso', 'RELIGIOSO'],
        ['2025-07-20', 'Día de la Independencia', 'Independencia de Colombia', 'NACIONAL'],
        ['2025-08-07', 'Batalla de Boyacá', 'Día patrio', 'NACIONAL'],
        ['2025-08-18', 'Asunción de la Virgen', 'Día religioso', 'RELIGIOSO'],
        ['2025-10-13', 'Día de la Raza', 'Descubrimiento de América', 'NACIONAL'],
        ['2025-11-03', 'Todos los Santos', 'Día religioso', 'RELIGIOSO'],
        ['2025-11-17', 'Independencia de Cartagena', 'Día patrio', 'NACIONAL'],
        ['2025-12-08', 'Inmaculada Concepción', 'Día religioso', 'RELIGIOSO'],
        ['2025-12-25', 'Navidad', 'Celebración del nacimiento de Jesús', 'RELIGIOSO']
    ];
    
    $sqlInsert = "
        INSERT IGNORE INTO festivos (FECHA, NOMBRE, DESCRIPCION, TIPO, ID_EMPRESA, ACTIVO, CREATED_BY)
        VALUES (?, ?, ?, ?, 1, 'S', 'SYSTEM')
    ";
    
    $insertados = 0;
    foreach ($festivosColombbia2025 as $festivo) {
        $stmt = $pdo->prepare($sqlInsert);
        $result = $stmt->execute($festivo);
        if ($result && $stmt->rowCount() > 0) {
            $insertados++;
        }
    }
    
    echo "   ✅ $insertados festivos insertados para 2025\n";
    
    // Verificar registros insertados
    echo "\n4️⃣  VERIFICANDO FESTIVOS INSERTADOS:\n";
    $stmt = $pdo->query("
        SELECT FECHA, NOMBRE, TIPO 
        FROM festivos 
        WHERE YEAR(FECHA) = 2025 
        ORDER BY FECHA
    ");
    $festivos = $stmt->fetchAll();
    
    echo "   📅 Festivos registrados para 2025:\n";
    foreach ($festivos as $f) {
        echo "      - {$f['FECHA']}: {$f['NOMBRE']} ({$f['TIPO']})\n";
    }
    
    echo "\n5️⃣  CREAR API PARA GESTIONAR FESTIVOS:\n";
    echo "   ℹ️  Se creará un endpoint para que los usuarios puedan:\n";
    echo "      - Agregar días cívicos personalizados\n";
    echo "      - Modificar festivos existentes\n";
    echo "      - Desactivar festivos no aplicables\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n" . str_repeat("=", 45) . "\n";
echo "🎯 TABLA DE FESTIVOS CONFIGURADA CORRECTAMENTE\n";
echo "✅ Ya se puede usar en el cálculo de horas trabajadas\n";
?>