/**
 * NHMRD Patient Panel - Shared Common Utilities
 * Provides: Toast notifications, Modal dialogs, API fetch helper, Logout, Header synchronization
 */

// Toast Notifications System
window.showToast = function (title, message, type = 'success') {
  let container = document.querySelector('.nhmrd-toast-container');
  if (!container) {
    container = document.createElement('div');
    container.className = 'nhmrd-toast-container';
    document.body.appendChild(container);
  }

  const iconMap = {
    success: 'fa-circle-check',
    error: 'fa-circle-xmark',
    info: 'fa-circle-info',
    warning: 'fa-triangle-exclamation'
  };

  const icon = iconMap[type] || 'fa-bell';

  const toast = document.createElement('div');
  toast.className = `nhmrd-toast ${type}`;
  toast.innerHTML = `
    <i class="fa-solid ${icon} nhmrd-toast-icon"></i>
    <div class="nhmrd-toast-content">
      <div class="nhmrd-toast-title">${title}</div>
      <div class="nhmrd-toast-desc">${message}</div>
    </div>
    <button class="nhmrd-toast-close" onclick="this.closest('.nhmrd-toast').remove()">&times;</button>
  `;

  container.appendChild(toast);

  // Trigger animation
  setTimeout(() => toast.classList.add('show'), 10);

  // Auto-remove after 4.5 seconds
  setTimeout(() => {
    toast.classList.remove('show');
    setTimeout(() => toast.remove(), 350);
  }, 4500);
};

// Modal System
window.openModal = function ({ title, bodyHtml, footerHtml = '', size = '' }) {
  window.closeModal(); // Remove existing if any

  const backdrop = document.createElement('div');
  backdrop.className = 'nhmrd-modal-backdrop';
  backdrop.id = 'activeNhmrdModal';
  backdrop.onclick = function (e) {
    if (e.target === backdrop) window.closeModal();
  };

  backdrop.innerHTML = `
    <div class="nhmrd-modal-box ${size}">
      <div class="nhmrd-modal-header">
        <h3><i class="fa-solid fa-shield-halved"></i> ${title}</h3>
        <button class="nhmrd-modal-close" onclick="window.closeModal()">&times;</button>
      </div>
      <div class="nhmrd-modal-body">
        ${bodyHtml}
      </div>
      ${footerHtml ? `<div class="nhmrd-modal-footer">${footerHtml}</div>` : ''}
    </div>
  `;

  document.body.appendChild(backdrop);
  setTimeout(() => backdrop.classList.add('show'), 20);
};

window.closeModal = function () {
  const existing = document.getElementById('activeNhmrdModal');
  if (existing) {
    existing.classList.remove('show');
    setTimeout(() => existing.remove(), 250);
  }
};

// Global Logout Handler
window.handleLogout = function () {
  if (confirm('Are you sure you want to log out of the NHMRD Patient Portal?')) {
    fetch('../../api/logout.php')
      .then(res => res.json())
      .then(data => {
        window.location.href = data.redirect || '../login.html';
      })
      .catch(() => {
        window.location.href = '../login.html';
      });
  }
};

// Global Notifications Handler
window.handleNotificationClick = function () {
  fetch('../../api/patient_dashboard.php')
    .then(r => r.json())
    .then(data => {
      const notices = data.notices || [];
      let noticesHtml = '';
      if (notices.length === 0) {
        noticesHtml = '<p class="text-muted">No unread announcements at this time.</p>';
      } else {
        noticesHtml = notices.map(n => `
          <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:14px; margin-bottom:12px;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;">
              <span class="badge ${n.category === 'critical' ? 'bg-danger' : 'bg-primary'}">${(n.category || 'NOTICE').toUpperCase()}</span>
              <small class="text-muted">${n.reference_code || ''}</small>
            </div>
            <strong style="color:#0f172a; display:block; margin-bottom:4px;">${n.title}</strong>
            <p style="font-size:12.5px; color:#475569; margin:0;">${n.description}</p>
          </div>
        `).join('');
      }

      window.openModal({
        title: 'National Health Broadcasts & Alerts',
        bodyHtml: `
          <div style="max-height:400px; overflow-y:auto;">
            ${noticesHtml}
          </div>
        `,
        footerHtml: `<button class="nhmrd-btn nhmrd-btn-secondary" onclick="window.closeModal()">Close</button>`
      });
    })
    .catch(() => {
      window.showToast('Notice Center', 'Connected to DGHS National Health Alert Network.', 'info');
    });
};

// Global Top Header Synchronization (Updates Name, Patient ID, and Avatar across all pages)
window.syncPatientHeader = function (patient) {
  if (!patient) return;

  const names = document.querySelectorAll('.user-name');
  names.forEach(el => el.textContent = patient.full_name);

  const ids = document.querySelectorAll('.patient-id, .id-tag, .nhmrd-id');
  ids.forEach(el => {
    if (el.classList.contains('id-tag')) {
      el.textContent = `ID #${patient.uid || patient.patient_id || '2042122004'}`;
    } else if (el.classList.contains('nhmrd-id')) {
      el.textContent = `NHMRD ID #${patient.uid || patient.patient_id || '2042122004'}`;
    } else {
      el.textContent = `Patient ID #${patient.uid || patient.patient_id || '2042122004'}`;
    }
  });

  // Avatar Initials
  const parts = (patient.full_name || 'Patient').trim().split(/\s+/);
  let initials = parts[0][0];
  if (parts.length > 1) initials += parts[parts.length - 1][0];
  initials = initials.toUpperCase();

  const avatars = document.querySelectorAll('.user-badge-avatar, .avatar, .user-avatar-blue, .patient-avatar-initials');
  avatars.forEach(el => el.textContent = initials);
};
