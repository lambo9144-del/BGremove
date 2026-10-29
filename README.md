# RemoveBG — Pixelcut AI

A database-free PHP background removal website using Pixelcut's `remove-background` API.

## Features
- PHP 8+, HTML/CSS, vanilla JavaScript, Bootstrap and Font Awesome.
- Pixelcut API calls are made server-side; API key is not sent to the browser.
- JPG/PNG upload validation, URL input, rate limiting, CSRF token and expiring temporary files.
- Transparent PNG preview/download and Canvas-based solid-background PNG export.
- No SQL database, accounts, registration, or persistent processing history.

## Important API limitation
The supplied Pixelcut request uses JSON `image_url`. Pixelcut must be able to fetch that URL. A browser upload on a local XAMPP installation is not automatically reachable from Pixelcut's servers. On localhost, test with a publicly accessible HTTPS image URL. To process local file uploads through this endpoint, deploy to HTTPS hosting and configure the token-gated `incoming.php` URL as described in `DEPLOY_CPANEL.md`. Do not use localhost or a private URL for that setting.

The API response schema can change. This implementation recognises common JSON fields (`result_url`, `image_url`, `url`, `output_url`, `image_base64`, `image`, `data`) and validates the PNG. If Pixelcut returns a different response shape, update `services/BackgroundRemovalService.php` after checking the current official docs. The app deliberately reports an error rather than faking success.

## Setup
1. Copy `config/config.example.php` to `config/config.php`.
2. Put your Pixelcut API key in `pixelcut_api_key`.
3. Make sure PHP cURL and Fileinfo are enabled. GD is recommended.
4. Open the project through Apache, not `file://`.
5. Read `INSTALL_XAMPP.md` or `DEPLOY_CPANEL.md`.

## Security note
The `storage` directory must not be publicly readable. The included `.htaccess` denies direct access on Apache. Prefer moving storage outside the web root on a production host and adjusting `STORAGE_ROOT` in `config/bootstrap.php`. Test the access restriction on your host.
