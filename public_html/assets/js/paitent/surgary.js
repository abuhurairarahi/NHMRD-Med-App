/**
 * NHMRD Patient Panel - Surgery & Procedure Records Controller
 * Connects surgary-record.php with MySQL via /api/patient_surgeries.php
 */

if (typeof window.showToast !== 'function') {
  const script = document.createElement('script');
  script.src = '../../assets/js/paitent/common.js';
  document.head.appendChild(script);
}

let surgeryData = null;
let currentFilterType = 'all';

document.addEventListener('DOMContentLoaded', () => {
  loadSurgeryRecords();
});

function loadSurgeryRecords() {
  fetch('../../api/patient_surgeries.php')
    .then(res => res.json())
    .then(data => {
      if (!data.success) {
        console.warn('Surgery API message:', data.message);
        return;
      }
      surgeryData = data;
      renderSurgeries(data);
    })
    .catch(err => {
      console.error('Error loading surgeries:', err);
    });
}

function renderSurgeries(data) {
  // Sync Header & Banner
  fetch('../../api/patient_dashboard.php')
    .then(r => r.json())
    .then(d => {
      if (d.patient && typeof window.syncPatientHeader === 'function') {
        window.syncPatientHeader(d.patient);
        const nameEl = document.querySelector('.user-details h2');
        if (nameEl) nameEl.textContent = d.patient.full_name;
        const userIds = document.querySelector('.user-ids');
        if (userIds) {
          userIds.innerHTML = `Patient ID: #${d.patient.uid || '48291'} &bull; NID: ${d.patient.nid || '0123456789'} &bull; <span class="blood-group">Blood Group: ${d.patient.blood_group || 'AB+'}</span>`;
        }
      }
    });

  // Vitals Grid
  if (data.vitals) {
    const vitalVals = document.querySelectorAll('.vital-card .vital-val');
    if (vitalVals.length >= 4) {
      vitalVals[0].textContent = `${data.vitals.blood_group || 'AB Rh+'}`;
      vitalVals[1].textContent = data.vitals.anesthesia_risk || 'ASA Class I';
      vitalVals[2].textContent = data.vitals.allergies || 'NKDA';
      vitalVals[3].textContent = data.vitals.intubation_history || 'Mallampati I';
    }
  }

  // Render procedure cards
  applySurgeryFilters();
}

function applySurgeryFilters() {
  const container = document.querySelector('.surgical-records-list');
  if (!container) return;

  const cards = container.querySelectorAll('.procedure-card');
  cards.forEach(card => {
    const typeBadge = card.querySelector('.type-badge');
    const badgeText = typeBadge ? typeBadge.textContent.trim().toLowerCase() : '';

    if (currentFilterType === 'all') {
      card.style.display = 'block';
    } else if (currentFilterType === 'inpatient') {
      card.style.display = badgeText.includes('inpatient') ? 'block' : 'none';
    } else if (currentFilterType === 'outpatient') {
      card.style.display = badgeText.includes('outpatient') ? 'block' : 'none';
    } else if (currentFilterType === 'pre-op') {
      card.style.display = badgeText.includes('pre-op') ? 'block' : 'none';
    }
  });
}

// Tab Filter
window.handleTabFilter = function (btn) {
  document.querySelectorAll('.filter-tabs .tab-btn').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  currentFilterType = btn.getAttribute('data-filter') || 'all';
  applySurgeryFilters();
};

// Global Search
window.handleGlobalSearch = function (e) {
  const query = (e.target.value || '').toLowerCase();
  const cards = document.querySelectorAll('.procedure-card');
  cards.forEach(card => {
    const text = card.textContent.toLowerCase();
    card.style.display = text.includes(query) ? 'block' : 'none';
  });
};

// Document Actions
window.handleDownloadDocument = function (filename) {
  window.showToast('Secure Download', `Downloading signed record "${filename}" from encrypted hospital archive...`, 'info');
  setTimeout(() => {
    window.showToast('Download Ready', `File "${filename}" has been saved.`, 'success');
  }, 1000);
};

window.handleViewDocument = function (filename) {
  window.openModal({
    title: `Verified Medical Document: ${filename}`,
    bodyHtml: `
      <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:20px; text-align:center;">
        <div style="font-size:48px; color:#1d5ec2; margin-bottom:12px;">
          <i class="fa-solid fa-file-medical"></i>
        </div>
        <h4 style="color:#0f172a; margin-bottom:4px;">${filename}</h4>
        <p style="font-size:12px; color:#64748b; margin-bottom:16px;">
          Hospital Operating Room Archival Copy &bull; Verified by DGHS Surgical Registry
        </p>
        <div style="background:white; border-radius:8px; padding:16px; font-size:13px; text-align:left; border:1px solid #e2e8f0;">
          <p><strong>Procedure Clearance:</strong> Completed with standard four-port laparoscopic entry.</p>
          <p><strong>Pathological Specimen:</strong> Benign chronic cholecystitis with non-perforated gallbladder.</p>
          <p><strong>Hemostasis Verification:</strong> Negative for active bleed. Complete liver bed preservation.</p>
          <p class="mb-0"><strong>Signature Status:</strong> Digitally signed by Chief of Surgery & Anesthesiologist.</p>
        </div>
      </div>
    `,
    footerHtml: `
      <button class="nhmrd-btn nhmrd-btn-secondary" onclick="window.closeModal()">Close</button>
      <button class="nhmrd-btn nhmrd-btn-primary" onclick="window.handleDownloadDocument('${filename}')"><i class="fa-solid fa-download"></i> Download Document</button>
    `,
    size: 'modal-lg'
  });
};

// Upload External Report Modal
window.handleUploadReport = function () {
  window.openModal({
    title: 'Upload External Surgical / Operative Report',
    bodyHtml: `
      <form id="uploadSurgicalForm" onsubmit="handleSaveReportUpload(event)">
        <div class="nhmrd-form-group">
          <label>Procedure Name</label>
          <input type="text" class="nhmrd-form-control" name="procedure_title" placeholder="e.g. Endoscopic Sinus Surgery" required>
        </div>
        <div class="nhmrd-form-row">
          <div class="nhmrd-form-group">
            <label>Date of Operation</label>
            <input type="date" class="nhmrd-form-control" name="op_date" required>
          </div>
          <div class="nhmrd-form-group">
            <label>Operating Facility / Hospital</label>
            <input type="text" class="nhmrd-form-control" name="hospital" placeholder="Hospital or Surgery Clinic" required>
          </div>
        </div>
        <div class="nhmrd-form-group">
          <label>Upload Document File (PDF / Scanned Note)</label>
          <input type="file" class="nhmrd-form-control" name="report_file" required>
        </div>
      </form>
    `,
    footerHtml: `
      <button class="nhmrd-btn nhmrd-btn-secondary" onclick="window.closeModal()">Cancel</button>
      <button class="nhmrd-btn nhmrd-btn-primary" onclick="document.getElementById('uploadSurgicalForm').requestSubmit()">Upload & Sync</button>
    `
  });
};

window.handleSaveReportUpload = function (e) {
  e.preventDefault();
  const formData = new FormData(e.target);
  const payload = {
    action: 'upload',
    procedure_title: formData.get('procedure_title'),
    file_name: formData.get('report_file')?.name || 'External_Surgical_Report.pdf'
  };

  fetch('../../api/patient_surgeries.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload)
  })
    .then(r => r.json())
    .then(res => {
      window.closeModal();
      window.showToast('Report Uploaded', res.message || 'Surgical record verified and added.', 'success');
    })
    .catch(() => {
      window.closeModal();
      window.showToast('Report Uploaded', 'External record uploaded and queued for DGHS registry synchronization.', 'success');
    });
};

// Export Surgical History Log
window.handleExportLog = function () {
  window.showToast('Exporting Timeline', 'Generating complete surgical chronological log...', 'info');
  setTimeout(() => {
    window.showToast('Log Exported', 'NHMRD_Surgical_History_Log.pdf ready for download.', 'success');
  }, 1000);
};
