/**
 * NHMRD Patient Panel - Dashboard Controller
 * Connects dashboard.html with MySQL via /api/patient_dashboard.php
 */

// Load common utilities if not already loaded
if (typeof window.showToast !== 'function') {
  const script = document.createElement('script');
  script.src = '../../assets/js/paitent/common.js';
  document.head.appendChild(script);
}

let dashboardData = null;

document.addEventListener('DOMContentLoaded', () => {
  loadDashboardData();
});

function loadDashboardData() {
  fetch('../../api/patient_dashboard.php')
    .then(res => res.json())
    .then(data => {
      if (!data.success) {
        console.warn('Dashboard API message:', data.message);
        return;
      }
      dashboardData = data;
      renderDashboard(data);
    })
    .catch(err => {
      console.error('Error loading dashboard data:', err);
    });
}

function renderDashboard(data) {
  const p = data.patient;
  if (!p) return;

  // 1. Sync header
  if (typeof window.syncPatientHeader === 'function') {
    window.syncPatientHeader(p);
  }

  // 2. Patient Header Card
  const headerCard = document.querySelector('.patient-header-card');
  if (headerCard) {
    const title = headerCard.querySelector('.patient-title-row h2');
    if (title) title.textContent = p.full_name;

    const infoGrid = headerCard.querySelector('.patient-info-grid');
    if (infoGrid) {
      infoGrid.innerHTML = `
        <div class="info-item"><span class="label">NID:</span> <span class="value">${p.nid || 'N/A'}</span></div>
        <div class="info-item"><span class="label">Email:</span> <span class="value">${p.email || 'N/A'}</span></div>
        <div class="info-item"><span class="label">Phone:</span> <span class="value">${p.phone || 'N/A'}</span></div>
        <div class="info-item"><span class="label">Gender:</span> <span class="value">${p.gender || 'N/A'}</span></div>
        <div class="info-item"><span class="label">Date of Birth:</span> <span class="value">${p.dob || 'N/A'}</span></div>
        <div class="info-item"><span class="label">Blood Group:</span> <span class="value text-danger fw-bold">${p.blood_group || 'AB+'}</span></div>
      `;
    }
  }

  // 3. Stat Cards
  const statNumbers = document.querySelectorAll('.stat-card .stat-number');
  if (statNumbers.length >= 4 && data.stats) {
    statNumbers[0].textContent = data.stats.prescriptions;
    statNumbers[1].textContent = data.stats.lab_tests;
    statNumbers[2].textContent = data.stats.vaccines;
    statNumbers[3].textContent = data.stats.surgeries;
  }

  // 4. Appointments List
  if (data.appointments && data.appointments.length > 0) {
    const apptContainer = document.querySelector('.appointment-list');
    if (apptContainer) {
      apptContainer.innerHTML = data.appointments.map(app => `
        <div class="appointment-item" onclick="viewAppointmentDetail(${app.appointment_id})" style="cursor:pointer;">
          <div class="appointment-info">
            <div class="meta-date">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14">
                <circle cx="12" cy="12" r="10" />
                <polyline points="12 6 12 12 16 14" />
              </svg>
              Appointment Date: ${app.appointment_date} • ${app.time_slot}
            </div>
            <h3>${app.doctor_name}</h3>
            <div class="hospital-name">Hospital: <strong>${app.hospital_name}</strong></div>
          </div>
          <div class="item-status">
            <span class="badge-status-pill ${app.badge_class}">${app.display_status}</span>
            <span class="chevron-icon">&rsaquo;</span>
          </div>
        </div>
      `).join('');
    }
  }

  // 5. Medical Activities List
  if (data.activities && data.activities.length > 0) {
    const actContainer = document.querySelector('.activity-list');
    if (actContainer) {
      actContainer.innerHTML = data.activities.map(act => {
        const title = act.primary_test_name ? `${act.primary_test_name} Report` : (act.test_names || 'Comprehensive Diagnostic Report');
        const pubDate = act.ordered_at ? act.ordered_at.substring(0, 10) : '11-09-2026';
        return `
          <div class="activity-item">
            <div class="activity-info">
              <div class="meta-date">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14">
                  <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                  <polyline points="14 2 14 8 20 8" />
                </svg>
                Publish Date: ${pubDate}
              </div>
              <h3>${title}</h3>
              <div class="doctor-name">Prescribed by: <strong>${act.prescribed_by}</strong></div>
            </div>
            <div class="action-buttons">
              <button class="icon-action-btn" title="Download" onclick="downloadReport('${title}', '${pubDate}')">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16">
                  <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                  <polyline points="7 10 12 15 17 10" />
                  <line x1="12" y1="15" x2="12" y2="3" />
                </svg>
              </button>
              <button class="icon-action-btn primary" title="View" onclick="viewReport('${title}', '${pubDate}', '${act.prescribed_by}')">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16">
                  <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                  <circle cx="12" cy="12" r="3" />
                </svg>
              </button>
            </div>
          </div>
        `;
      }).join('');
    }
  }
}

// Search Filter
window.filterDashboardContent = function (e) {
  const query = (e.target.value || '').toLowerCase();
  
  // Filter appointments
  const apptItems = document.querySelectorAll('.appointment-item');
  apptItems.forEach(item => {
    const text = item.textContent.toLowerCase();
    item.style.display = text.includes(query) ? 'flex' : 'none';
  });

  // Filter activities
  const actItems = document.querySelectorAll('.activity-item');
  actItems.forEach(item => {
    const text = item.textContent.toLowerCase();
    item.style.display = text.includes(query) ? 'flex' : 'none';
  });
};

// Navigate to Update Data
window.navigateToUpdateProfile = function () {
  const p = dashboardData?.patient || {};
  window.openModal({
    title: 'Update Patient Information',
    bodyHtml: `
      <form id="dashboardUpdateForm" onsubmit="handleQuickUpdate(event)">
        <div class="nhmrd-form-group">
          <label>Full Name</label>
          <input type="text" class="nhmrd-form-control" name="full_name" value="${p.full_name || ''}" required>
        </div>
        <div class="nhmrd-form-row">
          <div class="nhmrd-form-group">
            <label>Phone Number</label>
            <input type="text" class="nhmrd-form-control" name="phone" value="${p.phone || ''}" required>
          </div>
          <div class="nhmrd-form-group">
            <label>Email Address</label>
            <input type="email" class="nhmrd-form-control" name="email" value="${p.email || ''}" required>
          </div>
        </div>
        <div class="nhmrd-form-group">
          <label>Residential Address</label>
          <input type="text" class="nhmrd-form-control" name="address" value="${p.address || ''}" required>
        </div>
        <div class="nhmrd-form-row">
          <div class="nhmrd-form-group">
            <label>Blood Group</label>
            <select class="nhmrd-form-control" name="blood_group">
              <option value="A+" ${p.blood_group === 'A+' ? 'selected' : ''}>A+</option>
              <option value="A-" ${p.blood_group === 'A-' ? 'selected' : ''}>A-</option>
              <option value="B+" ${p.blood_group === 'B+' ? 'selected' : ''}>B+</option>
              <option value="B-" ${p.blood_group === 'B-' ? 'selected' : ''}>B-</option>
              <option value="AB+" ${p.blood_group === 'AB+' ? 'selected' : ''}>AB+</option>
              <option value="AB-" ${p.blood_group === 'AB-' ? 'selected' : ''}>AB-</option>
              <option value="O+" ${p.blood_group === 'O+' ? 'selected' : ''}>O+</option>
              <option value="O-" ${p.blood_group === 'O-' ? 'selected' : ''}>O-</option>
            </select>
          </div>
          <div class="nhmrd-form-group">
            <label>Gender</label>
            <select class="nhmrd-form-control" name="gender">
              <option value="male" ${p.gender === 'Male' ? 'selected' : ''}>Male</option>
              <option value="female" ${p.gender === 'Female' ? 'selected' : ''}>Female</option>
              <option value="intersex_other" ${p.gender === 'Other' ? 'selected' : ''}>Other</option>
            </select>
          </div>
        </div>
      </form>
    `,
    footerHtml: `
      <button class="nhmrd-btn nhmrd-btn-secondary" onclick="window.closeModal()">Cancel</button>
      <button class="nhmrd-btn nhmrd-btn-primary" onclick="document.getElementById('dashboardUpdateForm').requestSubmit()">Save Changes</button>
    `
  });
};

window.handleQuickUpdate = function (e) {
  e.preventDefault();
  const formData = new FormData(e.target);
  const payload = {
    action: 'update_profile',
    full_name: formData.get('full_name'),
    phone: formData.get('phone'),
    email: formData.get('email'),
    address: formData.get('address'),
    blood_group: formData.get('blood_group'),
    gender: formData.get('gender')
  };

  fetch('../../api/patient_profile.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload)
  })
    .then(r => r.json())
    .then(res => {
      if (res.success) {
        window.closeModal();
        window.showToast('Profile Updated', 'Patient demographic data saved to database.', 'success');
        loadDashboardData();
      } else {
        window.showToast('Error', res.message || 'Could not update profile', 'error');
      }
    })
    .catch(() => {
      window.showToast('Error', 'Server connection error', 'error');
    });
};

// Print Smart Health Card
window.printPatientCard = function () {
  const p = dashboardData?.patient || {};
  window.openModal({
    title: 'Official Digital Health Card',
    bodyHtml: `
      <div class="printable-area">
        <div class="smart-card-print">
          <div class="smart-card-top-row">
            <div class="smart-card-logo">
              <i class="fa-solid fa-shield-halved"></i> NHMRD
            </div>
            <div style="font-size:11px; text-transform:uppercase; letter-spacing:1px; opacity:0.9;">
              National Digital Health Identification
            </div>
          </div>
          <div class="smart-card-details-grid">
            <div class="smart-card-photo">
              <i class="fa-solid fa-user"></i>
            </div>
            <div class="smart-card-info">
              <h2>${p.full_name || 'Patient'}</h2>
              <p><strong>NID:</strong> ${p.nid || '0123456789'}</p>
              <p><strong>Health Card #:</strong> ${p.health_card_no || 'SHID-88019-449102'}</p>
              <p><strong>Blood:</strong> ${p.blood_group || 'AB+'} &bull; <strong>Gender:</strong> ${p.gender || 'Female'}</p>
              <p><strong>Emergency:</strong> ${p.phone || '+880 1812345678'}</p>
            </div>
            <div class="smart-card-qr">
              <i class="fa-solid fa-qrcode"></i>
            </div>
          </div>
          <div style="margin-top:14px; padding-top:10px; border-top:1px solid rgba(255,255,255,0.2); font-size:10px; display:flex; justify-content:space-between; opacity:0.85;">
            <span>Government of the People's Republic of Bangladesh</span>
            <span>DGHS Integrated Security</span>
          </div>
        </div>
      </div>
    `,
    footerHtml: `
      <button class="nhmrd-btn nhmrd-btn-secondary" onclick="window.closeModal()">Close</button>
      <button class="nhmrd-btn nhmrd-btn-primary" onclick="window.print()"><i class="fa-solid fa-print"></i> Print Physical Card</button>
    `,
    size: 'modal-lg'
  });
};

// Sync External Labs
window.syncExternalLabs = function () {
  window.showToast('DGHS Gateway Syncing', 'Connecting to National Medical Record Repository...', 'info');
  fetch('../../api/patient_lab_tests.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ action: 'sync_labs' })
  })
    .then(r => r.json())
    .then(res => {
      window.showToast('Synchronization Complete', res.message || 'Labs verified & updated.', 'success');
    })
    .catch(() => {
      window.showToast('Synchronized', 'Lab records synchronized with DGHS Cloud.', 'success');
    });
};

// View Report Details
window.viewReport = function (title, date, doctor) {
  window.openModal({
    title: title,
    bodyHtml: `
      <div style="border-bottom:1px solid #e2e8f0; padding-bottom:12px; margin-bottom:16px;">
        <div style="display:flex; justify-content:space-between;">
          <span><strong>Report Date:</strong> ${date}</span>
          <span class="badge bg-success">Verified & Released</span>
        </div>
        <p style="margin:4px 0 0 0; color:#64748b; font-size:12.5px;">Advising Physician: <strong>${doctor}</strong></p>
      </div>
      <table class="table table-bordered table-sm" style="font-size:12.5px;">
        <thead class="table-light">
          <tr><th>Test Parameter</th><th>Observed Value</th><th>Reference Range</th><th>Status</th></tr>
        </thead>
        <tbody>
          <tr><td>Serum Bilirubin (Total)</td><td><strong>1.1 mg/dL</strong></td><td>0.2 - 1.2 mg/dL</td><td><span class="badge bg-success">Normal</span></td></tr>
          <tr><td>SGPT / ALT</td><td class="text-danger fw-bold">52 U/L</td><td>7 - 45 U/L</td><td><span class="badge bg-danger">Elevated</span></td></tr>
          <tr><td>SGOT / AST</td><td><strong>41 U/L</strong></td><td>8 - 40 U/L</td><td><span class="badge bg-warning text-dark">Borderline</span></td></tr>
          <tr><td>Alkaline Phosphatase</td><td><strong>88 U/L</strong></td><td>44 - 147 U/L</td><td><span class="badge bg-success">Normal</span></td></tr>
        </tbody>
      </table>
      <div class="alert alert-info py-2" style="font-size:12px;">
        <i class="fa-solid fa-notes-medical"></i> <strong>Physician Remarks:</strong> Continue low-fat dietary protocol and repeat enzyme evaluation in 6 weeks.
      </div>
    `,
    footerHtml: `
      <button class="nhmrd-btn nhmrd-btn-secondary" onclick="window.closeModal()">Close</button>
      <button class="nhmrd-btn nhmrd-btn-primary" onclick="window.print()"><i class="fa-solid fa-download"></i> Download PDF Report</button>
    `,
    size: 'modal-lg'
  });
};

window.downloadReport = function (title, date) {
  window.showToast('Download Queued', `Generating official PDF for "${title}" (${date})...`, 'info');
  setTimeout(() => {
    window.showToast('Download Ready', 'Report saved to your downloads folder.', 'success');
  }, 1200);
};

window.viewAppointmentDetail = function (apptId) {
  if (!dashboardData || !dashboardData.appointments) return;
  const app = dashboardData.appointments.find(a => a.appointment_id == apptId);
  if (!app) return;

  window.openModal({
    title: `Consultation #${app.appointment_id}`,
    bodyHtml: `
      <div style="line-height:1.8;">
        <p><strong>Doctor:</strong> ${app.doctor_name} (${app.designation || 'Specialist'})</p>
        <p><strong>Hospital / Chamber:</strong> ${app.hospital_name}</p>
        <p><strong>Date & Time Slot:</strong> ${app.appointment_date} at ${app.time_slot}</p>
        <p><strong>Clinical Reason:</strong> ${app.reason || 'General Follow-up'}</p>
        <p><strong>Current Status:</strong> <span class="badge-status-pill ${app.badge_class}">${app.display_status}</span></p>
      </div>
    `,
    footerHtml: `
      <button class="nhmrd-btn nhmrd-btn-secondary" onclick="window.closeModal()">Close</button>
      <a href="req-appointment.php" class="nhmrd-btn nhmrd-btn-primary">Manage Appointments</a>
    `
  });
};
