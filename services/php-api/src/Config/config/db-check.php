<?php
/**
 * Script de verificación de base de datos
 * Este script verifica que las tablas y datos existen realmente
 */

// Activar reporte de errores
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Encabezados
header('Content-Type: text/html; charset=utf-8');

// Incluir configuración de base de datos
require_once './config/database.php';

echo "<h1>Verificación de Base de Datos</h1>";
echo "<hr>";

// Verificar conexión
try {
    echo "<h2>Conexión a Base de Datos</h2>";
    echo "<pre>";
    echo "Host: $host\n";
    echo "Base de datos: $dbname\n";
    echo "Usuario: $username\n";
    echo "</pre>";
    
    echo "<div style='color:green;'>✅ Conexión establecida correctamente</div>";
} catch (Exception $e) {
    echo "<div style='color:red;'>❌ Error de conexión: " . $e->getMessage() . "</div>";
    die();
}

// Verificar tablas
echo "<h2>Tablas Requeridas</h2>";
$tablesToCheck = ['empleado', 'empleados', 'establecimiento', 'sede', 'biometric_enrollment'];

foreach ($tablesToCheck as $table) {
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        $exists = $stmt->rowCount() > 0;
        
        echo "<div style='" . ($exists ? "color:green;" : "color:orange;") . "'>";
        echo $exists ? "✅ " : "⚠️ ";
        echo "Tabla <strong>$table</strong>: " . ($exists ? "Existe" : "No existe") . "</div>";
        
        // Si la tabla existe, contar registros
        if ($exists) {
            $countStmt = $pdo->query("SELECT COUNT(*) as total FROM $table");
            $count = $countStmt->fetch()['total'];
            echo "<div style='margin-left:20px;'>";
            echo "Registros: <strong>$count</strong>";
            
            if ($count == 0) {
                echo " <span style='color:orange;'>(⚠️ Sin datos)</span>";
            }
            
            echo "</div>";
            
            // Si es la tabla de empleados, mostrar los primeros 5
            if ($table == 'empleado' || $table == 'empleados') {
                $tableToUse = $table;
                $sampleStmt = $pdo->query("SELECT * FROM $tableToUse LIMIT 5");
                $samples = $sampleStmt->fetchAll();
                
                if (count($samples) > 0) {
                    echo "<div style='margin-left:20px; margin-top:10px;'>";
                    echo "<strong>Muestra de empleados:</strong>";
                    echo "<table border='1' cellpadding='3' cellspacing='0' style='border-collapse:collapse; margin-top:5px;'>";
                    echo "<tr>";
                    
                    // Headers
                    foreach (array_keys($samples[0]) as $key) {
                        echo "<th>$key</th>";
                    }
                    echo "</tr>";
                    
                    // Data
                    foreach ($samples as $row) {
                        echo "<tr>";
                        foreach ($row as $value) {
                            echo "<td>" . htmlspecialchars($value) . "</td>";
                        }
                        echo "</tr>";
                    }
                    
                    echo "</table>";
                    echo "</div>";
                }
            }
        }
    } catch (Exception $e) {
        echo "<div style='color:red;'>❌ Error verificando $table: " . $e->getMessage() . "</div>";
    }
}

// Verificar consulta principal
echo "<h2>Consulta Principal</h2>";
try {
    // Determinar tabla de empleados a usar
    $empleadosTable = 'empleado'; // Default
    $stmt = $pdo->query("SHOW TABLES LIKE 'empleados'");
    if ($stmt->rowCount() > 0) {
        $empleadosTable = 'empleados';
    }
    
    echo "<div>Usando tabla: <strong>$empleadosTable</strong></div>";
    
    // Consulta similar a la de direct-employees.php
    $sql = "SELECT 
                e.id as ID_EMPLEADO,
                e.codigo,
                e.nombre,
                e.apellido,
                e.id_establecimiento,
                est.nombre AS nombre_establecimiento,
                s.nombre AS nombre_sede,
                b.facial_enrolled,
                b.fingerprint_enrolled,
                b.last_updated
            FROM 
                $empleadosTable e
            LEFT JOIN establecimiento est ON e.id_establecimiento = est.ID_ESTABLECIMIENTO
            LEFT JOIN sede s ON est.ID_SEDE = s.ID_SEDE
            LEFT JOIN biometric_enrollment b ON e.id = b.id_empleado
            WHERE 
                e.estado = 'A'
            LIMIT 10";
    
    echo "<pre>" . htmlspecialchars($sql) . "</pre>";
    
    $stmt = $pdo->query($sql);
    $results = $stmt->fetchAll();
    
    echo "<div>";
    echo "Resultados: <strong>" . count($results) . "</strong>";
    echo "</div>";
    
    if (count($results) > 0) {
        echo "<table border='1' cellpadding='3' cellspacing='0' style='border-collapse:collapse; margin-top:10px;'>";
        echo "<tr>";
        echo "<th>ID</th>";
        echo "<th>Código</th>";
        echo "<th>Nombre</th>";
        echo "<th>Apellido</th>";
        echo "<th>Establecimiento</th>";
        echo "<th>Sede</th>";
        echo "<th>Facial</th>";
        echo "<th>Huella</th>";
        echo "</tr>";
        
        foreach ($results as $row) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['ID_EMPLEADO']) . "</td>";
            echo "<td>" . htmlspecialchars($row['codigo']) . "</td>";
            echo "<td>" . htmlspecialchars($row['nombre']) . "</td>";
            echo "<td>" . htmlspecialchars($row['apellido']) . "</td>";
            echo "<td>" . htmlspecialchars($row['nombre_establecimiento']) . "</td>";
            echo "<td>" . htmlspecialchars($row['nombre_sede']) . "</td>";
            echo "<td>" . ($row['facial_enrolled'] ? "✓" : "✗") . "</td>";
            echo "<td>" . ($row['fingerprint_enrolled'] ? "✓" : "✗") . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    } else {
        echo "<div style='color:orange;'>⚠️ No se encontraron resultados con la consulta</div>";
    }
} catch (Exception $e) {
    echo "<div style='color:red;'>❌ Error en consulta: " . $e->getMessage() . "</div>";
}

// Ver logs recientes
echo "<h2>Logs Recientes</h2>";

function getPhpErrorLogPath() {
    $path = ini_get('error_log');
    if (empty($path)) {
        // Intentar encontrar la ubicación por defecto
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            return 'C:/xampp/php/logs/php_error_log';
        } else {
            return '/var/log/apache2/error.log';
        }
    }
    return $path;
}

$logPath = getPhpErrorLogPath();
echo "<div>Ruta de log: <strong>$logPath</strong></div>";

try {
    if (file_exists($logPath) && is_readable($logPath)) {
        $logs = array_slice(file($logPath), -30); // Últimas 30 líneas
        echo "<pre style='max-height:200px;overflow:auto;background:#f5f5f5;padding:10px;'>";
        foreach ($logs as $log) {
            if (strpos($log, 'direct-employees') !== false) {
                echo "<span style='color:blue;'>" . htmlspecialchars($log) . "</span>";
            } else {
                echo htmlspecialchars($log);
            }
        }
        echo "</pre>";
    } else {
        echo "<div style='color:orange;'>⚠️ No se puede acceder al archivo de log</div>";
    }
} catch (Exception $e) {
    echo "<div style='color:red;'>❌ Error al leer logs: " . $e->getMessage() . "</div>";
}
?>
