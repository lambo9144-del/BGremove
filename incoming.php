<?php
declare(strict_types=1);
require_once __DIR__.'/config/bootstrap.php';
$token=(string)($_GET['token'] ?? '');
if (!preg_match('/^[a-f0-9]{48}$/',$token)) { http_response_code(404); exit; }
$metaPath=STORAGE_ROOT.'/incoming/'.$token.'.json';
if (!is_file($metaPath) || filemtime($metaPath)<time()-TEMP_RETENTION_MINUTES*60) { @unlink($metaPath); http_response_code(404); exit; }
$meta=json_decode((string)file_get_contents($metaPath),true);
$file=is_array($meta) ? (string)($meta['file'] ?? '') : '';
if (!preg_match('/^[a-f0-9]{32}\.(jpg|png)$/',$file)) { http_response_code(404); exit; }
$path=STORAGE_ROOT.'/incoming/'.$file;
if (!is_file($path)) { http_response_code(404); exit; }
$mime=(string)($meta['mime'] ?? '');
if (!in_array($mime,['image/jpeg','image/png'],true)) { http_response_code(404); exit; }
header('Content-Type: '.$mime);
header('Content-Length: '.filesize($path));
header('Cache-Control: no-store, private, max-age=0');
header('X-Content-Type-Options: nosniff');
readfile($path);
