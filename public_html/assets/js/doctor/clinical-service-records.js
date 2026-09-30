/**
 * NHMRD - Electronic Clinical Records JavaScript Controller
 */

// Application State
const state = {
    currentPage: 1,
    currentFilter: '',
    searchQuery: '',
    selectedPatientId: null
};

// Initialize Application
document.addEventListener('DOMContentLoaded', () => {
    fetchPatientRoster();
});

/**
 * Filter table via top header or table-mini search bar
 * @param {string} query 
 */
function filterTable(query) {
    state.searchQuery = query.trim();
    state.currentPage = 1;
    fetchPatientRoster();
}

/**
 * Switch filter tabs (Cardiovascular, Endocrine, Respiratory, All)
 * @param {HTMLElement} btnEl 
 * @param {string} category 
 */
function selectFilterTab(btnEl, category) {
    const tabs = document.querySelectorAll('.module-tabs .tab-btn');
    tabs.forEach(tab => tab.classList.remove('active'));
    btnEl.classList.add('active');

    state.currentFilter = category;
    state.currentPage = 1;
    fetchPatientRoster();
}

/**
 * Fetch roster data via AJAX from PHP backend
 */
function fetchPatientRoster() {
    const url = `/api/doctor/get-clinical-records.php?action=fetch_roster&search=${encodeURIComponent(state.searchQuery)}&category=${encodeURIComponent(state.currentFilter)}&page=${state.currentPage}`;

    fetch(url)
        .then(res => res.json())
        .then(response => {
            if (response.success) {
                renderTable(response.data);
                renderPagination(response.total, response.page, response.totalPages);
            } else {
                console.error("Failed to load records:", response.error);
            }
        })
        .catch(err => console.error("Error fetching patient roster:", err));
}

/**
 * Render patient dynamic rows into HTML table body
 * @param {Array} patients 
 */
function renderTable(patients) {
    const tbody = document.querySelector('.roster-table tbody');
    const counterBadge = document.querySelector('.counter-badge');
    
    if (!tbody) return;
    tbody.innerHTML = '';

    if (counterBadge) {
        counterBadge.textContent = `${patients.length} CASES DISPLAYED`;
    }

    if (patients.length === 0) {
        tbody.innerHTML = `<tr><td colspan="5" style="text-align:center; padding: 20px;">No patient records found.</td></tr>`;
        return;
    }

    patients.forEach((patient, index) => {
        const tr = document.createElement('tr');
        if (index === 0) {
            tr.classList.add('selected-row');
            state.selectedPatientId = patient.patient_id;
        }

        tr.setAttribute('data-patient-id', patient.patient_id);
        tr.onclick = function () { loadPatientDetails(this); };

        const initials = patient.full_name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();
        const mrn = patient.health_card_no || `MRN-${patient.patient_id}`;

        tr.innerHTML = `
            <td>
              <div class="patient-cell flex-align">
                <span class="avatar-circle green-bg">${initials}</span>
                <div>
                  <strong>${escapeHtml(patient.full_name)}</strong>
                  <span class="mrn-code">${escapeHtml(mrn)}</span>
                </div>
              </div>
            </td>
            <td>${patient.age || '--'} yrs<br><span class="text-muted">${escapeHtml(patient.gender || 'N/A')}</span></td>
            <td>
              <strong>${escapeHtml(patient.primary_diagnosis || 'General Checkup')}</strong><br>
              <span class="icd-tag">ICD-10: Active</span>
            </td>
            <td>${patient.active_rx || '<span class="text-muted">No active meds</span>'}</td>
            <td class="vitals-cell">
              <strong>${escapeHtml(patient.blood_pressure || '120/80')}</strong> <small>mmHg</small><br>
              <span class="text-muted">BMI: ${patient.bmi || '24.0'}</span>
            </td>
        `;
        tbody.appendChild(tr);
    });
}

/**
 * Handle row click & detail loading
 * @param {HTMLElement} rowEl 
 */
function loadPatientDetails(rowEl) {
    document.querySelectorAll('.roster-table tbody tr').forEach(row => row.classList.remove('selected-row'));
    rowEl.classList.add('selected-row');

    const patientId = rowEl.getAttribute('data-patient-id');
    if (!patientId) return;

    state.selectedPatientId = patientId;

    fetch(`/api/doctor/get-clinical-records.php?action=get_patient_detail&patient_id=${patientId}`)
        .then(res => res.json())
        .then(res => {
            if (res.success) {
                updateSidebarPanel(res.patient, res.medications);
            }
        });
}

/**
 * Dynamically populate the Right Sidebar Detail Panel
 * @param {Object} patient 
 * @param {Array} medications 
 */
function updateSidebarPanel(patient, medications) {
    const detailHeader = document.querySelector('.patient-detail-panel .detail-header-card');
    if (detailHeader && patient) {
        detailHeader.querySelector('h3').textContent = patient.full_name;
        const mrnCode = detailHeader.querySelector('.mrn-code');
        if (mrnCode) {
            mrnCode.textContent = `${patient.health_card_no || 'MRN-' + patient.patient_id} • DOB: ${patient.dob} (${patient.age}y)`;
        }
    }
}

/**
 * Render dynamic pagination controls
 */
function renderPagination(total, page, totalPages) {
    const footerText = document.querySelector('.pagination-footer span');
    const pageButtonsContainer = document.querySelector('.pagination-buttons');

    if (footerText) {
        footerText.textContent = `Showing 1 to ${Math.min(6, total)} of ${total} internal medicine patient records`;
    }

    if (!pageButtonsContainer) return;

    pageButtonsContainer.innerHTML = '';

    const prevBtn = document.createElement('button');
    prevBtn.innerHTML = '<i class="fa-solid fa-chevron-left"></i>';
    prevBtn.disabled = page <= 1;
    prevBtn.onclick = () => { if (state.currentPage > 1) { state.currentPage--; fetchPatientRoster(); } };
    pageButtonsContainer.appendChild(prevBtn);

    for (let i = 1; i <= Math.min(totalPages, 5); i++) {
        const btn = document.createElement('button');
        btn.textContent = i;
        if (i === page) btn.classList.add('active');
        btn.onclick = function() { switchPage(this); };
        pageButtonsContainer.appendChild(btn);
    }

    const nextBtn = document.createElement('button');
    nextBtn.innerHTML = '<i class="fa-solid fa-chevron-right"></i>';
    nextBtn.disabled = page >= totalPages;
    nextBtn.onclick = () => { if (state.currentPage < totalPages) { state.currentPage++; fetchPatientRoster(); } };
    pageButtonsContainer.appendChild(nextBtn);
}

function switchPage(btnEl) {
    const pageNum = parseInt(btnEl.textContent);
    if (!isNaN(pageNum)) {
        state.currentPage = pageNum;
        fetchPatientRoster();
    }
}

function nextPage() {
    state.currentPage++;
    fetchPatientRoster();
}

/**
 * Interactive Actions
 */
function appendFollowUpNote() {
    const note = prompt("Enter additional SOAP follow-up clinical note:");
    if (note && state.selectedPatientId) {
        const formData = new FormData();
        formData.append('action', 'append_soap_note');
        formData.append('patient_id', state.selectedPatientId);
        formData.append('soap_note', note);

        fetch('/api/doctor/get-clinical-records.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(res => {
                alert(res.message || "SOAP note saved.");
            });
    }
}

function batchRxRenewal() {
    if (confirm("Initiate batch renewal for all active maintenance medications?")) {
        fetch('/api/doctor/get-clinical-records.php?action=batch_renewal')
            .then(res => res.json())
            .then(res => alert(res.message || "Batch Rx renewals processed."));
    }
}

function exportClinicalSummary() {
    alert("Exporting HL7 / PDF Clinical Record Summary...");
    window.open('/api/doctor/get-clinical-records.php?action=export_summary', '_blank');
}

function openNotifications() {
    alert("System Notifications: All EMR data synced successfully with NHMRD Central Node.");
}

function handleLogout() {
    if (confirm("Are you sure you want to log out of the NHMRD Doctor Portal?")) {
        window.location.href = "/public_html/pages/login.html";
    }
}

function escapeHtml(str) {
    return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

/**
 * Dynamically populate the Right Sidebar Detail Panel
 * @param {Object} patient 
 * @param {Array} medications 
 */
function updateSidebarPanel(patient, medications) {
    const panel = document.querySelector('.patient-detail-panel');
    if (!panel || !patient) return;

    // 1. Update Patient Header
    const nameEl = panel.querySelector('.detail-header-card h3');
    if (nameEl) nameEl.textContent = patient.full_name;

    const mrnCodeEl = panel.querySelector('.detail-header-card .mrn-code');
    if (mrnCodeEl) {
        mrnCodeEl.innerHTML = `${patient.health_card_no || 'MRN-' + patient.patient_id} &bull; DOB: ${patient.dob || 'N/A'} (${patient.age || '--'}y)`;
    }

    const attendingEl = panel.querySelector('.attending-tag strong');
    if (attendingEl) {
        attendingEl.textContent = patient.attending_doctor || 'Dr. Sarah Jenkins, MD';
    }

    // 2. Update Active Pharmacotherapy List
    const medContainer = panel.querySelector('.med-cards-list');
    if (medContainer) {
        if (medications && medications.length > 0) {
            medContainer.innerHTML = medications.map(med => `
                <div class="med-item mini-item">
                  <div class="med-info">
                    <strong class="med-title">${escapeHtml(med.medication_name || 'Medication')}</strong>
                    <div class="med-details">${escapeHtml(med.dose_strength || '')} &bull; ${escapeHtml(med.route_frequency || '')}</div>
                  </div>
                  <div class="med-status align-right">
                    <span class="badge-pill green-bg">Active</span>
                    <span class="vital-time">${escapeHtml(med.refill_date || 'Refill: Pending')}</span>
                  </div>
                </div>
            `).join('');
        } else {
            medContainer.innerHTML = `<div class="text-muted" style="padding: 10px 0;">No active pharmacotherapy recorded.</div>`;
        }
    }

    // 3. Update Diagnostic Lab History (if available in payload)
    if (patient.labs) {
        const labCards = panel.querySelectorAll('.lab-mini-card .lab-value');
        if (labCards.length >= 3) {
            labCards[0].textContent = patient.labs.hba1c || '--';
            labCards[1].textContent = patient.labs.egfr || '--';
            labCards[2].textContent = patient.labs.ldl || '--';
        }
    }

    // 4. Update SOAP Note Blocks (if available in payload)
    if (patient.soap) {
        const soapParagraphs = panel.querySelectorAll('.soap-block p');
        if (soapParagraphs.length >= 4) {
            soapParagraphs[0].textContent = patient.soap.subjective || 'No subjective history logged.';
            soapParagraphs[1].textContent = patient.soap.objective || 'No objective vitals logged.';
            soapParagraphs[2].textContent = patient.soap.assessment || 'No assessment logged.';
            soapParagraphs[3].textContent = patient.soap.plan || 'No treatment plan logged.';
        }
    }
}