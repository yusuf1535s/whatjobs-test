<?php
/**
 * Authentication & Security Helper Functions
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/csrf.php';

function is_logged_in(): bool {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function current_user(): ?array {
    if (!is_logged_in()) {
        return null;
    }
    static $user = null;
    if ($user !== null) {
        return $user;
    }
    $db = Database::getConnection();
    $stmt = $db->prepare("SELECT id, full_name, email, phone, current_job_title, location, resume_path, bio, status, created_at FROM users WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $_SESSION['user_id']]);
    $user = $stmt->fetch() ?: null;
    return $user;
}

function require_login(): void {
    if (!is_logged_in()) {
        $currentUri = $_SERVER['REQUEST_URI'] ?? 'index.php';
        set_flash('error', 'Please log in to access this page.');
        header('Location: ' . url('login.php?redirect=' . urlencode($currentUri)));
        exit;
    }
}

function is_admin_logged_in(): bool {
    return isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id']);
}

function current_admin(): ?array {
    if (!is_admin_logged_in()) {
        return null;
    }
    static $admin = null;
    if ($admin !== null) {
        return $admin;
    }
    $db = Database::getConnection();
    $stmt = $db->prepare("SELECT id, username, email, full_name, role FROM admins WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $_SESSION['admin_id']]);
    $admin = $stmt->fetch() ?: null;
    return $admin;
}

function require_admin(): void {
    if (!is_admin_logged_in()) {
        set_flash('error', 'Admin authorization required.');
        header('Location: ' . url('admin/login.php'));
        exit;
    }
}

function set_flash(string $type, string $message): void {
    $_SESSION['flash_message'] = [
        'type' => $type, // 'success', 'error', 'info', 'warning'
        'message' => $message
    ];
}

function get_flash(): ?array {
    if (isset($_SESSION['flash_message'])) {
        $msg = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $msg;
    }
    return null;
}

function sanitize(string $input): string {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function format_salary(?float $min, ?float $max, string $type = 'year'): string {
    if (!$min && !$max) {
        return 'Competitive Salary';
    }
    
    $unit = match($type) {
        'hour' => 'per hour',
        'month' => 'per month',
        default => 'per year'
    };

    if ($min && $max) {
        if ($type === 'hour') {
            return '$' . number_format($min, 2) . ' - $' . number_format($max, 2) . ' ' . $unit;
        }
        return '$' . number_format($min) . ' - $' . number_format($max) . ' ' . $unit;
    }

    if ($min) {
        return 'From $' . number_format($min) . ' ' . $unit;
    }

    return 'Up to $' . number_format($max) . ' ' . $unit;
}

function time_ago(string $datetime): string {
    $timestamp = strtotime($datetime);
    $diff = time() - $timestamp;

    if ($diff < 60) {
        return 'Just now';
    }
    if ($diff < 3600) {
        $mins = floor($diff / 60);
        return $mins . 'm ago';
    }
    if ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours == 1 ? '1 hour ago' : $hours . ' hours ago';
    }
    if ($diff < 172800) {
        return 'Yesterday';
    }
    $days = floor($diff / 86400);
    if ($days < 30) {
        return $days . ' days ago';
    }
    return date('M j, Y', $timestamp);
}

