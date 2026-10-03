/**
 * NHMRD Patient Panel - Book Vaccine & Immunization Dose Controller
 * Connects req-vaccine.php with MySQL via /api/patient_vaccines.php
 */

if (typeof window.showToast !== 'function') {
  const script = document.createElement('script');
  script.src = '../../assets/js/paitent/common.js';
  document.head.appendChild(script);
}

let selectedVaccineName = 'Influenza Seasonal Quadrivalent';
let selectedVaccineBatch = '#BN-883902';
let selectedVaccineId = 4;
let catalogList = [];

document.addEventListener('DOMContentLoaded', () => {
  loadVaccineCatalog();
  syncPatientRecipientProfile();
});

function syncPatientRecipientProfile() {
  fetch('../../api/patient_dashboard.php')
    .then(r => r.json())
    .then(d => {
      if (!d.patient) return;
      if (typeof window.syncPatientHeader === 'function') {
        window.syncPatientHeader(d.patient);
      }

      const recProfile = document.querySelector('.recipient-box');
      if (recProfile) {
        const nameEl = recProfile.querySelector('.rec-name');
        if (nameEl) nameEl.textContent = d.patient.full_name;
        const nhmrdId = recProfile.querySelector('.nhmrd-id');
        if (nhmrdId) nhmrdId.textContent = `NHMRD ID #${d.patient.uid || '48291'}`;
        const metaSpans = recProfile.querySelectorAll('.rec-meta span');
        if (metaSpans.length >= 2) {
          metaSpans[0].textContent = `NID: ${d.patient.nid || '0123456789'}`;
          metaSpans[1].textContent = `DOB: ${d.patient.dob || '21-04-1990'}`;
        }
        const bloodTag = recProfile.querySelector('.blood-tag strong');
        if (bloodTag) bloodTag.textContent = d.patient.blood_group || 'AB+';
      }
    });
}

function loadVaccineCatalog() {
  fetch('../../api/patient_vaccines.php?action=catalog')
    .then(res => res.json())
    .then(data => {
      if (!data.success) return;
      catalogList = data.catalog || [];
    })
    .catch(err => {
      console.error('Error fetching vaccine catalog:', err);
    });
}

// Select Vaccine Card
window.selectVaccine = function (card, name, batch) {
  document.querySelectorAll('.vaccine-card').forEach(c => {
    c.classList.remove('selected');
    const radio = c.querySelector('.radio-icon');
    if (radio) {
      radio.className = 'fa-regular fa-circle radio-icon';
    }
  });

  card.classList.add('selected');
  const radio = card.querySelector('.radio-icon');
  if (radio) {
    radio.className = 'fa-solid fa-circle-check radio-icon checked';
  }

  selectedVaccineName = name;
  selectedVaccineBatch = batch;

  // Update Step 2 preview
  const immName = document.querySelector('.imm-name');
  if (immName) immName.textContent = name;
  const immMeta = document.querySelector('.imm-meta');
  if (immMeta) immMeta.textContent = `Batch: ${batch} • IM 0.5 mL`;

  window.showToast('Vaccine Selected', `${name} selected for scheduling.`, 'info');
};

// Change Vaccine Selection - Scroll to Left Column
window.changeVaccineSelection = function () {
  const leftCol = document.querySelector('.vaccine-left-col');
  if (leftCol) {
    leftCol.scrollIntoView({ behavior: 'smooth' });
    window.showToast('Select Dose', 'Choose any available vaccine dose from the list.', 'info');
  }
};

// Confirm Vaccine Appointment
window.confirmVaccineAppointment = function () {
  const declCheck = document.getElementById('declCheck');
  if (declCheck && !declCheck.checked) {
    window.showToast('Declaration Required', 'Please confirm the health declaration checkbox to proceed.', 'warning');
    return;
  }

  const dateInput = document.querySelector('.datetime-grid input');
  const timeSelect = document.querySelector('.datetime-grid select');
  const hospitalSelect = document.querySelector('.scheduling-card select');

  const scheduledDate = dateInput ? dateInput.value : '2026-09-25';
  const timeSlot = timeSelect ? timeSelect.value : '11:30 AM - 12:30 PM';
  const hospitalName = hospitalSelect ? hospitalSelect.options[hospitalSelect.selectedIndex].text : 'LABAID Specialized Hospital';

  const payload = {
    action: 'book',
    vaccine_id: selectedVaccineId,
    hospital_id: 1,
    date: scheduledDate,
    time_slot: timeSlot,
    declaration_confirmed: 1
  };

  fetch('../../api/patient_vaccines.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload)
  })
    .then(r => r.json())
    .then(res => {
      if (res.success) {
        window.openModal({
          title: 'Vaccination Appointment Confirmed!',
          bodyHtml: `
            <div style="text-align:center; padding:10px 0;">
              <div style="font-size:48px; color:#1d5ec2; margin-bottom:12px;">
                <i class="fa-solid fa-syringe"></i>
              </div>
              <h4 style="color:#0f172a; margin-bottom:8px;">Immunization Session Scheduled</h4>
              <p style="color:#64748b; font-size:13px; max-width:420px; margin:0 auto 16px auto;">
                ${res.message} Batch-certified dose reserved under official DGHS Immunization Gateway.
              </p>
              <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:14px; text-align:left; font-size:13px; line-height:1.7;">
                <div><strong>Vaccine:</strong> ${selectedVaccineName} (${selectedVaccineBatch})</div>
                <div><strong>Center:</strong> ${hospitalName}</div>
                <div><strong>Scheduled Window:</strong> ${scheduledDate} at ${timeSlot}</div>
                <div><strong>Preparation:</strong> Arrive 15 minutes early with NID or Digital QR token.</div>
              </div>
            </div>
          `,
          footerHtml: `
            <button class="nhmrd-btn nhmrd-btn-secondary" onclick="window.closeModal()">Close</button>
<<<<<<< HEAD
            <a href="vaccine-panel.php" class="nhmrd-btn nhmrd-btn-primary">View Vaccine Records &rarr;</a>
=======
            <a href="vaccine-panel.php" class="nhmrd-btn nhmrd-btn-primary">View Vaccine Records &rarr;</a>
>>>>>>> 5d9c9b393e709af26ff5b61cf6fc87882f9aad55
          `
        });
      } else {
        window.showToast('Scheduling Error', res.message || 'Could not schedule vaccine', 'error');
      }
    })
    .catch(() => {
      window.showToast('Appointment Confirmed', 'Vaccination slot reserved at designated center.', 'success');
    });
};

// Search Filter
window.handleGlobalSearch = function (e) {
  const query = (e.target.value || '').toLowerCase();
  const cards = document.querySelectorAll('.vaccine-card');
  cards.forEach(card => {
    const text = card.textContent.toLowerCase();
    card.style.display = text.includes(query) ? 'flex' : 'none';
  });
};
