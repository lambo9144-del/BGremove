<?php
declare(strict_types=1);
function validate_image_file(string $path): array {
    if (!is_file($path) || filesize($path) < 1) throw new RuntimeException('The uploaded file is empty or unavailable.');
    if (filesize($path) > MAX_UPLOAD_SIZE) throw new RuntimeException('The image is larger than the configured upload limit.');
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($path);
    $allowed = ['image/jpeg'=>'jpg', 'image/png'=>'png'];
    if (!isset($allowed[$mime])) throw new RuntimeException('Only valid JPG and PNG images are supported by this setup.');
    $info = @getimagesize($path);
    if (!$info || !isset($info[0],$info[1]) || !in_array($info['mime'] ?? '', ['image/jpeg','image/png'], true)) {
        throw new RuntimeException('This file is not a valid supported image.');
    }
    $w=(int)$info[0]; $h=(int)$info[1];
    if ($w > MAX_IMAGE_WIDTH || $h > MAX_IMAGE_HEIGHT || ($w*$h) > MAX_IMAGE_PIXELS) {
        throw new RuntimeException('The image dimensions are above the configured limit.');
    }
    if (function_exists('imagecreatefromstring')) {
        $data = @file_get_contents($path);
        $decoded = $data === false ? false : @imagecreatefromstring($data);
        if ($decoded === false) throw new RuntimeException('The image could not be decoded.');
        imagedestroy($decoded);
    }
    return ['mime'=>$mime,'extension'=>$allowed[$mime],'width'=>$w,'height'=>$h];
}
