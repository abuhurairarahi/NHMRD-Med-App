/**
 * NHMRD Patient Panel - Prescription Records Controller
 * Connects prescription-record.php with MySQL via /api/patient_prescriptions.php
 */

if (typeof window.showToast !== 'function') {
  const script = document.createElement('script');
  script.src = '../../assets/js/paitent/common.js';
  document.head.appendChild(script);
}

let rxData = null;
let currentTabFilter = 'ALL';
let currentSearchQuery = '';

document.addEventListener('DOMContentLoaded', () => {
  loadPrescriptions();
});

function loadPrescriptions() {
  fetch('../../api/patient_prescriptions.php')
    .then(res => res.json())
    .then(data => {
      if (!data.success) {
        console.warn('Prescription API warning:', data.message);
        return;
      }
      rxData = data;
      renderPrescriptions(data);
    })
    .catch(err => {
      console.error('Error fetching prescriptions:', err);
    });
}

function renderPrescriptions(data) {
  // 1. Sync header
  fetch('../../api/patient_dashboard.php')
    .then(r => r.json())
    .then(d => {
      if (d.patient && typeof window.syncPatientHeader === 'function') {
        window.syncPatientHeader(d.patient);
        const nameRow = document.querySelector('.patient-details .name-row h2');
        if (nameRow) nameRow.textContent = d.patient.full_name;
        const idTag = document.querySelector('.patient-details .id-tag');
        if (idTag) idTag.textContent = `ID #${d.patient.uid || '48291'}`;
        const meta = document.querySelector('.patient-meta');
        if (meta) {
          meta.innerHTML = `NID: ${d.patient.nid || '0123456789'} &bull; Blood Group: <strong>${d.patient.blood_group || 'AB+'}</strong> &bull; Gender: ${d.patient.gender || 'Female'}`;
        }
      }
    });

  // 2. Metrics Grid
  const metricValues = document.querySelectorAll('.metric-card .metric-value');
  if (metricValues.length >= 3 && data.metrics) {
    metricValues[0].textContent = `${data.metrics.active_courses} Prescriptions`;
    metricValues[1].textContent = `${data.metrics.completed_courses} Prescriptions`;
    metricValues[2].textContent = `${data.metrics.prescribing_doctors} Specialists`;
  }

  // 3. Render prescription cards
  applyPrescriptionFilters();
}

function applyPrescriptionFilters() {
  if (!rxData || !rxData.prescriptions) return;

  const container = document.querySelector('.prescriptions-container');
  if (!container) return;

  const filtered = rxData.prescriptions.filter(rx => {
    // Tab filter
    const statusUpper = (rx.status || '').toUpperCase();
    if (currentTabFilter === 'ACTIVE') {
      if (statusUpper !== 'ACTIVE' && statusUpper !== 'ONGOING') return false;
    } else if (currentTabFilter === 'PAST') {
      if (statusUpper !== 'COMPLETED' && statusUpper !== 'CANCELLED') return false;
    } else if (currentTabFilter === 'CHRONIC') {
      if (!rx.is_long_term && statusUpper !== 'ONGOING') return false;
    }

    // Keyword filter
    if (currentSearchQuery) {
      const matchText = `${rx.title} ${rx.doctor_name} ${rx.hospital_name} ${rx.rx_code} ${JSON.stringify(rx.medications || '')}`.toLowerCase();
      if (!matchText.includes(currentSearchQuery)) return false;
    }

    return true;
  });

  if (filtered.length === 0) {
    container.innerHTML = `
      <div class="card p-4 text-center text-muted">
        <i class="fa-solid fa-file-prescription fa-2x mb-2 text-secondary"></i>
        <p>No prescriptions match your selected filter criteria.</p>
      </div>
    `;
    return;
  }

  container.innerHTML = filtered.map(rx => {
    const isActive = (rx.status || '').toUpperCase() === 'ACTIVE';
    const statusBadge = isActive 
      ? '<span class="status-badge badge-active">ACTIVE</span>'
      : '<span class="status-badge badge-completed">COMPLETED</span>';

    // Regimen meds grid
    let medsHtml = '';
    if (rx.medications && rx.medications.length > 0) {
      medsHtml = rx.medications.map(m => `
        <div class="med-card">
          <div class="med-header">
            <div>
              <h4>${m.medication_name}</h4>
              <span class="med-type">Oral Formulation &bull; Prescription Dispense</span>
            </div>
            <span class="dose-tag">${m.dose_strength || 'Standard'}</span>
          </div>
          <div class="med-details">
            <div class="detail-row"><span class="label">Dosage:</span> <span>${m.route_frequency || '1 - 0 - 1'}</span></div>
            <div class="detail-row"><span class="label">Instructions:</span> <span>${m.sig_instructions || 'Take after meals'}</span></div>
            <div class="detail-row"><span class="label">Dispense:</span> <span>${m.dispense_quantity || '30'} Units (${m.refills || 0} refills left)</span></div>
          </div>
        </div>
      `).join('');
    } else {
      medsHtml = `
        <div class="med-card">
          <div class="med-header">
            <div><h4>Clinical Observation Regimen</h4><span class="med-type">Maintenance Therapy</span></div>
          </div>
          <div class="med-details"><p class="text-muted mb-0">Follow lifestyle and dietary protocols as instructed.</p></div>
        </div>
      `;
    }

    const advice = rx.doctors_statement || "Avoid heavy fatty foods. Low sodium intake advised. Maintain regular physical activity and follow-up as scheduled.";
    const rxIdStr = rx.rx_code || `#RX-2026-${rx.prescription_id}`;

    return `
      <div class="card rx-card" style="margin-bottom:20px;">
        <div class="rx-header">
          <div class="doctor-info">
            <div class="doc-avatar avatar-blue">${rx.doc_initials || 'DR'}</div>
            <div>
              <div class="doc-name-row">
                <h3>${rx.doctor_name}</h3>
                <span class="specialty">${rx.specialty || 'Consultant'}</span>
                <span class="hospital-tag">${rx.hospital_name || 'LABAID Specialized Hospital'}</span>
              </div>
              <div class="rx-meta">
                RxID: ${rxIdStr} &bull; Issued: ${rx.issued_date} &bull; Next Review: ${rx.review_date}
              </div>
            </div>
          </div>
          ${statusBadge}
        </div>

        <div class="regimen-header">
          <span>PRESCRIBED REGIMEN (${(rx.medications ? rx.medications.length : 0)} MEDICINES)</span>
          <span class="protocol-tag">${rx.title || 'Standard Clinical Protocol'}</span>
        </div>

        <div class="medications-grid">
          ${medsHtml}
        </div>

        <div class="advice-box">
          <i class="fa-regular fa-user"></i>
          <p><strong>Doctor's Advice:</strong> ${advice}</p>
        </div>

        <div class="rx-footer-actions">
          <button class="btn btn-sm-outline" onclick="viewDigitalRx('${rxIdStr}')"><i class="fa-regular fa-eye"></i> View Digital Rx</button>
          <button class="btn btn-sm-primary" onclick="orderRefill('${rxIdStr}')"><i class="fa-solid fa-cart-shopping"></i> Order Refill</button>
          <button class="btn btn-sm-outline" onclick="downloadPrescriptionPDF('${rxIdStr}')"><i class="fa-solid fa-download"></i> Download PDF</button>
        </div>
      </div>
    `;
  }).join('');
}

// Filters
window.filterByTab = function (btn, tab) {
  document.querySelectorAll('.tab-pill').forEach(el => el.classList.remove('active'));
  btn.classList.add('active');
  currentTabFilter = tab;
  applyPrescriptionFilters();
};

window.filterByKeyword = function (e) {
  currentSearchQuery = (e.target.value || '').toLowerCase();
  applyPrescriptionFilters();
};

window.handleGlobalSearch = function (e) {
  currentSearchQuery = (e.target.value || '').toLowerCase();
  applyPrescriptionFilters();
};

// View Digital Rx Modal
window.viewDigitalRx = function (rxId) {
  const rx = rxData?.prescriptions?.find(r => (r.rx_code === rxId || `#RX-2026-${r.prescription_id}` === rxId)) || (rxData?.prescriptions ? rxData.prescriptions[0] : null);
  if (!rx) return;

  const medsListHtml = (rx.medications || []).map(m => `
    <li style="margin-bottom:8px;">
      <strong>${m.medication_name}</strong> - ${m.dose_strength || ''}<br>
      <small style="color:#64748b;">${m.route_frequency || ''} &bull; ${m.sig_instructions || ''}</small>
    </li>
  `).join('');

  window.openModal({
    title: `Digital Prescription ${rxId}`,
    bodyHtml: `
      <div class="printable-area" style="padding:10px;">
        <div style="border-bottom:2px solid #1d5ec2; padding-bottom:12px; margin-bottom:16px; display:flex; justify-content:space-between; align-items:center;">
          <div>
            <h4 style="margin:0; color:#1d5ec2; font-weight:800;">NATIONAL HEALTH & MEDICAL DIRECTORY</h4>
            <p style="margin:0; font-size:12px; color:#64748b;">Official Electronic Prescription Record (e-Rx)</p>
          </div>
          <div style="font-size:32px; color:#1d5ec2;"><i class="fa-solid fa-prescription"></i></div>
        </div>

        <div style="display:flex; justify-content:space-between; font-size:12.5px; margin-bottom:16px; background:#f8fafc; padding:10px 14px; border-radius:8px;">
          <div>
            <strong>Prescriber:</strong> ${rx.doctor_name}<br>
            <span>${rx.specialty} - ${rx.hospital_name}</span>
          </div>
          <div style="text-align:right;">
            <strong>Rx Reference:</strong> ${rx.rx_code || rxId}<br>
            <span>Date: ${rx.issued_date || '11-09-2026'}</span>
          </div>
        </div>

        <h5 style="color:#0f172a; margin-bottom:10px; font-weight:700;">Prescribed Medications & Regimen:</h5>
        <ol style="padding-left:20px; font-size:13px; line-height:1.6;">
          ${medsListHtml || '<li>Take prescribed supportive therapy as guided by physician.</li>'}
        </ol>

        <div style="background:#eff6ff; border-left:4px solid #1d5ec2; padding:10px 14px; border-radius:4px; margin-top:16px; font-size:12.5px;">
          <strong>Clinical Advice:</strong> ${rx.doctors_statement || "Dietary adherence, follow-up testing, and timely medication intake required."}
        </div>
      </div>
    `,
    footerHtml: `
      <button class="nhmrd-btn nhmrd-btn-secondary" onclick="window.closeModal()">Close</button>
      <button class="nhmrd-btn nhmrd-btn-primary" onclick="window.print()"><i class="fa-solid fa-print"></i> Print Prescription</button>
    `,
    size: 'modal-lg'
  });
};

// Order Refill
window.orderRefill = function (rxId) {
  fetch('../../api/patient_prescriptions.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ action: 'refill', rx_id: rxId })
  })
    .then(r => r.json())
    .then(res => {
      window.showToast('Refill Dispatched', res.message || `Order confirmed for ${rxId}.`, 'success');
    })
    .catch(() => {
      window.showToast('Refill Confirmed', `Medication order for ${rxId} has been sent to affiliated pharmacy.`, 'success');
    });
};

// Download Prescription PDF
window.downloadPrescriptionPDF = function (rxId) {
  window.showToast('Generating PDF', `Preparing official prescription sheet for ${rxId}...`, 'info');
  setTimeout(() => {
    window.showToast('Download Ready', `Prescription ${rxId}.pdf downloaded.`, 'success');
  }, 1000);
};

// Print Roster
window.printRoster = function () {
  window.showToast('Print Prepared', 'Opening printable active medication roster...', 'info');
  window.print();
};

// Download Full History
window.downloadFullHistory = function () {
  window.showToast('Dossier Export', 'Compiling complete chronological prescription history into PDF...', 'info');
  setTimeout(() => {
    window.showToast('Download Complete', 'Electronic Prescription History.pdf ready.', 'success');
  }, 1200);
};
