<?php
/**
 * WhatJobs Clone - Reusable Admin Footer Layout
 */
?>
        </div> <!-- /admin-content -->
    </div> <!-- /admin-main -->
</div> <!-- /admin-layout -->

<script src="<?= url('assets/js/main.js') ?>"></script>
<?php if (isset($extraAdminJs) && is_array($extraAdminJs)): ?>
    <?php foreach ($extraAdminJs as $js): ?>
        <script src="<?= url($js) ?>"></script>
    <?php endforeach; ?>
<?php endif; ?>
</body>
</html>

