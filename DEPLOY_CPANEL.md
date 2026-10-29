# Deploy on cPanel

1. Choose a PHP version supported by your hosting account (PHP 8.3+ recommended).
2. Upload the project through cPanel File Manager. Keep `config/config.php`, logs and private storage outside `public_html` if your host allows it. If you place the project in a subfolder, the app can be opened at that subfolder URL.
3. Copy `config/config.example.php` to `config/config.php`, add your Pixelcut API key, and set `app_env` to `production`.
4. Enable PHP `curl` and `fileinfo`; enable `gd` if available. Set `upload_max_filesize`, `post_max_size` and execution limits within the hosting plan's permitted settings.
5. Use HTTPS. Verify that the project `.htaccess` is accepted by the host. The included `storage/.htaccess` denies direct requests to storage on Apache.
6. Test the public site with an HTTPS image URL first.
7. For local file uploads through Pixelcut's URL-based API, configure `public_incoming_base_url` to the full HTTPS URL of `incoming.php`, e.g. `https://example.com/RemoveBG/incoming.php`. The app exposes only a validated image through a random, expiring token; the private `storage` directory remains blocked by `.htaccess`. Pixelcut must be able to reach this HTTPS URL. Do not use localhost or a private host. Keep retention short and test the route on your host.
8. Ensure the `storage` directories are writable by PHP but not public. Never use `777` unless your host explicitly requires it temporarily for diagnosis; restore safe permissions immediately.
9. Optional cron job (use the PHP path shown by your host):
   `php /home/CPANELUSER/public_html/RemoveBG/cron/cleanup.php`
   Set it to run every 15–30 minutes if cron is available. Opportunistic cleanup also runs on normal requests, but cron is more reliable for quiet sites.
10. Test API success, invalid file rejection, downloads, colour export and expired tokens. Review cPanel Errors and the protected `storage/logs/app.log`.

## No database
No MySQL database, database user, SQL import, or database service is required.
