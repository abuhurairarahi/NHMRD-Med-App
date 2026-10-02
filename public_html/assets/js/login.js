/**
 * login.js - handles NHMRD unified login form submission
 */
async function handleLogin(event) {
    if (event) event.preventDefault();

    const userIdInput = document.getElementById('userid');
    const passwordInput = document.getElementById('password');
    const errorBox = document.getElementById('loginError');
    const submitBtn = event ? (event.target.querySelector('button[type="submit"]') || document.querySelector('.btn-submit')) : document.querySelector('.btn-submit');

    const identifier = (userIdInput ? userIdInput.value : '').trim();
    const password = (passwordInput ? passwordInput.value : '').trim();

    const showError = (msg) => {
        if (errorBox) {
            errorBox.textContent = msg;
            errorBox.style.display = 'block';
        } else {
            alert(msg);
        }
    };

    if (errorBox) {
        errorBox.style.display = 'none';
        errorBox.textContent = '';
    }

    if (!identifier || !password) {
        showError('Please enter your User ID / NID and password.');
        return;
    }

    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.textContent = 'Verifying credentials...';
    }

    try {
        // Resolve API path relative to current URL
        let apiUrl = '../api/login.php';
        const path = window.location.pathname;
        if (path.includes('/public_html/pages/')) {
            apiUrl = '../api/login.php';
        } else if (path.includes('/public_html/')) {
            apiUrl = 'api/login.php';
        } else {
            apiUrl = 'public_html/api/login.php';
        }

        const response = await fetch(apiUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ identifier, userid: identifier, password })
        });

        const data = await response.json();

        if (data.success) {
            if (submitBtn) submitBtn.textContent = 'Redirecting...';
            let target = data.redirect;
            // Adjust relative redirect if called from root vs inside /pages/
            if (window.location.pathname.includes('/pages/') && target.startsWith('../pages/')) {
                target = target.replace('../pages/', '');
            } else if (!window.location.pathname.includes('/public_html/') && !target.startsWith('/')) {
                if (target.startsWith('../')) {
                    target = 'public_html/' + target.replace('../', '');
                }
            }
            window.location.href = target;
        } else {
            showError(data.message || data.error || 'Login failed. Please check your credentials.');
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Log In';
            }
        }
    } catch (err) {
        console.error('Login error:', err);
        showError('Network error communicating with NHMRD authentication service.');
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.textContent = 'Log In';
        }
    }
}

window.handleLogin = handleLogin;
