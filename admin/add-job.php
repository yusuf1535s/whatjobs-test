<?php
/**
 * WhatJobs Clone - Add Job Vacancy (admin/add-job.php)
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/Job.php';
require_once __DIR__ . '/../includes/csrf.php';

require_admin();

$jobModel = new Job();
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
        $newId = $jobModel->create([
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

        if ($newId > 0) {
            set_flash('success', 'Job posting created successfully! (Job ID #' . $newId . ')');
            header('Location: ' . url('admin/manage-jobs.php'));
            exit;
        } else {
            $errors[] = 'Failed to create job posting. Please try again.';
        }
    }
}

$pageTitle = "Post New Job Vacancy | WhatJobs Admin";
require_once __DIR__ . '/includes/admin-header.php';
?>

<div style="max-width: 900px; margin: 0 auto;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
        <h1 style="font-size: 1.5rem; font-weight: 800; color: var(--text-dark);">
            Post New Job Vacancy
        </h1>
        <a href="<?= url('admin/manage-jobs.php') ?>" style="font-size: 0.88rem; color: var(--primary-red); font-weight: 600;">
            ← Back to Manage Jobs
        </a>
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
        <form action="<?= url('admin/add-job.php') ?>" method="POST">
            <?= csrf_input() ?>

            <!-- Title -->
            <div style="margin-bottom: 20px;">
                <label style="display: block; font-size: 0.88rem; font-weight: 700; color: var(--text-dark); margin-bottom: 6px;">
                    Job Title <span style="color: var(--primary-red);">*</span>
                </label>
                <input 
                    type="text" 
                    name="title" 
                    required 
                    value="<?= htmlspecialchars($_POST['title'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                    placeholder="e.g. Senior Backend Engineer - Cloud Systems"
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
                        <option value="">Select Company</option>
                        <?php foreach ($companies as $comp): ?>
                            <option value="<?= $comp['id'] ?>" <?= (isset($_POST['company_id']) && $_POST['company_id'] == $comp['id']) ? 'selected' : '' ?>>
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
                        <option value="">Select Category</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= (isset($_POST['category_id']) && $_POST['category_id'] == $cat['id']) ? 'selected' : '' ?>>
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
                        <option value="">Select Type</option>
                        <?php foreach ($jobTypes as $jt): ?>
                            <option value="<?= $jt['id'] ?>" <?= (isset($_POST['job_type_id']) && $_POST['job_type_id'] == $jt['id']) ? 'selected' : '' ?>>
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
                        Location (City, State) <span style="color: var(--primary-red);">*</span>
                    </label>
                    <input 
                        type="text" 
                        name="location" 
                        required 
                        value="<?= htmlspecialchars($_POST['location'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                        placeholder="e.g. Austin, TX or Remote, USA"
                        style="width: 100%; padding: 11px 14px; border: 1px solid var(--border-color); border-radius: var(--radius-sm); font-size: 0.95rem;"
                    >
                </div>

                <div>
                    <label style="display: block; font-size: 0.88rem; font-weight: 700; color: var(--text-dark); margin-bottom: 6px;">
                        ZIP Code (Optional)
                    </label>
                    <input 
                        type="text" 
                        name="zip_code" 
                        value="<?= htmlspecialchars($_POST['zip_code'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                        placeholder="e.g. 78701"
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
                        value="<?= htmlspecialchars($_POST['salary_min'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                        placeholder="e.g. 90000"
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
                        value="<?= htmlspecialchars($_POST['salary_max'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                        placeholder="e.g. 140000"
                        style="width: 100%; padding: 11px 14px; border: 1px solid var(--border-color); border-radius: var(--radius-sm); font-size: 0.95rem;"
                    >
                </div>

                <div>
                    <label style="display: block; font-size: 0.88rem; font-weight: 700; color: var(--text-dark); margin-bottom: 6px;">
                        Salary Frequency
                    </label>
                    <select name="salary_type" style="width: 100%; padding: 11px 12px; border: 1px solid var(--border-color); border-radius: var(--radius-sm); font-size: 0.9rem; background: #fff;">
                        <option value="year" <?= (isset($_POST['salary_type']) && $_POST['salary_type'] === 'year') ? 'selected' : '' ?>>Per Year</option>
                        <option value="month" <?= (isset($_POST['salary_type']) && $_POST['salary_type'] === 'month') ? 'selected' : '' ?>>Per Month</option>
                        <option value="hour" <?= (isset($_POST['salary_type']) && $_POST['salary_type'] === 'hour') ? 'selected' : '' ?>>Per Hour</option>
                    </select>
                </div>

                <div>
                    <label style="display: block; font-size: 0.88rem; font-weight: 700; color: var(--text-dark); margin-bottom: 6px;">
                        Experience Level
                    </label>
                    <select name="experience_level" style="width: 100%; padding: 11px 12px; border: 1px solid var(--border-color); border-radius: var(--radius-sm); font-size: 0.9rem; background: #fff;">
                        <option value="Entry Level">Entry Level</option>
                        <option value="Mid Level" selected>Mid Level</option>
                        <option value="Senior Level">Senior Level</option>
                        <option value="Lead / Director">Lead / Director</option>
                    </select>
                </div>
            </div>

            <!-- Toggles: Remote, Featured, Status -->
            <div style="display: flex; gap: 32px; margin-bottom: 24px; padding: 16px; background: #f8fafc; border-radius: var(--radius-sm);">
                <label style="display: flex; align-items: center; gap: 8px; font-size: 0.9rem; font-weight: 600; cursor: pointer;">
                    <input type="checkbox" name="is_remote" value="1" <?= isset($_POST['is_remote']) ? 'checked' : '' ?>>
                    <span>Remote Eligible</span>
                </label>

                <label style="display: flex; align-items: center; gap: 8px; font-size: 0.9rem; font-weight: 600; cursor: pointer;">
                    <input type="checkbox" name="is_featured" value="1" <?= isset($_POST['is_featured']) ? 'checked' : '' ?>>
                    <span>Featured on Homepage</span>
                </label>

                <div style="display: flex; align-items: center; gap: 8px; font-size: 0.9rem; font-weight: 600;">
                    <span>Status:</span>
                    <select name="status" style="padding: 4px 8px; border: 1px solid var(--border-color); border-radius: 4px; font-size: 0.85rem; background: #fff;">
                        <option value="active" selected>Active</option>
                        <option value="paused">Paused</option>
                        <option value="closed">Closed</option>
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
                    placeholder="Provide detailed description of role context and goals..."
                    style="width: 100%; padding: 11px 14px; border: 1px solid var(--border-color); border-radius: var(--radius-sm); font-size: 0.95rem; line-height: 1.5;"
                ><?= htmlspecialchars($_POST['description'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
            </div>

            <!-- Responsibilities -->
            <div style="margin-bottom: 20px;">
                <label style="display: block; font-size: 0.88rem; font-weight: 700; color: var(--text-dark); margin-bottom: 6px;">
                    Key Responsibilities
                </label>
                <textarea 
                    name="responsibilities" 
                    rows="4" 
                    placeholder="List core daily duties, workflows, or deliverables..."
                    style="width: 100%; padding: 11px 14px; border: 1px solid var(--border-color); border-radius: var(--radius-sm); font-size: 0.95rem; line-height: 1.5;"
                ><?= htmlspecialchars($_POST['responsibilities'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
            </div>

            <!-- Requirements -->
            <div style="margin-bottom: 20px;">
                <label style="display: block; font-size: 0.88rem; font-weight: 700; color: var(--text-dark); margin-bottom: 6px;">
                    Qualifications &amp; Requirements
                </label>
                <textarea 
                    name="requirements" 
                    rows="4" 
                    placeholder="List education, years of experience, technical proficiencies..."
                    style="width: 100%; padding: 11px 14px; border: 1px solid var(--border-color); border-radius: var(--radius-sm); font-size: 0.95rem; line-height: 1.5;"
                ><?= htmlspecialchars($_POST['requirements'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
            </div>

            <!-- Benefits -->
            <div style="margin-bottom: 28px;">
                <label style="display: block; font-size: 0.88rem; font-weight: 700; color: var(--text-dark); margin-bottom: 6px;">
                    Benefits &amp; Perks
                </label>
                <textarea 
                    name="benefits" 
                    rows="3" 
                    placeholder="List health insurance, 401(k), equity, PTO, wellness perks..."
                    style="width: 100%; padding: 11px 14px; border: 1px solid var(--border-color); border-radius: var(--radius-sm); font-size: 0.95rem; line-height: 1.5;"
                ><?= htmlspecialchars($_POST['benefits'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 14px;">
                <a href="<?= url('admin/manage-jobs.php') ?>" class="btn btn-outline-white" style="color: var(--text-dark); border-color: var(--border-color); padding: 11px 24px;">
                    Cancel
                </a>
                <button type="submit" class="btn btn-red" style="padding: 11px 32px; font-size: 0.95rem;">
                    Publish Job Listing
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>

