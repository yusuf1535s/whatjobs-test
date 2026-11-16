<?php
/**
 * WhatJobs Clone - Job Detail Page (job-detail.php)
 * Deep job view with company insights, highlights, requirements, and direct apply trigger
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/Job.php';
require_once __DIR__ . '/includes/Application.php';

$jobModel = new Job();
$appModel = new Application();

$jobId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$slug = trim($_GET['slug'] ?? '');

$job = null;
if ($jobId > 0) {
    $job = $jobModel->getById($jobId);
} elseif (!empty($slug)) {
    $job = $jobModel->getBySlug($slug);
}

if (!$job || $job['status'] !== 'active') {
    set_flash('error', 'The requested job vacancy was not found or is no longer active.');
    header('Location: ' . url('jobs.php'));
    exit;
}

// Increment view counter
$jobModel->incrementViews($job['id']);

$user = current_user();
$isSaved = $user ? $jobModel->isSavedByUser($job['id'], $user['id']) : false;
$hasApplied = $user ? $appModel->hasUserApplied($job['id'], $user['id']) : false;

// Get similar jobs from the same category
$similarJobs = $jobModel->search([
    'category_id' => $job['category_id']
], 4);

$pageTitle = htmlspecialchars($job['title']) . " at " . htmlspecialchars($job['company_name']) . " | WhatJobs";
require_once __DIR__ . '/includes/header.php';
?>

<!-- Breadcrumbs -->
<div style="background: #ffffff; border-bottom: 1px solid var(--border-color); padding: 12px 0;">
    <div class="container" style="display: flex; align-items: center; gap: 8px; font-size: 0.85rem; color: var(--text-muted);">
        <a href="<?= url('index.php') ?>" style="color: var(--text-dark);">Home</a>
        <span class="material-symbols-rounded" style="font-size: 16px;">chevron_right</span>
        <a href="<?= url('jobs.php') ?>" style="color: var(--text-dark);">Jobs</a>
        <span class="material-symbols-rounded" style="font-size: 16px;">chevron_right</span>
        <a href="<?= url('jobs.php?category=' . urlencode($job['category_slug'])) ?>" style="color: var(--text-dark);"><?= htmlspecialchars($job['category_name']) ?></a>
        <span class="material-symbols-rounded" style="font-size: 16px;">chevron_right</span>
        <span style="color: #999; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 300px;"><?= htmlspecialchars($job['title']) ?></span>
    </div>
</div>

<main class="container" style="padding-top: 32px; padding-bottom: 60px;">
    <div style="display: grid; grid-template-columns: 1fr 340px; gap: 32px; align-items: flex-start;">
        
        <!-- Left Main Content Column -->
        <div>
            <!-- Job Header Card -->
            <div style="background: #ffffff; border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 32px; margin-bottom: 24px; box-shadow: var(--shadow-sm);">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 20px; margin-bottom: 16px;">
                    <div>
                        <h1 style="font-size: 1.85rem; font-weight: 800; color: var(--text-dark); line-height: 1.25; margin-bottom: 8px;">
                            <?= htmlspecialchars($job['title'], ENT_QUOTES, 'UTF-8') ?>
                        </h1>
                        <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
                            <a href="<?= url('jobs.php?what=' . urlencode($job['company_name'])) ?>" style="font-size: 1.05rem; font-weight: 600; color: var(--primary-red); display: flex; align-items: center; gap: 4px;">
                                <span class="material-symbols-rounded" style="font-size: 20px;">apartment</span>
                                <?= htmlspecialchars($job['company_name'], ENT_QUOTES, 'UTF-8') ?>
                            </a>
                            <span style="color: #cbd5e1;">•</span>
                            <span style="font-size: 0.95rem; color: #4b5563; display: flex; align-items: center; gap: 4px;">
                                <span class="material-symbols-rounded" style="font-size: 18px;">location_on</span>
                                <?= htmlspecialchars($job['location'], ENT_QUOTES, 'UTF-8') ?>
                                <?php if (!empty($job['zip_code'])): ?>(<?= htmlspecialchars($job['zip_code']) ?>)<?php endif; ?>
                            </span>
                        </div>
                    </div>

                    <div class="company-logo-box" style="width: 64px; height: 64px; border-radius: var(--radius-md);">
                        <?php if (!empty($job['company_logo'])): ?>
                            <img src="<?= htmlspecialchars($job['company_logo'], ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($job['company_name'], ENT_QUOTES, 'UTF-8') ?>" onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($job['company_name']) ?>&background=181818&color=fff';">
                        <?php else: ?>
                            <span class="material-symbols-rounded" style="font-size: 32px; color: #666;">business</span>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Highlights row -->
                <div style="display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 24px; padding-bottom: 20px; border-bottom: 1px solid var(--border-light);">
                    <span class="salary-tag ai-est" style="font-size: 0.9rem; padding: 6px 14px;">
                        <span class="material-symbols-rounded" style="font-size: 18px;">stars</span>
                        <?= format_salary($job['salary_min'], $job['salary_max'], $job['salary_type']) ?>
                    </span>

                    <span style="background: #f3f4f6; color: #1f2937; padding: 6px 12px; border-radius: var(--radius-pill); font-size: 0.86rem; font-weight: 600; display: inline-flex; align-items: center; gap: 4px;">
                        <span class="material-symbols-rounded" style="font-size: 16px;">work</span>
                        <?= htmlspecialchars($job['job_type_name']) ?>
                    </span>

                    <span style="background: #f3f4f6; color: #1f2937; padding: 6px 12px; border-radius: var(--radius-pill); font-size: 0.86rem; font-weight: 600; display: inline-flex; align-items: center; gap: 4px;">
                        <span class="material-symbols-rounded" style="font-size: 16px;">stairs</span>
                        <?= htmlspecialchars($job['experience_level']) ?>
                    </span>

                    <?php if ($job['is_remote']): ?>
                        <span class="badge-remote" style="padding: 6px 12px; font-size: 0.86rem;">
                            Remote Eligible
                        </span>
                    <?php endif; ?>
                </div>

                <!-- Action CTA Buttons -->
                <div style="display: flex; align-items: center; gap: 16px; flex-wrap: wrap;">
                    <?php if ($hasApplied): ?>
                        <button class="btn" style="background: #e6f4ea; color: #137333; cursor: default; padding: 12px 28px; font-size: 0.98rem;" disabled>
                            <span class="material-symbols-rounded">check_circle</span> Application Submitted
                        </button>
                    <?php else: ?>
                        <a href="<?= url('apply.php?job_id=' . $job['id']) ?>" class="btn btn-red" style="padding: 13px 36px; font-size: 1rem;">
                            Apply for this Job <span class="material-symbols-rounded">send</span>
                        </a>
                    <?php endif; ?>

                    <button 
                        type="button" 
                        class="btn btn-save-job <?= $isSaved ? 'saved' : '' ?>" 
                        data-job-id="<?= $job['id'] ?>"
                        style="width: auto; height: auto; border-radius: var(--radius-pill); padding: 11px 20px; font-size: 0.92rem; font-weight: 600;"
                    >
                        <span class="material-symbols-rounded" style="font-size: 20px;">
                            <?= $isSaved ? 'bookmark' : 'bookmark_border' ?>
                        </span>
                        <span><?= $isSaved ? 'Saved' : 'Save Job' ?></span>
                    </button>
                </div>
            </div>

            <!-- Job Description Body -->
            <div style="background: #ffffff; border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 32px; box-shadow: var(--shadow-sm); margin-bottom: 24px;">
                <h2 style="font-size: 1.3rem; font-weight: 700; color: var(--text-dark); margin-bottom: 16px;">
                    Job Overview &amp; Description
                </h2>
                <div style="font-size: 1rem; color: #374151; line-height: 1.7; margin-bottom: 28px; white-space: pre-line;">
                    <?= nl2br(htmlspecialchars($job['description'], ENT_QUOTES, 'UTF-8')) ?>
                </div>

                <?php if (!empty($job['responsibilities'])): ?>
                    <h3 style="font-size: 1.15rem; font-weight: 700; color: var(--text-dark); margin-bottom: 12px;">
                        Key Responsibilities
                    </h3>
                    <div style="font-size: 0.95rem; color: #374151; line-height: 1.7; margin-bottom: 28px; white-space: pre-line;">
                        <?= nl2br(htmlspecialchars($job['responsibilities'], ENT_QUOTES, 'UTF-8')) ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($job['requirements'])): ?>
                    <h3 style="font-size: 1.15rem; font-weight: 700; color: var(--text-dark); margin-bottom: 12px;">
                        Required Qualifications &amp; Skills
                    </h3>
                    <div style="font-size: 0.95rem; color: #374151; line-height: 1.7; margin-bottom: 28px; white-space: pre-line;">
                        <?= nl2br(htmlspecialchars($job['requirements'], ENT_QUOTES, 'UTF-8')) ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($job['benefits'])): ?>
                    <h3 style="font-size: 1.15rem; font-weight: 700; color: var(--text-dark); margin-bottom: 12px;">
                        Benefits &amp; Compensation Perks
                    </h3>
                    <div style="font-size: 0.95rem; color: #374151; line-height: 1.7; white-space: pre-line;">
                        <?= nl2br(htmlspecialchars($job['benefits'], ENT_QUOTES, 'UTF-8')) ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Right Details Sidebar -->
        <aside style="display: flex; flex-direction: column; gap: 24px;">
            <!-- Key Facts Card -->
            <div style="background: #ffffff; border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 24px; box-shadow: var(--shadow-sm);">
                <h3 style="font-size: 1.05rem; font-weight: 700; color: var(--text-dark); margin-bottom: 16px; padding-bottom: 10px; border-bottom: 1px solid var(--border-light);">
                    Job Summary
                </h3>
                <div style="display: flex; flex-direction: column; gap: 14px; font-size: 0.88rem;">
                    <div>
                        <span style="color: var(--text-muted); display: block; font-size: 0.8rem;">Date Posted:</span>
                        <strong style="color: var(--text-dark);"><?= time_ago($job['created_at']) ?> (<?= date('M j, Y', strtotime($job['created_at'])) ?>)</strong>
                    </div>
                    <div>
                        <span style="color: var(--text-muted); display: block; font-size: 0.8rem;">Role Category:</span>
                        <strong style="color: var(--text-dark);"><?= htmlspecialchars($job['category_name']) ?></strong>
                    </div>
                    <div>
                        <span style="color: var(--text-muted); display: block; font-size: 0.8rem;">Employment Type:</span>
                        <strong style="color: var(--text-dark);"><?= htmlspecialchars($job['job_type_name']) ?></strong>
                    </div>
                    <div>
                        <span style="color: var(--text-muted); display: block; font-size: 0.8rem;">Experience Level:</span>
                        <strong style="color: var(--text-dark);"><?= htmlspecialchars($job['experience_level']) ?></strong>
                    </div>
                    <div>
                        <span style="color: var(--text-muted); display: block; font-size: 0.8rem;">Workplace Type:</span>
                        <strong style="color: var(--text-dark);"><?= $job['is_remote'] ? 'Remote / Work From Home' : 'On-Site (' . htmlspecialchars($job['location']) . ')' ?></strong>
                    </div>
                    <div>
                        <span style="color: var(--text-muted); display: block; font-size: 0.8rem;">Total Impressions:</span>
                        <strong style="color: var(--text-dark);"><?= (int)$job['views_count'] ?> views</strong>
                    </div>
                </div>
            </div>

            <!-- About Company Card -->
            <div style="background: #ffffff; border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 24px; box-shadow: var(--shadow-sm);">
                <h3 style="font-size: 1.05rem; font-weight: 700; color: var(--text-dark); margin-bottom: 14px;">
                    About <?= htmlspecialchars($job['company_name']) ?>
                </h3>
                <p style="font-size: 0.88rem; color: #4b5563; line-height: 1.55; margin-bottom: 16px;">
                    <?= htmlspecialchars($job['company_description'] ?? 'Leading organization providing opportunities and professional growth across industry sectors.') ?>
                </p>
                <?php if (!empty($job['company_website'])): ?>
                    <a href="<?= htmlspecialchars($job['company_website']) ?>" target="_blank" rel="noopener noreferrer" style="font-size: 0.88rem; color: var(--primary-red); font-weight: 600; display: inline-flex; align-items: center; gap: 4px;">
                        Visit Company Website <span class="material-symbols-rounded" style="font-size: 16px;">open_in_new</span>
                    </a>
                <?php endif; ?>
            </div>

            <!-- Similar Jobs -->
            <?php if (!empty($similarJobs)): ?>
                <div style="background: #ffffff; border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 24px; box-shadow: var(--shadow-sm);">
                    <h3 style="font-size: 1.05rem; font-weight: 700; color: var(--text-dark); margin-bottom: 14px;">
                        Similar Opportunities
                    </h3>
                    <div style="display: flex; flex-direction: column; gap: 14px;">
                        <?php foreach ($similarJobs as $sim): ?>
                            <?php if ($sim['id'] == $job['id']) continue; ?>
                            <div style="border-bottom: 1px solid var(--border-light); padding-bottom: 12px;">
                                <a href="<?= url('job-detail.php?id=' . $sim['id']) ?>" style="font-weight: 700; font-size: 0.92rem; color: var(--text-dark); display: block; margin-bottom: 4px;">
                                    <?= htmlspecialchars($sim['title']) ?>
                                </a>
                                <span style="font-size: 0.82rem; color: var(--text-muted); display: block;">
                                    <?= htmlspecialchars($sim['company_name']) ?> • <?= htmlspecialchars($sim['location']) ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </aside>

    </div>
</main>

<style>
@media (max-width: 860px) {
    main.container > div {
        grid-template-columns: 1fr !important;
    }
}
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

