<?php
/**
 * WhatJobs Clone - Administrator Login (admin/login.php)
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/User.php';
require_once __DIR__ . '/../includes/csrf.php';

if (is_admin_logged_in()) {
    header('Location: ' . url('admin/dashboard.php'));
    exit;
}

$userModel = new User();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Security token invalid. Please refresh the page.';
    }

    $identifier = trim($_POST['identifier'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($identifier)) {
        $errors[] = 'Please enter your username or admin email.';
    }
    if (empty($password)) {
        $errors[] = 'Please enter your password.';
    }

    if (empty($errors)) {
        $auth = $userModel->authenticateAdmin($identifier, $password);
        if ($auth['success']) {
            $_SESSION['admin_id'] = $auth['admin']['id'];
            $_SESSION['admin_username'] = $auth['admin']['username'];
            $_SESSION['admin_role'] = $auth['admin']['role'];

            set_flash('success', 'Logged in to WhatJobs Administrative Portal.');
            header('Location: ' . url('admin/dashboard.php'));
            exit;
        } else {
            $errors[] = $auth['error'];
        }
    }
}

$pageTitle = "Demo Administrator Sign In | WhatJobs Clone";
$flash = get_flash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link rel="stylesheet" href="<?= url('assets/css/style.css') ?>">
</head>
<body style="background: #141414; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 24px;">

<div style="width: 100%; max-width: 440px;">
    
    <!-- Brand Logo -->
    <div style="text-align: center; margin-bottom: 28px;">
        <a href="<?= url('index.php') ?>" style="display: inline-block;">
            <img src="<?= url('assets/images/logo.svg') ?>" alt="WhatJobs" width="200" height="38" style="height: 38px; width: auto; display: block; margin: 0 auto;">
        </a>
        <div style="color: #888; font-size: 0.82rem; margin-top: 8px; letter-spacing: 1px; text-transform: uppercase;">
            Demo Administrative Console
        </div>
    </div>

    <!-- Quick Demo Notice -->
    <div style="background: #222; border: 1px solid #333; color: #e0e0e0; border-radius: var(--radius-sm); padding: 12px 16px; margin-bottom: 20px; font-size: 0.85rem;">
        <strong style="color: #fff;"><span class="material-symbols-rounded" style="font-size: 16px; vertical-align: middle;">key</span> Admin Credentials:</strong><br>
        Username: <code style="color: #f87171;">admin</code> (or <code>admin@whatjobs.com</code>)<br>
        Password: <code style="color: #f87171;">admin123</code>
    </div>

    <?php if (!empty($errors)): ?>
        <div style="background: #fce8e6; border: 1px solid #f5c2c7; color: #c5221f; border-radius: var(--radius-sm); padding: 12px 16px; margin-bottom: 20px;">
            <ul style="margin-left: 20px; font-size: 0.86rem;">
                <?php foreach ($errors as $err): ?>
                    <li><?= htmlspecialchars($err) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <!-- Card -->
    <div style="background: #ffffff; border-radius: var(--radius-md); padding: 32px; box-shadow: 0 10px 30px rgba(0,0,0,0.5);">
        <h1 style="font-size: 1.35rem; font-weight: 800; color: var(--text-dark); margin-bottom: 20px; text-align: center;">
            Admin Sign In
        </h1>

        <form action="<?= url('admin/login.php') ?>" method="POST">
            <?= csrf_input() ?>

            <div style="margin-bottom: 18px;">
                <label style="display: block; font-size: 0.86rem; font-weight: 700; color: var(--text-dark); margin-bottom: 6px;">
                    Username or Email
                </label>
                <input 
                    type="text" 
                    name="identifier" 
                    required 
                    value="<?= htmlspecialchars($_POST['identifier'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                    placeholder="admin or admin@whatjobs.com"
                    style="width: 100%; padding: 11px 14px; border: 1px solid var(--border-color); border-radius: var(--radius-sm); font-size: 0.95rem;"
                >
            </div>

            <div style="margin-bottom: 24px;">
                <label style="display: block; font-size: 0.86rem; font-weight: 700; color: var(--text-dark); margin-bottom: 6px;">
                    Password
                </label>
                <input 
                    type="password" 
                    name="password" 
                    required 
                    placeholder="Enter admin password"
                    style="width: 100%; padding: 11px 14px; border: 1px solid var(--border-color); border-radius: var(--radius-sm); font-size: 0.95rem;"
                >
            </div>

            <button type="submit" class="btn btn-red" style="width: 100%; padding: 12px; font-size: 1rem;">
                Access Dashboard
            </button>
        </form>

        <div style="margin-top: 20px; text-align: center; font-size: 0.86rem;">
            <a href="<?= url('index.php') ?>" style="color: var(--text-muted); text-decoration: underline;">
                ← Back to Job Seeker Website
            </a>
        </div>
    </div>
</div>

</body>
</html>

