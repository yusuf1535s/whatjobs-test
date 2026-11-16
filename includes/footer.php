<?php
/**
 * WhatJobs Clone - Global Reusable Footer
 */
?>
<!-- Footer Global -->
<footer class="site-footer">
    <div class="container">
        <div class="footer-top-grid">
            <!-- Brand Column -->
            <div class="footer-brand">
                <a href="<?= url('index.php') ?>" class="brand-logo" title="WhatJobs Home">
                    <img src="<?= url('assets/images/logo.svg') ?>" alt="WhatJobs" width="180" height="34" style="height: 34px; width: auto; display: block;">
                </a>
                <p>
                    WhatJobs is the leading job search engine connecting ambitious job seekers with thousands of active vacancies across the United States. Search full-time, remote, and part-time positions and apply in seconds.
                </p>
            </div>

            <!-- Column 1: WhatJobs -->
            <div>
                <h4 class="footer-col-title">WhatJobs</h4>
                <ul class="footer-links">
                    <li><a href="<?= url('index.php') ?>">Home</a></li>
                    <li><a href="<?= url('jobs.php') ?>">About Us</a></li>
                    <li><a href="<?= url('jobs.php') ?>">Help &amp; FAQs</a></li>
                    <li><a href="<?= url('jobs.php') ?>">International</a></li>
                    <li><a href="<?= url('jobs.php') ?>">Contact Us</a></li>
                </ul>
            </div>

            <!-- Column 2: Job Seekers -->
            <div>
                <h4 class="footer-col-title">For Job Seekers</h4>
                <ul class="footer-links">
                    <li><a href="<?= url('jobs.php') ?>">Search Jobs</a></li>
                    <li><a href="<?= url('jobs.php?remote=1') ?>">Remote Jobs</a></li>
                    <li><a href="<?= url('register.php') ?>">Register Resume</a></li>
                    <li><a href="<?= url('login.php') ?>">Seeker Login</a></li>
                    <li><a href="<?= url('saved-jobs.php') ?>">Saved Jobs</a></li>
                    <li><a href="<?= url('jobs.php') ?>">Career Advice</a></li>
                </ul>
            </div>

            <!-- Column 3: For Business -->
            <div>
                <h4 class="footer-col-title">For Business</h4>
                <ul class="footer-links">
                    <li><a href="<?= url('admin/login.php') ?>">Employers | Post Job</a></li>
                    <li><a href="<?= url('admin/login.php') ?>">Hiring Solutions</a></li>
                    <li><a href="<?= url('admin/login.php') ?>">Candidate Search</a></li>
                    <li><a href="<?= url('admin/login.php') ?>">Employer Branding</a></li>
                    <li><a href="<?= url('admin/dashboard.php') ?>">Recruiter Dashboard</a></li>
                </ul>
            </div>

            <!-- Column 4: Helpful Resources -->
            <div>
                <h4 class="footer-col-title">Resources</h4>
                <ul class="footer-links">
                    <li><a href="<?= url('jobs.php') ?>">AI Powered Job Search</a></li>
                    <li><a href="<?= url('jobs.php') ?>">Salary Explorer</a></li>
                    <li><a href="<?= url('jobs.php') ?>">Your Safety</a></li>
                    <li><a href="<?= url('jobs.php') ?>">Terms of Service</a></li>
                    <li><a href="<?= url('jobs.php') ?>">Privacy Policy</a></li>
                </ul>
            </div>
        </div>

        <div class="footer-bottom">
            <div>
                &copy; <?= date('Y') ?> WhatJobs Clone. All rights reserved. Recreated for educational &amp; evaluation purposes.
            </div>
            <div class="footer-bottom-links">
                <a href="<?= url('jobs.php') ?>">Privacy</a>
                <a href="<?= url('jobs.php') ?>">Terms</a>
                <a href="<?= url('jobs.php') ?>">Security</a>
                <a href="<?= url('jobs.php') ?>">Sitemap</a>
            </div>
        </div>
    </div>
</footer>

<!-- Core Vanilla JS -->
<script src="<?= url('assets/js/main.js') ?>"></script>
<?php if (isset($extraJs) && is_array($extraJs)): ?>
    <?php foreach ($extraJs as $js): ?>
        <script src="<?= url($js) ?>"></script>
    <?php endforeach; ?>
<?php endif; ?>

</body>
</html>

