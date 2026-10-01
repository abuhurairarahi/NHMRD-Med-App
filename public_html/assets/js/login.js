<<<<<<< HEAD
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

=======
function handleLogin(event) {
    event.preventDefault(); // Prevent standard form submission

    const useridInput = document.getElementById('userid');
    const passwordInput = document.getElementById('password');
    const errorBox = document.getElementById('loginError');

    const userid = useridInput.value.trim();
    const password = passwordInput.value;

    if (!userid || !password) {
        showError('Please enter both UserID and Password.');
        return;
    }

    // Hide error box if previously shown
    if (errorBox) errorBox.style.display = 'none';

    const formData = new FormData();
    formData.append('userid', userid);
    formData.append('password', password);

    const submitBtn = document.querySelector('.btn-submit');
    if (submitBtn) {
        submitBtn.textContent = 'Logging in...';
        submitBtn.disabled = true;
    }

    fetch('/public_html/api/login.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (submitBtn) {
            submitBtn.textContent = 'Log In';
            submitBtn.disabled = false;
        }

        if (data.success) {
            // Redirect to appropriate dashboard
            window.location.href = data.redirect;
        } else {
            showError(data.error || 'Login failed. Please try again.');
        }
    })
    .catch(error => {
        console.error('Login error:', error);
        if (submitBtn) {
            submitBtn.textContent = 'Log In';
            submitBtn.disabled = false;
        }
        showError('An unexpected error occurred connecting to the server.');
    });
}

function showError(message) {
    const errorBox = document.getElementById('loginError');
    if (errorBox) {
        errorBox.textContent = message;
        errorBox.style.display = 'block';
    } else {
        alert(message); // Fallback if error box doesn't exist
    }
}
>>>>>>> 8e034926ccf50eb27ff0f452e34544453bde4e5d
