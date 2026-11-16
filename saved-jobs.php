<?php
/**
 * WhatJobs Clone - Saved Jobs (saved-jobs.php)
 * Handles both AJAX bookmark toggling and dedicated Saved Jobs management page
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/Job.php';
require_once __DIR__ . '/includes/csrf.php';

$jobModel = new Job();

// 1. AJAX Toggle Action
if (isset($_GET['action']) && $_GET['action'] === 'toggle') {
    header('Content-Type: application/json');

    if (!is_logged_in()) {
        echo json_encode(['success' => false, 'status' => 'unauthenticated', 'message' => 'Please log in to save jobs.']);
        exit;
    }

    $jobId = (int)($_POST['job_id'] ?? 0);
    if ($jobId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid job ID.']);
        exit;
    }

    $isNowSaved = $jobModel->toggleSave($jobId, $_SESSION['user_id']);
    echo json_encode([
        'success' => true,
        'saved' => $isNowSaved,
        'message' => $isNowSaved ? 'Job saved to your wishlist.' : 'Job removed from saved jobs.'
    ]);
    exit;
}

// 2. Direct Remove Action (from saved jobs page)
if (isset($_GET['action']) && $_GET['action'] === 'remove') {
    require_login();
    $jobId = (int)($_GET['id'] ?? 0);
    if ($jobId > 0) {
        $jobModel->toggleSave($jobId, $_SESSION['user_id']);
        set_flash('success', 'Job removed from your saved list.');
    }
    header('Location: ' . url('saved-jobs.php'));
    exit;
}

// 3. Render Saved Jobs Page
require_login();
$user = current_user();
$savedJobs = $jobModel->getSavedJobs($user['id']);

$pageTitle = "My Saved Jobs (" . count($savedJobs) . ") | WhatJobs";
require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="padding-top: 36px; padding-bottom: 72px;">
    
    <!-- Page Header -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 28px; flex-wrap: wrap; gap: 16px;">
        <div>
            <h1 style="font-size: 1.6rem; font-weight: 800; color: var(--text-dark);">
                My Saved Jobs
            </h1>
            <p style="font-size: 0.92rem; color: var(--text-muted); margin-top: 4px;">
                You have <strong><?= count($savedJobs) ?></strong> bookmarked opportunities saved for later review
            </p>
        </div>

        <a href="<?= url('jobs.php') ?>" class="btn btn-red" style="padding: 9px 22px; font-size: 0.9rem;">
            <span class="material-symbols-rounded">search</span> Browse More Jobs
        </a>
    </div>

    <!-- Saved Jobs List -->
    <?php if (empty($savedJobs)): ?>
        <div style="background: #ffffff; border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 56px 24px; text-align: center; box-shadow: var(--shadow-sm);">
            <span class="material-symbols-rounded" style="font-size: 54px; color: #cbd5e1; margin-bottom: 16px;">bookmark_border</span>
            <h2 style="font-size: 1.25rem; font-weight: 700; color: var(--text-dark); margin-bottom: 8px;">
                No saved jobs yet
            </h2>
            <p style="color: var(--text-muted); font-size: 0.92rem; max-width: 440px; margin: 0 auto 24px auto;">
                When you see an interesting job vacancy, click the bookmark icon on any card to save it here for fast application.
            </p>
            <a href="<?= url('jobs.php') ?>" class="btn btn-red">Find Jobs Now</a>
        </div>
    <?php else: ?>
        <div style="display: flex; flex-direction: column; gap: 16px;">
            <?php foreach ($savedJobs as $job): ?>
                <div class="saved-job-card-wrapper" style="transition: opacity 0.25s ease;">
                    <article class="job-card" style="box-shadow: var(--shadow-sm);">
                        <div class="job-card-header">
                            <div>
                                <h2 class="job-title" style="font-size: 1.25rem;">
                                    <a href="<?= url('job-detail.php?id=' . $job['id']) ?>">
                                        <?= htmlspecialchars($job['title'], ENT_QUOTES, 'UTF-8') ?>
                                    </a>
                                </h2>
                            </div>
                            <div class="company-logo-box">
                                <?php if (!empty($job['company_logo'])): ?>
                                    <img src="<?= htmlspecialchars($job['company_logo'], ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($job['company_name'], ENT_QUOTES, 'UTF-8') ?>" onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($job['company_name']) ?>&background=181818&color=fff';">
                                <?php else: ?>
                                    <span class="material-symbols-rounded" style="color: #666;">business</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="job-meta-row">
                            <span class="meta-item company-name-link">
                                <span class="material-symbols-rounded">apartment</span>
                                <?= htmlspecialchars($job['company_name'], ENT_QUOTES, 'UTF-8') ?>
                            </span>

                            <span class="meta-item">
                                <span class="material-symbols-rounded">pin_drop</span>
                                <?= htmlspecialchars($job['location'], ENT_QUOTES, 'UTF-8') ?>
                            </span>

                            <span class="salary-tag ai-est">
                                <span class="material-symbols-rounded" style="font-size: 16px;">stars</span>
                                <?= format_salary($job['salary_min'], $job['salary_max'], $job['salary_type']) ?>
                            </span>

                            <?php if ($job['is_remote']): ?>
                                <span class="badge-remote">Remote</span>
                            <?php endif; ?>

                            <span style="background: #f3f4f6; color: #4b5563; font-size: 0.8rem; font-weight: 600; padding: 3px 8px; border-radius: var(--radius-pill);">
                                <?= htmlspecialchars($job['job_type_name']) ?>
                            </span>
                        </div>

                        <div class="job-card-footer">
                            <div class="posted-time">
                                <span class="material-symbols-rounded" style="font-size: 16px;">bookmark</span>
                                Saved on <?= date('M j, Y', strtotime($job['saved_at'])) ?>
                            </div>

                            <div class="card-actions">
                                <a href="<?= url('saved-jobs.php?action=remove&id=' . $job['id']) ?>" class="btn btn-outline-white" style="color: #6b7280; border-color: var(--border-color); padding: 7px 16px; font-size: 0.84rem;">
                                    <span class="material-symbols-rounded" style="font-size: 18px;">delete</span> Remove
                                </a>
                                <a href="<?= url('apply.php?job_id=' . $job['id']) ?>" class="btn btn-red" style="padding: 7px 20px; font-size: 0.86rem;">
                                    Apply Now
                                </a>
                            </div>
                        </div>
                    </article>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

