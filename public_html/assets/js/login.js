/**
 * login.js — handles the NHMRD login form submission via fetch()
 */
async function handleLogin(event) {
  event.preventDefault();

  const form       = event.target;
  const userId     = document.getElementById('userid').value.trim();
  const password   = document.getElementById('password').value.trim();
  const submitBtn  = form.querySelector('button[type="submit"]');
  const errorEl    = document.getElementById('loginError');

  // Clear previous errors
  if (errorEl) errorEl.textContent = '';

  if (!userId || !password) {
    showError(errorEl, 'Please enter your User ID / NID and password.');
    return;
  }

  // Disable button to prevent double-submit
  if (submitBtn) {
    submitBtn.disabled = true;
    submitBtn.textContent = 'Logging in…';
  }

  try {
    const formData = new FormData();
    formData.append('identifier', userId);
    formData.append('password', password);

    const response = await fetch('/public_html/controllers/auth/login.php', {
      method: 'POST',
      body: formData
    });

    const data = await response.json();

    if (data.success) {
      // Brief success message then redirect
      if (submitBtn) submitBtn.textContent = 'Redirecting…';
      window.location.href = data.redirect;
    } else {
      showError(errorEl, data.message || 'Login failed. Please try again.');
      if (submitBtn) {
        submitBtn.disabled = false;
        submitBtn.textContent = 'Login';
      }
    }
  } catch (err) {
    console.error('Login fetch error:', err);
    showError(errorEl, 'Network error. Ensure the server is running and try again.');
    if (submitBtn) {
      submitBtn.disabled = false;
      submitBtn.textContent = 'Login';
    }
  }
}

function showError(el, message) {
  if (el) {
    el.textContent = message;
    el.style.display = 'block';
  } else {
    alert(message);
  }
}