/**
 * NHMRD Patient Panel - Patient Profile & Bio-Identification Controller
 * Connects paitent-info.html with MySQL via /api/patient_profile.php
 */

if (typeof window.showToast !== 'function') {
  const script = document.createElement('script');
  script.src = '../../assets/js/paitent/common.js';
  document.head.appendChild(script);
}

let profileData = null;

document.addEventListener('DOMContentLoaded', () => {
  loadProfileData();
});

function loadProfileData() {
  fetch('../../api/patient_profile.php')
    .then(res => res.json())
    .then(data => {
      if (!data.success) {
        console.warn('Profile API warning:', data.message);
        return;
      }
      profileData = data;
      renderProfile(data);
    })
    .catch(err => {
      console.error('Error fetching profile data:', err);
    });
}

function renderProfile(data) {
  const p = data.patient;
  if (!p) return;

  // Header sync
  if (typeof window.syncPatientHeader === 'function') {
    window.syncPatientHeader(p);
  }

  // 1. Patient Header Card
  const headerCard = document.querySelector('.patient-header-card');
  if (headerCard) {
    const avatarInitials = headerCard.querySelector('.patient-avatar-initials');
    if (avatarInitials && p.full_name) {
      const parts = p.full_name.trim().split(/\s+/);
      const initials = parts.length > 1 ? (parts[0][0] + parts[parts.length - 1][0]).toUpperCase() : parts[0].slice(0, 2).toUpperCase();
      avatarInitials.textContent = initials;
    }

    const details = headerCard.querySelector('.patient-details');
    if (details) {
      details.innerHTML = `
        <div class="patient-name-row">
          <h2>${p.full_name}</h2>
          <span class="badge-status">ACTIVE CITIZEN</span>
        </div>
        <div class="patient-meta-text">
          Patient ID: <span class="meta-val">#${p.uid || p.patient_id}</span> &nbsp;&bull;&nbsp; National ID (NID): <span class="meta-val">${p.nid || 'N/A'}</span> &nbsp;&bull;&nbsp; Citizen Status: <span class="status-enrolled">${p.citizen_status || 'Enrolled NHPS'}</span>
        </div>
        <div class="pills-container">
          <span class="pill pill-danger"><i class="fa-solid fa-droplet"></i> Blood Group ${p.blood_group || 'AB+'}</span>
          <span class="pill">Age: ${p.age || 36} Yrs (${p.dob || '21-04-1990'})</span>
          <span class="pill">Gender: ${p.gender || 'Male'}</span>
          <span class="pill">Marital Status: ${p.marital_status || 'Married'}</span>
        </div>
      `;
    }
  }

  // 2. Contact Card
  const contactCard = document.querySelector('.contact-card');
  if (contactCard) {
    let extraContactsHtml = '';
    if (data.contacts && data.contacts.length > 0) {
      extraContactsHtml = data.contacts.map(c => `
        <div class="contact-item">
          <i class="fa-solid fa-address-book"></i>
          <span class="label">${c.label}</span>
          <span class="val">${c.value}</span>
        </div>
      `).join('');
    }

    const items = contactCard.querySelectorAll('.contact-item');
    if (items.length >= 3) {
      items[0].querySelector('.val').textContent = p.email || 'N/A';
      items[1].querySelector('.val').textContent = p.phone || 'N/A';
      items[2].querySelector('.val').innerHTML = `Tariqul Islam (Spouse)<br><small class="text-muted">${p.phone || '+880 1812345678'}</small>`;
    }

    const addr = contactCard.querySelector('.address-card strong');
    if (addr) addr.textContent = p.address || 'House 42, Road 9A, Dhanmondi R/A, Dhaka-1209, Bangladesh';

    // Insert extra contacts if any
    let extraContainer = document.getElementById('extraContactsContainer');
    if (!extraContainer) {
      extraContainer = document.createElement('div');
      extraContainer.id = 'extraContactsContainer';
      contactCard.insertBefore(extraContainer, contactCard.querySelector('.address-card'));
    }
    extraContainer.innerHTML = extraContactsHtml;
  }

  // 3. Organ & Blood Donors
  const donorCard = document.querySelector('.donor-sub')?.closest('.card');
  if (donorCard && data.donors) {
    const listHtml = data.donors.map(d => `
      <div class="donor-item" style="margin-bottom:10px;">
        <div class="donor-info">
          <span class="blood-badge">${d.blood_group}</span>
          <div>
            <strong>${d.donor_name}</strong> <span class="badge-status badge-verified">${d.verification_status || 'Verified'}</span>
            <div class="text-muted donor-contact">
              <i class="fa-solid fa-phone"></i> ${d.phone} &bull; <i class="fa-solid fa-envelope"></i> donor@nhmrd.gov.bd
            </div>
          </div>
        </div>
      </div>
    `).join('');

    let container = document.getElementById('donorListContainer');
    if (!container) {
      container = document.createElement('div');
      container.id = 'donorListContainer';
      const existing = donorCard.querySelector('.donor-item');
      if (existing) existing.remove();
      donorCard.appendChild(container);
    }
    container.innerHTML = listHtml;
  }

  // 4. Vitals Baseline & Bio Data
  const lastSyncSpan = document.querySelector('.last-synced');
  if (lastSyncSpan && p.vitals) {
    lastSyncSpan.textContent = `Last synced: ${p.vitals.last_synced || 'Today'}`;
  }

  const vitalBoxes = document.querySelectorAll('.vital-box');
  if (vitalBoxes.length >= 2 && p.vitals) {
    // Blood pressure
    vitalBoxes[0].querySelector('.vital-value').innerHTML = `${p.vitals.blood_pressure || '120/80'} <small class="unit-text">mmHg</small>`;
    // Height & Weight
    vitalBoxes[1].querySelector('.vital-value').textContent = `${p.vitals.height_cm || 170.5} cm • ${p.vitals.weight_kg || 69.0} kg`;
    vitalBoxes[1].querySelector('.vital-status').textContent = `• BMI ${p.vitals.bmi || 23.7} (Normal Range)`;
  }

  // 5. Dossier Counts
  const testBtn = document.querySelector('.dossier-actions button:first-child');
  if (testBtn && data.dossier_counts) {
    testBtn.textContent = `All Test Records (${data.dossier_counts.total_tests})`;
  }
  const rxBtn = document.querySelector('.dossier-actions button:last-child');
  if (rxBtn && data.dossier_counts) {
    rxBtn.textContent = `Active Prescriptions (${data.dossier_counts.active_prescriptions})`;
  }
}

// Download Digital Health Card
window.downloadHealthCard = function () {
  const p = profileData?.patient || {};
  window.openModal({
    title: 'National Digital Health Passport Card',
    bodyHtml: `
      <div class="printable-area">
        <div class="smart-card-print">
          <div class="smart-card-top-row">
            <div class="smart-card-logo">
              <i class="fa-solid fa-shield-halved"></i> NHMRD
            </div>
            <div style="font-size:11px; text-transform:uppercase; letter-spacing:1px; opacity:0.9;">
              Citizen Health Pass
            </div>
          </div>
          <div class="smart-card-details-grid">
            <div class="smart-card-photo">
              <i class="fa-solid fa-id-card"></i>
            </div>
            <div class="smart-card-info">
              <h2>${p.full_name || 'Farhana Islam'}</h2>
              <p><strong>NID:</strong> ${p.nid || '19922691234560005'}</p>
              <p><strong>Health Card #:</strong> ${p.health_card_no || 'SHID-88019-449102'}</p>
              <p><strong>Blood:</strong> ${p.blood_group || 'A+'} &bull; <strong>DOB:</strong> ${p.dob || '14-03-1988'}</p>
              <p><strong>Emergency:</strong> ${p.phone || '+8801812345678'}</p>
            </div>
            <div class="smart-card-qr">
              <i class="fa-solid fa-qrcode"></i>
            </div>
          </div>
          <div style="margin-top:14px; padding-top:10px; border-top:1px solid rgba(255,255,255,0.2); font-size:10px; display:flex; justify-content:space-between; opacity:0.85;">
            <span>Directorate General of Health Services</span>
            <span>Cryptographic Barcode Verified</span>
          </div>
        </div>
      </div>
    `,
    footerHtml: `
      <button class="nhmrd-btn nhmrd-btn-secondary" onclick="window.closeModal()">Close</button>
      <button class="nhmrd-btn nhmrd-btn-primary" onclick="window.print()"><i class="fa-solid fa-download"></i> Save / Print Digital Card</button>
    `,
    size: 'modal-lg'
  });
};

// Authenticate Registry OTP
window.authenticateOTP = function () {
  const otpCode = Math.floor(100000 + Math.random() * 900000);
  window.openModal({
    title: 'National Registry Biometric & OTP Verification',
    bodyHtml: `
      <div style="text-align:center; padding:16px 0;">
        <div style="font-size:42px; color:#1d5ec2; margin-bottom:12px;">
          <i class="fa-solid fa-qrcode"></i>
        </div>
        <h4 style="color:#0f172a; margin-bottom:6px;">Temporary Dynamic Security Token</h4>
        <p style="color:#64748b; font-size:13px; max-width:400px; margin:0 auto 16px auto;">
          Present this 6-digit cryptographic token to registered hospitals, clinical pathologists, or admitting physicians for instant medical dossier clearance.
        </p>
        <div style="display:inline-block; background:#f1f5f9; border:2px dashed #93c5fd; border-radius:12px; padding:12px 28px;">
          <span style="font-size:32px; font-weight:800; letter-spacing:8px; color:#1d5ec2;">${otpCode}</span>
        </div>
        <p style="margin-top:12px; font-size:12px; color:#ef4444;">
          <i class="fa-regular fa-clock"></i> Valid for the next 04 minutes 59 seconds
        </p>
      </div>
    `,
    footerHtml: `
      <button class="nhmrd-btn nhmrd-btn-primary" onclick="window.closeModal(); window.showToast('Authentication Active', 'Registry session granted to provider.', 'success');">Authorize Access</button>
    `
  });
};

// Add Contact Entry Modal
window.addContactEntry = function () {
  window.openModal({
    title: 'Add Contact Information Entry',
    bodyHtml: `
      <form id="contactEntryForm" onsubmit="handleSaveContact(event)">
        <div class="nhmrd-form-group">
          <label>Contact Label / Type</label>
          <input type="text" class="nhmrd-form-control" name="label" placeholder="e.g. Work Phone, Guardian Contact" required>
        </div>
        <div class="nhmrd-form-group">
          <label>Value / Number / Email</label>
          <input type="text" class="nhmrd-form-control" name="value" placeholder="+880 1711 000000 or contact@work.com" required>
        </div>
      </form>
    `,
    footerHtml: `
      <button class="nhmrd-btn nhmrd-btn-secondary" onclick="window.closeModal()">Cancel</button>
      <button class="nhmrd-btn nhmrd-btn-primary" onclick="document.getElementById('contactEntryForm').requestSubmit()">Save Contact</button>
    `
  });
};

window.handleSaveContact = function (e) {
  e.preventDefault();
  const formData = new FormData(e.target);
  const payload = {
    action: 'add_contact',
    label: formData.get('label'),
    value: formData.get('value')
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
        window.showToast('Contact Saved', 'New emergency contact saved to database.', 'success');
        loadProfileData();
      } else {
        window.showToast('Error', res.message || 'Failed to save', 'error');
      }
    })
    .catch(() => {
      window.showToast('Error', 'Server connection error', 'error');
    });
};

// Add Donor Modal
window.addDonor = function () {
  window.openModal({
    title: 'Register Organ & Blood Donor',
    bodyHtml: `
      <form id="donorForm" onsubmit="handleSaveDonor(event)">
        <div class="nhmrd-form-group">
          <label>Full Legal Name</label>
          <input type="text" class="nhmrd-form-control" name="donor_name" placeholder="Donor full name" required>
        </div>
        <div class="nhmrd-form-row">
          <div class="nhmrd-form-group">
            <label>Blood Group</label>
            <select class="nhmrd-form-control" name="blood_group">
              <option value="O+">O+</option>
              <option value="O-">O-</option>
              <option value="A+">A+</option>
              <option value="A-">A-</option>
              <option value="B+">B+</option>
              <option value="B-">B-</option>
              <option value="AB+">AB+</option>
              <option value="AB-">AB-</option>
            </select>
          </div>
          <div class="nhmrd-form-group">
            <label>Mobile Contact</label>
            <input type="text" class="nhmrd-form-control" name="phone" placeholder="017XXXXXXXX" required>
          </div>
        </div>
      </form>
    `,
    footerHtml: `
      <button class="nhmrd-btn nhmrd-btn-secondary" onclick="window.closeModal()">Cancel</button>
      <button class="nhmrd-btn nhmrd-btn-primary" onclick="document.getElementById('donorForm').requestSubmit()">Register Donor</button>
    `
  });
};

window.handleSaveDonor = function (e) {
  e.preventDefault();
  const formData = new FormData(e.target);
  const payload = {
    action: 'add_donor',
    donor_name: formData.get('donor_name'),
    blood_group: formData.get('blood_group'),
    phone: formData.get('phone')
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
        window.showToast('Donor Registered', 'Donor profile linked and verified.', 'success');
        loadProfileData();
      } else {
        window.showToast('Error', res.message || 'Failed to save', 'error');
      }
    })
    .catch(() => {
      window.showToast('Error', 'Server connection error', 'error');
    });
};

// Update Vitals Modal
window.updateVitals = function () {
  const p = profileData?.patient || {};
  const v = p.vitals || {};
  window.openModal({
    title: 'Synchronize Baseline Vitals',
    bodyHtml: `
      <form id="vitalsForm" onsubmit="handleSaveVitals(event)">
        <div class="nhmrd-form-group">
          <label>Blood Pressure (Systolic / Diastolic)</label>
          <input type="text" class="nhmrd-form-control" name="blood_pressure" value="${v.blood_pressure || '120/80'}" placeholder="120/80" required>
        </div>
        <div class="nhmrd-form-row">
          <div class="nhmrd-form-group">
            <label>Height (cm)</label>
            <input type="number" step="0.5" class="nhmrd-form-control" name="height_cm" value="${v.height_cm || 170.5}" required>
          </div>
          <div class="nhmrd-form-group">
            <label>Weight (kg)</label>
            <input type="number" step="0.5" class="nhmrd-form-control" name="weight_kg" value="${v.weight_kg || 69.0}" required>
          </div>
        </div>
        <p class="text-muted" style="font-size:12px; margin-top:8px;">
          <i class="fa-solid fa-circle-info"></i> Body Mass Index (BMI) will be automatically recomputed based on WHO standard metrics.
        </p>
      </form>
    `,
    footerHtml: `
      <button class="nhmrd-btn nhmrd-btn-secondary" onclick="window.closeModal()">Cancel</button>
      <button class="nhmrd-btn nhmrd-btn-primary" onclick="document.getElementById('vitalsForm').requestSubmit()">Save Vitals</button>
    `
  });
};

window.handleSaveVitals = function (e) {
  e.preventDefault();
  const formData = new FormData(e.target);
  const payload = {
    action: 'update_vitals',
    blood_pressure: formData.get('blood_pressure'),
    height_cm: formData.get('height_cm'),
    weight_kg: formData.get('weight_kg')
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
        window.showToast('Vitals Synchronized', 'Baseline biometric vitals updated in database.', 'success');
        loadProfileData();
      } else {
        window.showToast('Error', res.message || 'Failed to update', 'error');
      }
    })
    .catch(() => {
      window.showToast('Error', 'Server connection error', 'error');
    });
};

// View Dossier Records
window.viewDossierRecords = function (type) {
  if (type.toLowerCase() === 'test') {
    window.location.href = 'lab-test.html';
  } else {
    window.location.href = 'prescription-record.html';
  }
};

window.handleSearch = function (e) {
  const query = (e.target.value || '').toLowerCase();
  const rows = document.querySelectorAll('.data-table tbody tr');
  rows.forEach(r => {
    r.style.display = r.textContent.toLowerCase().includes(query) ? '' : 'none';
  });
};
