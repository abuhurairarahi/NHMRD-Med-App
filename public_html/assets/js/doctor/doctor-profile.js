// Tab Switching
document.querySelectorAll('.profile-tabs .tab-item').forEach(tab => {
  tab.addEventListener('click', (e) => {
    document.querySelectorAll('.profile-tabs .tab-item').forEach(t => t.classList.remove('active'));
    e.currentTarget.classList.add('active');
  });
});

// Header Search Input
const searchInput = document.querySelector('.search-bar input');
if (searchInput) {
  searchInput.addEventListener('input', (e) => {
    const query = e.target.value.trim();
    console.log('Searching:', query);
  });
}

// Sidebar Logout Button
const logoutBtn = document.querySelector('.logout-btn');
if (logoutBtn) {
  logoutBtn.addEventListener('click', () => {
    if (confirm('Are you sure you want to log out?')) {
      window.location.href = '/public_html/pages/login.html';
    }
  });
}

// Edit Profile Button
const editProfileBtn = document.querySelector('.profile-actions .btn-emerald');
if (editProfileBtn) {
  editProfileBtn.addEventListener('click', () => {
    console.log('Edit profile clicked');
  });
}

// Credential Packet Button
const credentialBtn = document.querySelector('.profile-actions .btn-outline-dark');
if (credentialBtn) {
  credentialBtn.addEventListener('click', () => {
    console.log('Downloading credentials...');
  });
}

// Education Edit & Verify Buttons
const editEduBtn = document.querySelector('.edu-actions .btn-outline-xs');
if (editEduBtn) {
  editEduBtn.addEventListener('click', () => {
    console.log('Edit education clicked');
  });
}

const verifyEduBtn = document.querySelector('.edu-actions .btn-emerald-xs');
if (verifyEduBtn) {
  verifyEduBtn.addEventListener('click', () => {
    console.log('Verification requested');
  });
}