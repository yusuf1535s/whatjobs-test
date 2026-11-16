<?php
/**
 * WhatJobs Clone - Admin Index Redirect
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';

if (is_admin_logged_in()) {
    header('Location: ' . url('admin/dashboard.php'));
} else {
    header('Location: ' . url('admin/login.php'));
}
exit;

