<?php
/**
 * WhatJobs Clone - User Sign In (login.php)
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/User.php';
require_once __DIR__ . '/includes/csrf.php';

// If already logged in as seeker, redirect to dashboard
if (is_logged_in()) {
    header('Location: ' . url('seeker-dashboard.php'));
    exit;
}

$userModel = new User();
$errors = [];
$redirect = $_GET['redirect'] ?? $_POST['redirect'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Security token invalid. Please refresh and try again.';
    }

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please provide a valid email address.';
    }
    if (empty($password)) {
        $errors[] = 'Please enter your password.';
    }

    if (empty($errors)) {
        $auth = $userModel->authenticate($email, $password);
        if ($auth['success']) {
            $_SESSION['user_id'] = $auth['user']['id'];
            $_SESSION['user_name'] = $auth['user']['full_name'];
            $_SESSION['user_email'] = $auth['user']['email'];

            set_flash('success', 'Welcome back, ' . htmlspecialchars($auth['user']['full_name']) . '!');

            if (!empty($redirect) && str_starts_with($redirect, '/')) {
                header('Location: ' . $redirect);
            } else {
                header('Location: ' . url('seeker-dashboard.php'));
            }
            exit;
        } else {
            $errors[] = $auth['error'];
        }
    }
}

$pageTitle = "Sign In to Your Account | WhatJobs";
require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="max-width: 480px; padding-top: 48px; padding-bottom: 72px;">
    
    <!-- Demo Credentials Card -->
    <div style="background: #eef2ff; border: 1px solid #c7d2fe; border-radius: var(--radius-sm); padding: 14px 18px; margin-bottom: 24px; font-size: 0.88rem; color: #3730a3;">
        <strong><span class="material-symbols-rounded" style="font-size: 18px; vertical-align: middle;">key</span> Quick Demo Login:</strong><br>
        Email: <code>seeker@example.com</code><br>
        Password: <code>password123</code>
    </div>

    <!-- Error Banner -->
    <?php if (!empty($errors)): ?>
        <div style="background: #fce8e6; border: 1px solid #f5c2c7; color: #c5221f; border-radius: var(--radius-sm); padding: 14px 18px; margin-bottom: 20px;">
            <ul style="margin-left: 20px; font-size: 0.88rem;">
                <?php foreach ($errors as $err): ?>
                    <li><?= htmlspecialchars($err) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <!-- Login Box -->
    <div style="background: #ffffff; border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 36px; box-shadow: var(--shadow-sm);">
        <h1 style="font-size: 1.5rem; font-weight: 800; color: var(--text-dark); margin-bottom: 6px; text-align: center;">
            Sign In to WhatJobs
        </h1>
        <p style="font-size: 0.9rem; color: var(--text-muted); text-align: center; margin-bottom: 28px;">
            Manage your applications, saved jobs, and career profile
        </p>

        <form action="<?= url('login.php') ?>" method="POST">
            <?= csrf_input() ?>
            <?php if (!empty($redirect)): ?>
                <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirect, ENT_QUOTES, 'UTF-8') ?>">
            <?php endif; ?>

            <!-- Email -->
            <div style="margin-bottom: 18px;">
                <label for="email" style="display: block; font-size: 0.88rem; font-weight: 700; color: var(--text-dark); margin-bottom: 6px;">
                    Email Address
                </label>
                <input 
                    type="email" 
                    id="email" 
                    name="email" 
                    required 
                    autocomplete="email"
                    value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                    placeholder="name@example.com"
                    style="width: 100%; padding: 11px 14px; border: 1px solid var(--border-color); border-radius: var(--radius-sm); font-size: 0.95rem;"
                >
            </div>

            <!-- Password -->
            <div style="margin-bottom: 24px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                    <label for="password" style="font-size: 0.88rem; font-weight: 700; color: var(--text-dark);">
                        Password
                    </label>
                </div>
                <input 
                    type="password" 
                    id="password" 
                    name="password" 
                    required 
                    autocomplete="current-password"
                    placeholder="Enter your password"
                    style="width: 100%; padding: 11px 14px; border: 1px solid var(--border-color); border-radius: var(--radius-sm); font-size: 0.95rem;"
                >
            </div>

            <button type="submit" class="btn btn-red" style="width: 100%; padding: 13px; font-size: 1rem;">
                Sign In
            </button>
        </form>

        <div style="margin-top: 24px; padding-top: 20px; border-top: 1px solid var(--border-light); text-align: center; font-size: 0.9rem; color: var(--text-muted);">
            Don't have an account? 
            <a href="<?= url('register.php') ?>" style="color: var(--primary-red); font-weight: 600;">
                Register Resume
            </a>
        </div>

        <div style="margin-top: 14px; text-align: center; font-size: 0.86rem;">
            <a href="<?= url('admin/login.php') ?>" style="color: var(--text-muted); text-decoration: underline;">
                Employer / Admin Portal Sign In
            </a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

