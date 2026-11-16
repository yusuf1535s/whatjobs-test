/**
 * WhatJobs Clone - Core Frontend JavaScript
 */

document.addEventListener('DOMContentLoaded', () => {
    // 1. Mobile Menu Drawer Toggle
    const mobileToggle = document.getElementById('mobileMenuToggle');
    const mobileDrawer = document.getElementById('mobileDrawer');
    const mobileDrawerClose = document.getElementById('mobileDrawerClose');

    if (mobileToggle && mobileDrawer) {
        mobileToggle.addEventListener('click', () => {
            mobileDrawer.classList.add('open');
            document.body.style.overflow = 'hidden';
        });
    }

    if (mobileDrawerClose && mobileDrawer) {
        mobileDrawerClose.addEventListener('click', () => {
            mobileDrawer.classList.remove('open');
            document.body.style.overflow = '';
        });
    }

    // 2. Clear input buttons for search inputs
    const clearWhat = document.getElementById('clearSearchWhat');
    const inputWhat = document.querySelector('input[name="what"]');
    if (clearWhat && inputWhat) {
        clearWhat.addEventListener('click', () => {
            inputWhat.value = '';
            inputWhat.focus();
        });
    }

    const clearWhere = document.getElementById('clearSearchWhere');
    const inputWhere = document.querySelector('input[name="where"]');
    if (clearWhere && inputWhere) {
        clearWhere.addEventListener('click', () => {
            inputWhere.value = '';
            inputWhere.focus();
        });
    }

    // 3. Save / Unsave Job via Fetch API
    document.querySelectorAll('.btn-save-job').forEach(btn => {
        btn.addEventListener('click', async (e) => {
            e.preventDefault();
            e.stopPropagation();
            const jobId = btn.dataset.jobId;
            if (!jobId) return;

            try {
                const formData = new FormData();
                formData.append('job_id', jobId);
                
                // Get CSRF token if present
                const csrfMeta = document.querySelector('meta[name="csrf-token"]');
                if (csrfMeta) {
                    formData.append('csrf_token', csrfMeta.content);
                }

                const response = await fetch('saved-jobs.php?action=toggle', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                const data = await response.json();
                if (data.status === 'unauthenticated') {
                    window.location.href = 'login.php?redirect=' + encodeURIComponent(window.location.pathname + window.location.search);
                    return;
                }

                if (data.success) {
                    if (data.saved) {
                        btn.classList.add('saved');
                        btn.querySelector('.material-symbols-rounded').textContent = 'bookmark';
                        btn.title = 'Remove from saved jobs';
                    } else {
                        btn.classList.remove('saved');
                        btn.querySelector('.material-symbols-rounded').textContent = 'bookmark_border';
                        btn.title = 'Save job';

                        // If on saved-jobs.php, optionally fade out card
                        const cardWrapper = btn.closest('.saved-job-card-wrapper');
                        if (cardWrapper) {
                            cardWrapper.style.opacity = '0';
                            setTimeout(() => cardWrapper.remove(), 250);
                        }
                    }
                }
            } catch (err) {
                console.error('Error toggling job bookmark:', err);
            }
        });
    });

    // 4. Dismissible alerts
    document.querySelectorAll('.flash-close').forEach(btn => {
        btn.addEventListener('click', () => {
            const alert = btn.closest('.flash-message');
            if (alert) alert.remove();
        });
    });
});

