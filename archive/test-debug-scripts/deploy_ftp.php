<?php
/**
 * DEPLOYMENT VIA FTP/SFTP - SYNKTIME
 * Sube archivos al servidor LAMP via FTP o SFTP
 */

// Configuración del servidor
$config = [
    'method' => 'sftp', // 'ftp' o 'sftp'
    'host' => 'tu_servidor_ip',
    'username' => 'tu_usuario',
    'password' => 'tu_password',
    'port' => 22, // 21 para FTP, 22 para SFTP
    'remote_path' => '/var/www/html/synktime',
    'local_path' => __DIR__
];

// Archivos y carpetas a excluir
$exclude = [
    'deploy_*.php',
    'deploy_*.bat',
    'deploy_*.sh',
    '*.log',
    '*.tmp',
    'test_*',
    'debug_*',
    '.git',
    'node_modules'
];

echo "========================================\n";
echo " DEPLOYMENT A SERVIDOR LAMP - SYNKTIME\n";
echo "========================================\n\n";

echo "Configuración:\n";
echo "- Método: " . strtoupper($config['method']) . "\n";
echo "- Servidor: {$config['host']}:{$config['port']}\n";
echo "- Usuario: {$config['username']}\n";
echo "- Ruta remota: {$config['remote_path']}\n";
echo "- Ruta local: {$config['local_path']}\n\n";

// Confirmar deployment
echo "¿Deseas continuar con el deployment? (s/N): ";
$confirm = trim(fgets(STDIN));
if (strtolower($confirm) !== 's') {
    echo "Deployment cancelado.\n";
    exit(0);
}

echo "\n=== INICIANDO DEPLOYMENT ===\n";

try {
    if ($config['method'] === 'sftp') {
        deployViaSFTP($config, $exclude);
    } else {
        deployViaFTP($config, $exclude);
    }
    
    echo "\n✅ Deployment completado exitosamente\n";
    
} catch (Exception $e) {
    echo "\n❌ Error durante deployment: " . $e->getMessage() . "\n";
    exit(1);
}

function deployViaSFTP($config, $exclude) {
    if (!extension_loaded('ssh2')) {
        throw new Exception('Extensión SSH2 no está instalada');
    }
    
    echo "1. Conectando via SFTP...\n";
    $connection = ssh2_connect($config['host'], $config['port']);
    
    if (!$connection) {
        throw new Exception('No se pudo conectar al servidor');
    }
    
    if (!ssh2_auth_password($connection, $config['username'], $config['password'])) {
        throw new Exception('Autenticación fallida');
    }
    
    $sftp = ssh2_sftp($connection);
    if (!$sftp) {
        throw new Exception('No se pudo inicializar SFTP');
    }
    
    echo "2. Creando backup...\n";
    $backup_cmd = "cd /var/www/html && tar -czf synktime_backup_$(date +%Y%m%d_%H%M%S).tar.gz synktime/ 2>/dev/null || echo 'Backup omitido'";
    ssh2_exec($connection, $backup_cmd);
    
    echo "3. Creando directorio remoto...\n";
    ssh2_exec($connection, "mkdir -p {$config['remote_path']}");
    
    echo "4. Subiendo archivos...\n";
    uploadDirectory($sftp, $config['local_path'], $config['remote_path'], $exclude);
    
    echo "5. Configurando permisos...\n";
    ssh2_exec($connection, "chmod -R 755 {$config['remote_path']} && chown -R www-data:www-data {$config['remote_path']}");
}

function deployViaFTP($config, $exclude) {
    echo "1. Conectando via FTP...\n";
    $connection = ftp_connect($config['host'], $config['port']);
    
    if (!$connection) {
        throw new Exception('No se pudo conectar al servidor FTP');
    }
    
    if (!ftp_login($connection, $config['username'], $config['password'])) {
        throw new Exception('Autenticación FTP fallida');
    }
    
    ftp_pasv($connection, true);
    
    echo "2. Subiendo archivos...\n";
    uploadDirectoryFTP($connection, $config['local_path'], $config['remote_path'], $exclude);
    
    ftp_close($connection);
}

function uploadDirectory($sftp, $localDir, $remoteDir, $exclude) {
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($localDir),
        RecursiveIteratorIterator::SELF_FIRST
    );
    
    foreach ($iterator as $file) {
        if ($file->isDir()) {
            continue;
        }
        
        $relativePath = str_replace($localDir, '', $file->getPathname());
        $relativePath = str_replace('\\', '/', $relativePath);
        
        // Verificar exclusiones
        $skip = false;
        foreach ($exclude as $pattern) {
            if (fnmatch($pattern, basename($file->getPathname()))) {
                $skip = true;
                break;
            }
        }
        
        if ($skip) {
            continue;
        }
        
        $remotePath = $remoteDir . $relativePath;
        $remoteDir_for_file = dirname($remotePath);
        
        // Crear directorio si no existe
        ssh2_exec($sftp, "mkdir -p '$remoteDir_for_file'");
        
        if (ssh2_scp_send($sftp, $file->getPathname(), $remotePath)) {
            echo "  ✓ {$relativePath}\n";
        } else {
            echo "  ✗ Error: {$relativePath}\n";
        }
    }
}

function uploadDirectoryFTP($connection, $localDir, $remoteDir, $exclude) {
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($localDir),
        RecursiveIteratorIterator::SELF_FIRST
    );
    
    foreach ($iterator as $file) {
        if ($file->isDir()) {
            continue;
        }
        
        $relativePath = str_replace($localDir, '', $file->getPathname());
        $relativePath = str_replace('\\', '/', $relativePath);
        
        // Verificar exclusiones
        $skip = false;
        foreach ($exclude as $pattern) {
            if (fnmatch($pattern, basename($file->getPathname()))) {
                $skip = true;
                break;
            }
        }
        
        if ($skip) {
            continue;
        }
        
        $remotePath = $remoteDir . $relativePath;
        
        if (ftp_put($connection, $remotePath, $file->getPathname(), FTP_BINARY)) {
            echo "  ✓ {$relativePath}\n";
        } else {
            echo "  ✗ Error: {$relativePath}\n";
        }
    }
}
?>