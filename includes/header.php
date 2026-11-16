<?php
/**
 * WhatJobs Clone - Global Reusable Header
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/csrf.php';

$pageTitle = $pageTitle ?? 'WhatJobs Demo | Developer Portfolio Showcase';
$currentPage = basename($_SERVER['PHP_SELF']);
$user = current_user();
$admin = current_admin();
$flash = get_flash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <meta name="csrf-token" content="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
    <meta name="description" content="Educational portfolio demonstration project replicating a modern job search interface. Not affiliated with or endorsed by WhatJobs.">
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>
    
    <!-- Design System CSS -->
    <link rel="stylesheet" href="<?= url('assets/css/style.css') ?>">
    <?php if (isset($extraCss) && is_array($extraCss)): ?>
        <?php foreach ($extraCss as $css): ?>
            <link rel="stylesheet" href="<?= url($css) ?>">
        <?php endforeach; ?>
    <?php endif; ?>
</head>
<body>

<!-- Header Global -->
<header class="site-header">
    <div class="container nav-container">
        <!-- Brand & Main Nav -->
        <div class="brand-wrapper">
            <a href="<?= url('index.php') ?>" class="brand-logo" title="WhatJobs Home">
                <img src="<?= url('assets/images/logo.svg') ?>" alt="WhatJobs" width="190" height="35" style="height: 35px; width: auto; display: block;">
            </a>

            <nav>
                <ul class="main-nav">
                    <li><a href="<?= url('jobs.php') ?>" class="<?= $currentPage === 'jobs.php' ? 'active' : '' ?>">Search Jobs</a></li>
                    <li><a href="<?= url('jobs.php?what=Google') ?>">Top Employers</a></li>
                    <li><a href="<?= url('jobs.php?remote=1') ?>">Remote Roles</a></li>
                </ul>
            </nav>
        </div>

        <!-- Action Buttons & Account -->
        <div class="nav-actions">
            <?php if ($admin): ?>
                <a href="<?= url('admin/dashboard.php') ?>" class="btn btn-red">
                    <span class="material-symbols-rounded">admin_panel_settings</span> Admin Panel
                </a>
                <a href="<?= url('logout.php') ?>" class="btn-signin">Logout</a>
            <?php elseif ($user): ?>
                <a href="<?= url('seeker-dashboard.php') ?>" class="btn btn-green">
                    <span class="material-symbols-rounded">dashboard</span> Dashboard
                </a>
                <a href="<?= url('saved-jobs.php') ?>" class="btn btn-outline-white">
                    <span class="material-symbols-rounded">bookmark</span> Saved
                </a>
                <a href="<?= url('logout.php') ?>" class="btn-signin">Logout</a>
            <?php else: ?>
                <a href="<?= url('register.php') ?>" class="btn btn-green">Register Resume</a>
                <a href="<?= url('admin/login.php') ?>" class="btn btn-outline-white">Employers | Post Job</a>
                <a href="<?= url('login.php') ?>" class="btn-signin">Sign In</a>
            <?php endif; ?>

            <!-- Mobile Menu Toggle -->
            <button class="mobile-menu-toggle" id="mobileMenuToggle" aria-label="Open Navigation Menu">
                <span class="material-symbols-rounded" style="font-size: 28px;">menu</span>
            </button>
        </div>
    </div>
</header>

<!-- Mobile Navigation Drawer -->
<div id="mobileDrawer" style="display:none; position:fixed; inset:0; z-index:2000; background:rgba(0,0,0,0.7);">
    <div style="background:#181818; width:280px; height:100%; padding:24px; color:#fff; display:flex; flex-direction:column; gap:20px;">
        <div style="display:flex; justify-content:space-between; align-items:center;">
            <span style="font-weight:700; font-size:1.1rem; color:#fff;">Menu</span>
            <button id="mobileDrawerClose" style="background:none; border:none; color:#fff; cursor:pointer;">
                <span class="material-symbols-rounded" style="font-size:28px;">close</span>
            </button>
        </div>
        <div style="display:flex; flex-direction:column; gap:16px;">
            <a href="<?= url('index.php') ?>" style="color:#fff; font-size:1rem; padding:8px 0; border-bottom:1px solid #333;">Home</a>
            <a href="<?= url('jobs.php') ?>" style="color:#fff; font-size:1rem; padding:8px 0; border-bottom:1px solid #333;">Search Jobs</a>
            <a href="<?= url('jobs.php?remote=1') ?>" style="color:#fff; font-size:1rem; padding:8px 0; border-bottom:1px solid #333;">Remote Jobs</a>
            <?php if ($user): ?>
                <a href="<?= url('seeker-dashboard.php') ?>" style="color:#00c853; font-weight:600; padding:8px 0;">My Dashboard</a>
                <a href="<?= url('saved-jobs.php') ?>" style="color:#fff; padding:8px 0;">Saved Jobs</a>
                <a href="<?= url('logout.php') ?>" style="color:#E64545; padding:8px 0;">Log Out</a>
            <?php elseif ($admin): ?>
                <a href="<?= url('admin/dashboard.php') ?>" style="color:#E64545; font-weight:600; padding:8px 0;">Admin Dashboard</a>
                <a href="<?= url('logout.php') ?>" style="color:#fff; padding:8px 0;">Log Out</a>
            <?php else: ?>
                <a href="<?= url('login.php') ?>" style="color:#fff; font-size:1rem; padding:8px 0;">Sign In</a>
                <a href="<?= url('register.php') ?>" class="btn btn-green" style="text-align:center; margin-top:10px;">Register Resume</a>
                <a href="<?= url('admin/login.php') ?>" class="btn btn-outline-white" style="text-align:center;">Post Job</a>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    // Inline mobile drawer show/hide
    const mBtn = document.getElementById('mobileMenuToggle');
    const mDrawer = document.getElementById('mobileDrawer');
    const mClose = document.getElementById('mobileDrawerClose');
    if (mBtn && mDrawer) {
        mBtn.addEventListener('click', () => mDrawer.style.display = 'block');
    }
    if (mClose && mDrawer) {
        mClose.addEventListener('click', () => mDrawer.style.display = 'none');
    }
</script>

<!-- Flash Notification Area -->
<?php if ($flash): ?>
    <div class="container" style="margin-top: 16px;">
        <div class="flash-message flash-<?= htmlspecialchars($flash['type'], ENT_QUOTES, 'UTF-8') ?>">
            <span><?= htmlspecialchars($flash['message'], ENT_QUOTES, 'UTF-8') ?></span>
            <button class="flash-close" style="background:none; border:none; cursor:pointer; color:inherit;">
                <span class="material-symbols-rounded" style="font-size: 18px;">close</span>
            </button>
        </div>
    </div>
<?php endif; ?>

