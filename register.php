<?php
/**
 * WhatJobs Clone - Candidate Registration (register.php)
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/User.php';
require_once __DIR__ . '/includes/csrf.php';

if (is_logged_in()) {
    header('Location: ' . url('seeker-dashboard.php'));
    exit;
}

$userModel = new User();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Security token invalid. Please refresh the page.';
    }

    $name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $phone = trim($_POST['phone'] ?? '');
    $title = trim($_POST['current_job_title'] ?? '');
    $location = trim($_POST['location'] ?? '');

    if (empty($name)) {
        $errors[] = 'Please enter your full legal name.';
    }
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }
    if (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters long.';
    }
    if ($password !== $confirmPassword) {
        $errors[] = 'Password confirmation does not match.';
    }

    // Optional resume upload
    $resumePath = null;
    if (isset($_FILES['resume']) && $_FILES['resume']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['resume'];
        if ($file['size'] > MAX_UPLOAD_SIZE) {
            $errors[] = 'Resume file exceeds the 5MB size limit.';
        } else {
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, ALLOWED_EXTENSIONS)) {
                $errors[] = 'Resume must be a PDF, DOC, or DOCX file.';
            } else {
                $safeName = 'resume_' . time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
                if (!is_dir(UPLOAD_DIR)) {
                    mkdir(UPLOAD_DIR, 0755, true);
                }
                if (move_uploaded_file($file['tmp_name'], UPLOAD_DIR . $safeName)) {
                    $resumePath = 'uploads/resumes/' . $safeName;
                }
            }
        }
    }

    if (empty($errors)) {
        $reg = $userModel->register($name, $email, $password, $phone, $title, $location);
        if ($reg['success']) {
            $userId = $reg['user_id'];
            if ($resumePath) {
                $userModel->updateResume($userId, $resumePath);
            }

            // Log user in
            $_SESSION['user_id'] = $userId;
            $_SESSION['user_name'] = $name;
            $_SESSION['user_email'] = $email;

            set_flash('success', 'Your candidate profile has been registered successfully! Welcome to WhatJobs.');
            header('Location: ' . url('seeker-dashboard.php'));
            exit;
        } else {
            $errors[] = $reg['error'];
        }
    }
}

$pageTitle = "Register Resume & Create Account | WhatJobs";
require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="max-width: 580px; padding-top: 40px; padding-bottom: 72px;">
    
    <!-- Error Banner -->
    <?php if (!empty($errors)): ?>
        <div style="background: #fce8e6; border: 1px solid #f5c2c7; color: #c5221f; border-radius: var(--radius-sm); padding: 14px 18px; margin-bottom: 20px;">
            <ul style="margin-left: 20px; font-size: 0.88rem;">
                <?php foreach ($errors as $err): ?>
                    <li><?= htmlspecialchars($err) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <!-- Register Card -->
    <div style="background: #ffffff; border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 36px; box-shadow: var(--shadow-sm);">
        <h1 style="font-size: 1.5rem; font-weight: 800; color: var(--text-dark); margin-bottom: 6px; text-align: center;">
            Register Your Resume
        </h1>
        <p style="font-size: 0.9rem; color: var(--text-muted); text-align: center; margin-bottom: 28px;">
            Join thousands of professionals applying to top jobs with 1-click
        </p>

        <form action="<?= url('register.php') ?>" method="POST" enctype="multipart/form-data">
            <?= csrf_input() ?>

            <!-- Full Name -->
            <div style="margin-bottom: 18px;">
                <label for="full_name" style="display: block; font-size: 0.88rem; font-weight: 700; color: var(--text-dark); margin-bottom: 6px;">
                    Full Name <span style="color: var(--primary-red);">*</span>
                </label>
                <input 
                    type="text" 
                    id="full_name" 
                    name="full_name" 
                    required 
                    value="<?= htmlspecialchars($_POST['full_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                    placeholder="e.g. Alex Johnson"
                    style="width: 100%; padding: 11px 14px; border: 1px solid var(--border-color); border-radius: var(--radius-sm); font-size: 0.95rem;"
                >
            </div>

            <!-- Email -->
            <div style="margin-bottom: 18px;">
                <label for="email" style="display: block; font-size: 0.88rem; font-weight: 700; color: var(--text-dark); margin-bottom: 6px;">
                    Email Address <span style="color: var(--primary-red);">*</span>
                </label>
                <input 
                    type="email" 
                    id="email" 
                    name="email" 
                    required 
                    value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                    placeholder="name@example.com"
                    style="width: 100%; padding: 11px 14px; border: 1px solid var(--border-color); border-radius: var(--radius-sm); font-size: 0.95rem;"
                >
            </div>

            <!-- Password & Confirm -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 18px;">
                <div>
                    <label for="password" style="display: block; font-size: 0.88rem; font-weight: 700; color: var(--text-dark); margin-bottom: 6px;">
                        Password <span style="color: var(--primary-red);">*</span>
                    </label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        required 
                        placeholder="Min 6 chars"
                        style="width: 100%; padding: 11px 14px; border: 1px solid var(--border-color); border-radius: var(--radius-sm); font-size: 0.95rem;"
                    >
                </div>
                <div>
                    <label for="confirm_password" style="display: block; font-size: 0.88rem; font-weight: 700; color: var(--text-dark); margin-bottom: 6px;">
                        Confirm Password <span style="color: var(--primary-red);">*</span>
                    </label>
                    <input 
                        type="password" 
                        id="confirm_password" 
                        name="confirm_password" 
                        required 
                        placeholder="Repeat password"
                        style="width: 100%; padding: 11px 14px; border: 1px solid var(--border-color); border-radius: var(--radius-sm); font-size: 0.95rem;"
                    >
                </div>
            </div>

            <!-- Phone & Target Job Title -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 18px;">
                <div>
                    <label for="phone" style="display: block; font-size: 0.88rem; font-weight: 700; color: var(--text-dark); margin-bottom: 6px;">
                        Phone Number
                    </label>
                    <input 
                        type="tel" 
                        id="phone" 
                        name="phone" 
                        value="<?= htmlspecialchars($_POST['phone'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                        placeholder="+1 (555) 000-0000"
                        style="width: 100%; padding: 11px 14px; border: 1px solid var(--border-color); border-radius: var(--radius-sm); font-size: 0.95rem;"
                    >
                </div>
                <div>
                    <label for="current_job_title" style="display: block; font-size: 0.88rem; font-weight: 700; color: var(--text-dark); margin-bottom: 6px;">
                        Target / Current Role
                    </label>
                    <input 
                        type="text" 
                        id="current_job_title" 
                        name="current_job_title" 
                        value="<?= htmlspecialchars($_POST['current_job_title'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                        placeholder="e.g. Software Engineer"
                        style="width: 100%; padding: 11px 14px; border: 1px solid var(--border-color); border-radius: var(--radius-sm); font-size: 0.95rem;"
                    >
                </div>
            </div>

            <!-- Location -->
            <div style="margin-bottom: 20px;">
                <label for="location" style="display: block; font-size: 0.88rem; font-weight: 700; color: var(--text-dark); margin-bottom: 6px;">
                    Location (City, State)
                </label>
                <input 
                    type="text" 
                    id="location" 
                    name="location" 
                    value="<?= htmlspecialchars($_POST['location'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                    placeholder="e.g. New York, NY"
                    style="width: 100%; padding: 11px 14px; border: 1px solid var(--border-color); border-radius: var(--radius-sm); font-size: 0.95rem;"
                >
            </div>

            <!-- Resume Upload (Optional at registration) -->
            <div style="margin-bottom: 24px; padding: 16px; background: #f8fafc; border: 1px solid var(--border-color); border-radius: var(--radius-sm);">
                <label for="resume" style="display: block; font-size: 0.88rem; font-weight: 700; color: var(--text-dark); margin-bottom: 6px;">
                    Upload Resume (Optional now, can be added later)
                </label>
                <input 
                    type="file" 
                    id="resume" 
                    name="resume" 
                    accept=".pdf,.doc,.docx"
                    style="width: 100%; font-size: 0.88rem;"
                >
                <span style="font-size: 0.78rem; color: var(--text-muted); display: block; margin-top: 4px;">
                    PDF, DOC, DOCX up to 5MB
                </span>
            </div>

            <button type="submit" class="btn btn-green" style="width: 100%; padding: 13px; font-size: 1rem;">
                Register Resume &amp; Account
            </button>
        </form>

        <div style="margin-top: 24px; padding-top: 20px; border-top: 1px solid var(--border-light); text-align: center; font-size: 0.9rem; color: var(--text-muted);">
            Already have an account? 
            <a href="<?= url('login.php') ?>" style="color: var(--primary-red); font-weight: 600;">
                Sign In
            </a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

