<?php
declare(strict_types=1);
$configFile = __DIR__ . '/config.php';
if (!is_file($configFile)) {
    $config = require __DIR__ . '/config.example.php';
} else {
    $config = require $configFile;
}
if (!is_array($config)) { $config = []; }
define('APP_ENV', (string)($config['app_env'] ?? 'production'));
define('PIXELCUT_API_KEY', (string)($config['pixelcut_api_key'] ?? ''));
define('PIXELCUT_API_URL', (string)($config['pixelcut_api_url'] ?? 'https://api.developer.pixelcut.ai/v1/remove-background'));
define('MAX_UPLOAD_SIZE', max(1024, (int)($config['max_upload_size'] ?? 8*1024*1024)));
define('MAX_IMAGE_WIDTH', max(1, (int)($config['max_image_width'] ?? 6000)));
define('MAX_IMAGE_HEIGHT', max(1, (int)($config['max_image_height'] ?? 6000)));
define('MAX_IMAGE_PIXELS', max(1, (int)($config['max_image_pixels'] ?? 25000000)));
define('API_TIMEOUT_SECONDS', max(10, (int)($config['api_timeout_seconds'] ?? 90)));
define('TEMP_RETENTION_MINUTES', max(5, (int)($config['temp_retention_minutes'] ?? 30)));
define('RATE_LIMIT_PER_HOUR', max(1, (int)($config['rate_limit_per_hour'] ?? 10)));
define('PUBLIC_INCOMING_BASE_URL', rtrim((string)($config['public_incoming_base_url'] ?? ''), '/'));
define('PROJECT_ROOT', dirname(__DIR__));
define('STORAGE_ROOT', PROJECT_ROOT . '/storage');
foreach ([STORAGE_ROOT, STORAGE_ROOT.'/originals', STORAGE_ROOT.'/processed', STORAGE_ROOT.'/incoming', STORAGE_ROOT.'/rate_limits', STORAGE_ROOT.'/logs'] as $dir) {
    if (!is_dir($dir)) { @mkdir($dir, 0700, true); }
}
require_once PROJECT_ROOT . '/includes/functions.php';
require_once PROJECT_ROOT . '/includes/csrf.php';
require_once PROJECT_ROOT . '/includes/image_validator.php';
require_once PROJECT_ROOT . '/includes/rate_limiter.php';
require_once PROJECT_ROOT . '/includes/cleanup.php';
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');
header('X-Frame-Options: DENY');
header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
