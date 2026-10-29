# Security notes

- Keep `config/config.php` out of public repositories and protect it with file permissions. Prefer configuration outside the document root.
- `storage/.htaccess` blocks direct storage access on Apache. Verify this with a direct URL on your actual host; `.htaccess` is not supported by every web server.
- Pixelcut is called from PHP; the API key is never included in frontend JavaScript.
- Uploads are checked by actual MIME type, dimensions and image decoding. The default allowlist is JPG/PNG.
- URL mode requires HTTPS and rejects literal private/local IP addresses. The provider must still be trusted to fetch the URL; use only image URLs you control or trust.
- Rate limits are filesystem-based and keyed by the remote IP. On some proxies all users may share an IP; tune limits for your hosting setup.
- Temporary data expires after `temp_retention_minutes` when cleanup runs. Cron is recommended.
- The JSON API response parser is intentionally conservative and must be matched against the current Pixelcut response contract.
- For production, move storage outside the public document root and change `STORAGE_ROOT` accordingly. Keep incoming public files separate from private originals and processed files.
- Add HTTPS, hosting-level request limits and monitoring before exposing the site to the public.
