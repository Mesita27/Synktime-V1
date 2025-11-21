<?php
// config/database.php

// Configurar zona horaria de Bogotá, Colombia
date_default_timezone_set('America/Bogota');

if (!function_exists('synktime_env')) {
    function synktime_env(string $key, $default = null): mixed
    {
        $value = getenv($key);

        if ($value === false || $value === '') {
            if (array_key_exists($key, $_ENV) && $_ENV[$key] !== '') {
                $value = $_ENV[$key];
            } elseif (array_key_exists($key, $_SERVER) && $_SERVER[$key] !== '') {
                $value = $_SERVER[$key];
            } else {
                $value = $default;
            }
        }

        return $value;
    }
}

if (!function_exists('synktime_db_config')) {
    function synktime_db_config(): array
    {
        static $config = null;

        if ($config === null) {
            $config = [
                'host' => synktime_env('DB_HOST', 'synktime-db'),
                'port' => synktime_env('DB_PORT', '3306'),
                'dbname' => synktime_env('DB_NAME', 'synktime'),
                'username' => synktime_env('DB_USER', 'root'),
                'password' => (string) synktime_env('DB_PASSWORD', ''),
            ];
        }

        return $config;
    }
}

if (!function_exists('synktime_get_pdo')) {
    function synktime_get_pdo(): PDO
    {
        static $pdoInstance = null;

        if ($pdoInstance === null) {
            $cfg = synktime_db_config();
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=utf8',
                $cfg['host'],
                $cfg['port'],
                $cfg['dbname']
            );

            $pdoInstance = new PDO($dsn, $cfg['username'], $cfg['password']);
            $pdoInstance->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdoInstance->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $pdoInstance->exec("SET time_zone = '-05:00'");
        }

        return $pdoInstance;
    }
}

if (!function_exists('synktime_get_mysqli')) {
    function synktime_get_mysqli(): mysqli
    {
        $cfg = synktime_db_config();

        $mysqli = mysqli_init();
        $mysqli->options(MYSQLI_INIT_COMMAND, "SET time_zone = '-05:00'");

        if (!@$mysqli->real_connect(
            $cfg['host'],
            $cfg['username'],
            $cfg['password'],
            $cfg['dbname'],
            (int) $cfg['port']
        )) {
            throw new RuntimeException('Error conectando a MySQL: ' . $mysqli->connect_error);
        }

        return $mysqli;
    }
}

try {
    $pdo = synktime_get_pdo();
    $conn = $pdo; // Compatibilidad legacy
} catch (Throwable $e) {
    error_log('Error de conexión a la base de datos: ' . $e->getMessage());
    echo 'Error de conexión: ' . $e->getMessage();
    die();
}
?>
