<?php
/**
 * WhatJobs Clone - Edit Job Vacancy (admin/edit-job.php)
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/Job.php';
require_once __DIR__ . '/../includes/csrf.php';

require_admin();

$jobModel = new Job();
$jobId = isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : 0;
$job = $jobModel->getById($jobId);

if (!$job) {
    set_flash('error', 'Job posting not found.');
    header('Location: ' . url('admin/manage-jobs.php'));
    exit;
}

$categories = $jobModel->getCategories();
$jobTypes = $jobModel->getJobTypes();
$companies = $jobModel->getCompanies();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Security token invalid. Please refresh the page.';
    }

    $title = trim($_POST['title'] ?? '');
    $companyId = (int)($_POST['company_id'] ?? 0);
    $categoryId = (int)($_POST['category_id'] ?? 0);
    $jobTypeId = (int)($_POST['job_type_id'] ?? 0);
    $location = trim($_POST['location'] ?? '');
    $zipCode = trim($_POST['zip_code'] ?? '');
    $salaryMin = !empty($_POST['salary_min']) ? (float)$_POST['salary_min'] : null;
    $salaryMax = !empty($_POST['salary_max']) ? (float)$_POST['salary_max'] : null;
    $salaryType = $_POST['salary_type'] ?? 'year';
    $isRemote = isset($_POST['is_remote']) ? 1 : 0;
    $isFeatured = isset($_POST['is_featured']) ? 1 : 0;
    $experienceLevel = $_POST['experience_level'] ?? 'Mid Level';
    $status = $_POST['status'] ?? 'active';
    $description = trim($_POST['description'] ?? '');
    $responsibilities = trim($_POST['responsibilities'] ?? '');
    $requirements = trim($_POST['requirements'] ?? '');
    $benefits = trim($_POST['benefits'] ?? '');

    if (empty($title)) {
        $errors[] = 'Job title is required.';
    }
    if ($companyId <= 0) {
        $errors[] = 'Please select a hiring company.';
    }
    if ($categoryId <= 0) {
        $errors[] = 'Please select a job category.';
    }
    if ($jobTypeId <= 0) {
        $errors[] = 'Please select an employment type.';
    }
    if (empty($location)) {
        $errors[] = 'Job location is required.';
    }
    if (empty($description)) {
        $errors[] = 'Job description is required.';
    }

    if (empty($errors)) {
        $success = $jobModel->update($job['id'], [
            'company_id' => $companyId,
            'category_id' => $categoryId,
            'job_type_id' => $jobTypeId,
            'title' => $title,
            'location' => $location,
            'zip_code' => $zipCode,
            'salary_min' => $salaryMin,
            'salary_max' => $salaryMax,
            'salary_type' => $salaryType,
            'is_salary_negotiable' => 0,
            'is_remote' => $isRemote,
            'is_featured' => $isFeatured,
            'experience_level' => $experienceLevel,
            'description' => $description,
            'responsibilities' => $responsibilities,
            'requirements' => $requirements,
            'benefits' => $benefits,
            'status' => $status
        ]);

        if ($success) {
            set_flash('success', 'Job posting #' . $job['id'] . ' updated successfully.');
            header('Location: ' . url('admin/manage-jobs.php'));
            exit;
        } else {
            $errors[] = 'Failed to update job posting.';
        }
    }
}

// Reload current job data
$job = $jobModel->getById($jobId);

$pageTitle = "Edit Job #" . $job['id'] . " | WhatJobs Admin";
require_once __DIR__ . '/includes/admin-header.php';
?>

<div style="max-width: 900px; margin: 0 auto;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
        <div>
            <h1 style="font-size: 1.5rem; font-weight: 800; color: var(--text-dark);">
                Edit Job Vacancy #<?= $job['id'] ?>
            </h1>
            <p style="font-size: 0.88rem; color: var(--text-muted); margin-top: 4px;">
                Editing: <?= htmlspecialchars($job['title']) ?>
            </p>
        </div>
        <div style="display: flex; gap: 12px;">
            <a href="<?= url('job-detail.php?id=' . $job['id']) ?>" target="_blank" class="btn btn-outline-white" style="color: var(--text-dark); border-color: var(--border-color); font-size: 0.85rem; padding: 7px 14px;">
                <span class="material-symbols-rounded" style="font-size: 16px;">open_in_new</span> Preview Live
            </a>
            <a href="<?= url('admin/manage-jobs.php') ?>" style="font-size: 0.88rem; color: var(--primary-red); font-weight: 600; align-self: center;">
                ← Back to Manage Jobs
            </a>
        </div>
    </div>

    <?php if (!empty($errors)): ?>
        <div style="background: #fce8e6; border: 1px solid #f5c2c7; color: #c5221f; border-radius: var(--radius-sm); padding: 14px 18px; margin-bottom: 24px;">
            <ul style="margin-left: 20px; font-size: 0.88rem;">
                <?php foreach ($errors as $err): ?>
                    <li><?= htmlspecialchars($err) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="admin-card" style="padding: 32px;">
        <form action="<?= url('admin/edit-job.php?id=' . $job['id']) ?>" method="POST">
            <?= csrf_input() ?>
            <input type="hidden" name="id" value="<?= $job['id'] ?>">

            <!-- Title -->
            <div style="margin-bottom: 20px;">
                <label style="display: block; font-size: 0.88rem; font-weight: 700; color: var(--text-dark); margin-bottom: 6px;">
                    Job Title <span style="color: var(--primary-red);">*</span>
                </label>
                <input 
                    type="text" 
                    name="title" 
                    required 
                    value="<?= htmlspecialchars($_POST['title'] ?? $job['title'], ENT_QUOTES, 'UTF-8') ?>"
                    style="width: 100%; padding: 11px 14px; border: 1px solid var(--border-color); border-radius: var(--radius-sm); font-size: 0.95rem;"
                >
            </div>

            <!-- Company, Category, Job Type Grid -->
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px; margin-bottom: 20px;">
                <div>
                    <label style="display: block; font-size: 0.88rem; font-weight: 700; color: var(--text-dark); margin-bottom: 6px;">
                        Hiring Company <span style="color: var(--primary-red);">*</span>
                    </label>
                    <select name="company_id" required style="width: 100%; padding: 11px 12px; border: 1px solid var(--border-color); border-radius: var(--radius-sm); font-size: 0.9rem; background: #fff;">
                        <?php foreach ($companies as $comp): ?>
                            <option value="<?= $comp['id'] ?>" <?= ($job['company_id'] == $comp['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($comp['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label style="display: block; font-size: 0.88rem; font-weight: 700; color: var(--text-dark); margin-bottom: 6px;">
                        Category <span style="color: var(--primary-red);">*</span>
                    </label>
                    <select name="category_id" required style="width: 100%; padding: 11px 12px; border: 1px solid var(--border-color); border-radius: var(--radius-sm); font-size: 0.9rem; background: #fff;">
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= ($job['category_id'] == $cat['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label style="display: block; font-size: 0.88rem; font-weight: 700; color: var(--text-dark); margin-bottom: 6px;">
                        Employment Type <span style="color: var(--primary-red);">*</span>
                    </label>
                    <select name="job_type_id" required style="width: 100%; padding: 11px 12px; border: 1px solid var(--border-color); border-radius: var(--radius-sm); font-size: 0.9rem; background: #fff;">
                        <?php foreach ($jobTypes as $jt): ?>
                            <option value="<?= $jt['id'] ?>" <?= ($job['job_type_id'] == $jt['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($jt['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Location & ZIP -->
            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 16px; margin-bottom: 20px;">
                <div>
                    <label style="display: block; font-size: 0.88rem; font-weight: 700; color: var(--text-dark); margin-bottom: 6px;">
                        Location <span style="color: var(--primary-red);">*</span>
                    </label>
                    <input 
                        type="text" 
                        name="location" 
                        required 
                        value="<?= htmlspecialchars($_POST['location'] ?? $job['location'], ENT_QUOTES, 'UTF-8') ?>"
                        style="width: 100%; padding: 11px 14px; border: 1px solid var(--border-color); border-radius: var(--radius-sm); font-size: 0.95rem;"
                    >
                </div>

                <div>
                    <label style="display: block; font-size: 0.88rem; font-weight: 700; color: var(--text-dark); margin-bottom: 6px;">
                        ZIP Code
                    </label>
                    <input 
                        type="text" 
                        name="zip_code" 
                        value="<?= htmlspecialchars($_POST['zip_code'] ?? $job['zip_code'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                        style="width: 100%; padding: 11px 14px; border: 1px solid var(--border-color); border-radius: var(--radius-sm); font-size: 0.95rem;"
                    >
                </div>
            </div>

            <!-- Salary Range & Type -->
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap: 16px; margin-bottom: 20px;">
                <div>
                    <label style="display: block; font-size: 0.88rem; font-weight: 700; color: var(--text-dark); margin-bottom: 6px;">
                        Min Salary ($)
                    </label>
                    <input 
                        type="number" 
                        step="0.01" 
                        name="salary_min" 
                        value="<?= htmlspecialchars($_POST['salary_min'] ?? $job['salary_min'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                        style="width: 100%; padding: 11px 14px; border: 1px solid var(--border-color); border-radius: var(--radius-sm); font-size: 0.95rem;"
                    >
                </div>

                <div>
                    <label style="display: block; font-size: 0.88rem; font-weight: 700; color: var(--text-dark); margin-bottom: 6px;">
                        Max Salary ($)
                    </label>
                    <input 
                        type="number" 
                        step="0.01" 
                        name="salary_max" 
                        value="<?= htmlspecialchars($_POST['salary_max'] ?? $job['salary_max'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                        style="width: 100%; padding: 11px 14px; border: 1px solid var(--border-color); border-radius: var(--radius-sm); font-size: 0.95rem;"
                    >
                </div>

                <div>
                    <label style="display: block; font-size: 0.88rem; font-weight: 700; color: var(--text-dark); margin-bottom: 6px;">
                        Salary Frequency
                    </label>
                    <select name="salary_type" style="width: 100%; padding: 11px 12px; border: 1px solid var(--border-color); border-radius: var(--radius-sm); font-size: 0.9rem; background: #fff;">
                        <option value="year" <?= $job['salary_type'] === 'year' ? 'selected' : '' ?>>Per Year</option>
                        <option value="month" <?= $job['salary_type'] === 'month' ? 'selected' : '' ?>>Per Month</option>
                        <option value="hour" <?= $job['salary_type'] === 'hour' ? 'selected' : '' ?>>Per Hour</option>
                    </select>
                </div>

                <div>
                    <label style="display: block; font-size: 0.88rem; font-weight: 700; color: var(--text-dark); margin-bottom: 6px;">
                        Experience Level
                    </label>
                    <select name="experience_level" style="width: 100%; padding: 11px 12px; border: 1px solid var(--border-color); border-radius: var(--radius-sm); font-size: 0.9rem; background: #fff;">
                        <option value="Entry Level" <?= $job['experience_level'] === 'Entry Level' ? 'selected' : '' ?>>Entry Level</option>
                        <option value="Mid Level" <?= $job['experience_level'] === 'Mid Level' ? 'selected' : '' ?>>Mid Level</option>
                        <option value="Senior Level" <?= $job['experience_level'] === 'Senior Level' ? 'selected' : '' ?>>Senior Level</option>
                        <option value="Lead / Director" <?= $job['experience_level'] === 'Lead / Director' ? 'selected' : '' ?>>Lead / Director</option>
                    </select>
                </div>
            </div>

            <!-- Toggles: Remote, Featured, Status -->
            <div style="display: flex; gap: 32px; margin-bottom: 24px; padding: 16px; background: #f8fafc; border-radius: var(--radius-sm);">
                <label style="display: flex; align-items: center; gap: 8px; font-size: 0.9rem; font-weight: 600; cursor: pointer;">
                    <input type="checkbox" name="is_remote" value="1" <?= $job['is_remote'] ? 'checked' : '' ?>>
                    <span>Remote Eligible</span>
                </label>

                <label style="display: flex; align-items: center; gap: 8px; font-size: 0.9rem; font-weight: 600; cursor: pointer;">
                    <input type="checkbox" name="is_featured" value="1" <?= $job['is_featured'] ? 'checked' : '' ?>>
                    <span>Featured on Homepage</span>
                </label>

                <div style="display: flex; align-items: center; gap: 8px; font-size: 0.9rem; font-weight: 600;">
                    <span>Status:</span>
                    <select name="status" style="padding: 4px 8px; border: 1px solid var(--border-color); border-radius: 4px; font-size: 0.85rem; background: #fff;">
                        <option value="active" <?= $job['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="paused" <?= $job['status'] === 'paused' ? 'selected' : '' ?>>Paused</option>
                        <option value="closed" <?= $job['status'] === 'closed' ? 'selected' : '' ?>>Closed</option>
                    </select>
                </div>
            </div>

            <!-- Description -->
            <div style="margin-bottom: 20px;">
                <label style="display: block; font-size: 0.88rem; font-weight: 700; color: var(--text-dark); margin-bottom: 6px;">
                    Full Job Description <span style="color: var(--primary-red);">*</span>
                </label>
                <textarea 
                    name="description" 
                    rows="6" 
                    required 
                    style="width: 100%; padding: 11px 14px; border: 1px solid var(--border-color); border-radius: var(--radius-sm); font-size: 0.95rem; line-height: 1.5;"
                ><?= htmlspecialchars($_POST['description'] ?? $job['description'], ENT_QUOTES, 'UTF-8') ?></textarea>
            </div>

            <!-- Responsibilities -->
            <div style="margin-bottom: 20px;">
                <label style="display: block; font-size: 0.88rem; font-weight: 700; color: var(--text-dark); margin-bottom: 6px;">
                    Key Responsibilities
                </label>
                <textarea 
                    name="responsibilities" 
                    rows="4" 
                    style="width: 100%; padding: 11px 14px; border: 1px solid var(--border-color); border-radius: var(--radius-sm); font-size: 0.95rem; line-height: 1.5;"
                ><?= htmlspecialchars($_POST['responsibilities'] ?? $job['responsibilities'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
            </div>

            <!-- Requirements -->
            <div style="margin-bottom: 20px;">
                <label style="display: block; font-size: 0.88rem; font-weight: 700; color: var(--text-dark); margin-bottom: 6px;">
                    Qualifications &amp; Requirements
                </label>
                <textarea 
                    name="requirements" 
                    rows="4" 
                    style="width: 100%; padding: 11px 14px; border: 1px solid var(--border-color); border-radius: var(--radius-sm); font-size: 0.95rem; line-height: 1.5;"
                ><?= htmlspecialchars($_POST['requirements'] ?? $job['requirements'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
            </div>

            <!-- Benefits -->
            <div style="margin-bottom: 28px;">
                <label style="display: block; font-size: 0.88rem; font-weight: 700; color: var(--text-dark); margin-bottom: 6px;">
                    Benefits &amp; Perks
                </label>
                <textarea 
                    name="benefits" 
                    rows="3" 
                    style="width: 100%; padding: 11px 14px; border: 1px solid var(--border-color); border-radius: var(--radius-sm); font-size: 0.95rem; line-height: 1.5;"
                ><?= htmlspecialchars($_POST['benefits'] ?? $job['benefits'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 14px;">
                <a href="<?= url('admin/manage-jobs.php') ?>" class="btn btn-outline-white" style="color: var(--text-dark); border-color: var(--border-color); padding: 11px 24px;">
                    Cancel
                </a>
                <button type="submit" class="btn btn-red" style="padding: 11px 32px; font-size: 0.95rem;">
                    Update Job Posting
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>

