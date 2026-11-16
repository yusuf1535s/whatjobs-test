<?php
/**
 * WhatJobs Clone - Registered Candidates Management (admin/users.php)
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/User.php';
require_once __DIR__ . '/../includes/csrf.php';

require_admin();

$userModel = new User();

// Handle Status Toggle
if (isset($_GET['action']) && $_GET['action'] === 'toggle' && isset($_GET['id'])) {
    $userId = (int)$_GET['id'];
    $newStatus = $userModel->toggleUserStatus($userId);
    set_flash('success', 'User #' . $userId . ' account status set to ' . ucfirst($newStatus) . '.');
    header('Location: ' . url('admin/users.php'));
    exit;
}

$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;

$totalUsers = $userModel->countAllUsers();
$users = $userModel->getAllUsers($limit, $offset);
$totalPages = ceil($totalUsers / $limit);

$pageTitle = "Registered Candidates (" . $totalUsers . ") | WhatJobs Admin";
require_once __DIR__ . '/includes/admin-header.php';
?>

<!-- Header -->
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 16px;">
    <div>
        <h1 style="font-size: 1.5rem; font-weight: 800; color: var(--text-dark);">
            Job Seeker Accounts
        </h1>
        <p style="font-size: 0.88rem; color: var(--text-muted); margin-top: 4px;">
            Total registered candidates on the WhatJobs platform: <strong><?= $totalUsers ?></strong>
        </p>
    </div>
</div>

<!-- Users Table -->
<div class="admin-card">
    <?php if (empty($users)): ?>
        <div style="padding: 48px; text-align: center; color: var(--text-muted);">
            No registered candidates found.
        </div>
    <?php else: ?>
        <div style="overflow-x: auto;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 50px;">ID</th>
                        <th>Full Name</th>
                        <th>Email &amp; Phone</th>
                        <th>Target Role &amp; Location</th>
                        <th>Applications</th>
                        <th>Resume</th>
                        <th>Status</th>
                        <th style="text-align: right;">Account Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                        <tr>
                            <td>#<?= $u['id'] ?></td>
                            <td>
                                <strong style="font-size: 0.95rem; color: var(--text-dark);">
                                    <?= htmlspecialchars($u['full_name']) ?>
                                </strong>
                                <div style="font-size: 0.78rem; color: #64748b;">
                                    Joined <?= date('M j, Y', strtotime($u['created_at'])) ?>
                                </div>
                            </td>
                            <td>
                                <a href="mailto:<?= htmlspecialchars($u['email']) ?>" style="color: var(--primary-red); font-weight: 500;">
                                    <?= htmlspecialchars($u['email']) ?>
                                </a><br>
                                <span style="font-size: 0.8rem; color: #64748b;"><?= htmlspecialchars($u['phone'] ?? 'No phone provided') ?></span>
                            </td>
                            <td>
                                <span style="font-weight: 600; color: var(--text-dark);"><?= htmlspecialchars($u['current_job_title'] ?? 'Candidate') ?></span><br>
                                <span style="font-size: 0.8rem; color: #64748b;"><?= htmlspecialchars($u['location'] ?? 'Location not specified') ?></span>
                            </td>
                            <td>
                                <strong style="color: var(--text-dark);"><?= (int)$u['applications_count'] ?></strong> submitted
                            </td>
                            <td>
                                <?php if (!empty($u['resume_path'])): ?>
                                    <a href="<?= url($u['resume_path']) ?>" target="_blank" style="color: var(--primary-red); font-weight: 600; font-size: 0.82rem; display: inline-flex; align-items: center; gap: 4px;">
                                        <span class="material-symbols-rounded" style="font-size: 16px;">description</span> Stored CV
                                    </a>
                                <?php else: ?>
                                    <span style="color: #94a3b8; font-size: 0.82rem;">None</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge-status <?= $u['status'] === 'active' ? 'badge-active' : 'badge-rejected' ?>">
                                    <?= htmlspecialchars($u['status']) ?>
                                </span>
                            </td>
                            <td style="text-align: right; white-space: nowrap;">
                                <a href="<?= url('admin/users.php?action=toggle&id=' . $u['id']) ?>" class="btn btn-outline-white" style="color: var(--text-dark); border-color: var(--border-color); font-size: 0.8rem; padding: 5px 12px;" onclick="return confirm('Change user account status?');">
                                    <?= $u['status'] === 'active' ? 'Suspend' : 'Reactivate' ?>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if ($totalPages > 1): ?>
            <div style="display: flex; justify-content: center; gap: 8px; padding: 20px; border-top: 1px solid var(--border-light);">
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <a href="<?= url('admin/users.php?page=' . $i) ?>" class="btn" style="<?= $i === $page ? 'background: #181818; color: #fff;' : 'background: #fff; color: var(--text-dark); border: 1px solid var(--border-color);' ?> padding: 6px 12px; font-size: 0.84rem;">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>

