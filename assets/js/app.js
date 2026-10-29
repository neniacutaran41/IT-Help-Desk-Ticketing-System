/**
 * DeskHero IT Help Desk - Front-End Interactions
 */

document.addEventListener('DOMContentLoaded', function () {
    // 1. One-Click Demo Credentials Auto-Fill
    const demoButtons = document.querySelectorAll('.demo-btn');
    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('password');

    demoButtons.forEach(function (btn) {
        btn.addEventListener('click', function () {
            const email = this.getAttribute('data-demo-email');
            const pass = this.getAttribute('data-demo-pass');

            if (emailInput && passwordInput) {
                emailInput.value = email;
                passwordInput.value = pass;

                // Visual focus feedback
                passwordInput.focus();

                // Highlight active button briefly
                demoButtons.forEach(b => b.classList.remove('active'));
                this.classList.add('active');
            }
        });
    });

    // 2. Prevent Double Form Submission
    const forms = document.querySelectorAll('form[data-prevent-double-submit="true"], form');
    forms.forEach(function (form) {
        form.addEventListener('submit', function () {
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn && !submitBtn.disabled) {
                // Allow a tiny delay so submit event triggers properly
                setTimeout(() => {
                    submitBtn.disabled = true;
                    const originalHtml = submitBtn.innerHTML;
                    submitBtn.setAttribute('data-original-html', originalHtml);
                    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Processing...';
                }, 10);
            }
        });
    });

    // 3. Confirm Delete Actions
    const deleteButtons = document.querySelectorAll('[data-confirm]');
    deleteButtons.forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            const message = this.getAttribute('data-confirm') || 'Are you sure you want to proceed?';
            if (!confirm(message)) {
                e.preventDefault();
            }
        });
    });

    // 4. Initialize Bootstrap Tooltips if available
    if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }
});
