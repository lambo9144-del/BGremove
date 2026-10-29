<?php
declare(strict_types=1);
function enforce_rate_limit(): void {
    $key = hash('sha256', (string)($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
    $path = STORAGE_ROOT.'/rate_limits/'.$key.'.json';
    $fp = @fopen($path, 'c+');
    if (!$fp) { app_log('Rate limit storage unavailable'); json_response(['ok'=>false,'error'=>'The service is temporarily unavailable. Please try again later.'],503); }
    flock($fp, LOCK_EX);
    $raw = stream_get_contents($fp);
    $data = json_decode($raw ?: '{}', true);
    if (!is_array($data) || (int)($data['start'] ?? 0) < time()-3600) $data=['start'=>time(),'count'=>0];
    if ((int)$data['count'] >= RATE_LIMIT_PER_HOUR) {
        flock($fp, LOCK_UN); fclose($fp);
        json_response(['ok'=>false,'error'=>'Hourly processing limit reached. Please try again later.'],429);
    }
    $data['count']++;
    rewind($fp); ftruncate($fp,0); fwrite($fp,json_encode($data)); fflush($fp);
    flock($fp, LOCK_UN); fclose($fp);
}
