<?php
/**
 * WhatJobs Clone - Core Configuration
 */

// Error reporting (set to 0 in strict production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Session Security Configuration
if (session_status() === PHP_SESSION_NONE) {
    // Ensure session directory is writable (needed for Vercel/serverless environments)
    $sessPath = session_save_path();
    if (empty($sessPath) || !is_writable($sessPath)) {
        session_save_path(sys_get_temp_dir());
    }
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_samesite', 'Lax');
    session_start();
}

// Database Credentials (Standard XAMPP Defaults with Cloud/Vercel Env Variable Overrides)
define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_NAME', getenv('DB_NAME') ?: 'whatjobs_clone');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');
define('DB_CHARSET', getenv('DB_CHARSET') ?: 'utf8mb4');

// Application Details
define('APP_NAME', 'WhatJobs');
define('APP_TAGLINE', 'Find thousands of jobs near you');
define('APP_URL', rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\'));

// Uploads - fallback to writable temp folder in serverless environments
$defaultUploadDir = __DIR__ . '/../uploads/resumes/';
if (!is_dir($defaultUploadDir)) {
    @mkdir($defaultUploadDir, 0755, true);
}
if (!is_writable($defaultUploadDir)) {
    $defaultUploadDir = sys_get_temp_dir() . '/uploads/resumes/';
    if (!is_dir($defaultUploadDir)) {
        @mkdir($defaultUploadDir, 0755, true);
    }
}
define('UPLOAD_DIR', $defaultUploadDir);
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_EXTENSIONS', ['pdf', 'doc', 'docx']);

// Helper for absolute asset / page URLs across root and subfolders
function get_base_url(): string {
    static $base = null;
    if ($base !== null) {
        return $base;
    }

    $projectRoot = str_replace('\\', '/', realpath(__DIR__ . '/..') ?: __DIR__ . '/..');
    $docRoot = isset($_SERVER['DOCUMENT_ROOT']) ? str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT']) ?: $_SERVER['DOCUMENT_ROOT']) : '';

    if (!empty($docRoot) && str_starts_with($projectRoot, $docRoot)) {
        $sub = substr($projectRoot, strlen($docRoot));
        $base = rtrim($sub, '/');
        return $base;
    }

    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    if (preg_match('#^(.*?)/admin(/.*)?$#i', $script, $m)) {
        $base = rtrim($m[1], '/');
        return $base;
    }

    $dir = dirname($script);
    $base = ($dir === '/' || $dir === '\\' || $dir === '.') ? '' : rtrim($dir, '/');
    return $base;
}

function url(string $path = ''): string {
    $base = get_base_url();
    $cleanPath = ltrim($path, '/');
    if ($cleanPath === '') {
        return $base === '' ? '/' : $base;
    }
    return $base . '/' . $cleanPath;
}

