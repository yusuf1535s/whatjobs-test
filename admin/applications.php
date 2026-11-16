<?php
/**
 * WhatJobs Clone - Application Review Center (admin/applications.php)
 * Candidate review, status pipelines, notes, and resume inspection
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/Application.php';
require_once __DIR__ . '/../includes/Job.php';
require_once __DIR__ . '/../includes/csrf.php';

require_admin();

$appModel = new Application();
$jobModel = new Job();

// Handle Status & Notes Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        set_flash('error', 'Invalid security token.');
    } else {
        $appId = (int)($_POST['application_id'] ?? 0);
        $newStatus = trim($_POST['status'] ?? 'pending');
        $notes = trim($_POST['admin_notes'] ?? '');

        if ($appId > 0 && in_array($newStatus, ['pending', 'reviewing', 'shortlisted', 'rejected', 'accepted'])) {
            $appModel->updateStatus($appId, $newStatus, $notes);
            set_flash('success', 'Application #' . $appId . ' updated to ' . ucfirst($newStatus) . '.');
        } else {
            set_flash('error', 'Invalid application data.');
        }
    }
    header('Location: ' . url('admin/applications.php'));
    exit;
}

// Search & Filter Parameters
$status = trim($_GET['status'] ?? '');
$jobId = !empty($_GET['job_id']) ? (int)$_GET['job_id'] : null;
$search = trim($_GET['search'] ?? '');

$filters = [
    'status' => $status,
    'job_id' => $jobId,
    'search' => $search
];

$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;

$totalApps = $appModel->countAllForAdmin($filters);
$applications = $appModel->getAllForAdmin($filters, $limit, $offset);
$totalPages = ceil($totalApps / $limit);

$allJobs = $jobModel->getAllForAdmin([], 100);

$pageTitle = "Candidate Applications (" . $totalApps . ") | WhatJobs Admin";
require_once __DIR__ . '/includes/admin-header.php';
?>

<!-- Header -->
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 16px;">
    <div>
        <h1 style="font-size: 1.5rem; font-weight: 800; color: var(--text-dark);">
            Candidate Applications
        </h1>
        <p style="font-size: 0.88rem; color: var(--text-muted); margin-top: 4px;">
            Review incoming resumes, manage interview stages, and deliver candidate feedback
        </p>
    </div>
</div>

<!-- Filters Bar -->
<div style="background: #ffffff; border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 18px 24px; margin-bottom: 24px; box-shadow: var(--shadow-sm);">
    <form action="<?= url('admin/applications.php') ?>" method="GET" style="display: flex; gap: 16px; flex-wrap: wrap; align-items: center;">
        <!-- Search Keyword -->
        <div style="flex: 2; min-width: 220px;">
            <input 
                type="text" 
                name="search" 
                value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>" 
                placeholder="Search by applicant name, email, or role..."
                style="width: 100%; padding: 9px 14px; border: 1px solid var(--border-color); border-radius: var(--radius-sm); font-size: 0.9rem;"
            >
        </div>

        <!-- Status Filter -->
        <div style="flex: 1; min-width: 140px;">
            <select name="status" style="width: 100%; padding: 9px 12px; border: 1px solid var(--border-color); border-radius: var(--radius-sm); font-size: 0.9rem; background: #fff;">
                <option value="">All Review Stages</option>
                <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>Pending Review</option>
                <option value="reviewing" <?= $status === 'reviewing' ? 'selected' : '' ?>>Under Review</option>
                <option value="shortlisted" <?= $status === 'shortlisted' ? 'selected' : '' ?>>Shortlisted</option>
                <option value="accepted" <?= $status === 'accepted' ? 'selected' : '' ?>>Offer Accepted</option>
                <option value="rejected" <?= $status === 'rejected' ? 'selected' : '' ?>>Not Selected</option>
            </select>
        </div>

        <!-- Job Filter -->
        <div style="flex: 1.5; min-width: 200px;">
            <select name="job_id" style="width: 100%; padding: 9px 12px; border: 1px solid var(--border-color); border-radius: var(--radius-sm); font-size: 0.9rem; background: #fff;">
                <option value="">All Job Postings</option>
                <?php foreach ($allJobs as $aj): ?>
                    <option value="<?= $aj['id'] ?>" <?= $jobId == $aj['id'] ? 'selected' : '' ?>>
                        #<?= $aj['id'] ?> - <?= htmlspecialchars($aj['title']) ?> (<?= htmlspecialchars($aj['company_name']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Submit & Reset -->
        <button type="submit" class="btn" style="background: var(--text-dark); color: #fff; padding: 9px 20px; font-size: 0.9rem;">
            Filter
        </button>
        <?php if (!empty($search) || !empty($status) || $jobId): ?>
            <a href="<?= url('admin/applications.php') ?>" style="font-size: 0.86rem; color: var(--primary-red); font-weight: 600;">
                Reset
            </a>
        <?php endif; ?>
    </form>
</div>

<!-- Applications Data Table -->
<div class="admin-card">
    <?php if (empty($applications)): ?>
        <div style="padding: 48px; text-align: center; color: var(--text-muted);">
            No applications match the current filter.
        </div>
    <?php else: ?>
        <div style="overflow-x: auto;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 50px;">ID</th>
                        <th>Candidate Details</th>
                        <th>Position Applied</th>
                        <th>Date Applied</th>
                        <th>Resume Document</th>
                        <th>Current Stage</th>
                        <th style="width: 280px;">Update Review Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($applications as $app): ?>
                        <tr>
                            <td>#<?= $app['id'] ?></td>
                            <td>
                                <strong style="font-size: 0.95rem; color: var(--text-dark);"><?= htmlspecialchars($app['applicant_name']) ?></strong><br>
                                <a href="mailto:<?= htmlspecialchars($app['applicant_email']) ?>" style="color: var(--primary-red); font-size: 0.82rem;">
                                    <?= htmlspecialchars($app['applicant_email']) ?>
                                </a> • 
                                <span style="font-size: 0.82rem; color: #64748b;">
                                    <?= htmlspecialchars($app['applicant_phone']) ?>
                                </span>

                                <?php if (!empty($app['cover_letter'])): ?>
                                    <details style="margin-top: 6px; font-size: 0.8rem; color: #475569;">
                                        <summary style="cursor: pointer; color: var(--text-dark); font-weight: 600;">Cover Letter</summary>
                                        <p style="padding: 8px 10px; background: #f8fafc; border-radius: 4px; margin-top: 4px; white-space: pre-line;">
                                            <?= htmlspecialchars($app['cover_letter']) ?>
                                        </p>
                                    </details>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="<?= url('job-detail.php?id=' . $app['job_id']) ?>" target="_blank" style="font-weight: 700; color: var(--text-dark);">
                                    <?= htmlspecialchars($app['job_title']) ?>
                                </a><br>
                                <span style="font-size: 0.82rem; color: #64748b;"><?= htmlspecialchars($app['company_name']) ?></span>
                            </td>
                            <td style="white-space: nowrap; font-size: 0.84rem; color: #64748b;">
                                <?= date('M j, Y', strtotime($app['created_at'])) ?><br>
                                <span style="font-size: 0.78rem;"><?= date('g:i A', strtotime($app['created_at'])) ?></span>
                            </td>
                            <td style="white-space: nowrap;">
                                <a href="<?= url($app['resume_path']) ?>" target="_blank" class="btn btn-outline-white" style="color: var(--primary-red); border-color: var(--border-color); font-size: 0.8rem; padding: 5px 12px; display: inline-flex; align-items: center; gap: 4px;">
                                    <span class="material-symbols-rounded" style="font-size: 16px;">description</span> Download CV
                                </a>
                            </td>
                            <td>
                                <span class="badge-status badge-<?= htmlspecialchars($app['status']) ?>">
                                    <?= htmlspecialchars($app['status']) ?>
                                </span>
                            </td>
                            <td>
                                <form action="<?= url('admin/applications.php') ?>" method="POST" style="display: flex; flex-direction: column; gap: 6px;">
                                    <?= csrf_input() ?>
                                    <input type="hidden" name="action" value="update_status">
                                    <input type="hidden" name="application_id" value="<?= $app['id'] ?>">
                                    
                                    <div style="display: flex; gap: 6px;">
                                        <select name="status" style="flex: 1; padding: 5px 8px; border: 1px solid var(--border-color); border-radius: var(--radius-sm); font-size: 0.82rem; background: #fff;">
                                            <option value="pending" <?= $app['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                                            <option value="reviewing" <?= $app['status'] === 'reviewing' ? 'selected' : '' ?>>Reviewing</option>
                                            <option value="shortlisted" <?= $app['status'] === 'shortlisted' ? 'selected' : '' ?>>Shortlisted</option>
                                            <option value="accepted" <?= $app['status'] === 'accepted' ? 'selected' : '' ?>>Accepted</option>
                                            <option value="rejected" <?= $app['status'] === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                                        </select>
                                        <button type="submit" class="btn btn-red" style="padding: 5px 12px; font-size: 0.78rem;">
                                            Save
                                        </button>
                                    </div>
                                    <input 
                                        type="text" 
                                        name="admin_notes" 
                                        value="<?= htmlspecialchars($app['admin_notes'] ?? '', ENT_QUOTES, 'UTF-8') ?>" 
                                        placeholder="Add private candidate note..."
                                        style="width: 100%; padding: 5px 8px; border: 1px solid var(--border-color); border-radius: var(--radius-sm); font-size: 0.78rem;"
                                    >
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <div style="display: flex; justify-content: center; gap: 8px; padding: 20px; border-top: 1px solid var(--border-light);">
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <?php 
                        $pParams = $_GET;
                        $pParams['page'] = $i;
                    ?>
                    <a href="<?= url('admin/applications.php?' . http_build_query($pParams)) ?>" class="btn" style="<?= $i === $page ? 'background: #181818; color: #fff;' : 'background: #fff; color: var(--text-dark); border: 1px solid var(--border-color);' ?> padding: 6px 12px; font-size: 0.84rem;">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>

