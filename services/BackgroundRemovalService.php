<?php

declare(strict_types=1);

final class BackgroundRemovalService
{
    public function removeBackgroundFromUrl(string $imageUrl): string
    {
        if (!filter_var($imageUrl, FILTER_VALIDATE_URL)) {
            throw new RuntimeException('Please enter a valid image URL.');
        }

        $parts = parse_url($imageUrl);

        if (
            !$parts ||
            strtolower((string)($parts['scheme'] ?? '')) !== 'https' ||
            empty($parts['host']) ||
            isset($parts['user']) ||
            isset($parts['pass'])
        ) {
            throw new RuntimeException('Image URLs must use HTTPS.');
        }

        $host = strtolower((string)$parts['host']);

        if (
            $host === 'localhost' ||
            str_ends_with($host, '.localhost') ||
            str_ends_with($host, '.local') ||
            filter_var($host, FILTER_VALIDATE_IP) &&
            !filter_var(
                $host,
                FILTER_VALIDATE_IP,
                FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
            )
        ) {
            throw new RuntimeException('Private or local image URLs are not allowed.');
        }

        $payload = json_encode(
            ['image_url' => $imageUrl, 'format' => 'png'],
            JSON_UNESCAPED_SLASHES
        );

        if ($payload === false) {
            throw new RuntimeException('Could not prepare the API request.');
        }

        $ch = curl_init(PIXELCUT_API_URL);

        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_TIMEOUT => API_TIMEOUT_SECONDS,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json',
                'X-API-KEY: ' . PIXELCUT_API_KEY,
            ],
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);

        $body = curl_exec($ch);
        $curlError = curl_error($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $contentType = (string) curl_getinfo($ch, CURLINFO_CONTENT_TYPE);

        curl_close($ch);

        if ($body === false) {
            app_log('Pixelcut transport failure: ' . $curlError);
            throw new RuntimeException(
                'Could not connect to Pixelcut. Check server internet access and try again.'
            );
        }

        if ($status < 200 || $status >= 300) {
            app_log(
                'Pixelcut HTTP status ' . $status .
                    ' response=' .
                    substr(preg_replace('/\s+/', ' ', (string)$body) ?? '', 0, 400)
            );

            if ($status === 401 || $status === 403) {
                throw new RuntimeException(
                    'Pixelcut rejected the API key. Check your key and account access.'
                );
            }

            if ($status === 429) {
                throw new RuntimeException(
                    'Pixelcut rate limit or credit limit reached. Check your API account.'
                );
            }

            if ($status >= 500) {
                throw new RuntimeException(
                    'Pixelcut is temporarily unavailable. Please retry later.'
                );
            }

            throw new RuntimeException(
                'Pixelcut rejected this request (HTTP ' . $status .
                    '). Check the image URL and API request settings.'
            );
        }

        $json = json_decode((string)$body, true);

        if (!is_array($json)) {
            app_log(
                'Invalid Pixelcut JSON response; content-type=' . $contentType
            );

            throw new RuntimeException(
                'Pixelcut returned an unexpected response. Check the server log.'
            );
        }

        // Accept common output URL fields.
        $outputUrl =
            $json['result_url'] ??
            $json['image_url'] ??
            $json['url'] ??
            $json['output_url'] ??
            null;

        if (is_string($outputUrl) && filter_var($outputUrl, FILTER_VALIDATE_URL)) {
            return $this->downloadProviderImage($outputUrl);
        }

        // Some API responses may contain Base64 image data.
        $base64 =
            $json['image_base64'] ??
            $json['image'] ??
            $json['data'] ??
            null;

        if (is_string($base64)) {
            if (str_starts_with($base64, 'data:image/')) {
                $comma = strpos($base64, ',');

                if ($comma !== false) {
                    $base64 = substr($base64, $comma + 1);
                }
            }

            $decoded = base64_decode($base64, true);

            if ($decoded !== false && $this->isPng($decoded)) {
                return $decoded;
            }
        }

        app_log(
            'Unexpected Pixelcut success response; content-type=' .
                $contentType . '; keys=' . implode(',', array_keys($json))
        );

        throw new RuntimeException(
            'Pixelcut responded successfully, but the image format was not recognised. Check the server log.'
        );
    }

    /**
     * Download an image from the HTTPS URL supplied by Pixelcut.
     * Redirects are disabled to avoid silently following an unverified URL.
     */
    private function downloadProviderImage(string $url): string
    {
        $parts = parse_url($url);

        if (
            !$parts ||
            strtolower((string)($parts['scheme'] ?? '')) !== 'https' ||
            empty($parts['host']) ||
            isset($parts['user']) ||
            isset($parts['pass'])
        ) {
            throw new RuntimeException(
                'Pixelcut returned an invalid or unsafe output URL.'
            );
        }

        $host = strtolower((string)$parts['host']);

        // Reject private or local IP addresses and local hostnames.
        if (
            $host === 'localhost' ||
            str_ends_with($host, '.localhost') ||
            str_ends_with($host, '.local')
        ) {
            app_log('Blocked local Pixelcut output host: ' . $host);

            throw new RuntimeException(
                'Pixelcut returned a local or unsafe image URL.'
            );
        }

        if (filter_var($host, FILTER_VALIDATE_IP)) {
            if (!filter_var(
                $host,
                FILTER_VALIDATE_IP,
                FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
            )) {
                app_log('Blocked private output IP: ' . $host);

                throw new RuntimeException(
                    'Pixelcut returned a private or reserved image address.'
                );
            }
        }

        // Log the actual host for diagnosis if the download fails.
        app_log('Pixelcut output host: ' . $host);

        $ch = curl_init($url);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => API_TIMEOUT_SECONDS,
            CURLOPT_HTTPHEADER => ['Accept: image/png,image/*'],
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);

        $data = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);

        curl_close($ch);

        if ($data === false || $status < 200 || $status >= 300) {
            app_log(
                'Pixelcut image download failed; host=' . $host .
                    '; status=' . $status . '; error=' . $error
            );

            throw new RuntimeException(
                'Could not download the processed image from Pixelcut. Check the server log.'
            );
        }

        if (!$this->isPng($data)) {
            app_log(
                'Pixelcut returned non-PNG data; host=' . $host .
                    '; bytes=' . strlen($data)
            );

            throw new RuntimeException(
                'Pixelcut output was not a valid PNG image.'
            );
        }

        return $data;
    }

    private function isPng(string $data): bool
    {
        return strlen($data) > 8
            && substr($data, 0, 8) === "\x89PNG\r\n\x1a\n"
            && @getimagesizefromstring($data) !== false;
    }
}
