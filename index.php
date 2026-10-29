<?php

declare(strict_types=1);
require_once __DIR__ . '/config/bootstrap.php';
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="description" content="Remove image backgrounds in seconds with Pixelcut AI.">
    <title>RemoveBG — AI Background Remover</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>

<body>
    <nav class="navbar navbar-expand-lg bg-white border-bottom sticky-top">
        <div class="container">
            <a class="navbar-brand fw-bold" href="#"><span class="brand-mark"><i class="fa-solid fa-scissors"></i></span> Remove<span class="text-primary">BG</span></a>
            <div class="ms-auto small text-secondary"><i class="fa-solid fa-wand-magic-sparkles text-primary me-1"></i> Powered by Pixelcut AI</div>
        </div>
    </nav>
    <main class="container py-5">
        <section class="hero text-center mx-auto mb-5">
            <div class="eyebrow"><i class="fa-solid fa-sparkles me-2"></i>AI IMAGE TOOL</div>
            <h1 class="display-4 fw-bold mt-3">Remove image backgrounds<br><span class="gradient-text">in seconds.</span></h1>
            <p class="lead text-secondary mt-3">Upload a photo, let Pixelcut AI isolate your subject, then download a clean transparent PNG.</p>
        </section>
        <div class="row g-4 align-items-start">
            <div class="col-lg-5">
                <div class="panel p-4">
                    <h2 class="h5 fw-bold mb-1">Start with an image</h2>
                    <p class="small text-secondary mb-4">JPG or PNG. Maximum <?= (int)(MAX_UPLOAD_SIZE / 1024 / 1024) ?> MB.</p>
                    <form id="process-form">
                        <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
                        <div id="dropzone" class="dropzone text-center p-4">
                            <div class="upload-icon"><i class="fa-solid fa-cloud-arrow-up"></i></div>
                            <p class="fw-semibold mt-3 mb-1">Drag and drop an image here</p>
                            <p class="small text-secondary">or choose a file from your device</p>
                            <input
                                id="image-file"
                                class="form-control mt-3"
                                type="file"
                                name="image"
                                accept="image/jpeg,image/png">
                            <div id="file-info" class="small text-secondary mt-2"></div>
                        </div>
                        <div class="text-center my-3 text-secondary small">OR PROCESS AN IMAGE ALREADY ONLINE</div>
                        <label class="form-label" for="image-url">Public image URL (optional)</label>
                        <input class="form-control" type="url" id="image-url" name="image_url" placeholder="https://example.com/photo.jpg">
                        <div class="form-text mb-3">Use this on localhost, because Pixelcut must be able to access the image URL.</div>
                        <button id="process-btn" class="btn btn-primary btn-lg w-100" type="submit"><i class="fa-solid fa-wand-magic-sparkles me-2"></i>Remove background</button>
                        <div id="status" class="status mt-3" role="status" aria-live="polite"></div>
                    </form>
                    <div class="small text-secondary mt-3"><i class="fa-solid fa-lock me-1"></i>Your API key stays on the server. Images are handled as temporary files.</div>
                </div>
                <div class="row g-3 mt-1">
                    <div class="col-4">
                        <div class="mini-feature"><i class="fa-regular fa-image"></i><span>Transparent PNG</span></div>
                    </div>
                    <div class="col-4">
                        <div class="mini-feature"><i class="fa-solid fa-palette"></i><span>Custom colours</span></div>
                    </div>
                    <div class="col-4">
                        <div class="mini-feature"><i class="fa-solid fa-download"></i><span>Easy export</span></div>
                    </div>
                </div>
            </div>
            <div class="col-lg-7">
                <div class="panel result-panel p-3 p-md-4">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                        <div>
                            <h2 class="h5 fw-bold mb-1">Your result</h2>
                            <p class="small text-secondary mb-0">Preview, customise and download</p>
                        </div><span id="result-badge" class="badge text-bg-light">Waiting for image</span>
                    </div>
                    <div id="empty-state" class="empty-state">
                        <div class="empty-art"><i class="fa-regular fa-images"></i></div>
                        <h3 class="h5 mt-3">Your cutout will appear here</h3>
                        <p class="text-secondary small">Choose an image and select “Remove background”.</p>
                    </div>
                    <div id="editor" hidden>
                        <div class="preview-grid">
                            <div>
                                <div class="preview-label">Original</div>
                                <div class="preview-frame"><img id="original-preview" alt="Original uploaded image"></div>
                            </div>
                            <div>
                                <div class="preview-label">Background removed</div>
                                <div id="result-frame" class="preview-frame checkerboard"><img id="result-preview" alt="AI background removed image"></div>
                            </div>
                        </div>
                        <div class="d-flex flex-wrap align-items-center gap-2 mt-4">
                            <label for="bg-colour" class="form-label mb-0 me-1">Preview colour</label>
                            <button class="btn btn-sm btn-outline-secondary bg-choice" data-colour="transparent" type="button">Transparent</button>
                            <button class="btn btn-sm btn-outline-secondary bg-choice" data-colour="#ffffff" type="button">White</button>
                            <button class="btn btn-sm btn-outline-secondary bg-choice" data-colour="#111827" type="button">Dark</button>
                            <input id="bg-colour" class="form-control form-control-color" type="color" value="#dbeafe" title="Choose background colour" aria-label="Choose preview background colour">
                        </div>
                        <div class="d-flex flex-wrap gap-2 mt-4">
                            <button id="download-png" class="btn btn-primary" type="button"><i class="fa-solid fa-download me-2"></i>Download transparent PNG</button>
                            <button id="download-colour" class="btn btn-outline-primary" type="button"><i class="fa-solid fa-palette me-2"></i>Download with colour</button>
                            <button id="reset-btn" class="btn btn-outline-secondary" type="button">Process another</button>
                        </div>
                        <p class="small text-secondary mt-3 mb-0">Colour export is composited into the downloaded image; the transparent master remains unchanged.</p>
                    </div>
                </div>
            </div>
        </div>
        <section class="mt-5 pt-4">
            <h2 class="h3 fw-bold text-center mb-4">How it works</h2>
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="info-card"><span>01</span>
                        <h3 class="h5 mt-3">Upload</h3>
                        <p class="text-secondary mb-0">Choose a valid JPG or PNG, or supply an image URL that Pixelcut can access.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="info-card"><span>02</span>
                        <h3 class="h5 mt-3">AI removes the background</h3>
                        <p class="text-secondary mb-0">Your PHP server securely calls Pixelcut and validates the returned image.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="info-card"><span>03</span>
                        <h3 class="h5 mt-3">Download</h3>
                        <p class="text-secondary mb-0">Keep a transparent PNG or composite the cutout onto a colour.</p>
                    </div>
                </div>
            </div>
        </section>
        <section class="mt-5 faq">
            <h2 class="h3 fw-bold text-center mb-4">Frequently asked questions</h2>
            <details>
                <summary>Do I need a database or account?</summary>
                <p>No. This project uses PHP, temporary files and a Pixelcut API key. It does not use SQL or user accounts.</p>
            </details>
            <details>
                <summary>Why might a local upload fail with Pixelcut?</summary>
                <p>The supplied Pixelcut endpoint accepts an image URL. A remote service cannot fetch a file from your computer's localhost address. For local development, use a public image URL; for uploaded files, deploy to HTTPS hosting or use a secure public tunnel you control.</p>
            </details>
            <details>
                <summary>Will the API be free?</summary>
                <p>Pixelcut may charge credits per successful operation. Check your current API plan and usage limits before processing images.</p>
            </details>
        </section>
    </main>
    <footer class="border-top py-4 mt-5">
        <div class="container d-flex flex-wrap justify-content-between gap-2 small text-secondary"><span>© <?= date('Y') ?> RemoveBG</span><span>Temporary image processing · No accounts · No SQL database</span><span><a href="privacy.php">Privacy</a> · <a href="terms.php">Terms</a></span></div>
    </footer>
    <script>
        window.REMOVEBG_CONFIG = {
            maxUploadBytes: <?= (int)MAX_UPLOAD_SIZE ?>
        };
    </script>
    <script src="assets/js/app.js" defer></script>
</body>

</html>