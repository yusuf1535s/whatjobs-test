<?php
/**
 * WhatJobs Clone - Manage Job Postings (admin/manage-jobs.php)
 * Full CRUD management: Search, Filter, Status Toggle, Edit, Delete
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/Job.php';
require_once __DIR__ . '/../includes/csrf.php';

require_admin();
$jobModel = new Job();

// 1. Handle Status Toggle
if (isset($_GET['action']) && $_GET['action'] === 'toggle' && isset($_GET['id'])) {
    $jobId = (int)$_GET['id'];
    $newStatus = $jobModel->toggleStatus($jobId);
    set_flash('success', 'Job #' . $jobId . ' status changed to ' . ucfirst($newStatus) . '.');
    header('Location: ' . url('admin/manage-jobs.php'));
    exit;
}

// 2. Handle Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        set_flash('error', 'Invalid security token.');
    } else {
        $jobId = (int)($_POST['id'] ?? 0);
        if ($jobId > 0 && $jobModel->delete($jobId)) {
            set_flash('success', 'Job posting #' . $jobId . ' was deleted permanently.');
        } else {
            set_flash('error', 'Could not delete job.');
        }
    }
    header('Location: ' . url('admin/manage-jobs.php'));
    exit;
}

// 3. Search & Filters
$search = trim($_GET['search'] ?? '');
$status = trim($_GET['status'] ?? '');
$catId = !empty($_GET['category_id']) ? (int)$_GET['category_id'] : null;

$filters = [
    'search' => $search,
    'status' => $status,
    'category_id' => $catId
];

$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 15;
$offset = ($page - 1) * $limit;

$totalJobs = $jobModel->countAllForAdmin($filters);
$jobs = $jobModel->getAllForAdmin($filters, $limit, $offset);
$totalPages = ceil($totalJobs / $limit);

$categories = $jobModel->getCategories();

$pageTitle = "Manage Job Postings (" . $totalJobs . ") | WhatJobs Admin";
require_once __DIR__ . '/includes/admin-header.php';
?>

<!-- Header & Add Job CTA -->
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 16px;">
    <div>
        <h1 style="font-size: 1.5rem; font-weight: 800; color: var(--text-dark);">
            Job Vacancy Listings
        </h1>
        <p style="font-size: 0.88rem; color: var(--text-muted); margin-top: 4px;">
            Showing <?= count($jobs) ?> of <?= $totalJobs ?> total job records across all companies
        </p>
    </div>
    <a href="<?= url('admin/add-job.php') ?>" class="btn btn-red" style="padding: 9px 22px; font-size: 0.9rem;">
        <span class="material-symbols-rounded">add</span> Create New Job
    </a>
</div>

<!-- Filters Bar Card -->
<div style="background: #ffffff; border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 18px 24px; margin-bottom: 24px; box-shadow: var(--shadow-sm);">
    <form action="<?= url('admin/manage-jobs.php') ?>" method="GET" style="display: flex; gap: 16px; flex-wrap: wrap; align-items: center;">
        <!-- Search Keyword -->
        <div style="flex: 2; min-width: 220px;">
            <input 
                type="text" 
                name="search" 
                value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>" 
                placeholder="Search by job title, company, or location..."
                style="width: 100%; padding: 9px 14px; border: 1px solid var(--border-color); border-radius: var(--radius-sm); font-size: 0.9rem;"
            >
        </div>

        <!-- Status Filter -->
        <div style="flex: 1; min-width: 140px;">
            <select name="status" style="width: 100%; padding: 9px 12px; border: 1px solid var(--border-color); border-radius: var(--radius-sm); font-size: 0.9rem; background: #fff;">
                <option value="">All Statuses</option>
                <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Active Only</option>
                <option value="paused" <?= $status === 'paused' ? 'selected' : '' ?>>Paused Only</option>
                <option value="closed" <?= $status === 'closed' ? 'selected' : '' ?>>Closed Only</option>
            </select>
        </div>

        <!-- Category Filter -->
        <div style="flex: 1; min-width: 160px;">
            <select name="category_id" style="width: 100%; padding: 9px 12px; border: 1px solid var(--border-color); border-radius: var(--radius-sm); font-size: 0.9rem; background: #fff;">
                <option value="">All Categories</option>
                <?php foreach ($categories as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= $catId == $c['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($c['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Submit & Reset -->
        <button type="submit" class="btn" style="background: var(--text-dark); color: #fff; padding: 9px 20px; font-size: 0.9rem;">
            Filter
        </button>
        <?php if (!empty($search) || !empty($status) || $catId): ?>
            <a href="<?= url('admin/manage-jobs.php') ?>" style="font-size: 0.86rem; color: var(--primary-red); font-weight: 600;">
                Reset
            </a>
        <?php endif; ?>
    </form>
</div>

<!-- Jobs Table -->
<div class="admin-card">
    <?php if (empty($jobs)): ?>
        <div style="padding: 48px; text-align: center; color: var(--text-muted);">
            No jobs found matching your criteria.
        </div>
    <?php else: ?>
        <div style="overflow-x: auto;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 50px;">ID</th>
                        <th>Job Title &amp; Company</th>
                        <th>Category</th>
                        <th>Job Type</th>
                        <th>Salary</th>
                        <th>Apps</th>
                        <th>Status</th>
                        <th style="text-align: right; width: 180px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($jobs as $job): ?>
                        <tr>
                            <td>#<?= $job['id'] ?></td>
                            <td>
                                <strong style="color: var(--text-dark); font-size: 0.95rem;">
                                    <?= htmlspecialchars($job['title']) ?>
                                </strong>
                                <div style="font-size: 0.82rem; color: #64748b; margin-top: 2px;">
                                    <?= htmlspecialchars($job['company_name']) ?> • <?= htmlspecialchars($job['location']) ?>
                                    <?php if ($job['is_remote']): ?>
                                        <span class="badge-remote" style="padding: 2px 6px; font-size: 0.72rem;">Remote</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td><?= htmlspecialchars($job['category_name']) ?></td>
                            <td>
                                <span style="font-size: 0.84rem; background: #f1f5f9; padding: 3px 8px; border-radius: var(--radius-pill);">
                                    <?= htmlspecialchars($job['job_type_name']) ?>
                                </span>
                            </td>
                            <td><?= format_salary($job['salary_min'], $job['salary_max'], $job['salary_type']) ?></td>
                            <td>
                                <a href="<?= url('admin/applications.php?job_id=' . $job['id']) ?>" style="font-weight: 700; color: var(--primary-red);">
                                    <?= (int)$job['applications_count'] ?> applicants
                                </a>
                            </td>
                            <td>
                                <a href="<?= url('admin/manage-jobs.php?action=toggle&id=' . $job['id']) ?>" title="Click to toggle status" style="text-decoration: none;">
                                    <span class="badge-status badge-<?= htmlspecialchars($job['status']) ?>">
                                        <?= htmlspecialchars($job['status']) ?> ⇄
                                    </span>
                                </a>
                            </td>
                            <td style="text-align: right; white-space: nowrap;">
                                <a href="<?= url('admin/edit-job.php?id=' . $job['id']) ?>" class="btn btn-outline-white" style="color: var(--text-dark); border-color: var(--border-color); padding: 5px 12px; font-size: 0.8rem;">
                                    Edit
                                </a>
                                
                                <form action="<?= url('admin/manage-jobs.php') ?>" method="POST" style="display: inline-block;" onsubmit="return confirm('Are you sure you want to permanently delete this job posting?');">
                                    <?= csrf_input() ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $job['id'] ?>">
                                    <button type="submit" class="btn btn-outline-white" style="color: #dc2626; border-color: var(--border-color); padding: 5px 10px; font-size: 0.8rem;" title="Delete Job">
                                        <span class="material-symbols-rounded" style="font-size: 16px;">delete</span>
                                    </button>
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
                    <a href="<?= url('admin/manage-jobs.php?' . http_build_query($pParams)) ?>" class="btn" style="<?= $i === $page ? 'background: #181818; color: #fff;' : 'background: #fff; color: var(--text-dark); border: 1px solid var(--border-color);' ?> padding: 6px 12px; font-size: 0.84rem;">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>

