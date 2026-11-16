<?php
/**
 * WhatJobs Clone - Reusable Admin Header Layout
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/csrf.php';

require_admin();
$admin = current_admin();
$adminPage = basename($_SERVER['PHP_SELF']);
$pageTitle = $pageTitle ?? 'Admin Dashboard | WhatJobs';
$flash = get_flash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    
    <link rel="stylesheet" href="<?= url('assets/css/style.css') ?>">
    <link rel="stylesheet" href="<?= url('assets/css/admin.css') ?>">
</head>
<body>

<div class="admin-layout">
    <!-- Admin Sidebar -->
    <aside class="admin-sidebar">
        <div class="admin-sidebar-header">
            <a href="<?= url('admin/dashboard.php') ?>" class="brand-logo" title="WhatJobs Admin">
                <img src="<?= url('assets/images/logo.svg') ?>" alt="WhatJobs Admin" width="160" height="30" style="height: 30px; width: auto; display: block;">
            </a>
            <div style="font-size: 0.76rem; color: #888; margin-top: 6px; letter-spacing: 0.5px; text-transform: uppercase;">
                Administration Suite
            </div>
        </div>

        <ul class="admin-nav">
            <li>
                <a href="<?= url('admin/dashboard.php') ?>" class="<?= $adminPage === 'dashboard.php' ? 'active' : '' ?>">
                    <span class="material-symbols-rounded">dashboard</span>
                    <span class="nav-text">Dashboard</span>
                </a>
            </li>
            <li>
                <a href="<?= url('admin/manage-jobs.php') ?>" class="<?= in_array($adminPage, ['manage-jobs.php', 'edit-job.php']) ? 'active' : '' ?>">
                    <span class="material-symbols-rounded">work</span>
                    <span class="nav-text">Manage Jobs</span>
                </a>
            </li>
            <li>
                <a href="<?= url('admin/add-job.php') ?>" class="<?= $adminPage === 'add-job.php' ? 'active' : '' ?>">
                    <span class="material-symbols-rounded">add_circle</span>
                    <span class="nav-text">Post New Job</span>
                </a>
            </li>
            <li>
                <a href="<?= url('admin/applications.php') ?>" class="<?= $adminPage === 'applications.php' ? 'active' : '' ?>">
                    <span class="material-symbols-rounded">assignment</span>
                    <span class="nav-text">Applications</span>
                </a>
            </li>
            <li>
                <a href="<?= url('admin/users.php') ?>" class="<?= $adminPage === 'users.php' ? 'active' : '' ?>">
                    <span class="material-symbols-rounded">group</span>
                    <span class="nav-text">Job Seekers</span>
                </a>
            </li>
            <li>
                <a href="<?= url('index.php') ?>" target="_blank">
                    <span class="material-symbols-rounded">visibility</span>
                    <span class="nav-text">View Live Website</span>
                </a>
            </li>
        </ul>

        <div class="admin-sidebar-footer">
            <div style="display: flex; align-items: center; justify-content: space-between;">
                <div>
                    <strong style="color: #fff; display: block;"><?= htmlspecialchars($admin['username']) ?></strong>
                    <span style="font-size: 0.75rem; color: #888; text-transform: capitalize;"><?= htmlspecialchars($admin['role']) ?></span>
                </div>
                <a href="<?= url('logout.php') ?>" title="Sign Out" style="color: var(--primary-red);">
                    <span class="material-symbols-rounded">logout</span>
                </a>
            </div>
        </div>
    </aside>

    <!-- Main Content Area -->
    <div class="admin-main">
        <!-- Top Bar -->
        <header class="admin-topbar">
            <div>
                <span style="font-size: 0.95rem; font-weight: 600; color: #475569;">
                    <?= htmlspecialchars($pageTitle) ?>
                </span>
            </div>
            <div style="display: flex; align-items: center; gap: 16px;">
                <a href="<?= url('admin/add-job.php') ?>" class="btn btn-red" style="padding: 7px 18px; font-size: 0.86rem;">
                    <span class="material-symbols-rounded" style="font-size: 18px;">add</span> Post Job
                </a>
                <a href="<?= url('logout.php') ?>" class="btn btn-outline-white" style="color: #475569; border-color: #cbd5e1; padding: 6px 14px; font-size: 0.84rem;">
                    Log Out
                </a>
            </div>
        </header>

        <div class="admin-content">
            <?php if ($flash): ?>
                <div class="flash-message flash-<?= htmlspecialchars($flash['type']) ?>" style="margin-bottom: 24px;">
                    <span><?= htmlspecialchars($flash['message']) ?></span>
                </div>
            <?php endif; ?>

