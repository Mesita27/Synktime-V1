<?php
header('Content-Type: application/json');

// Directorio de uploads
$uploads_dir = __DIR__ . '/../uploads/';

$files = [];
if (is_dir($uploads_dir)) {
    $dir_contents = scandir($uploads_dir);
    foreach ($dir_contents as $file) {
        if ($file !== '.' && $file !== '..' && is_file($uploads_dir . $file)) {
            // Solo incluir archivos de imagen recientes
            if (preg_match('/\.(jpg|jpeg|png)$/i', $file)) {
                $files[] = $file;
            }
        }
    }
    
    // Ordenar por fecha de modificación (más recientes primero)
    usort($files, function($a, $b) use ($uploads_dir) {
        return filemtime($uploads_dir . $b) - filemtime($uploads_dir . $a);
    });
}

echo json_encode($files);
?>
