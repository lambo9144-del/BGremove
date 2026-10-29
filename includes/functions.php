<?php
declare(strict_types=1);
function h(string $value): string { return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function json_response(array $payload, int $status=200): never {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}
function app_log(string $message): void {
    $safe = preg_replace('/[\r\n]+/', ' ', $message) ?? 'log';
    @file_put_contents(STORAGE_ROOT.'/logs/app.log', '['.date('c').'] '.$safe.PHP_EOL, FILE_APPEND | LOCK_EX);
}
function safe_id(int $bytes=16): string { return bin2hex(random_bytes($bytes)); }
function cleanup_expired_files(): void {
    $cutoff = time() - TEMP_RETENTION_MINUTES*60;
    foreach (['originals','processed','incoming','rate_limits'] as $folder) {
        $dir = STORAGE_ROOT.'/'.$folder;
        foreach (glob($dir.'/*') ?: [] as $path) {
            if (is_file($path) && filemtime($path) < $cutoff) { @unlink($path); }
        }
    }
}
function ensure_api_key(): void {
    if (PIXELCUT_API_KEY === '' || PIXELCUT_API_KEY === 'PASTE_YOUR_PIXELCUT_API_KEY_HERE') {
        json_response(['ok'=>false,'error'=>'Pixelcut API key is not configured. Copy config/config.example.php to config/config.php and add your key.'], 503);
    }
}
function app_origin(): string {
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    $scheme = $https ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    if (!preg_match('/^[a-zA-Z0-9.\-:\[\]]+$/', $host)) return '';
    $script = str_replace('\\','/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
    $script = preg_replace('#/api$#', '', $script) ?? '';
    return $scheme.'://'.$host.rtrim($script, '/');
}
