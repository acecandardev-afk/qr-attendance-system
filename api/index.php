<?php

declare(strict_types=1);

// Vercel serverless entrypoint for Laravel.
// Keep this wrapper defensive so deployments never return a blank 500 page.
try {
    require __DIR__.'/../public/index.php';
} catch (\Throwable $e) {
    http_response_code(500);
    header('Content-Type: text/html; charset=UTF-8');
    echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">';
    echo '<title>Setup required</title></head><body style="font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;background:#0b1220;color:#e2e8f0;margin:0;padding:24px;">';
    echo '<div style="max-width:860px;margin:0 auto;border:1px solid rgba(148,163,184,.25);background:rgba(15,23,42,.9);border-radius:16px;padding:18px;">';
    echo '<div style="font-size:14px;opacity:.9;margin-bottom:8px;">QR Attendance System</div>';
    echo '<h1 style="font-size:22px;margin:0 0 10px;">Deployment setup required</h1>';
    echo '<p style="margin:0 0 12px;opacity:.9;">The app failed to boot on this server. Check these settings in Vercel and redeploy:</p>';
    echo '<ul style="margin:0 0 12px;padding-left:18px;">';
    echo '<li style="margin:6px 0;">APP_KEY must be set</li>';
    echo '<li style="margin:6px 0;">DB_CONNECTION / DB_HOST / DB_PORT / DB_DATABASE / DB_USERNAME / DB_PASSWORD must be correct</li>';
    echo '<li style="margin:6px 0;">APP_ENV=production and APP_DEBUG=false</li>';
    echo '</ul>';
    echo '<p style="margin:0;font-size:12px;opacity:.7;">Technical detail: '.htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8').'</p>';
    echo '</div></body></html>';
}

