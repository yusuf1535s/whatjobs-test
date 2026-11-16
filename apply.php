<?php
/**
 * WhatJobs Clone - Job Application Form (apply.php)
 * Secure candidate application with file upload validation & CSRF protection
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/Job.php';
require_once __DIR__ . '/includes/Application.php';
require_once __DIR__ . '/includes/csrf.php';

$jobModel = new Job();
$appModel = new Application();

$jobId = isset($_REQUEST['job_id']) ? (int)$_REQUEST['job_id'] : 0;
$job = $jobModel->getById($jobId);

if (!$job || $job['status'] !== 'active') {
    set_flash('error', 'Job vacancy not found or no longer accepting applications.');
    header('Location: ' . url('jobs.php'));
    exit;
}

$user = current_user();
$errors = [];

// Check if user already applied
if ($user && $appModel->hasUserApplied($job['id'], $user['id'])) {
    set_flash('info', 'You have already submitted an application for this position.');
    header('Location: ' . url('job-detail.php?id=' . $job['id']));
    exit;
}

// Process POST Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Security token expired or invalid. Please try submitting again.';
    }

    $name = trim($_POST['applicant_name'] ?? '');
    $email = trim($_POST['applicant_email'] ?? '');
    $phone = trim($_POST['applicant_phone'] ?? '');
    $coverLetter = trim($_POST['cover_letter'] ?? '');
    $useProfileResume = isset($_POST['use_profile_resume']) && $_POST['use_profile_resume'] == '1';

    if (empty($name)) {
        $errors[] = 'Please provide your full legal name.';
    }
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please provide a valid email address.';
    }
    if (empty($phone)) {
        $errors[] = 'Please provide a contact phone number.';
    }

    $finalResumePath = null;

    // Check if using existing profile resume
    if ($user && $useProfileResume && !empty($user['resume_path']) && file_exists(__DIR__ . '/' . $user['resume_path'])) {
        $finalResumePath = $user['resume_path'];
    } else {
        // Must upload resume
        if (!isset($_FILES['resume']) || $_FILES['resume']['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Please upload your CV/Resume file (PDF, DOC, or DOCX).';
        } else {
            $file = $_FILES['resume'];
            $fileSize = $file['size'];
            $fileName = $file['name'];
            $fileTmp = $file['tmp_name'];

            if ($fileSize > MAX_UPLOAD_SIZE) {
                $errors[] = 'Resume file exceeds the 5MB size limit.';
            } else {
                $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                if (!in_array($ext, ALLOWED_EXTENSIONS)) {
                    $errors[] = 'Invalid file format. Allowed formats: .pdf, .doc, .docx.';
                } else {
                    // Verify MIME type using fileinfo
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $mime = finfo_file($finfo, $fileTmp);
                    finfo_close($finfo);

                    $allowedMimes = [
                        'application/pdf',
                        'application/msword',
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                        'application/octet-stream'
                    ];

                    if (!in_array($mime, $allowedMimes)) {
                        $errors[] = 'Suspicious file format detected. Please upload a standard PDF or DOCX file.';
                    } else {
                        // Generate safe unique filename
                        $safeName = 'resume_' . time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
                        $destination = UPLOAD_DIR . $safeName;

                        if (!is_dir(UPLOAD_DIR)) {
                            mkdir(UPLOAD_DIR, 0755, true);
                        }

                        if (move_uploaded_file($fileTmp, $destination)) {
                            $finalResumePath = 'uploads/resumes/' . $safeName;

                            // If user is logged in, also update their profile resume for convenience
                            if ($user && empty($user['resume_path'])) {
                                $userModel = new User();
                                $userModel->updateResume($user['id'], $finalResumePath);
                            }
                        } else {
                            $errors[] = 'Failed to save uploaded resume. Please check server permissions.';
                        }
                    }
                }
            }
        }
    }

    if (empty($errors) && $finalResumePath) {
        $result = $appModel->apply(
            $job['id'],
            $user['id'] ?? null,
            $name,
            $email,
            $phone,
            $coverLetter,
            $finalResumePath
        );

        if ($result['success']) {
            set_flash('success', 'Congratulations! Your application for "' . $job['title'] . '" was successfully submitted.');
            if ($user) {
                header('Location: ' . url('seeker-dashboard.php'));
            } else {
                header('Location: ' . url('job-detail.php?id=' . $job['id']));
            }
            exit;
        } else {
            $errors[] = $result['error'] ?? 'Application could not be saved. Please try again.';
        }
    }
}

$pageTitle = "Apply for " . htmlspecialchars($job['title']) . " | WhatJobs";
require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="max-width: 760px; padding-top: 36px; padding-bottom: 60px;">
    
    <!-- Job Header Summary -->
    <div style="background: #ffffff; border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 24px; margin-bottom: 24px; display: flex; align-items: center; justify-content: space-between; gap: 16px;">
        <div>
            <span style="font-size: 0.85rem; color: var(--text-muted); text-transform: uppercase; font-weight: 700; letter-spacing: 0.5px;">Applying For:</span>
            <h1 style="font-size: 1.35rem; font-weight: 800; color: var(--text-dark); margin: 4px 0;">
                <?= htmlspecialchars($job['title'], ENT_QUOTES, 'UTF-8') ?>
            </h1>
            <p style="font-size: 0.9rem; color: var(--text-muted);">
                <?= htmlspecialchars($job['company_name']) ?> • <?= htmlspecialchars($job['location']) ?> • <?= format_salary($job['salary_min'], $job['salary_max'], $job['salary_type']) ?>
            </p>
        </div>
        <a href="<?= url('job-detail.php?id=' . $job['id']) ?>" style="font-size: 0.86rem; color: var(--primary-red); font-weight: 600; text-decoration: underline; white-space: nowrap;">
            Back to Job
        </a>
    </div>

    <!-- Error Banner -->
    <?php if (!empty($errors)): ?>
        <div style="background: #fce8e6; border: 1px solid #f5c2c7; color: #c5221f; border-radius: var(--radius-sm); padding: 16px 20px; margin-bottom: 24px;">
            <div style="font-weight: 700; margin-bottom: 6px; display: flex; align-items: center; gap: 6px;">
                <span class="material-symbols-rounded">error</span> Please fix the following errors:
            </div>
            <ul style="margin-left: 24px; font-size: 0.9rem;">
                <?php foreach ($errors as $err): ?>
                    <li><?= htmlspecialchars($err) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <!-- Application Form Card -->
    <div style="background: #ffffff; border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 36px; box-shadow: var(--shadow-sm);">
        <h2 style="font-size: 1.25rem; font-weight: 700; color: var(--text-dark); margin-bottom: 8px;">
            Candidate Information
        </h2>
        <p style="font-size: 0.88rem; color: var(--text-muted); margin-bottom: 28px;">
            Please fill out your contact details and attach your latest resume.
        </p>

        <form action="<?= url('apply.php?job_id=' . $job['id']) ?>" method="POST" enctype="multipart/form-data">
            <?= csrf_input() ?>
            <input type="hidden" name="job_id" value="<?= $job['id'] ?>">

            <!-- Full Name -->
            <div style="margin-bottom: 20px;">
                <label for="applicant_name" style="display: block; font-size: 0.9rem; font-weight: 700; color: var(--text-dark); margin-bottom: 6px;">
                    Full Name <span style="color: var(--primary-red);">*</span>
                </label>
                <input 
                    type="text" 
                    id="applicant_name" 
                    name="applicant_name" 
                    required 
                    value="<?= htmlspecialchars($_POST['applicant_name'] ?? $user['full_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                    placeholder="e.g. Alex Johnson"
                    style="width: 100%; padding: 12px 14px; border: 1px solid var(--border-color); border-radius: var(--radius-sm); font-size: 0.95rem; background: #fff;"
                >
            </div>

            <!-- Email & Phone Grid -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 20px;">
                <div>
                    <label for="applicant_email" style="display: block; font-size: 0.9rem; font-weight: 700; color: var(--text-dark); margin-bottom: 6px;">
                        Email Address <span style="color: var(--primary-red);">*</span>
                    </label>
                    <input 
                        type="email" 
                        id="applicant_email" 
                        name="applicant_email" 
                        required 
                        value="<?= htmlspecialchars($_POST['applicant_email'] ?? $user['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                        placeholder="e.g. alex@example.com"
                        style="width: 100%; padding: 12px 14px; border: 1px solid var(--border-color); border-radius: var(--radius-sm); font-size: 0.95rem; background: #fff;"
                    >
                </div>

                <div>
                    <label for="applicant_phone" style="display: block; font-size: 0.9rem; font-weight: 700; color: var(--text-dark); margin-bottom: 6px;">
                        Phone Number <span style="color: var(--primary-red);">*</span>
                    </label>
                    <input 
                        type="tel" 
                        id="applicant_phone" 
                        name="applicant_phone" 
                        required 
                        value="<?= htmlspecialchars($_POST['applicant_phone'] ?? $user['phone'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                        placeholder="e.g. +1 (555) 000-0000"
                        style="width: 100%; padding: 12px 14px; border: 1px solid var(--border-color); border-radius: var(--radius-sm); font-size: 0.95rem; background: #fff;"
                    >
                </div>
            </div>

            <!-- Resume Section -->
            <div style="margin-bottom: 24px; padding: 18px; background: #f8fafc; border: 1px solid var(--border-color); border-radius: var(--radius-sm);">
                <label style="display: block; font-size: 0.92rem; font-weight: 700; color: var(--text-dark); margin-bottom: 8px;">
                    Resume / Curriculum Vitae <span style="color: var(--primary-red);">*</span>
                </label>

                <?php if ($user && !empty($user['resume_path'])): ?>
                    <div style="margin-bottom: 12px;">
                        <label style="display: flex; align-items: center; gap: 8px; font-size: 0.9rem; color: #1e293b; cursor: pointer;">
                            <input type="checkbox" name="use_profile_resume" value="1" id="useProfileResumeToggle" checked>
                            <span>Use saved profile resume (<strong><?= basename($user['resume_path']) ?></strong>)</span>
                        </label>
                    </div>
                <?php endif; ?>

                <div id="fileUploadContainer" style="<?= ($user && !empty($user['resume_path'])) ? 'display: none;' : '' ?>">
                    <input 
                        type="file" 
                        name="resume" 
                        id="resumeInput" 
                        accept=".pdf,.doc,.docx"
                        style="width: 100%; padding: 8px 0; font-size: 0.9rem;"
                    >
                    <span style="font-size: 0.8rem; color: var(--text-muted); display: block; margin-top: 4px;">
                        Accepted file formats: PDF, DOC, DOCX. Max file size: 5MB.
                    </span>
                </div>
            </div>

            <!-- Cover Letter -->
            <div style="margin-bottom: 28px;">
                <label for="cover_letter" style="display: block; font-size: 0.9rem; font-weight: 700; color: var(--text-dark); margin-bottom: 6px;">
                    Cover Letter (Optional)
                </label>
                <textarea 
                    id="cover_letter" 
                    name="cover_letter" 
                    rows="5"
                    placeholder="Briefly introduce yourself and share why you are a great match for this role..."
                    style="width: 100%; padding: 12px 14px; border: 1px solid var(--border-color); border-radius: var(--radius-sm); font-size: 0.95rem; line-height: 1.5;"
                ><?= htmlspecialchars($_POST['cover_letter'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
            </div>

            <!-- Submit Button -->
            <div style="display: flex; align-items: center; justify-content: space-between;">
                <a href="<?= url('job-detail.php?id=' . $job['id']) ?>" style="font-size: 0.92rem; color: var(--text-muted);">
                    Cancel
                </a>
                <button type="submit" class="btn btn-red" style="padding: 13px 36px; font-size: 1rem;">
                    Submit Application <span class="material-symbols-rounded">arrow_forward</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    const useProfile = document.getElementById('useProfileResumeToggle');
    const uploadBox = document.getElementById('fileUploadContainer');
    if (useProfile && uploadBox) {
        useProfile.addEventListener('change', () => {
            uploadBox.style.display = useProfile.checked ? 'none' : 'block';
        });
    }
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

