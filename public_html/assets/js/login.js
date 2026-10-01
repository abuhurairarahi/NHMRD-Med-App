/**
 * login.js - handles NHMRD unified login form submission
 */
async function handleLogin(event) {
    event.preventDefault();

    const userIdInput = document.getElementById('userid');
    const passwordInput = document.getElementById('password');
    const submitBtn = event.target.querySelector('button[type="submit"]');

    const identifier = (userIdInput ? userIdInput.value : '').trim();
    const password = (passwordInput ? passwordInput.value : '').trim();

    if (!identifier || !password) {
        alert('Please enter your UserID / NID and password.');
        return;
    }

    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.textContent = 'Verifying credentials...';
    }

    try {
        // Resolve API path relative to current page
        let apiUrl = '../api/login.php';
        const path = window.location.pathname;
        if (path.includes('/public_html/')) {
            const pubIdx = path.indexOf('/public_html/');
            apiUrl = path.substring(0, pubIdx) + '/public_html/api/login.php';
        }

        const response = await fetch(apiUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ identifier, password })
        });

        const data = await response.json();

        if (data.success) {
            if (submitBtn) submitBtn.textContent = 'Redirecting...';
            let target = data.redirect;
            if (window.location.pathname.includes('/pages/') && target.startsWith('../pages/')) {
                target = target.replace('../pages/', '');
            }
            window.location.href = target;
        } else {
            alert(data.message || 'Login failed. Please check your credentials.');
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Log In';
            }
        }
    } catch (err) {
        console.error('Login error:', err);
        alert('Network error communicating with NHMRD authentication service.');
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.textContent = 'Log In';
        }
    }
}

window.handleLogin = handleLogin;

