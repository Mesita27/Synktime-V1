<?php
declare(strict_types=1);

$systemsFile = __DIR__ . '/config/portal_systems.php';
$systems = is_file($systemsFile) ? require $systemsFile : [];

if (!is_array($systems)) {
    $systems = [];
}

if (empty($systems)) {
    $systems = [[
        'key' => 'synktime',
        'name' => 'SynkTime by Kromez',
        'description' => 'Plataforma de asistencia inteligente de Kromez para turnos, puntualidad e incidencias.',
        'url' => '/login.php',
        'cta' => 'Entrar a SynkTime',
        'badge' => 'Operativo',
        'status' => 'online',
        'health_url' => null,
        'icon' => 'fa-regular fa-clock',
        'color' => '#2563EB',
        'image' => '/assets/img/synktime-logo.png',
        'open_in_new_tab' => false,
        'tags' => ['Kromez', 'Asistencia'],
    ]];
}

$statusMap = [
    'online' => ['label' => 'Disponible', 'class' => 'status-online'],
    'maintenance' => ['label' => 'En mantenimiento', 'class' => 'status-maintenance'],
    'offline' => ['label' => 'Fuera de línea', 'class' => 'status-offline'],
    'coming-soon' => ['label' => 'Próximamente', 'class' => 'status-coming-soon'],
];

$companyName = getenv('PORTAL_COMPANY_NAME') ?: 'Kromez';
$heroTitle = getenv('PORTAL_HERO_TITLE') ?: 'Tu ecosistema digital en un solo lugar';
$heroSubtitle = getenv('PORTAL_HERO_SUBTITLE') ?: 'Selecciona el sistema con el que quieres trabajar hoy.';
$supportEmail = getenv('PORTAL_SUPPORT_EMAIL') ?: 'cm417196@gmail.com';
$supportPhone = getenv('PORTAL_SUPPORT_PHONE') ?: '304 2844477';
$primaryCtaLabel = getenv('PORTAL_PRIMARY_CTA_LABEL') ?: '';
$primaryCtaUrl = getenv('PORTAL_PRIMARY_CTA_URL') ?: '';
$primaryCtaNewTab = filter_var(getenv('PORTAL_PRIMARY_CTA_NEW_TAB'), FILTER_VALIDATE_BOOL);

$systemsForJs = [];
foreach ($systems as $system) {
    if (!is_array($system)) {
        continue;
    }

    $key = $system['key'] ?? uniqid('system_', true);
    $systemsForJs[] = [
        'key' => $key,
        'healthUrl' => $system['health_url'] ?? null,
        'timeout' => $system['health_timeout'] ?? 4000,
    ];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Portal de Sistemas | <?php echo htmlspecialchars($companyName, ENT_QUOTES, 'UTF-8'); ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-pVnXcC6zFfsggqvYEj9mX8dYV1+sUWjTqCtitDpBvIb3hwZVDmSzJrlLwBEmp6kAMPx/+4vYX0fOk7p3rDQg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/portal.css">
</head>
<body class="portal-body">
    <header class="portal-hero">
        <div class="portal-hero-gradient" aria-hidden="true"></div>
        <div class="portal-hero-content">
            <span class="portal-eyebrow"><?php echo htmlspecialchars($companyName, ENT_QUOTES, 'UTF-8'); ?></span>
            <h1><?php echo htmlspecialchars($heroTitle, ENT_QUOTES, 'UTF-8'); ?></h1>
            <p><?php echo htmlspecialchars($heroSubtitle, ENT_QUOTES, 'UTF-8'); ?></p>
            <?php if ($primaryCtaLabel && $primaryCtaUrl): ?>
                <a class="portal-hero-cta" href="<?php echo htmlspecialchars($primaryCtaUrl, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $primaryCtaNewTab ? 'target="_blank" rel="noopener noreferrer"' : ''; ?>>
                    <span><?php echo htmlspecialchars($primaryCtaLabel, ENT_QUOTES, 'UTF-8'); ?></span>
                    <i class="fa-solid fa-arrow-up-right-from-square"></i>
                </a>
            <?php endif; ?>
        </div>
        <div class="portal-hero-illustration" aria-hidden="true">
            <div class="portal-orbit orbit-1"></div>
            <div class="portal-orbit orbit-2"></div>
            <div class="portal-orbit orbit-3"></div>
        </div>
    </header>

    <main class="portal-main">
        <section class="portal-grid" aria-label="Sistemas disponibles">
            <?php foreach ($systems as $system): ?>
                <?php
                if (!is_array($system) || !empty($system['hidden'])) {
                    continue;
                }

                $key = $system['key'] ?? uniqid('system_', true);
                $name = $system['name'] ?? 'Sistema sin nombre';
                $description = $system['description'] ?? '';
                $url = $system['url'] ?? '#';
                $cta = $system['cta'] ?? 'Ingresar';
                $badge = $system['badge'] ?? '';
                $statusKey = $system['status'] ?? 'online';
                $status = $statusMap[$statusKey] ?? $statusMap['online'];
                $icon = $system['icon'] ?? 'fa-solid fa-layer-group';
                $color = $system['color'] ?? '#2563EB';
                $image = $system['image'] ?? null;
                $healthUrl = $system['health_url'] ?? null;
                $openInNewTab = isset($system['open_in_new_tab']) ? (bool) $system['open_in_new_tab'] : false;
                $tags = is_array($system['tags'] ?? null) ? $system['tags'] : [];
                $rel = $openInNewTab ? 'noopener noreferrer' : '';
                ?>
                <article class="portal-card" data-system="<?php echo htmlspecialchars($key, ENT_QUOTES, 'UTF-8'); ?>" data-status="<?php echo htmlspecialchars($statusKey, ENT_QUOTES, 'UTF-8'); ?>"<?php echo $healthUrl ? ' data-health-url="' . htmlspecialchars($healthUrl, ENT_QUOTES, 'UTF-8') . '"' : ''; ?>>
                    <div class="portal-card-header">
                        <?php if ($image): ?>
                            <div class="portal-card-logo">
                                <img src="<?php echo htmlspecialchars($image, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?>" loading="lazy">
                            </div>
                        <?php else: ?>
                            <span class="portal-card-icon" style="--portal-accent: <?php echo htmlspecialchars($color, ENT_QUOTES, 'UTF-8'); ?>;">
                                <i class="<?php echo htmlspecialchars($icon, ENT_QUOTES, 'UTF-8'); ?>" aria-hidden="true"></i>
                            </span>
                        <?php endif; ?>
                        <?php if ($badge): ?>
                            <span class="portal-card-badge"><?php echo htmlspecialchars($badge, ENT_QUOTES, 'UTF-8'); ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="portal-card-body">
                        <h3><?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?></h3>
                        <?php if ($description): ?>
                            <p><?php echo htmlspecialchars($description, ENT_QUOTES, 'UTF-8'); ?></p>
                        <?php endif; ?>
                        <?php if ($tags): ?>
                            <ul class="portal-card-tags">
                                <?php foreach ($tags as $tag): ?>
                                    <li><?php echo htmlspecialchars($tag, ENT_QUOTES, 'UTF-8'); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                    <div class="portal-card-footer">
                        <span class="portal-card-status <?php echo htmlspecialchars($status['class'], ENT_QUOTES, 'UTF-8'); ?>">
                            <span class="portal-status-indicator" aria-hidden="true"></span>
                            <span><?php echo htmlspecialchars($status['label'], ENT_QUOTES, 'UTF-8'); ?></span>
                        </span>
                        <a class="portal-card-cta" href="<?php echo htmlspecialchars($url, ENT_QUOTES, 'UTF-8'); ?>"<?php echo $openInNewTab ? ' target="_blank"' : ''; ?><?php echo $rel ? ' rel="' . $rel . '"' : ''; ?>>
                            <span><?php echo htmlspecialchars($cta, ENT_QUOTES, 'UTF-8'); ?></span>
                            <i class="fa-solid fa-arrow-right"></i>
                        </a>
                    </div>
                </article>
            <?php endforeach; ?>
        </section>
    </main>

    <footer class="portal-footer">
        <div class="portal-footer-content">
            <p>&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($companyName, ENT_QUOTES, 'UTF-8'); ?>. Todos los derechos reservados.</p>
            <div class="portal-footer-links">
                <?php if ($supportEmail): ?>
                    <a href="mailto:<?php echo htmlspecialchars($supportEmail, ENT_QUOTES, 'UTF-8'); ?>">
                        <i class="fa-regular fa-envelope"></i>
                        <span><?php echo htmlspecialchars($supportEmail, ENT_QUOTES, 'UTF-8'); ?></span>
                    </a>
                <?php endif; ?>
                <?php if ($supportPhone): ?>
                    <a href="tel:<?php echo htmlspecialchars($supportPhone, ENT_QUOTES, 'UTF-8'); ?>">
                        <i class="fa-solid fa-phone"></i>
                        <span><?php echo htmlspecialchars($supportPhone, ENT_QUOTES, 'UTF-8'); ?></span>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </footer>

    <script>
        window.PORTAL_SYSTEMS = <?php echo json_encode($systemsForJs, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
    </script>
    <script src="assets/js/portal.js" defer></script>
</body>
</html>
