# Install on XAMPP (Mac or Windows)

1. Install a current XAMPP package with PHP 8.3+ if available for your platform. Start **Apache**. MySQL does not need to be started.
2. Copy the `RemoveBG` folder into the XAMPP document root:
   - Windows: `C:\\xampp\\htdocs\\RemoveBG`
   - macOS: `/Applications/XAMPP/xamppfiles/htdocs/RemoveBG`
3. In `config`, copy `config.example.php` to `config.php`.
4. Edit `config/config.php` and replace `PASTE_YOUR_PIXELCUT_API_KEY_HERE` with your API key. Never paste the key into JavaScript or a public repository.
5. Enable/check PHP extensions `curl` and `fileinfo`; `gd` is recommended for image decode validation. Restart Apache after changing `php.ini`.
6. Confirm the PHP upload limit in `php.ini` is at least the configured app limit, e.g. `upload_max_filesize = 8M`, `post_max_size = 10M`, and `max_execution_time = 120`. Restart Apache.
7. Open `http://localhost/RemoveBG/`.
8. **Local upload caveat:** the supplied Pixelcut endpoint accepts `image_url`, and Pixelcut's servers cannot reach your computer's `localhost`. To test the real API locally, use a publicly reachable HTTPS image URL in the URL field. To test uploads end-to-end, deploy to a public HTTPS host and configure `public_incoming_base_url` as described in `DEPLOY_CPANEL.md`.
9. Test an image URL and confirm a transparent PNG is returned. API usage may consume credits.
10. Check `storage/logs/app.log` for server-side errors. Do not expose this log publicly.

### Permissions
The app attempts to create storage folders automatically. If needed, give the web server write access to `storage/originals`, `storage/processed`, `storage/incoming`, `storage/rate_limits`, and `storage/logs`. Avoid `777`; use the narrowest permissions that work. On macOS XAMPP, adjust ownership/permissions for the Apache process rather than making all files world-writable.

### Common problems
- **API key not configured:** create `config/config.php` and add your key.
- **cURL missing:** enable `extension=curl` in the PHP configuration used by Apache.
- **Fileinfo missing:** enable `extension=fileinfo`.
- **HTTP 401/403:** check the API key and API access.
- **HTTP 429:** check credits/rate limits.
- **Unexpected response format:** review current Pixelcut docs and the log entry; update response parsing in `services/BackgroundRemovalService.php`.
- **Local file upload says public HTTPS required:** expected for a URL-based API from localhost; use the URL field or deploy publicly.
