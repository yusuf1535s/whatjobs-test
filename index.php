<?php
/**
 * WhatJobs Clone - Homepage (index.php)
 * Faithful recreation of https://www.whatjobs.com/
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/Job.php';

$jobModel = new Job();

$featuredJobs = $jobModel->getFeatured(6);
$latestJobs = $jobModel->getLatest(6);
$categories = $jobModel->getCategories();

$pageTitle = "What Jobs Are Near Me? | USA Local Job Search | WhatJobs";
require_once __DIR__ . '/includes/header.php';
?>

<!-- 1. Hero Search Section -->
<section class="hero-section">
    <div class="container">
        <h1 class="hero-tagline" style="font-size: 1.6rem; font-weight: 700; color: #1a1a1a; margin-bottom: 24px;">
            Find thousands of jobs near you
        </h1>

        <!-- Unified Pill Search Bar (Signature WhatJobs Design) -->
        <div class="search-bar-wrapper">
            <form action="<?= url('jobs.php') ?>" method="GET" class="search-pill-form">
                <!-- What Field -->
                <div class="search-field">
                    <span class="material-symbols-rounded">search</span>
                    <input 
                        type="text" 
                        name="what" 
                        class="search-input" 
                        placeholder="Search by job title/role" 
                        autocomplete="off"
                    >
                    <span class="material-symbols-rounded" id="clearSearchWhat" style="cursor: pointer; color: #aaa; font-size: 18px;" title="Clear">close</span>
                </div>

                <div class="field-divider"></div>

                <!-- Where Field -->
                <div class="search-field">
                    <span class="material-symbols-rounded">location_on</span>
                    <input 
                        type="text" 
                        name="where" 
                        class="search-input" 
                        placeholder="Location, city, state or zip" 
                        autocomplete="off"
                    >
                    <span class="material-symbols-rounded" id="clearSearchWhere" style="cursor: pointer; color: #aaa; font-size: 18px;" title="Clear">close</span>
                </div>

                <div class="field-divider"></div>

                <!-- Remote Work Switch -->
                <div class="remote-toggle-wrapper">
                    <label for="remoteToggle">Working Remotely?</label>
                    <label class="switch">
                        <input type="checkbox" id="remoteToggle" name="remote" value="1">
                        <span class="slider"></span>
                    </label>
                </div>

                <!-- Red Submit Button -->
                <button type="submit" class="search-btn-submit">
                    Search Jobs
                </button>
            </form>
        </div>

        <!-- Popular Searches / Quick Chips -->
        <div class="popular-searches">
            <span class="popular-label">Popular Searches:</span>
            <a href="<?= url('jobs.php?remote=1') ?>" class="search-chip">Remote</a>
            <a href="<?= url('jobs.php?what=Software+Developer') ?>" class="search-chip">Software Developer</a>
            <a href="<?= url('jobs.php?what=Nurse') ?>" class="search-chip">Healthcare &amp; Nursing</a>
            <a href="<?= url('jobs.php?what=Customer+Service') ?>" class="search-chip">Customer Service</a>
            <a href="<?= url('jobs.php?job_type=part-time') ?>" class="search-chip">Part-Time</a>
            <a href="<?= url('jobs.php?what=Financial+Analyst') ?>" class="search-chip">Finance</a>
            <a href="<?= url('jobs.php?what=Work+from+Home') ?>" class="search-chip">Work From Home</a>
        </div>
    </div>
</section>

<!-- 2. Promotional Feature Cards (Exact WhatJobs Cards) -->
<section class="promo-section">
    <div class="container">
        <div class="promo-grid">
            <!-- Card 1 -->
            <div class="promo-card">
                <div>
                    <div class="promo-icon">
                        <span class="material-symbols-rounded" style="font-size: 26px;">description</span>
                    </div>
                    <h2 class="promo-title">Register Your Resume</h2>
                    <p class="promo-desc">Apply for jobs in seconds and get noticed by top employers looking for your skills.</p>
                </div>
                <div>
                    <a href="<?= url('register.php') ?>" class="promo-action">
                        Get started <span class="material-symbols-rounded">arrow_forward</span>
                    </a>
                </div>
            </div>

            <!-- Card 2 -->
            <div class="promo-card">
                <div>
                    <div class="promo-icon">
                        <span class="material-symbols-rounded" style="font-size: 26px;">notifications_active</span>
                    </div>
                    <h2 class="promo-title">Instant Job Alerts</h2>
                    <p class="promo-desc">Get customized job openings matching your search criteria sent directly to your inbox.</p>
                </div>
                <div>
                    <a href="<?= url('register.php') ?>" class="promo-action">
                        Create Alert <span class="material-symbols-rounded">arrow_forward</span>
                    </a>
                </div>
            </div>

            <!-- Card 3 -->
            <div class="promo-card">
                <div>
                    <div class="promo-icon">
                        <span class="material-symbols-rounded" style="font-size: 26px;">business_center</span>
                    </div>
                    <h2 class="promo-title">Hiring Now?</h2>
                    <p class="promo-desc">Discover top candidates today. Post open vacancies and reach thousands of qualified applicants.</p>
                </div>
                <div>
                    <a href="<?= url('admin/login.php') ?>" class="promo-action">
                        Post Job <span class="material-symbols-rounded">arrow_forward</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- 3. Featured Jobs Section -->
<section class="section-wrapper" style="background-color: #f8f9fa;">
    <div class="container">
        <div class="section-header">
            <div>
                <h2 class="section-title">Featured Jobs</h2>
                <p class="section-subtitle">Hand-picked high-growth opportunities from leading organizations</p>
            </div>
            <a href="<?= url('jobs.php?featured=1') ?>" class="view-all-link">
                View all featured jobs <span class="material-symbols-rounded">chevron_right</span>
            </a>
        </div>

        <div class="job-grid">
            <?php foreach ($featuredJobs as $job): ?>
                <?php 
                    $isSaved = $user ? $jobModel->isSavedByUser($job['id'], $user['id']) : false;
                ?>
                <article class="job-card">
                    <div class="job-card-header">
                        <div>
                            <h3 class="job-title">
                                <a href="<?= url('job-detail.php?id=' . $job['id']) ?>">
                                    <?= htmlspecialchars($job['title'], ENT_QUOTES, 'UTF-8') ?>
                                </a>
                            </h3>
                        </div>
                        <div class="company-logo-box">
                            <?php if (!empty($job['company_logo'])): ?>
                                <img src="<?= htmlspecialchars($job['company_logo'], ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($job['company_name'], ENT_QUOTES, 'UTF-8') ?>" onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($job['company_name']) ?>&background=181818&color=fff';">
                            <?php else: ?>
                                <span class="material-symbols-rounded" style="color: #666;">business</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Highlights & Meta row -->
                    <div class="job-meta-row">
                        <a href="<?= url('jobs.php?what=' . urlencode($job['company_name'])) ?>" class="meta-item company-name-link">
                            <span class="material-symbols-rounded">apartment</span>
                            <?= htmlspecialchars($job['company_name'], ENT_QUOTES, 'UTF-8') ?>
                        </a>

                        <span class="meta-item">
                            <span class="material-symbols-rounded">pin_drop</span>
                            <?= htmlspecialchars($job['location'], ENT_QUOTES, 'UTF-8') ?>
                        </span>

                        <?php if ($job['is_remote']): ?>
                            <span class="badge-remote">Remote</span>
                        <?php endif; ?>

                        <span class="badge-featured">Featured</span>

                        <!-- Salary estimate badge -->
                        <span class="salary-tag ai-est" title="Estimated compensation range">
                            <span class="material-symbols-rounded" style="font-size: 16px;">stars</span>
                            <?= format_salary($job['salary_min'], $job['salary_max'], $job['salary_type']) ?>
                        </span>
                    </div>

                    <p class="job-snippet">
                        <?= htmlspecialchars(substr($job['description'], 0, 160), ENT_QUOTES, 'UTF-8') ?>...
                    </p>

                    <div class="job-card-footer">
                        <div class="posted-time">
                            <span class="material-symbols-rounded" style="font-size: 16px;">schedule</span>
                            <?= time_ago($job['created_at']) ?>
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
                                View Details
                            </a>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- 4. Job Categories Section -->
<section class="section-wrapper" style="background-color: #ffffff;">
    <div class="container">
        <div class="section-header">
            <div>
                <h2 class="section-title">Explore by Job Category</h2>
                <p class="section-subtitle">Find jobs aligned with your career focus and industry expertise</p>
            </div>
            <a href="<?= url('jobs.php') ?>" class="view-all-link">
                All categories <span class="material-symbols-rounded">chevron_right</span>
            </a>
        </div>

        <div class="category-grid">
            <?php foreach ($categories as $cat): ?>
                <a href="<?= url('jobs.php?category=' . urlencode($cat['slug'])) ?>" class="category-card">
                    <div class="category-icon-box">
                        <span class="material-symbols-rounded"><?= htmlspecialchars($cat['icon'], ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                    <div class="category-info">
                        <h3><?= htmlspecialchars($cat['name'], ENT_QUOTES, 'UTF-8') ?></h3>
                        <div class="category-count"><?= (int)$cat['active_count'] ?> active jobs</div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- 5. Latest Jobs Section -->
<section class="section-wrapper" style="background-color: #f8f9fa;">
    <div class="container">
        <div class="section-header">
            <div>
                <h2 class="section-title">Latest Job Openings</h2>
                <p class="section-subtitle">Newly posted job openings across top industries</p>
            </div>
            <a href="<?= url('jobs.php?sort=newest') ?>" class="view-all-link">
                View all recent jobs <span class="material-symbols-rounded">chevron_right</span>
            </a>
        </div>

        <div class="job-grid">
            <?php foreach ($latestJobs as $job): ?>
                <?php 
                    $isSaved = $user ? $jobModel->isSavedByUser($job['id'], $user['id']) : false;
                ?>
                <article class="job-card">
                    <div class="job-card-header">
                        <div>
                            <h3 class="job-title">
                                <a href="<?= url('job-detail.php?id=' . $job['id']) ?>">
                                    <?= htmlspecialchars($job['title'], ENT_QUOTES, 'UTF-8') ?>
                                </a>
                            </h3>
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

                        <span class="salary-tag">
                            <?= format_salary($job['salary_min'], $job['salary_max'], $job['salary_type']) ?>
                        </span>

                        <?php if ($job['is_remote']): ?>
                            <span class="badge-remote">Remote</span>
                        <?php endif; ?>
                    </div>

                    <p class="job-snippet">
                        <?= htmlspecialchars(substr($job['description'], 0, 160), ENT_QUOTES, 'UTF-8') ?>...
                    </p>

                    <div class="job-card-footer">
                        <div class="posted-time">
                            <span class="material-symbols-rounded" style="font-size: 16px;">schedule</span>
                            <?= time_ago($job['created_at']) ?>
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
                                View Details
                            </a>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- 6. Popular Locations Section -->
<section class="section-wrapper" style="background-color: #ffffff; border-top: 1px solid var(--border-color);">
    <div class="container">
        <div class="section-header">
            <div>
                <h2 class="section-title">Popular Job Locations</h2>
                <p class="section-subtitle">Explore job markets with high employer demand</p>
            </div>
        </div>

        <div class="location-chips-grid">
            <a href="<?= url('jobs.php?where=New+York%2C+NY') ?>" class="location-chip">
                <span class="material-symbols-rounded">location_city</span> New York, NY
            </a>
            <a href="<?= url('jobs.php?where=Austin%2C+TX') ?>" class="location-chip">
                <span class="material-symbols-rounded">location_city</span> Austin, TX
            </a>
            <a href="<?= url('jobs.php?where=Seattle%2C+WA') ?>" class="location-chip">
                <span class="material-symbols-rounded">location_city</span> Seattle, WA
            </a>
            <a href="<?= url('jobs.php?where=Mountain+View%2C+CA') ?>" class="location-chip">
                <span class="material-symbols-rounded">location_city</span> Mountain View, CA
            </a>
            <a href="<?= url('jobs.php?where=San+Francisco%2C+CA') ?>" class="location-chip">
                <span class="material-symbols-rounded">location_city</span> San Francisco, CA
            </a>
            <a href="<?= url('jobs.php?where=Chicago%2C+IL') ?>" class="location-chip">
                <span class="material-symbols-rounded">location_city</span> Chicago, IL
            </a>
            <a href="<?= url('jobs.php?remote=1') ?>" class="location-chip">
                <span class="material-symbols-rounded">home_work</span> Remote (Nationwide)
            </a>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

