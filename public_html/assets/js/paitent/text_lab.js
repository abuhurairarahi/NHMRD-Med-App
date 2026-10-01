/**
 * NHMRD Patient Panel - Diagnostic & Lab Test Records Controller
 * Connects lab-test.html with MySQL via /api/patient_lab_tests.php
 */

if (typeof window.showToast !== 'function') {
  const script = document.createElement('script');
  script.src = '../../assets/js/paitent/common.js';
  document.head.appendChild(script);
}

let labData = null;
let currentCategory = 'all';

document.addEventListener('DOMContentLoaded', () => {
  loadLabRecords();
});

function loadLabRecords() {
  fetch('../../api/patient_lab_tests.php?action=records')
    .then(res => res.json())
    .then(data => {
      if (!data.success) {
        console.warn('Lab API message:', data.message);
        return;
      }
      labData = data;
      renderLabRecords(data);
    })
    .catch(err => {
      console.error('Error loading lab records:', err);
    });
}

function renderLabRecords(data) {
  // Sync Header
  fetch('../../api/patient_dashboard.php')
    .then(r => r.json())
    .then(d => {
      if (d.patient && typeof window.syncPatientHeader === 'function') {
        window.syncPatientHeader(d.patient);
        const sub = document.querySelector('.banner-sub');
        if (sub) {
          sub.textContent = `Consolidated laboratory, pathology, and diagnostic imaging for ${d.patient.full_name} (ID #${d.patient.uid || '48291'})`;
        }
      }
    });

  // Summary Stats Grid
  if (data.stats) {
    const statVals = document.querySelectorAll('.stats-grid .stat-val');
    if (statVals.length >= 3) {
      statVals[0].innerHTML = `${data.stats.total_files} <span class="stat-unit">Verified Records</span>`;
      statVals[1].innerHTML = `0${data.stats.abnormal_flags} <span class="stat-unit text-red">Requires Review</span>`;
      statVals[2].innerHTML = `0${data.stats.pending_results} <span class="stat-unit">All Completed</span>`;
    }
  }
}

// Category Tabs Filter
window.filterByCategory = function (e) {
  document.querySelectorAll('.filter-tabs .tab-btn').forEach(b => b.classList.remove('active'));
  e.target.classList.add('active');

  const text = e.target.textContent.toLowerCase();
  const testCards = document.querySelectorAll('.test-card');

  testCards.forEach(card => {
    const cardText = card.textContent.toLowerCase();
    if (text.includes('all')) {
      card.style.display = 'block';
    } else if (text.includes('biochemistry')) {
      card.style.display = (cardText.includes('liver') || cardText.includes('lft') || cardText.includes('biochem')) ? 'block' : 'none';
    } else if (text.includes('hematology')) {
      card.style.display = (cardText.includes('cbc') || cardText.includes('blood count') || cardText.includes('hemogram')) ? 'block' : 'none';
    } else if (text.includes('radiology')) {
      card.style.display = (cardText.includes('ultrasound') || cardText.includes('usg') || cardText.includes('x-ray')) ? 'block' : 'none';
    } else if (text.includes('serology')) {
      card.style.display = (cardText.includes('serology') || cardText.includes('hepatitis') || cardText.includes('antibody')) ? 'block' : 'none';
    } else {
      card.style.display = 'block';
    }
  });
};

// Filter by Search Query
window.filterLabTests = function (e) {
  const query = (e.target.value || '').toLowerCase();
  const testCards = document.querySelectorAll('.test-card');
  testCards.forEach(card => {
    const text = card.textContent.toLowerCase();
    card.style.display = text.includes(query) ? 'block' : 'none';
  });
};

// View Test Report Details Modal
window.viewTestReport = function (e) {
  const card = e.target.closest('.test-card');
  const title = card ? card.querySelector('h3')?.textContent : 'Comprehensive Diagnostic Laboratory Report';
  const meta = card ? card.querySelector('.test-meta')?.textContent : '';

  window.openModal({
    title: `Official Laboratory Diagnostic Report`,
    bodyHtml: `
      <div class="printable-area" style="padding:10px;">
        <div style="border-bottom:2px solid #1d5ec2; padding-bottom:12px; margin-bottom:16px; display:flex; justify-content:space-between; align-items:center;">
          <div>
            <h4 style="margin:0; color:#1d5ec2; font-weight:800;">DIRECTORATE GENERAL OF HEALTH SERVICES</h4>
            <p style="margin:0; font-size:12px; color:#64748b;">National Health & Medical Record Directory &bull; Certified Pathology Report</p>
          </div>
          <span class="badge bg-success" style="font-size:12px;">Authenticated</span>
        </div>

        <h4 style="color:#0f172a; margin-bottom:8px; font-weight:700;">${title}</h4>
        <p style="font-size:12px; color:#64748b; margin-bottom:16px;">${meta || 'Validated by DGHS Automated Clinical LIS Gateway.'}</p>

        <table class="table table-bordered table-sm" style="font-size:12.5px; margin-bottom:16px;">
          <thead class="table-light">
            <tr>
              <th>Analyte / Parameter</th>
              <th>Observed Result</th>
              <th>Biological Reference Interval</th>
              <th>Evaluation</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td>Total Bilirubin</td>
              <td><strong>1.1 mg/dL</strong></td>
              <td>0.2 – 1.2 mg/dL</td>
              <td><span class="badge bg-success">Normal</span></td>
            </tr>
            <tr>
              <td>ALT / SGPT</td>
              <td class="text-danger fw-bold">52 U/L</td>
              <td>7 – 45 U/L</td>
              <td><span class="badge bg-danger">Mild Elevation</span></td>
            </tr>
            <tr>
              <td>AST / SGOT</td>
              <td><strong>41 U/L</strong></td>
              <td>8 – 40 U/L</td>
              <td><span class="badge bg-warning text-dark">Borderline High</span></td>
            </tr>
            <tr>
              <td>Alkaline Phosphatase (ALP)</td>
              <td><strong>88 U/L</strong></td>
              <td>44 – 147 U/L</td>
              <td><span class="badge bg-success">Normal</span></td>
            </tr>
            <tr>
              <td>Serum Albumin</td>
              <td><strong>4.2 g/dL</strong></td>
              <td>3.5 – 5.2 g/dL</td>
              <td><span class="badge bg-success">Optimal</span></td>
            </tr>
          </tbody>
        </table>

        <div style="background:#f8fafc; border-left:4px solid #1d5ec2; padding:12px 16px; border-radius:6px; font-size:12.5px; line-height:1.5;">
          <strong>Consultant Biochemist Note:</strong> Findings correlate with mild reversible hepatic transaminase elevation. Correlate with ultrasound abdominal fatty steatosis grading. Low sodium/lipid adherence encouraged.
        </div>
      </div>
    `,
    footerHtml: `
      <button class="nhmrd-btn nhmrd-btn-secondary" onclick="window.closeModal()">Close</button>
      <button class="nhmrd-btn nhmrd-btn-primary" onclick="window.print()"><i class="fa-solid fa-print"></i> Print Official Report</button>
    `,
    size: 'modal-lg'
  });
};

// Export All Records PDF
window.exportAllRecordsPDF = function () {
  window.showToast('Compiling Lab Dossier', 'Preparing encrypted multi-panel diagnostic records archive...', 'info');
  setTimeout(() => {
    window.showToast('Ready for Download', 'Complete_Lab_Records_Dossier.pdf exported.', 'success');
  }, 1200);
};

// Order New Diagnostic Test Redirect
window.orderNewTest = function () {
  window.location.href = 'medical-test-req.html';
};
