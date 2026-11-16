<?php
/**
 * Vercel Serverless Gateway & Front Controller
 * Routes all incoming dynamic requests to the appropriate PHP script.
 */

// Normalize URI path
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$path = parse_url($requestUri, PHP_URL_PATH);
$path = trim($path, '/');

// Base directory (project root)
$rootDir = dirname(__DIR__);

// Route mapping
if ($path === '' || $path === '/') {
    $script = 'index.php';
} else {
    $script = $path;
}

// Support extensionless URLs (e.g. /jobs -> /jobs.php)
if (!pathinfo($script, PATHINFO_EXTENSION) && file_exists($rootDir . '/' . $script . '.php')) {
    $script .= '.php';
}

// Support directory root requests (e.g. /admin -> /admin/index.php)
if (is_dir($rootDir . '/' . $script)) {
    if (file_exists($rootDir . '/' . $script . '/index.php')) {
        $script = rtrim($script, '/') . '/index.php';
    }
}

$targetFile = $rootDir . '/' . $script;

// 1. If target is a valid PHP file in the project, execute it
if (file_exists($targetFile) && (substr($targetFile, -4) === '.php')) {
    $_SERVER['SCRIPT_NAME'] = '/' . ltrim($script, '/');
    $_SERVER['PHP_SELF'] = '/' . ltrim($script, '/');
    chdir(dirname($targetFile));
    require $targetFile;
    exit;
}

// 2. Fallback static asset server (in case static file route didn't catch it)
if (file_exists($targetFile) && !is_dir($targetFile)) {
    $ext = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));
    $mimeTypes = [
        'css'  => 'text/css',
        'js'   => 'application/javascript',
        'json' => 'application/json',
        'svg'  => 'image/svg+xml',
        'png'  => 'image/png',
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif'  => 'image/gif',
        'ico'  => 'image/x-icon',
        'pdf'  => 'application/pdf',
        'woff' => 'font/woff',
        'woff2'=> 'font/woff2',
        'ttf'  => 'font/ttf',
    ];

    $mime = $mimeTypes[$ext] ?? 'application/octet-stream';
    header('Content-Type: ' . $mime);
    header('Cache-Control: public, max-age=86400');
    readfile($targetFile);
    exit;
}

// 3. Fallback 404
http_response_code(404);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>404 - Page Not Found</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        .notfound-box { text-align: center; padding: 100px 20px; font-family: sans-serif; }
        .notfound-box h1 { font-size: 60px; color: #e64545; margin-bottom: 10px; }
        .notfound-box p { font-size: 18px; color: #666; margin-bottom: 25px; }
        .notfound-box a { display: inline-block; padding: 10px 24px; background: #e64545; color: #fff; text-decoration: none; border-radius: 6px; }
    </style>
</head>
<body>
    <div class="notfound-box">
        <h1>404</h1>
        <p>The page you are looking for does not exist on WhatJobs.</p>
        <a href="/">Back to Homepage</a>
    </div>
</body>
</html>

