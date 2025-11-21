<?php
// Autoloader básico para PhpSpreadsheet y dependencias
spl_autoload_register(function ($class) {
    // Autoloader para PhpSpreadsheet
    $prefix = 'PhpOffice\\PhpSpreadsheet\\';
    $base_dir = __DIR__ . '/phpoffice/phpspreadsheet/PhpSpreadsheet/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) === 0) {
        $relative_class = substr($class, $len);
        $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
        if (file_exists($file)) {
            require $file;
        }
        return;
    }

    // Autoloader para PSR Simple Cache
    $psr_prefix = 'Psr\\SimpleCache\\';
    $psr_base_dir = __DIR__ . '/psr/simple-cache/';

    $psr_len = strlen($psr_prefix);
    if (strncmp($psr_prefix, $class, $psr_len) === 0) {
        $relative_class = substr($class, $psr_len);
        $file = $psr_base_dir . str_replace('\\', '/', $relative_class) . '.php';
        if (file_exists($file)) {
            require $file;
        }
        return;
    }

    // Autoloader para ZipStream
    $zip_prefix = 'ZipStream\\';
    $zip_base_dir = __DIR__ . '/maennchen/zipstream-php/';

    $zip_len = strlen($zip_prefix);
    if (strncmp($zip_prefix, $class, $zip_len) === 0) {
        $relative_class = substr($class, $zip_len);
        $file = $zip_base_dir . str_replace('\\', '/', $relative_class) . '.php';
        if (file_exists($file)) {
            require $file;
        }
        return;
    }
});
?>