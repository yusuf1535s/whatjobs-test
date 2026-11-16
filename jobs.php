<?php
/**
 * WhatJobs Clone - Job Listings & Search Results (jobs.php)
 * Closely matching WhatJobs search results layout and multi-faceted filtering
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/Job.php';

$jobModel = new Job();

// Collect search filters
$what = trim($_GET['what'] ?? '');
$where = trim($_GET['where'] ?? '');
$remote = isset($_GET['remote']) && $_GET['remote'] == '1' ? 1 : 0;
$categorySlug = trim($_GET['category'] ?? '');
$jobTypeSlug = trim($_GET['job_type'] ?? '');
$experience = trim($_GET['experience'] ?? '');
$salaryMin = !empty($_GET['salary_min']) ? (float)$_GET['salary_min'] : null;
$sort = trim($_GET['sort'] ?? 'newest');
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 8;
$offset = ($page - 1) * $limit;

$filters = [
    'what' => $what,
    'where' => $where,
    'remote' => $remote,
    'category_slug' => $categorySlug,
    'job_type_slug' => $jobTypeSlug,
    'experience_level' => $experience,
    'salary_min' => $salaryMin,
    'sort' => $sort,
];

$totalJobs = $jobModel->count($filters);
$jobs = $jobModel->search($filters, $limit, $offset);
$totalPages = ceil($totalJobs / $limit);

$categories = $jobModel->getCategories();
$jobTypes = $jobModel->getJobTypes();

// Build page title based on query
if (!empty($what) && !empty($where)) {
    $pageTitle = "What " . htmlspecialchars($what) . " Jobs are in " . htmlspecialchars($where) . "? | WhatJobs";
} elseif (!empty($what)) {
    $pageTitle = "What " . htmlspecialchars($what) . " Jobs are in the United States? | WhatJobs";
} elseif (!empty($where)) {
    $pageTitle = "Jobs in " . htmlspecialchars($where) . " | WhatJobs";
} else {
    $pageTitle = "Browse All Job Vacancies | WhatJobs";
}

require_once __DIR__ . '/includes/header.php';
?>

<!-- Search Bar Header (Compact Variant) -->
<section style="background-color: #ffffff; border-bottom: 1px solid var(--border-color); padding: 20px 0;">
    <div class="container">
        <form action="<?= url('jobs.php') ?>" method="GET" class="search-pill-form" style="max-width: 1000px; margin: 0 auto;">
            <!-- What -->
            <div class="search-field">
                <span class="material-symbols-rounded">search</span>
                <input 
                    type="text" 
                    name="what" 
                    value="<?= htmlspecialchars($what, ENT_QUOTES, 'UTF-8') ?>" 
                    class="search-input" 
                    placeholder="Search by job title/role"
                >
            </div>

            <div class="field-divider"></div>

            <!-- Where -->
            <div class="search-field">
                <span class="material-symbols-rounded">location_on</span>
                <input 
                    type="text" 
                    name="where" 
                    value="<?= htmlspecialchars($where, ENT_QUOTES, 'UTF-8') ?>" 
                    class="search-input" 
                    placeholder="Location, city, state or zip"
                >
            </div>

            <div class="field-divider"></div>

            <!-- Remote -->
            <div class="remote-toggle-wrapper">
                <label for="searchRemoteToggle">Working Remotely?</label>
                <label class="switch">
                    <input type="checkbox" id="searchRemoteToggle" name="remote" value="1" <?= $remote ? 'checked' : '' ?>>
                    <span class="slider"></span>
                </label>
            </div>

            <button type="submit" class="search-btn-submit">
                Find Jobs
            </button>
        </form>
    </div>
</section>

<!-- Main Results & Sidebar Filter Layout -->
<main class="container" style="padding-top: 32px; padding-bottom: 60px;">
    <div style="display: grid; grid-template-columns: 280px 1fr; gap: 32px; align-items: flex-start;">
        
        <!-- Left Filter Sidebar -->
        <aside style="background: #ffffff; border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 24px; position: sticky; top: 88px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-bottom: 12px; border-bottom: 1px solid var(--border-light);">
                <h3 style="font-size: 1.1rem; font-weight: 700; color: var(--text-dark); display: flex; align-items: center; gap: 6px;">
                    <span class="material-symbols-rounded">tune</span> Filters
                </h3>
                <?php if (!empty($what) || !empty($where) || $remote || !empty($categorySlug) || !empty($jobTypeSlug) || !empty($experience) || $salaryMin): ?>
                    <a href="<?= url('jobs.php') ?>" style="font-size: 0.82rem; color: var(--primary-red); font-weight: 600;">Clear All</a>
                <?php endif; ?>
            </div>

            <form action="<?= url('jobs.php') ?>" method="GET" id="sidebarFilterForm">
                <!-- Preserve search terms -->
                <input type="hidden" name="what" value="<?= htmlspecialchars($what, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="where" value="<?= htmlspecialchars($where, ENT_QUOTES, 'UTF-8') ?>">
                <?php if ($remote): ?><input type="hidden" name="remote" value="1"><?php endif; ?>

                <!-- Sort Filter -->
                <div style="margin-bottom: 22px;">
                    <label style="display: block; font-size: 0.88rem; font-weight: 700; color: var(--text-dark); margin-bottom: 8px;">Sort By</label>
                    <select name="sort" onchange="this.form.submit()" style="width: 100%; padding: 8px 12px; border: 1px solid var(--border-color); border-radius: var(--radius-sm); font-size: 0.88rem; background: #fff;">
                        <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Most Recent</option>
                        <option value="salary" <?= $sort === 'salary' ? 'selected' : '' ?>>Highest Salary</option>
                        <option value="popular" <?= $sort === 'popular' ? 'selected' : '' ?>>Most Popular</option>
                    </select>
                </div>

                <!-- Job Type Filter -->
                <div style="margin-bottom: 22px;">
                    <label style="display: block; font-size: 0.88rem; font-weight: 700; color: var(--text-dark); margin-bottom: 10px;">Job Type</label>
                    <div style="display: flex; flex-direction: column; gap: 8px;">
                        <label style="display: flex; align-items: center; gap: 8px; font-size: 0.88rem; color: #4b5563; cursor: pointer;">
                            <input type="radio" name="job_type" value="" <?= empty($jobTypeSlug) ? 'checked' : '' ?> onchange="this.form.submit()">
                            All Types
                        </label>
                        <?php foreach ($jobTypes as $jt): ?>
                            <label style="display: flex; align-items: center; gap: 8px; font-size: 0.88rem; color: #4b5563; cursor: pointer;">
                                <input type="radio" name="job_type" value="<?= htmlspecialchars($jt['slug']) ?>" <?= $jobTypeSlug === $jt['slug'] ? 'checked' : '' ?> onchange="this.form.submit()">
                                <?= htmlspecialchars($jt['name']) ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Category Filter -->
                <div style="margin-bottom: 22px;">
                    <label style="display: block; font-size: 0.88rem; font-weight: 700; color: var(--text-dark); margin-bottom: 8px;">Category</label>
                    <select name="category" onchange="this.form.submit()" style="width: 100%; padding: 8px 12px; border: 1px solid var(--border-color); border-radius: var(--radius-sm); font-size: 0.88rem; background: #fff;">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= htmlspecialchars($cat['slug']) ?>" <?= $categorySlug === $cat['slug'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['name']) ?> (<?= $cat['active_count'] ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Experience Level -->
                <div style="margin-bottom: 22px;">
                    <label style="display: block; font-size: 0.88rem; font-weight: 700; color: var(--text-dark); margin-bottom: 8px;">Experience Level</label>
                    <select name="experience" onchange="this.form.submit()" style="width: 100%; padding: 8px 12px; border: 1px solid var(--border-color); border-radius: var(--radius-sm); font-size: 0.88rem; background: #fff;">
                        <option value="">All Levels</option>
                        <option value="Entry Level" <?= $experience === 'Entry Level' ? 'selected' : '' ?>>Entry Level</option>
                        <option value="Mid Level" <?= $experience === 'Mid Level' ? 'selected' : '' ?>>Mid Level</option>
                        <option value="Senior Level" <?= $experience === 'Senior Level' ? 'selected' : '' ?>>Senior Level</option>
                        <option value="Lead / Director" <?= $experience === 'Lead / Director' ? 'selected' : '' ?>>Lead / Director</option>
                    </select>
                </div>

                <!-- Minimum Salary Filter -->
                <div style="margin-bottom: 22px;">
                    <label style="display: block; font-size: 0.88rem; font-weight: 700; color: var(--text-dark); margin-bottom: 8px;">Minimum Salary</label>
                    <select name="salary_min" onchange="this.form.submit()" style="width: 100%; padding: 8px 12px; border: 1px solid var(--border-color); border-radius: var(--radius-sm); font-size: 0.88rem; background: #fff;">
                        <option value="">Any Salary</option>
                        <option value="50000" <?= $salaryMin == 50000 ? 'selected' : '' ?>>$50,000+ / yr</option>
                        <option value="80000" <?= $salaryMin == 80000 ? 'selected' : '' ?>>$80,000+ / yr</option>
                        <option value="100000" <?= $salaryMin == 100000 ? 'selected' : '' ?>>$100,000+ / yr</option>
                        <option value="130000" <?= $salaryMin == 130000 ? 'selected' : '' ?>>$130,000+ / yr</option>
                    </select>
                </div>
            </form>
        </aside>

        <!-- Right Job Listings Column -->
        <section>
            <!-- Results Count Bar (WhatJobs Style) -->
            <div style="margin-bottom: 20px;">
                <h1 style="font-size: 1.35rem; font-weight: 700; color: var(--text-dark); line-height: 1.4;">
                    Showing <strong><?= $totalJobs ?></strong> 
                    <?= !empty($what) ? '<strong>' . htmlspecialchars($what) . '</strong>' : '' ?> 
                    jobs <?= !empty($where) ? 'in <strong>' . htmlspecialchars($where) . '</strong>' : 'in <strong>the United States</strong>' ?>
                </h1>
                <p style="font-size: 0.88rem; color: var(--text-muted); margin-top: 4px;">
                    Updated live from verified employers and top recruitment partners
                </p>
            </div>

            <!-- Active Filter Badges -->
            <?php if (!empty($categorySlug) || !empty($jobTypeSlug) || !empty($experience) || $remote || $salaryMin): ?>
                <div style="display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 20px;">
                    <?php if ($remote): ?>
                        <span style="background: #e0f2fe; color: #0369a1; padding: 4px 10px; border-radius: var(--radius-pill); font-size: 0.8rem; font-weight: 600;">
                            Remote Only
                        </span>
                    <?php endif; ?>
                    <?php if (!empty($categorySlug)): ?>
                        <span style="background: #f1f5f9; color: #334155; padding: 4px 10px; border-radius: var(--radius-pill); font-size: 0.8rem; font-weight: 600;">
                            Category: <?= htmlspecialchars($categorySlug) ?>
                        </span>
                    <?php endif; ?>
                    <?php if (!empty($jobTypeSlug)): ?>
                        <span style="background: #f1f5f9; color: #334155; padding: 4px 10px; border-radius: var(--radius-pill); font-size: 0.8rem; font-weight: 600;">
                            Type: <?= htmlspecialchars($jobTypeSlug) ?>
                        </span>
                    <?php endif; ?>
                    <?php if (!empty($experience)): ?>
                        <span style="background: #f1f5f9; color: #334155; padding: 4px 10px; border-radius: var(--radius-pill); font-size: 0.8rem; font-weight: 600;">
                            Level: <?= htmlspecialchars($experience) ?>
                        </span>
                    <?php endif; ?>
                    <?php if ($salaryMin): ?>
                        <span style="background: #f1f5f9; color: #334155; padding: 4px 10px; border-radius: var(--radius-pill); font-size: 0.8rem; font-weight: 600;">
                            Min: $<?= number_format($salaryMin) ?>
                        </span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- Job Listings Stack -->
            <?php if (empty($jobs)): ?>
                <div style="background: #ffffff; border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 48px 24px; text-align: center;">
                    <span class="material-symbols-rounded" style="font-size: 48px; color: #9ca3af; margin-bottom: 16px;">search_off</span>
                    <h3 style="font-size: 1.2rem; font-weight: 700; color: var(--text-dark); margin-bottom: 8px;">No matching jobs found</h3>
                    <p style="color: var(--text-muted); font-size: 0.92rem; max-width: 480px; margin: 0 auto 20px auto;">
                        Try broadening your search keywords, clearing specific filters, or searching in a different city or state.
                    </p>
                    <a href="<?= url('jobs.php') ?>" class="btn btn-red">View All Jobs</a>
                </div>
            <?php else: ?>
                <div style="display: flex; flex-direction: column; gap: 16px;">
                    <?php foreach ($jobs as $job): ?>
                        <?php 
                            $isSaved = $user ? $jobModel->isSavedByUser($job['id'], $user['id']) : false;
                        ?>
                        <article class="job-card" style="box-shadow: 0 1px 4px rgba(0,0,0,0.04);">
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
                                <a href="<?= url('jobs.php?what=' . urlencode($job['company_name'])) ?>" class="meta-item company-name-link">
                                    <span class="material-symbols-rounded">apartment</span>
                                    <?= htmlspecialchars($job['company_name'], ENT_QUOTES, 'UTF-8') ?>
                                </a>

                                <span class="meta-item">
                                    <span class="material-symbols-rounded">pin_drop</span>
                                    <?= htmlspecialchars($job['location'], ENT_QUOTES, 'UTF-8') ?>
                                    <?php if (!empty($job['zip_code'])): ?>
                                        (<?= htmlspecialchars($job['zip_code']) ?>)
                                    <?php endif; ?>
                                </span>

                                <span class="salary-tag ai-est" title="Estimated salary range">
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

                            <p class="job-snippet">
                                <?= htmlspecialchars(substr($job['description'], 0, 220), ENT_QUOTES, 'UTF-8') ?>...
                            </p>

                            <div class="job-card-footer">
                                <div class="posted-time">
                                    <span class="material-symbols-rounded" style="font-size: 16px;">schedule</span>
                                    <?= time_ago($job['created_at']) ?>
                                    <span style="margin: 0 6px;">•</span>
                                    <span><?= (int)$job['views_count'] ?> views</span>
                                </div>

                                <div class="card-actions">
                                    <button 
                                        type="button" 
                                        class="btn-save-job <?= $isSaved ? 'saved' : '' ?>" 
                                        data-job-id="<?= $job['id'] ?>"
                                        title="<?= $isSaved ? 'Remove from saved' : 'Save job' ?>"
                                    >
                                        <span class="material-symbols-rounded" style="font-size: 20px;">
                                            <?= $isSaved ? 'bookmark' : 'bookmark_border' ?>
                                        </span>
                                    </button>
                                    <a href="<?= url('job-detail.php?id=' . $job['id']) ?>" class="btn btn-red" style="padding: 7px 18px; font-size: 0.86rem;">
                                        Apply / Details
                                    </a>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <div style="display: flex; justify-content: center; gap: 8px; margin-top: 36px;">
                        <?php if ($page > 1): ?>
                            <?php 
                                $prevParams = $_GET;
                                $prevParams['page'] = $page - 1;
                            ?>
                            <a href="<?= url('jobs.php?' . http_build_query($prevParams)) ?>" class="btn btn-outline-white" style="color: var(--text-dark); border-color: var(--border-color);">
                                <span class="material-symbols-rounded">chevron_left</span> Previous
                            </a>
                        <?php endif; ?>

                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <?php 
                                $pageParams = $_GET;
                                $pageParams['page'] = $i;
                                $isActive = $i === $page;
                            ?>
                            <a href="<?= url('jobs.php?' . http_build_query($pageParams)) ?>" class="btn" style="<?= $isActive ? 'background: var(--text-dark); color: #fff;' : 'background: #fff; color: var(--text-dark); border: 1px solid var(--border-color);' ?> min-width: 40px; padding: 8px 14px;">
                                <?= $i ?>
                            </a>
                        <?php endfor; ?>

                        <?php if ($page < $totalPages): ?>
                            <?php 
                                $nextParams = $_GET;
                                $nextParams['page'] = $page + 1;
                            ?>
                            <a href="<?= url('jobs.php?' . http_build_query($nextParams)) ?>" class="btn btn-outline-white" style="color: var(--text-dark); border-color: var(--border-color);">
                                Next <span class="material-symbols-rounded">chevron_right</span>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </section>

    </div>
</main>

<style>
@media (max-width: 860px) {
    main.container > div {
        grid-template-columns: 1fr !important;
    }
    aside {
        position: static !important;
    }
}
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

