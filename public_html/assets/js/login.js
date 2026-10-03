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
        // Find the base path of the project (e.g. /NHMRD GitHub/ or /)
        const path = window.location.pathname;
        let basePath = '';
        
        // If we are in the root (index.php)
        if (!path.includes('/public_html/')) {
            const parts = path.split('/');
            parts.pop(); // remove index.php or trailing slash
            basePath = parts.join('/') + '/';
            if (basePath === '//') basePath = '/';
        } else {
            // We are already inside public_html
            const parts = path.split('/public_html/');
            basePath = parts[0] + '/';
        }

        const apiUrl = basePath + 'public_html/api/login.php';

        const response = await fetch(apiUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ identifier, userid: identifier, password })
        });

        const data = await response.json();

        if (data.success) {
            if (submitBtn) submitBtn.textContent = 'Redirecting...';
            // target from api/login.php is usually like '../pages/doctor-panel/dashboard.php'
            let target = data.redirect; 
            target = target.replace('../', 'public_html/'); // -> public_html/pages/...
            window.location.href = basePath + target;
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
