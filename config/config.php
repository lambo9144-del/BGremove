<?php

declare(strict_types=1);
/*
 * Copy to config/config.php and add your own Pixelcut API key.
 * Never upload config.php to a public source repository.
 */
return [
    'app_env' => 'development',
    'pixelcut_api_key' => 'sk_e8e31c07cd8f4bc6879c6bdfb5db5fb3',
    'pixelcut_api_url' => 'https://api.developer.pixelcut.ai/v1/remove-background',
    'max_upload_size' => 8 * 1024 * 1024,
    'max_image_width' => 6000,
    'max_image_height' => 6000,
    'max_image_pixels' => 25000000,
    'api_timeout_seconds' => 90,
    'temp_retention_minutes' => 30,
    'rate_limit_per_hour' => 10,
    /*
     * For file uploads on public HTTPS hosting, set this to the full incoming.php URL,
     * e.g. https://example.com/RemoveBG/incoming.php. Leave blank on localhost.
     */
    'public_incoming_base_url' => '',
];
