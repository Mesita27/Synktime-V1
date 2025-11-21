<?php
/**
 * Script de limpieza y reorganización del sistema
 * 
 * Este script mueve archivos redundantes a carpetas de respaldo
 * y organiza el sistema para mejorar su mantenimiento.
 */

// Iniciar la salida
echo "==============================================\n";
echo "SCRIPT DE LIMPIEZA Y REORGANIZACIÓN DEL SISTEMA\n";
echo "==============================================\n\n";

// Verificar permisos de escritura
if (!is_writable(__DIR__)) {
    echo "ERROR: No se tienen permisos de escritura en el directorio actual.\n";
    exit(1);
}

// Crear directorio de respaldo si no existe
$backupDir = __DIR__ . '/backup';
if (!file_exists($backupDir)) {
    echo "Creando directorio de respaldo...\n";
    if (!mkdir($backupDir, 0755, true)) {
        echo "ERROR: No se pudo crear el directorio de respaldo.\n";
        exit(1);
    }
}

// Crear subdirectorios de respaldo
$backupSubdirs = [
    'js', 'php', 'html', 'docs', 'api'
];

foreach ($backupSubdirs as $subdir) {
    $path = $backupDir . '/' . $subdir;
    if (!file_exists($path)) {
        echo "Creando subdirectorio $subdir...\n";
        if (!mkdir($path, 0755, true)) {
            echo "ERROR: No se pudo crear el subdirectorio $subdir.\n";
            exit(1);
        }
    }
}

// Lista de archivos JS a mover al respaldo
$jsFiles = [
    'assets/js/biometric-enrollment.js',
    'assets/js/biometric-fix.js',
    'assets/js/biometric-fix-v2.js',
    'assets/js/biometric-repair.js',
    'assets/js/biometric-integration.js',
    'assets/js/biometric-verifier.js',
    'assets/js/biometric-diagnostic.js',
    'assets/js/real-data-only.js',
    'assets/js/endpoint-fix.js',
    'assets/js/biometric-diagnostico.js'
];

// Lista de archivos PHP de demostración a mover al respaldo
$phpFiles = [
    'biometric-demo.php',
    'biometric-demo-simple.php',
    'biometric-functional-demo.php',
    'biometric-enrollment-new.php',
    'biometric-quick-setup.php',
    'comparison-demo.php',
    'debug-empleados.php',
    'debug-session.php'
];

// Lista de archivos HTML de diagnóstico a mover al respaldo
$htmlFiles = [
    'api-diagnostico.html',
    'biometric-diagnostico.html',
    'debug-modal-error.html',
    'json-debug.html',
    'test-api.html',
    'test-apis.html',
    'test-apis-fixed.html',
    'test-attendance.html',
    'test-biometric-enrollment.html',
    'test-biometric-simple.html',
    'test-biometric.html',
    'test-diagnostic.html',
    'test-employee-debug.html',
    'test-final-modal-fix.html',
    'test-json-debug.html',
    'test-pagination.html',
    'test-quick-fix.html',
    'test-refactoring-complete.html',
    'senior-debug-analysis.html',
    'correcciones-aplicadas.html',
    'executive-summary.html',
    'sql-error-correccion.html'
];

// Lista de archivos MD de documentación a mover al respaldo
$docFiles = [
    'ATTENDANCE_FIXES.md',
    'ATTENDANCE_FIXES_V2.md',
    'BIOMETRIC_SYSTEM_DOCS.md',
    'BUSQUEDA_PANTALLA_COMPLETA.md',
    'CORRECCIONES_APLICADAS.md',
    'CORRECCIONES_BIOMETRIC_DEMO.md',
    'CORRECCION_DEFINITIVA.md',
    'CORRECCION_DETECCION_PROBLEMAS.md',
    'DEBUGGING_COMPLETO.md',
    'DEBUGGING_FINAL.md',
    'DIAGNOSTICO_APIS.md',
    'DIAGNOSTICO_MODAL.md',
    'IMPLEMENTACION_COMPLETA.md',
    'OPTIMIZACIONES_BIOMETRICAS.md',
    'SENIOR_ANALYSIS_COMPLETE.md',
    'SISTEMA_COMPLETO_FINAL.md',
    'UNIFICACION_PORCENTAJES.md'
];

// Lista de archivos API a mover al respaldo
$apiFiles = [
    'api/biometric/mock-employees.php',
    'api/biometric/direct-employees.php',
    'api/biometric/get-employees.php',
    'api/biometric/get-employees-fixed.php',
    'api/biometric/temp-direct-employees.php',
    'api/test/simple-employees.php'
];

// Función para mover archivos al respaldo
function moveToBackup($fileList, $subdir) {
    global $backupDir;
    $count = 0;
    $backupPath = $backupDir . '/' . $subdir;
    
    foreach ($fileList as $file) {
        if (file_exists($file)) {
            $filename = basename($file);
            $destination = $backupPath . '/' . $filename;
            
            echo "Moviendo $file a backup/$subdir/\n";
            
            if (copy($file, $destination)) {
                // Verificar que la copia fue exitosa antes de eliminar el original
                if (file_exists($destination) && filesize($destination) == filesize($file)) {
                    // Conservamos una copia de seguridad pero no eliminamos los originales
                    // unlink($file); // Comentado para mayor seguridad
                    $count++;
                } else {
                    echo "ERROR: La copia de $file no se realizó correctamente.\n";
                }
            } else {
                echo "ERROR: No se pudo copiar $file a $destination.\n";
            }
        } else {
            echo "AVISO: El archivo $file no existe.\n";
        }
    }
    
    return $count;
}

// Mover archivos al respaldo
echo "\nMoviendo archivos JavaScript redundantes...\n";
$jsCount = moveToBackup($jsFiles, 'js');

echo "\nMoviendo archivos PHP de demostración...\n";
$phpCount = moveToBackup($phpFiles, 'php');

echo "\nMoviendo archivos HTML de diagnóstico...\n";
$htmlCount = moveToBackup($htmlFiles, 'html');

echo "\nMoviendo archivos de documentación...\n";
$docCount = moveToBackup($docFiles, 'docs');

echo "\nMoviendo archivos API redundantes...\n";
$apiCount = moveToBackup($apiFiles, 'api');

// Renombrar archivo principal unificado
$sourceFile = 'assets/js/biometric-system-unified.js';
$targetFile = 'assets/js/biometric-system.js';

echo "\nRenombrando $sourceFile a $targetFile...\n";
if (file_exists($sourceFile)) {
    if (copy($sourceFile, $targetFile)) {
        echo "Archivo renombrado exitosamente.\n";
        // No eliminamos el original para mayor seguridad
        // unlink($sourceFile);
    } else {
        echo "ERROR: No se pudo renombrar el archivo.\n";
    }
} else {
    echo "ERROR: El archivo $sourceFile no existe.\n";
}

// Actualizar las referencias en biometric-enrollment.php
$enrollmentFile = 'biometric-enrollment.php';
if (file_exists($enrollmentFile)) {
    echo "\nActualizando referencias en $enrollmentFile...\n";
    $content = file_get_contents($enrollmentFile);
    $content = str_replace('biometric-system-unified.js', 'biometric-system.js', $content);
    
    if (file_put_contents($enrollmentFile, $content)) {
        echo "Referencias actualizadas exitosamente.\n";
    } else {
        echo "ERROR: No se pudieron actualizar las referencias.\n";
    }
} else {
    echo "ERROR: El archivo $enrollmentFile no existe.\n";
}

// Resultados finales
echo "\n==============================================\n";
echo "RESUMEN DE LA OPERACIÓN\n";
echo "==============================================\n";
echo "Archivos JavaScript copiados: $jsCount\n";
echo "Archivos PHP copiados: $phpCount\n";
echo "Archivos HTML copiados: $htmlCount\n";
echo "Archivos de documentación copiados: $docCount\n";
echo "Archivos API copiados: $apiCount\n";
echo "Total de archivos copiados: " . ($jsCount + $phpCount + $htmlCount + $docCount + $apiCount) . "\n";
echo "==============================================\n";
echo "\nOperación completada. Los archivos originales siguen en su lugar como medida de seguridad.\n";
echo "Para eliminarlos definitivamente, descomente las líneas 'unlink(\$file)' en el código.\n";
echo "Para probar el sistema antes de eliminar, modifique biometric-enrollment.php para usar biometric-system.js.\n";

// Fin del script
?>
