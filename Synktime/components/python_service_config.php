<?php
/**
 * Global configuration bootstrap for the Python biometric microservice.
 *
 * Exposes window.SYNKTIME.pythonService with runtime URLs derived from
 * environment variables or sensible fallbacks.
 */

declare(strict_types=1);

// Función para cargar variables de entorno desde .env
if (!function_exists('load_env_file')) {
    function load_env_file(string $filePath): void
    {
        if (!file_exists($filePath)) {
            return;
        }

        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if (strpos($line, '#') === 0) {
                continue; // Ignorar comentarios
            }

            $parts = explode('=', $line, 2);
            if (count($parts) === 2) {
                $key = trim($parts[0]);
                $value = trim($parts[1]);
                // Remover comillas si existen
                $value = trim($value, '"\'');
                putenv("$key=$value");
                $_ENV[$key] = $value;
                $_SERVER[$key] = $value;
            }
        }
    }
}

// Determinar qué archivo .env cargar basado en ENVIRONMENT
$environment = getenv('ENVIRONMENT') ?: ($_ENV['ENVIRONMENT'] ?? 'production');
$envFile = __DIR__ . '/../.env.' . $environment;

if (file_exists($envFile)) {
    load_env_file($envFile);
} else {
    // Fallback al .env por defecto
    load_env_file(__DIR__ . '/../.env');
}

$environment = getenv('ENVIRONMENT') ?: ($_ENV['ENVIRONMENT'] ?? 'production');

// URLs por defecto basadas en el entorno
if ($environment === 'local') {
    $defaultBaseUrl = 'http://localhost:8000';
} else {
    $defaultBaseUrl = 'https://kromez.dev/python-service';
}
$envKeys = [
    'PYTHON_SERVICE_URL',
    'PYTHON_SERVICE_BASE_URL',
    // Compatibilidad con variable previa utilizada en docker-compose
    'PY_SERVICE_URL',
    'PY_SERVICE_BASE_URL'
];
$baseUrl = null;

foreach ($envKeys as $key) {
    $value = getenv($key);

    if ($value === false || $value === '') {
        if (isset($_ENV[$key]) && $_ENV[$key] !== '') {
            $value = $_ENV[$key];
        } elseif (isset($_SERVER[$key]) && $_SERVER[$key] !== '') {
            $value = $_SERVER[$key];
        }
    }

    if (!empty($value)) {
        $baseUrl = $value;
        break;
    }
}

if (empty($baseUrl)) {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? '';

    if (!empty($host)) {
        $baseUrl = $scheme . '://' . $host;
        // Solo asumir el puerto 8000 de forma automática para hosts de loopback
        $isLoopbackHost = in_array($host, ['localhost', '127.0.0.1'], true);
        if (!$isLoopbackHost && filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $isLoopbackHost = preg_match('/^(127\.0\.0\.1)$/', $host) === 1;
        }

        if ($isLoopbackHost && strpos($host, ':') === false) {
            $baseUrl .= ':8000';
        }
    } else {
        $baseUrl = $defaultBaseUrl;
    }
}

$baseUrl = rtrim($baseUrl, '/');

$forcePublicIp = getenv('PYTHON_SERVICE_FORCE_PUBLIC_IP');
if ($forcePublicIp === false || $forcePublicIp === '') {
    $forcePublicIp = $_ENV['PYTHON_SERVICE_FORCE_PUBLIC_IP'] ?? $_SERVER['PYTHON_SERVICE_FORCE_PUBLIC_IP'] ?? null;
}
if (!empty($forcePublicIp)) {
    $baseUrl = rtrim($forcePublicIp, '/');
}

$internalBaseUrl = $baseUrl;

$publicEnvKeys = [
    'PYTHON_SERVICE_PUBLIC_URL',
    'PYTHON_SERVICE_BROWSER_URL',
    'PY_SERVICE_PUBLIC_URL',
    'PY_SERVICE_BROWSER_URL'
];

$publicBaseUrl = null;

foreach ($publicEnvKeys as $key) {
    $value = getenv($key);

    if ($value === false || $value === '') {
        if (isset($_ENV[$key]) && $_ENV[$key] !== '') {
            $value = $_ENV[$key];
        } elseif (isset($_SERVER[$key]) && $_SERVER[$key] !== '') {
            $value = $_SERVER[$key];
        }
    }

    if (!empty($value)) {
        $publicBaseUrl = $value;
        break;
    }
}

$effectiveBaseUrl = $publicBaseUrl ?: $baseUrl;
$effectiveBaseUrl = rtrim($effectiveBaseUrl, '/');

if (empty($effectiveBaseUrl)) {
    $effectiveBaseUrl = $internalBaseUrl ?: $defaultBaseUrl;
}

$forceHttpsEnv = getenv('PYTHON_SERVICE_FORCE_HTTPS');
if ($forceHttpsEnv === false || $forceHttpsEnv === '') {
    $forceHttpsEnv = $_ENV['PYTHON_SERVICE_FORCE_HTTPS'] ?? $_SERVER['PYTHON_SERVICE_FORCE_HTTPS'] ?? null;
}

$forceHttps = null;
$forceHttpsExplicit = false;
if ($forceHttpsEnv !== null && $forceHttpsEnv !== false && $forceHttpsEnv !== '') {
    $normalized = strtolower(trim((string) $forceHttpsEnv));
    $forceHttps = in_array($normalized, ['1', 'true', 'yes', 'on'], true);
    $forceHttpsExplicit = true;
}

if ($forceHttps === null) {
    $forceHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
}

$isLoopbackHost = static function (?string $url): bool {
    if (empty($url)) {
        return false;
    }

    $host = parse_url($url, PHP_URL_HOST);
    if ($host === false || $host === null) {
        return false;
    }

    return in_array($host, ['localhost', '127.0.0.1'], true);
};

$isIpAddress = static function (?string $url): bool {
    if (empty($url)) {
        return false;
    }

    $host = parse_url($url, PHP_URL_HOST);
    if ($host === false || $host === null) {
        return false;
    }

    return filter_var($host, FILTER_VALIDATE_IP) !== false;
};

if (!$forceHttpsExplicit) {
    if ($isIpAddress($effectiveBaseUrl) || $isIpAddress($internalBaseUrl)) {
        $forceHttps = false;
    }
}

$upgradeScheme = static function (?string $url) use ($isLoopbackHost, $isIpAddress) {
    if (empty($url) || stripos($url, 'http://') !== 0) {
        return $url;
    }

    if ($isLoopbackHost($url) || $isIpAddress($url)) {
        return $url;
    }

    return preg_replace('/^http:\/\//i', 'https://', $url);
};

if ($forceHttps) {
    $effectiveBaseUrl = $upgradeScheme($effectiveBaseUrl);
}

$normalizeIpScheme = static function (?string $url) use ($isIpAddress) {
    if (empty($url)) {
        return $url;
    }

    if ($isIpAddress($url) && stripos($url, 'https://') === 0) {
        return preg_replace('/^https:\/\//i', 'http://', $url);
    }

    return $url;
};

$effectiveBaseUrl = $normalizeIpScheme($effectiveBaseUrl);
$internalBaseUrl = $normalizeIpScheme($internalBaseUrl);

$healthPathKeys = ['PYTHON_SERVICE_HEALTH_PATH'];
$healthPath = null;

foreach ($healthPathKeys as $key) {
    $value = getenv($key);

    if ($value === false || $value === '') {
        if (isset($_ENV[$key]) && $_ENV[$key] !== '') {
            $value = $_ENV[$key];
        } elseif (isset($_SERVER[$key]) && $_SERVER[$key] !== '') {
            $value = $_SERVER[$key];
        }
    }

    if (!empty($value)) {
        $healthPath = $value;
        break;
    }
}

if (empty($healthPath)) {
    $healthPath = 'healthz';
}

$healthPath = ltrim($healthPath, '/');
$healthUrl = $effectiveBaseUrl . '/' . $healthPath;

$timeout = getenv('PYTHON_SERVICE_TIMEOUT');
if ($timeout === false || $timeout === '') {
    $timeout = $_ENV['PYTHON_SERVICE_TIMEOUT'] ?? $_SERVER['PYTHON_SERVICE_TIMEOUT'] ?? 30;
}
$timeout = (int) $timeout;
if ($timeout <= 0) {
    $timeout = 30;
}

$proxyUrl = getenv('PYTHON_SERVICE_PROXY_URL');
if ($proxyUrl === false || $proxyUrl === '') {
    $proxyUrl = $_ENV['PYTHON_SERVICE_PROXY_URL'] ?? $_SERVER['PYTHON_SERVICE_PROXY_URL'] ?? '/api/biometric/python-proxy.php';
}
$proxyUrl = trim($proxyUrl);
if ($proxyUrl === '') {
    $proxyUrl = '/api/biometric/python-proxy.php';
}
?>
<script>
(function (global) {
    const existing = (global.SYNKTIME && global.SYNKTIME.pythonService) || {};

    const config = {
    baseUrl: <?php echo json_encode($effectiveBaseUrl, JSON_UNESCAPED_SLASHES); ?>,
    internalBaseUrl: <?php echo json_encode($internalBaseUrl, JSON_UNESCAPED_SLASHES); ?>,
    forcedIp: <?php echo !empty($forcePublicIp) ? json_encode($internalBaseUrl, JSON_UNESCAPED_SLASHES) : 'null'; ?>,
        healthPath: <?php echo json_encode($healthPath, JSON_UNESCAPED_SLASHES); ?>,
        healthUrl: <?php echo json_encode($healthUrl, JSON_UNESCAPED_SLASHES); ?>,
        timeout: <?php echo json_encode($timeout); ?>,
        forceHttps: <?php echo json_encode((bool) $forceHttps); ?>,
        proxyUrl: <?php echo json_encode($proxyUrl, JSON_UNESCAPED_SLASHES); ?>
    };

    global.SYNKTIME = global.SYNKTIME || {};
    global.SYNKTIME.pythonService = Object.assign({}, existing, config);

    // Compatibilidad global con scripts existentes
    global.PYTHON_SERVICE_URL = config.baseUrl;
    global.PYTHON_SERVICE_PUBLIC_URL = config.baseUrl;
    global.PYTHON_SERVICE_INTERNAL_URL = config.internalBaseUrl;
    global.PYTHON_SERVICE_HEALTH_URL = config.healthUrl;
    global.PYTHON_SERVICE_TIMEOUT = config.timeout;
})(window);
</script>
