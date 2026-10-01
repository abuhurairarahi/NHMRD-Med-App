/**
 * NHMRD Patient Panel - Vaccination Records Controller
 * Connects vaccine-panel.html with MySQL via /api/patient_vaccines.php
 */

if (typeof window.showToast !== 'function') {
  const script = document.createElement('script');
  script.src = '../../assets/js/paitent/common.js';
  document.head.appendChild(script);
}

let vaccineData = null;

document.addEventListener('DOMContentLoaded', () => {
  loadVaccineRecords();
});

function loadVaccineRecords() {
  fetch('../../api/patient_vaccines.php?action=records')
    .then(res => res.json())
    .then(data => {
      if (!data.success) {
        console.warn('Vaccine API warning:', data.message);
        return;
      }
      vaccineData = data;
      renderVaccines(data);
    })
    .catch(err => {
      console.error('Error fetching vaccine data:', err);
    });
}

function renderVaccines(data) {
  // Sync Header & Profile Card
  fetch('../../api/patient_dashboard.php')
    .then(r => r.json())
    .then(d => {
      if (d.patient) {
        if (typeof window.syncPatientHeader === 'function') {
          window.syncPatientHeader(d.patient);
        }
        const subtitle = document.querySelector('.page-title-row .subtitle');
        if (subtitle) {
          subtitle.textContent = `Official verified inoculation schedule and digital credentials for ${d.patient.full_name}`;
        }
        const nameEl = document.querySelector('.user-name-row h2');
        if (nameEl) nameEl.textContent = d.patient.full_name;
        const meta = document.querySelector('.user-profile-card .user-meta');
        if (meta) {
          meta.innerHTML = `
            <span>National ID: <strong>${d.patient.nid || 'N/A'}</strong></span> &bull; 
            <span>DOB: <strong>${d.patient.dob || 'N/A'}</strong></span> &bull; 
            <span>Citizen Status: <strong>${d.patient.citizen_status || 'Enrolled NHPS'}</strong></span> &bull; 
            <span class="blood-group"><i class="fa-solid fa-droplet"></i> Blood Group: ${d.patient.blood_group || 'AB+'}</span>
          `;
        }
      }
    });

  // Stats Grid
  if (data.stats) {
    const stats = document.querySelectorAll('.stats-grid .stat-value');
    if (stats.length >= 3) {
      stats[0].innerHTML = `${data.stats.completed_vaccines} <span class="stat-unit">Primary series</span>`;
      stats[1].innerHTML = `${data.stats.boosters_due} <span class="stat-unit">Upcoming</span>`;
    }
  }
}

// Request New Vaccine
window.handleRequestVaccine = function () {
  window.location.href = 'req-vaccine.html';
};

// Search Filter
window.handleSearch = function (e) {
  const query = (e.target.value || '').toLowerCase();
  const cards = document.querySelectorAll('.vaccine-card');
  cards.forEach(card => {
    const text = card.textContent.toLowerCase();
    card.style.display = text.includes(query) ? 'block' : 'none';
  });
};

// Add External Certificate Modal
window.handleAddExternalCertificate = function () {
  window.openModal({
    title: 'Register External Inoculation Certificate',
    bodyHtml: `
      <form id="externalCertForm" onsubmit="handleSaveExternalCert(event)">
        <div class="nhmrd-form-group">
          <label>Vaccine / Immunization Name</label>
          <input type="text" class="nhmrd-form-control" name="vaccine_name" placeholder="e.g. Yellow Fever, Rabies, Meningococcal" required>
        </div>
        <div class="nhmrd-form-row">
          <div class="nhmrd-form-group">
            <label>Dose Number</label>
            <select class="nhmrd-form-control" name="dose_number">
              <option value="1">Dose 01</option>
              <option value="2">Dose 02</option>
              <option value="3">Dose 03</option>
              <option value="4">Booster</option>
            </select>
          </div>
          <div class="nhmrd-form-group">
            <label>Administered Date</label>
            <input type="date" class="nhmrd-form-control" name="administered_date" required>
          </div>
        </div>
        <div class="nhmrd-form-group">
          <label>Certificate or Batch Number</label>
          <input type="text" class="nhmrd-form-control" name="certificate_no" placeholder="e.g. VAC-WHO-991204" required>
        </div>
        <div class="nhmrd-form-group">
          <label>Administering Hospital or Country Center</label>
          <input type="text" class="nhmrd-form-control" name="facility_name" placeholder="e.g. Singapore General Hospital" required>
        </div>
      </form>
    `,
    footerHtml: `
      <button class="nhmrd-btn nhmrd-btn-secondary" onclick="window.closeModal()">Cancel</button>
      <button class="nhmrd-btn nhmrd-btn-primary" onclick="document.getElementById('externalCertForm').requestSubmit()">Save to Registry</button>
    `
  });
};

window.handleSaveExternalCert = function (e) {
  e.preventDefault();
  const formData = new FormData(e.target);
  const payload = {
    action: 'add_external',
    vaccine_name: formData.get('vaccine_name'),
    dose_number: formData.get('dose_number'),
    administered_date: formData.get('administered_date'),
    certificate_no: formData.get('certificate_no'),
    facility_name: formData.get('facility_name')
  };

  fetch('../../api/patient_vaccines.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload)
  })
    .then(r => r.json())
    .then(res => {
      if (res.success) {
        window.closeModal();
        window.showToast('Certificate Validated', res.message || 'Immunization record added to database.', 'success');
        loadVaccineRecords();
      } else {
        window.showToast('Error', res.message || 'Failed to save', 'error');
      }
    })
    .catch(() => {
      window.showToast('Error', 'Server connection error', 'error');
    });
};

// Digital Vaccine Passport Modal
window.handleDigitalPassport = function () {
  fetch('../../api/patient_dashboard.php')
    .then(r => r.json())
    .then(d => {
      const p = d.patient || {};
      window.openModal({
        title: 'DGHS Digital Immunization Passport',
        bodyHtml: `
          <div class="printable-area" style="text-align:center; padding:10px;">
            <div style="background:linear-gradient(135deg, #064e3b 0%, #059669 100%); color:white; border-radius:14px; padding:20px; box-shadow:0 10px 25px rgba(5,150,105,0.25);">
              <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid rgba(255,255,255,0.2); padding-bottom:10px; margin-bottom:14px;">
                <span style="font-weight:800; font-size:16px;"><i class="fa-solid fa-shield-virus"></i> NHMRD IMMUNIZATION PASS</span>
                <span style="font-size:11px; background:rgba(255,255,255,0.2); padding:3px 8px; border-radius:12px;">GLOBAL ICAO ALIGNED</span>
              </div>

              <div style="font-size:72px; color:white; margin:10px 0;">
                <i class="fa-solid fa-qrcode"></i>
              </div>

              <h3 style="color:white; margin-bottom:4px; font-weight:700;">${p.full_name || 'Farhana Islam'}</h3>
              <p style="font-size:12px; opacity:0.9; margin-bottom:12px;">
                NID: ${p.nid || '19922691234560005'} &bull; Citizen ID: #${p.uid || '48291'}
              </p>

              <div style="background:rgba(255,255,255,0.15); border-radius:8px; padding:10px; font-size:12px; text-align:left; line-height:1.6;">
                <div><i class="fa-solid fa-circle-check"></i> <strong>COVID-19:</strong> Fully Inoculated (Pfizer-BioNTech BNT162b2)</div>
                <div><i class="fa-solid fa-circle-check"></i> <strong>Hepatitis B:</strong> Recombinant Series Complete (Titer Valid)</div>
                <div><i class="fa-solid fa-circle-check"></i> <strong>Tetanus Toxoid:</strong> Valid Protective Window (Until 2034)</div>
              </div>
            </div>
          </div>
        `,
        footerHtml: `
          <button class="nhmrd-btn nhmrd-btn-secondary" onclick="window.closeModal()">Close</button>
          <button class="nhmrd-btn nhmrd-btn-primary" onclick="window.print()"><i class="fa-solid fa-print"></i> Print Official Passport</button>
        `,
        size: 'modal-lg'
      });
    });
};
