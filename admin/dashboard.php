<?php
/**
 * WhatJobs Clone - Admin Dashboard (admin/dashboard.php)
 * Central analytics & operations console
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/Job.php';
require_once __DIR__ . '/../includes/Application.php';
require_once __DIR__ . '/../includes/User.php';

require_admin();

$jobModel = new Job();
$appModel = new Application();
$userModel = new User();

$stats = $appModel->getDashboardStats();
$recentApplications = $appModel->getAllForAdmin([], 6);
$recentJobs = $jobModel->getAllForAdmin([], 6);

$pageTitle = "Dashboard Overview | WhatJobs Admin";
require_once __DIR__ . '/includes/admin-header.php';
?>

<!-- 1. Stats Counter Cards -->
<div class="stat-grid">
    <!-- Card 1: Active Jobs -->
    <div class="stat-card">
        <div class="stat-icon" style="background: #eff6ff; color: #1d4ed8;">
            <span class="material-symbols-rounded" style="font-size: 28px;">work</span>
        </div>
        <div>
            <div class="stat-val"><?= $stats['total_jobs'] ?></div>
            <div class="stat-lbl">Active Job Postings</div>
        </div>
    </div>

    <!-- Card 2: Total Applications -->
    <div class="stat-card">
        <div class="stat-icon" style="background: #f0fdf4; color: #15803d;">
            <span class="material-symbols-rounded" style="font-size: 28px;">assignment</span>
        </div>
        <div>
            <div class="stat-val"><?= $stats['total_applications'] ?></div>
            <div class="stat-lbl">Total Applications</div>
        </div>
    </div>

    <!-- Card 3: Pending Review -->
    <div class="stat-card">
        <div class="stat-icon" style="background: #fffbeb; color: #b45309;">
            <span class="material-symbols-rounded" style="font-size: 28px;">pending_actions</span>
        </div>
        <div>
            <div class="stat-val"><?= $stats['pending_applications'] ?></div>
            <div class="stat-lbl">Pending Review</div>
        </div>
    </div>

    <!-- Card 4: Registered Candidates -->
    <div class="stat-card">
        <div class="stat-icon" style="background: #fdf2f8; color: #be185d;">
            <span class="material-symbols-rounded" style="font-size: 28px;">people</span>
        </div>
        <div>
            <div class="stat-val"><?= $stats['total_users'] ?></div>
            <div class="stat-lbl">Registered Candidates</div>
        </div>
    </div>
</div>

<!-- 2. Quick Operations Banner -->
<div style="background: #ffffff; border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 20px 24px; margin-bottom: 32px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px; box-shadow: var(--shadow-sm);">
    <div>
        <h3 style="font-size: 1.1rem; font-weight: 700; color: var(--text-dark);">
            WhatJobs Administrative Quick Actions
        </h3>
        <p style="font-size: 0.86rem; color: var(--text-muted);">
            Manage job postings, process applications, and oversee seeker accounts
        </p>
    </div>
    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
        <a href="<?= url('admin/add-job.php') ?>" class="btn btn-red" style="font-size: 0.88rem; padding: 8px 18px;">
            <span class="material-symbols-rounded" style="font-size: 18px;">add</span> Add New Job
        </a>
        <a href="<?= url('admin/manage-jobs.php') ?>" class="btn btn-outline-white" style="color: var(--text-dark); border-color: var(--border-color); font-size: 0.88rem; padding: 8px 18px;">
            <span class="material-symbols-rounded" style="font-size: 18px;">list</span> Manage All Jobs
        </a>
        <a href="<?= url('admin/applications.php') ?>" class="btn btn-outline-white" style="color: var(--text-dark); border-color: var(--border-color); font-size: 0.88rem; padding: 8px 18px;">
            <span class="material-symbols-rounded" style="font-size: 18px;">inbox</span> Review Applications
        </a>
    </div>
</div>

<!-- 3. Recent Applications Table -->
<div class="admin-card">
    <div class="admin-card-header">
        <h2 class="admin-card-title">Recent Candidate Applications</h2>
        <a href="<?= url('admin/applications.php') ?>" style="font-size: 0.86rem; color: var(--primary-red); font-weight: 600;">
            View All Applications &rarr;
        </a>
    </div>

    <?php if (empty($recentApplications)): ?>
        <div style="padding: 36px; text-align: center; color: var(--text-muted); font-size: 0.9rem;">
            No applications submitted yet.
        </div>
    <?php else: ?>
        <div style="overflow-x: auto;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Applicant</th>
                        <th>Applied Role</th>
                        <th>Date</th>
                        <th>Resume</th>
                        <th>Status</th>
                        <th style="text-align: right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentApplications as $app): ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($app['applicant_name']) ?></strong><br>
                                <span style="font-size: 0.8rem; color: #64748b;"><?= htmlspecialchars($app['applicant_email']) ?> • <?= htmlspecialchars($app['applicant_phone']) ?></span>
                            </td>
                            <td>
                                <span style="font-weight: 600; color: var(--text-dark);"><?= htmlspecialchars($app['job_title']) ?></span><br>
                                <span style="font-size: 0.8rem; color: #64748b;"><?= htmlspecialchars($app['company_name']) ?></span>
                            </td>
                            <td style="white-space: nowrap;">
                                <?= date('M j, Y', strtotime($app['created_at'])) ?>
                            </td>
                            <td>
                                <a href="<?= url($app['resume_path']) ?>" target="_blank" style="color: var(--primary-red); font-weight: 600; font-size: 0.84rem; display: inline-flex; align-items: center; gap: 4px;">
                                    <span class="material-symbols-rounded" style="font-size: 16px;">download</span> Download
                                </a>
                            </td>
                            <td>
                                <span class="badge-status badge-<?= htmlspecialchars($app['status']) ?>">
                                    <?= htmlspecialchars($app['status']) ?>
                                </span>
                            </td>
                            <td style="text-align: right; white-space: nowrap;">
                                <a href="<?= url('admin/applications.php?search=' . urlencode($app['applicant_name'])) ?>" class="btn btn-outline-white" style="color: var(--text-dark); border-color: var(--border-color); padding: 5px 12px; font-size: 0.8rem;">
                                    Review
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- 4. Recent Jobs Table -->
<div class="admin-card">
    <div class="admin-card-header">
        <h2 class="admin-card-title">Recent Job Postings</h2>
        <a href="<?= url('admin/manage-jobs.php') ?>" style="font-size: 0.86rem; color: var(--primary-red); font-weight: 600;">
            Manage All Jobs &rarr;
        </a>
    </div>

    <div style="overflow-x: auto;">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Job Title &amp; Company</th>
                    <th>Category</th>
                    <th>Salary Range</th>
                    <th>Views</th>
                    <th>Applicants</th>
                    <th>Status</th>
                    <th style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recentJobs as $j): ?>
                    <tr>
                        <td>
                            <strong style="color: var(--text-dark);"><?= htmlspecialchars($j['title']) ?></strong><br>
                            <span style="font-size: 0.8rem; color: #64748b;"><?= htmlspecialchars($j['company_name']) ?> • <?= htmlspecialchars($j['location']) ?></span>
                        </td>
                        <td><?= htmlspecialchars($j['category_name']) ?></td>
                        <td><?= format_salary($j['salary_min'], $j['salary_max'], $j['salary_type']) ?></td>
                        <td><?= (int)$j['views_count'] ?></td>
                        <td><strong><?= (int)$j['applications_count'] ?></strong></td>
                        <td>
                            <span class="badge-status badge-<?= htmlspecialchars($j['status']) ?>">
                                <?= htmlspecialchars($j['status']) ?>
                            </span>
                        </td>
                        <td style="text-align: right; white-space: nowrap;">
                            <a href="<?= url('admin/edit-job.php?id=' . $j['id']) ?>" class="btn btn-outline-white" style="color: var(--text-dark); border-color: var(--border-color); padding: 5px 12px; font-size: 0.8rem;">
                                Edit
                            </a>
                            <a href="<?= url('job-detail.php?id=' . $j['id']) ?>" target="_blank" class="btn btn-outline-white" style="color: #64748b; border-color: var(--border-color); padding: 5px 10px; font-size: 0.8rem;" title="View Live">
                                <span class="material-symbols-rounded" style="font-size: 16px;">open_in_new</span>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>

