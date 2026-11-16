<?php
/**
 * WhatJobs Clone - Job Seeker Dashboard (seeker-dashboard.php)
 * Full candidate suite: Application tracking, Profile management, and Resume updates
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/User.php';
require_once __DIR__ . '/includes/Application.php';
require_once __DIR__ . '/includes/Job.php';
require_once __DIR__ . '/includes/csrf.php';

require_login();
$user = current_user();

$userModel = new User();
$appModel = new Application();
$jobModel = new Job();

$applications = $appModel->getByUserId($user['id']);
$savedJobs = $jobModel->getSavedJobs($user['id']);

$activeTab = $_GET['tab'] ?? 'applications';
$errors = [];

// Handle Profile Updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Security token expired. Please try again.';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'update_profile') {
            $name = trim($_POST['full_name'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $title = trim($_POST['current_job_title'] ?? '');
            $location = trim($_POST['location'] ?? '');
            $bio = trim($_POST['bio'] ?? '');

            if (empty($name)) {
                $errors[] = 'Full name is required.';
            } else {
                $userModel->updateProfile($user['id'], [
                    'full_name' => $name,
                    'phone' => $phone,
                    'current_job_title' => $title,
                    'location' => $location,
                    'bio' => $bio
                ]);
                set_flash('success', 'Profile details updated successfully.');
                header('Location: ' . url('seeker-dashboard.php?tab=profile'));
                exit;
            }
        } elseif ($action === 'update_resume') {
            if (!isset($_FILES['resume']) || $_FILES['resume']['error'] !== UPLOAD_ERR_OK) {
                $errors[] = 'Please select a valid resume document to upload.';
            } else {
                $file = $_FILES['resume'];
                if ($file['size'] > MAX_UPLOAD_SIZE) {
                    $errors[] = 'Resume file exceeds the 5MB size limit.';
                } else {
                    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                    if (!in_array($ext, ALLOWED_EXTENSIONS)) {
                        $errors[] = 'Invalid file format. Allowed formats: PDF, DOC, DOCX.';
                    } else {
                        $safeName = 'resume_' . time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
                        if (!is_dir(UPLOAD_DIR)) {
                            mkdir(UPLOAD_DIR, 0755, true);
                        }
                        if (move_uploaded_file($file['tmp_name'], UPLOAD_DIR . $safeName)) {
                            $userModel->updateResume($user['id'], 'uploads/resumes/' . $safeName);
                            set_flash('success', 'Resume document replaced successfully.');
                            header('Location: ' . url('seeker-dashboard.php?tab=profile'));
                            exit;
                        } else {
                            $errors[] = 'Failed to save resume file. Please check folder permissions.';
                        }
                    }
                }
            }
        } elseif ($action === 'update_password') {
            $currentPass = $_POST['current_password'] ?? '';
            $newPass = $_POST['new_password'] ?? '';
            $confirmPass = $_POST['confirm_new_password'] ?? '';

            if (strlen($newPass) < 6) {
                $errors[] = 'New password must be at least 6 characters.';
            } elseif ($newPass !== $confirmPass) {
                $errors[] = 'New password confirmation does not match.';
            } else {
                $res = $userModel->updatePassword($user['id'], $currentPass, $newPass);
                if ($res['success']) {
                    set_flash('success', 'Password updated successfully.');
                    header('Location: ' . url('seeker-dashboard.php?tab=security'));
                    exit;
                } else {
                    $errors[] = $res['error'];
                }
            }
        }
    }
}

// Refresh user info after potential update
$user = current_user();

$pageTitle = "Job Seeker Dashboard | WhatJobs";
require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="padding-top: 36px; padding-bottom: 72px;">
    
    <!-- User Welcome Header Banner -->
    <div style="background: #ffffff; border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 28px; margin-bottom: 28px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px; box-shadow: var(--shadow-sm);">
        <div style="display: flex; align-items: center; gap: 18px;">
            <div style="width: 58px; height: 58px; border-radius: 50%; background: #181818; color: #fff; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; font-weight: 700;">
                <?= strtoupper(substr($user['full_name'], 0, 1)) ?>
            </div>
            <div>
                <h1 style="font-size: 1.45rem; font-weight: 800; color: var(--text-dark);">
                    <?= htmlspecialchars($user['full_name']) ?>
                </h1>
                <p style="font-size: 0.9rem; color: var(--text-muted);">
                    <?= !empty($user['current_job_title']) ? htmlspecialchars($user['current_job_title']) : 'Job Seeker' ?> 
                    <?= !empty($user['location']) ? ' • ' . htmlspecialchars($user['location']) : '' ?>
                </p>
            </div>
        </div>

        <!-- Quick Stats Counters -->
        <div style="display: flex; gap: 24px; align-items: center;">
            <div style="text-align: center;">
                <span style="font-size: 1.6rem; font-weight: 800; color: var(--primary-red); display: block;">
                    <?= count($applications) ?>
                </span>
                <span style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600; text-transform: uppercase;">Applied</span>
            </div>
            <div style="width: 1px; height: 36px; background-color: var(--border-color);"></div>
            <div style="text-align: center;">
                <span style="font-size: 1.6rem; font-weight: 800; color: var(--btn-green); display: block;">
                    <?= count($savedJobs) ?>
                </span>
                <span style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600; text-transform: uppercase;">Saved</span>
            </div>
        </div>
    </div>

    <!-- Error Banner -->
    <?php if (!empty($errors)): ?>
        <div style="background: #fce8e6; border: 1px solid #f5c2c7; color: #c5221f; border-radius: var(--radius-sm); padding: 14px 18px; margin-bottom: 24px;">
            <ul style="margin-left: 20px; font-size: 0.88rem;">
                <?php foreach ($errors as $err): ?>
                    <li><?= htmlspecialchars($err) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <!-- Navigation Tabs -->
    <div style="display: flex; gap: 12px; margin-bottom: 24px; border-bottom: 2px solid var(--border-color); padding-bottom: 8px;">
        <a href="<?= url('seeker-dashboard.php?tab=applications') ?>" class="btn" style="<?= $activeTab === 'applications' ? 'background: #181818; color: #fff;' : 'background: #fff; color: var(--text-dark); border: 1px solid var(--border-color);' ?> padding: 9px 20px; font-size: 0.9rem;">
            <span class="material-symbols-rounded">assignment</span> My Applications (<?= count($applications) ?>)
        </a>
        <a href="<?= url('seeker-dashboard.php?tab=profile') ?>" class="btn" style="<?= $activeTab === 'profile' ? 'background: #181818; color: #fff;' : 'background: #fff; color: var(--text-dark); border: 1px solid var(--border-color);' ?> padding: 9px 20px; font-size: 0.9rem;">
            <span class="material-symbols-rounded">person</span> Profile &amp; Resume
        </a>
        <a href="<?= url('seeker-dashboard.php?tab=security') ?>" class="btn" style="<?= $activeTab === 'security' ? 'background: #181818; color: #fff;' : 'background: #fff; color: var(--text-dark); border: 1px solid var(--border-color);' ?> padding: 9px 20px; font-size: 0.9rem;">
            <span class="material-symbols-rounded">lock</span> Security
        </a>
    </div>

    <!-- TAB 1: Applications -->
    <?php if ($activeTab === 'applications'): ?>
        <?php if (empty($applications)): ?>
            <div style="background: #ffffff; border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 56px 24px; text-align: center;">
                <span class="material-symbols-rounded" style="font-size: 54px; color: #cbd5e1; margin-bottom: 16px;">post_add</span>
                <h3 style="font-size: 1.25rem; font-weight: 700; color: var(--text-dark); margin-bottom: 8px;">No applications submitted yet</h3>
                <p style="color: var(--text-muted); font-size: 0.92rem; max-width: 440px; margin: 0 auto 24px auto;">
                    Explore active job vacancies and submit applications with your stored resume in seconds.
                </p>
                <a href="<?= url('jobs.php') ?>" class="btn btn-red">Find Jobs</a>
            </div>
        <?php else: ?>
            <div style="background: #ffffff; border: 1px solid var(--border-color); border-radius: var(--radius-md); overflow: hidden; box-shadow: var(--shadow-sm);">
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse; font-size: 0.9rem; text-align: left;">
                        <thead>
                            <tr style="background: #f8fafc; border-bottom: 1px solid var(--border-color); color: #475569; font-size: 0.82rem; text-transform: uppercase;">
                                <th style="padding: 14px 20px;">Role &amp; Company</th>
                                <th style="padding: 14px 20px;">Applied Date</th>
                                <th style="padding: 14px 20px;">Review Status</th>
                                <th style="padding: 14px 20px;">Resume Sent</th>
                                <th style="padding: 14px 20px; text-align: right;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($applications as $app): ?>
                                <tr style="border-bottom: 1px solid var(--border-light);">
                                    <td style="padding: 16px 20px;">
                                        <a href="<?= url('job-detail.php?id=' . $app['job_id']) ?>" style="font-weight: 700; color: var(--text-dark); font-size: 0.95rem; display: block;">
                                            <?= htmlspecialchars($app['job_title']) ?>
                                        </a>
                                        <span style="font-size: 0.84rem; color: var(--text-muted);">
                                            <?= htmlspecialchars($app['company_name']) ?> • <?= htmlspecialchars($app['job_location']) ?>
                                        </span>
                                    </td>
                                    <td style="padding: 16px 20px; color: #64748b; white-space: nowrap;">
                                        <?= date('M j, Y', strtotime($app['created_at'])) ?>
                                    </td>
                                    <td style="padding: 16px 20px;">
                                        <?php 
                                            $st = $app['status'];
                                            $badgeStyle = match($st) {
                                                'accepted' => 'background: #e6f4ea; color: #137333;',
                                                'shortlisted' => 'background: #f3e8ff; color: #7e22ce;',
                                                'reviewing' => 'background: #e0f2fe; color: #0369a1;',
                                                'rejected' => 'background: #f1f5f9; color: #64748b;',
                                                default => 'background: #fef3c7; color: #b45309;'
                                            };
                                            $label = match($st) {
                                                'accepted' => 'Offer Extended',
                                                'shortlisted' => 'Shortlisted',
                                                'reviewing' => 'Under Review',
                                                'rejected' => 'Not Selected',
                                                default => 'Submitted'
                                            };
                                        ?>
                                        <span style="display: inline-block; padding: 4px 10px; border-radius: var(--radius-pill); font-size: 0.8rem; font-weight: 700; <?= $badgeStyle ?>">
                                            <?= $label ?>
                                        </span>

                                        <?php if (!empty($app['admin_notes'])): ?>
                                            <div style="margin-top: 6px; font-size: 0.8rem; color: #475569; background: #f8fafc; padding: 6px 10px; border-radius: 4px;">
                                                <strong>Feedback:</strong> <?= htmlspecialchars($app['admin_notes']) ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td style="padding: 16px 20px; white-space: nowrap;">
                                        <a href="<?= url($app['resume_path']) ?>" target="_blank" style="color: var(--primary-red); font-size: 0.84rem; font-weight: 600; display: inline-flex; align-items: center; gap: 4px;">
                                            <span class="material-symbols-rounded" style="font-size: 16px;">description</span> View Resume
                                        </a>
                                    </td>
                                    <td style="padding: 16px 20px; text-align: right; white-space: nowrap;">
                                        <a href="<?= url('job-detail.php?id=' . $app['job_id']) ?>" class="btn btn-outline-white" style="color: var(--text-dark); border-color: var(--border-color); padding: 6px 14px; font-size: 0.82rem;">
                                            View Posting
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <!-- TAB 2: Profile & Resume -->
    <?php if ($activeTab === 'profile'): ?>
        <div style="display: grid; grid-template-columns: 1fr 340px; gap: 28px; align-items: flex-start;">
            <!-- Profile Details Form -->
            <div style="background: #ffffff; border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 32px; box-shadow: var(--shadow-sm);">
                <h2 style="font-size: 1.25rem; font-weight: 700; color: var(--text-dark); margin-bottom: 20px;">
                    Edit Candidate Profile
                </h2>

                <form action="<?= url('seeker-dashboard.php?tab=profile') ?>" method="POST">
                    <?= csrf_input() ?>
                    <input type="hidden" name="action" value="update_profile">

                    <div style="margin-bottom: 18px;">
                        <label style="display: block; font-size: 0.88rem; font-weight: 700; margin-bottom: 6px;">Full Name</label>
                        <input type="text" name="full_name" required value="<?= htmlspecialchars($user['full_name']) ?>" style="width: 100%; padding: 11px 14px; border: 1px solid var(--border-color); border-radius: var(--radius-sm); font-size: 0.95rem;">
                    </div>

                    <div style="margin-bottom: 18px;">
                        <label style="display: block; font-size: 0.88rem; font-weight: 700; margin-bottom: 6px;">Email Address (Account ID)</label>
                        <input type="email" disabled value="<?= htmlspecialchars($user['email']) ?>" style="width: 100%; padding: 11px 14px; border: 1px solid var(--border-color); border-radius: var(--radius-sm); font-size: 0.95rem; background: #f8fafc; color: #64748b;">
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 18px;">
                        <div>
                            <label style="display: block; font-size: 0.88rem; font-weight: 700; margin-bottom: 6px;">Phone</label>
                            <input type="tel" name="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" style="width: 100%; padding: 11px 14px; border: 1px solid var(--border-color); border-radius: var(--radius-sm); font-size: 0.95rem;">
                        </div>
                        <div>
                            <label style="display: block; font-size: 0.88rem; font-weight: 700; margin-bottom: 6px;">Target Job Title</label>
                            <input type="text" name="current_job_title" value="<?= htmlspecialchars($user['current_job_title'] ?? '') ?>" style="width: 100%; padding: 11px 14px; border: 1px solid var(--border-color); border-radius: var(--radius-sm); font-size: 0.95rem;">
                        </div>
                    </div>

                    <div style="margin-bottom: 18px;">
                        <label style="display: block; font-size: 0.88rem; font-weight: 700; margin-bottom: 6px;">Location</label>
                        <input type="text" name="location" value="<?= htmlspecialchars($user['location'] ?? '') ?>" style="width: 100%; padding: 11px 14px; border: 1px solid var(--border-color); border-radius: var(--radius-sm); font-size: 0.95rem;">
                    </div>

                    <div style="margin-bottom: 24px;">
                        <label style="display: block; font-size: 0.88rem; font-weight: 700; margin-bottom: 6px;">Professional Summary</label>
                        <textarea name="bio" rows="4" style="width: 100%; padding: 11px 14px; border: 1px solid var(--border-color); border-radius: var(--radius-sm); font-size: 0.95rem;"><?= htmlspecialchars($user['bio'] ?? '') ?></textarea>
                    </div>

                    <button type="submit" class="btn btn-red" style="padding: 12px 28px;">
                        Save Profile Changes
                    </button>
                </form>
            </div>

            <!-- Resume Upload / Replace Card -->
            <div style="background: #ffffff; border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 24px; box-shadow: var(--shadow-sm);">
                <h3 style="font-size: 1.1rem; font-weight: 700; color: var(--text-dark); margin-bottom: 12px;">
                    Primary Resume Document
                </h3>
                
                <?php if (!empty($user['resume_path'])): ?>
                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: var(--radius-sm); padding: 16px; margin-bottom: 20px;">
                        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                            <span class="material-symbols-rounded" style="color: var(--primary-red); font-size: 28px;">description</span>
                            <div style="overflow: hidden;">
                                <strong style="display: block; font-size: 0.88rem; text-overflow: ellipsis; white-space: nowrap;"><?= basename($user['resume_path']) ?></strong>
                                <span style="font-size: 0.78rem; color: #64748b;">Ready for 1-click apply</span>
                            </div>
                        </div>
                        <a href="<?= url($user['resume_path']) ?>" target="_blank" class="btn btn-outline-white" style="width: 100%; color: var(--text-dark); border-color: var(--border-color); font-size: 0.84rem; padding: 7px;">
                            Download Current Resume
                        </a>
                    </div>
                <?php else: ?>
                    <div style="background: #fffbeb; border: 1px solid #fef3c7; color: #92400e; padding: 12px; border-radius: var(--radius-sm); font-size: 0.84rem; margin-bottom: 16px;">
                        No resume uploaded yet. Upload your resume below to activate instant applications.
                    </div>
                <?php endif; ?>

                <form action="<?= url('seeker-dashboard.php?tab=profile') ?>" method="POST" enctype="multipart/form-data">
                    <?= csrf_input() ?>
                    <input type="hidden" name="action" value="update_resume">
                    
                    <label style="display: block; font-size: 0.86rem; font-weight: 700; margin-bottom: 6px;">
                        <?= !empty($user['resume_path']) ? 'Replace Resume (PDF/DOCX)' : 'Upload New Resume' ?>
                    </label>
                    <input type="file" name="resume" required accept=".pdf,.doc,.docx" style="width: 100%; font-size: 0.85rem; margin-bottom: 14px;">
                    
                    <button type="submit" class="btn btn-green" style="width: 100%; font-size: 0.88rem; padding: 10px;">
                        <?= !empty($user['resume_path']) ? 'Upload & Replace' : 'Upload Resume' ?>
                    </button>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <!-- TAB 3: Security -->
    <?php if ($activeTab === 'security'): ?>
        <div style="background: #ffffff; border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 32px; max-width: 540px; box-shadow: var(--shadow-sm);">
            <h2 style="font-size: 1.25rem; font-weight: 700; color: var(--text-dark); margin-bottom: 20px;">
                Change Account Password
            </h2>

            <form action="<?= url('seeker-dashboard.php?tab=security') ?>" method="POST">
                <?= csrf_input() ?>
                <input type="hidden" name="action" value="update_password">

                <div style="margin-bottom: 18px;">
                    <label style="display: block; font-size: 0.88rem; font-weight: 700; margin-bottom: 6px;">Current Password</label>
                    <input type="password" name="current_password" required placeholder="Enter current password" style="width: 100%; padding: 11px 14px; border: 1px solid var(--border-color); border-radius: var(--radius-sm); font-size: 0.95rem;">
                </div>

                <div style="margin-bottom: 18px;">
                    <label style="display: block; font-size: 0.88rem; font-weight: 700; margin-bottom: 6px;">New Password (Min 6 characters)</label>
                    <input type="password" name="new_password" required placeholder="Enter new password" style="width: 100%; padding: 11px 14px; border: 1px solid var(--border-color); border-radius: var(--radius-sm); font-size: 0.95rem;">
                </div>

                <div style="margin-bottom: 24px;">
                    <label style="display: block; font-size: 0.88rem; font-weight: 700; margin-bottom: 6px;">Confirm New Password</label>
                    <input type="password" name="confirm_new_password" required placeholder="Repeat new password" style="width: 100%; padding: 11px 14px; border: 1px solid var(--border-color); border-radius: var(--radius-sm); font-size: 0.95rem;">
                </div>

                <button type="submit" class="btn btn-red" style="padding: 12px 28px;">
                    Update Password
                </button>
            </form>
        </div>
    <?php endif; ?>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

