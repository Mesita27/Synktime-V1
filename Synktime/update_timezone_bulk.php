<?php
/**
 * Script para actualizar zona horaria en todos los archivos PHP principales
 * Ejecutar desde línea de comandos: php update_timezone_bulk.php
 */

echo "=== ACTUALIZANDO ZONA HORARIA A BOGOTÁ, COLOMBIA ===\n\n";

// Configurar zona horaria
date_default_timezone_set('America/Bogota');

// Archivos principales a actualizar
$archivos_principales = [
    'api/attendance/register-salida.php',
    'api/attendance/employees-available-fixed.php',
    'api/check-employee-schedule.php',
    'api/horarios-personalizados/register-attendance-personalized.php',
    'api/attendance/validate-attendance.php',
    'api/horarios-personalizados/list-employees.php',
    'api/horarios-personalizados/check-schedule.php',
    'api/horarios-personalizados/register-attendance.php',
    'dashboard-controller.php',
    'system_status.php'
];

echo "Archivos a procesar: " . count($archivos_principales) . "\n\n";

foreach ($archivos_principales as $archivo) {
    $ruta_completa = __DIR__ . '/' . $archivo;
    
    echo "Procesando: $archivo\n";
    
    if (!file_exists($ruta_completa)) {
        echo "  ⚠️  Archivo no existe: $ruta_completa\n";
        continue;
    }
    
    $contenido = file_get_contents($ruta_completa);
    $contenido_original = $contenido;
    
    // 1. Agregar require de timezone al inicio si no está presente
    if (!strpos($contenido, 'config/timezone.php') && !strpos($contenido, 'date_default_timezone_set')) {
        // Buscar la primera línea después del <?php
        $patron_inicio = '/(<\?php\s*(?:\/\*.*?\*\/\s*)?(?:\/\/.*?\n\s*)?)/s';
        if (preg_match($patron_inicio, $contenido, $matches)) {
            $inicio = $matches[1];
            $resto = substr($contenido, strlen($inicio));
            
            $nuevo_inicio = $inicio . "\n// Configurar zona horaria de Bogotá, Colombia\nrequire_once __DIR__ . '/../config/timezone.php';\n";
            
            // Ajustar el path relativo según la profundidad del archivo
            $niveles = substr_count($archivo, '/');
            $path_relativo = str_repeat('../', $niveles) . 'config/timezone.php';
            
            $nuevo_inicio = $inicio . "\n// Configurar zona horaria de Bogotá, Colombia\nrequire_once __DIR__ . '/" . $path_relativo . "';\n";
            
            $contenido = $nuevo_inicio . $resto;
            echo "  ✅ Agregado require de timezone\n";
        }
    }
    
    // 2. Reemplazar date('Y-m-d') por getBogotaDate()
    $contenido = preg_replace('/date\s*\(\s*[\'"]Y-m-d[\'"]\s*\)/', 'getBogotaDate()', $contenido);
    
    // 3. Reemplazar date('H:i:s') por getBogotaTime()
    $contenido = preg_replace('/date\s*\(\s*[\'"]H:i:s[\'"]\s*\)/', 'getBogotaTime()', $contenido);
    
    // 4. Reemplazar date('Y-m-d H:i:s') por getBogotaDateTime()
    $contenido = preg_replace('/date\s*\(\s*[\'"]Y-m-d H:i:s[\'"]\s*\)/', 'getBogotaDateTime()', $contenido);
    
    // 5. Reemplazar date('N') cuando no tiene parámetros adicionales
    $contenido = preg_replace('/date\s*\(\s*[\'"]N[\'"]\s*,\s*strtotime\s*\(\s*\$fecha\s*\)\s*\)/', "date('N')", $contenido);
    
    // 6. Reemplazar date_default_timezone_set por comentario si ya está presente
    $contenido = preg_replace('/date_default_timezone_set\s*\(\s*[\'"]America\/Bogota[\'"]\s*\)\s*;/', '// Zona horaria configurada en config/timezone.php', $contenido);
    
    // Verificar si hubo cambios
    if ($contenido !== $contenido_original) {
        if (file_put_contents($ruta_completa, $contenido)) {
            echo "  ✅ Archivo actualizado exitosamente\n";
        } else {
            echo "  ❌ Error al guardar el archivo\n";
        }
    } else {
        echo "  ℹ️  No se requirieron cambios\n";
    }
    
    echo "\n";
}

echo "=== PROCESO COMPLETADO ===\n";
echo "Zona horaria configurada: " . date_default_timezone_get() . "\n";
echo "Fecha/hora actual en Bogotá: " . date('Y-m-d H:i:s') . "\n";
?>