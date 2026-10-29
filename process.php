<?php
declare(strict_types=1);
require_once __DIR__.'/config/bootstrap.php';
require_once __DIR__.'/services/BackgroundRemovalService.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_response(['ok'=>false,'error'=>'Method not allowed.'],405);
if (!verify_csrf($_POST['csrf_token'] ?? null)) json_response(['ok'=>false,'error'=>'Session expired. Refresh the page and try again.'],419);
ensure_api_key();
enforce_rate_limit();
try {
    $imageUrl = trim((string)($_POST['image_url'] ?? ''));
    $tmpPath = null; $incomingPath = null; $originalBytes = null;
    if ($imageUrl !== '') {
        if (!empty($_FILES['image']['tmp_name'])) json_response(['ok'=>false,'error'=>'Choose either a local file or an image URL, not both.'],400);
        $service = new BackgroundRemovalService();
        $png = $service->removeBackgroundFromUrl($imageUrl);
        $originalUrl = $imageUrl;
    } else {
        if (!isset($_FILES['image']) || (int)($_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_OK) {
            $code=(int)($_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE);
            $msg = $code===UPLOAD_ERR_INI_SIZE || $code===UPLOAD_ERR_FORM_SIZE ? 'The image exceeds the server upload limit.' : 'Choose an image file before processing.';
            json_response(['ok'=>false,'error'=>$msg],400);
        }
        $tmpPath=$_FILES['image']['tmp_name'];
        $meta=validate_image_file($tmpPath);
        $originalBytes=file_get_contents($tmpPath);
        if ($originalBytes===false) throw new RuntimeException('Could not read the uploaded image.');
        $originalId=safe_id();
        $savedOriginal=STORAGE_ROOT.'/originals/'.$originalId.'.'.$meta['extension'];
        if (!move_uploaded_file($tmpPath,$savedOriginal)) {
            // For non-HTTP tests or alternate upload handlers, copy is a fallback.
            if (!@copy($tmpPath,$savedOriginal)) throw new RuntimeException('Could not store the temporary original image.');
        }
        $incomingPath=STORAGE_ROOT.'/incoming/'.$originalId.'.'.$meta['extension'];
        if (!@copy($savedOriginal,$incomingPath)) throw new RuntimeException('Could not prepare the temporary image URL.');
        if (PUBLIC_INCOMING_BASE_URL === '') {
            @unlink($savedOriginal); @unlink($incomingPath);
            json_response(['ok'=>false,'error'=>'Pixelcut accepts an image URL. Local file uploads require a publicly reachable HTTPS deployment. For localhost testing, use the public image URL field instead. On public HTTPS hosting, set public_incoming_base_url to the full incoming.php URL.'],422);
        }
        $incomingToken=safe_id(24);
        $incomingMetaPath=STORAGE_ROOT.'/incoming/'.$incomingToken.'.json';
        if (file_put_contents($incomingMetaPath,json_encode(['file'=>basename($incomingPath),'mime'=>$meta['mime'],'created'=>time()]),LOCK_EX)===false) {
            @unlink($savedOriginal); @unlink($incomingPath);
            throw new RuntimeException('Could not create temporary access for the image.');
        }
        $incomingUrl=PUBLIC_INCOMING_BASE_URL.(str_contains(PUBLIC_INCOMING_BASE_URL,'?') ? '&' : '?').'token='.$incomingToken;
        $service=new BackgroundRemovalService();
        $png=$service->removeBackgroundFromUrl($incomingUrl);
        $originalUrl = 'data:'.$meta['mime'].';base64,'.base64_encode($originalBytes);
        @unlink($savedOriginal); @unlink($incomingPath); @unlink($incomingMetaPath);
    }
    if (!isset($png) || strlen($png)<9 || substr($png,0,8)!=="\x89PNG\r\n\x1a\n" || @getimagesizefromstring($png)===false) {
        throw new RuntimeException('The processed result was not a valid PNG.');
    }
    $resultId=safe_id();
    $resultPath=STORAGE_ROOT.'/processed/'.$resultId.'.png';
    if (file_put_contents($resultPath,$png,LOCK_EX)===false) throw new RuntimeException('Could not save the temporary processed image.');
    $token=safe_id(24);
    $tokenPath=STORAGE_ROOT.'/processed/'.$token.'.json';
    file_put_contents($tokenPath,json_encode(['file'=>basename($resultPath),'created'=>time()]),LOCK_EX);
    $resultUrl='download.php?token='.$token.'&preview=1';
    json_response(['ok'=>true,'result_url'=>$resultUrl,'download_url'=>'download.php?token='.$token,'original_url'=>$originalUrl,'message'=>'Background removed successfully.']);
} catch (Throwable $e) {
    app_log('Processing failed: '.get_class($e).' '.$e->getMessage());
    $message = $e instanceof RuntimeException ? $e->getMessage() : 'Processing failed. Please try again.';
    json_response(['ok'=>false,'error'=>$message],422);
}
