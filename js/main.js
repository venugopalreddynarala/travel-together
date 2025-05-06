document.addEventListener('DOMContentLoaded', () => {
    // Auto-scroll chat box to bottom
    const chatBox = document.getElementById('chat-box');
    if (chatBox) {
        chatBox.scrollTop = chatBox.scrollHeight;
    }

    // Toggle mobile navigation
    const navToggle = document.querySelector('.nav-toggle');
    const navLinks = document.querySelector('.nav-links');
    if (navToggle) {
        navToggle.addEventListener('click', () => {
            navLinks.classList.toggle('active');
            navToggle.textContent = navLinks.classList.contains('active') ? '✕' : '☰';
        });
    }

    // Theme toggle
    const themeToggle = document.getElementById('theme-toggle');
    if (themeToggle) {
        themeToggle.addEventListener('click', () => {
            const body = document.body;
            const isDark = body.classList.contains('dark');
            body.classList.toggle('dark');
            body.classList.toggle('light');
            fetch('profile.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `csrf_token=${encodeURIComponent('<?php echo $_SESSION['csrf_token']; ?>')}&theme=${isDark ? 'light' : 'dark'}`
            });
        });
    }

    // Form validation
    const forms = document.querySelectorAll('.form');
    forms.forEach(form => {
        form.addEventListener('submit', (e) => {
            const inputs = form.querySelectorAll('input:not([type="file"]), textarea, select');
            let valid = true;
            inputs.forEach(input => {
                if (!input.value.trim()) {
                    valid = false;
                    input.style.borderColor = '#d32f2f';
                } else {
                    input.style.borderColor = '#ccc';
                }
            });

            // Password confirmation validation
            const newPassword = form.querySelector('input[name="new_password"]');
            const confirmPassword = form.querySelector('input[name="confirm_password"]');
            if (newPassword && confirmPassword && newPassword.value !== confirmPassword.value) {
                valid = false;
                confirmPassword.style.borderColor = '#d32f2f';
                alert('Passwords do not match.');
            }

            // Date validation for trip plans
            const startDate = form.querySelector('input[name="start_date"]');
            const endDate = form.querySelector('input[name="end_date"]');
            if (startDate && endDate && new Date(endDate.value) < new Date(startDate.value)) {
                valid = false;
                endDate.style.borderColor = '#d32f2f';
                alert('End date cannot be before start date.');
            }

            // File size validation for profile picture
            const fileInput = form.querySelector('input[type="file"]');
            if (fileInput && fileInput.files.length > 0) {
                const maxSize = 2 * 1024 * 1024; // 2MB
                if (fileInput.files[0].size > maxSize) {
                    valid = false;
                    fileInput.style.borderColor = '#d32f2f';
                    alert('File size exceeds 2MB.');
                }
            }

            if (!valid) {
                e.preventDefault();
                alert('Please correct the errors in the form.');
            }
        });
    });

    // Animate elements on scroll
    const animateElements = document.querySelectorAll('.animate');
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, { threshold: 0.1 });

    animateElements.forEach(el => observer.observe(el));
});