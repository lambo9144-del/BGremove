<?php
declare(strict_types=1);
require_once __DIR__.'/config/bootstrap.php';
$token=(string)($_GET['token'] ?? '');
if (!preg_match('/^[a-f0-9]{48}$/',$token)) { http_response_code(404); exit('Download not found or expired.'); }
$tokenPath=STORAGE_ROOT.'/processed/'.$token.'.json';
if (!is_file($tokenPath) || filemtime($tokenPath)<time()-TEMP_RETENTION_MINUTES*60) { @unlink($tokenPath); http_response_code(404); exit('Download not found or expired.'); }
$meta=json_decode((string)file_get_contents($tokenPath),true);
$file=is_array($meta) ? (string)($meta['file'] ?? '') : '';
if (!preg_match('/^[a-f0-9]{32}\.png$/',$file)) { http_response_code(404); exit('Download not found or expired.'); }
$path=STORAGE_ROOT.'/processed/'.$file;
if (!is_file($path)) { http_response_code(404); exit('Download not found or expired.'); }
header('X-Content-Type-Options: nosniff'); header('Cache-Control: no-store, private');
header('Content-Type: image/png');
if (isset($_GET['preview'])) header('Content-Disposition: inline; filename="removebg-result.png"');
else header('Content-Disposition: attachment; filename="removebg-transparent.png"');
header('Content-Length: '.filesize($path));
readfile($path);
