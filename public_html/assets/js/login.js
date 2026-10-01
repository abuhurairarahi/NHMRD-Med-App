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
