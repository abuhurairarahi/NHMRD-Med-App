/**
 * NHMRD Doctor Dashboard UI & API Controller
 */

const API_BASE_URL = '/public_html/api/dashboard-api.php';

document.addEventListener('DOMContentLoaded', () => {
    initSearch();
    initPrescriber();
});

// --- Action Handlers --- //

/**
 * Position 1: Call Triage Button
 */
async function callTriage() {
    const btn = document.getElementById('btn-call-triage');
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Calling...';

    try {
        const response = await fetch(`${API_BASE_URL}?action=call_triage`, { method: 'POST' });
        const data = await response.json();
        
        if (data.success) {
            alert(`[EMERGENCY RESPONSE]: ${data.message}`);
        } else {
            alert('Failed to alert triage: ' + data.message);
        }
    } catch (err) {
        alert('Triage Dispatch Error: Unable to communicate with server.');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-asterisk"></i> Call Triage';
    }
}

/**
 * Position 1: Start New Encounter
 */
function startNewEncounter() {
    const mrn = prompt("Enter Patient MRN or Health Card Number to begin encounter:");
    if (mrn) {
        window.location.href = `/public_html/pages/Doctor-panel/doctor-clinical-service-records.html?mrn=${encodeURIComponent(mrn)}`;
    }
}

/**
 * Position 2: Patient Queue - View Records & Active Chart
 */
function viewPatientRecords(buttonElement) {
    const queueItem = buttonElement.closest('.queue-item');
    const patientName = queueItem.querySelector('.patient-name').textContent;
    alert(`Opening clinical records history for patient: ${patientName}`);
    window.location.href = `/public_html/pages/Doctor-panel/doctor-clinical-service-records.html?patient=${encodeURIComponent(patientName)}`;
}

function openActiveChart(buttonElement) {
    const queueItem = buttonElement.closest('.queue-item');
    const patientName = queueItem.querySelector('.patient-name').textContent;
    alert(`Initializing active charting session for: ${patientName}`);
}

/**
 * Schedule Filter Dropdown Placeholder
 */
function openScheduleFilter() {
    alert("Filter options: Today, Tomorrow, Next 7 Days, Custom Range.");
}

/**
 * Position 3: Telemetry Action - Page Cardiology
 */
async function pageCardiology(buttonElement) {
    const row = buttonElement.closest('tr');
    const mrn = row.querySelector('.p-mrn').textContent;
    
    buttonElement.disabled = true;
    buttonElement.textContent = 'Paging...';

    try {
        const formData = new FormData();
        formData.append('action', 'page_cardiology');
        formData.append('mrn', mrn);

        const response = await fetch(API_BASE_URL, {
            method: 'POST',
            body: formData
        });
        const data = await response.json();

        if (data.success) {
            alert(`[CARDIOLOGY ALERT]: ${data.message}`);
            buttonElement.textContent = 'Paged';
            buttonElement.style.backgroundColor = '#6c757d';
        } else {
            alert('Error sending page: ' + data.message);
            buttonElement.disabled = false;
            buttonElement.textContent = 'Page Cardiology';
        }
    } catch (e) {
        alert('Server connection error while trying to page cardiology.');
        buttonElement.disabled = false;
        buttonElement.textContent = 'Page Cardiology';
    }
}

/**
 * Position 4: Quick Prescriber Card Handlers
 */
function initPrescriber() {
    const medInput = document.getElementById('prescriber-med-input');
    
    if (medInput) {
        medInput.addEventListener('change', async (e) => {
            const medName = e.target.value;
            if (!medName) return;

            try {
                const response = await fetch(`${API_BASE_URL}?action=check_medication_renal&medication=${encodeURIComponent(medName)}`);
                const result = await response.json();

                if (result.success) {
                    document.getElementById('egfr-status').textContent = result.data.egfr_status;
                    document.getElementById('max-daily-threshold').textContent = result.data.max_daily_threshold;
                }
            } catch (err) {
                console.error("Renal check fetch error:", err);
            }
        });
    }
}

function clearPrescriber() {
    document.getElementById('prescriber-med-input').value = '';
    document.getElementById('egfr-status').textContent = 'eGFR --';
    document.getElementById('max-daily-threshold').textContent = '-- mg/day';
}

async function queuePrescription() {
    const med = document.getElementById('prescriber-med-input').value;
    const dosage = document.getElementById('dosage-form-select').textContent;
    const frequency = document.getElementById('frequency-select').textContent;

    if (!med) {
        alert('Please enter a medication name.');
        return;
    }

    const formData = new FormData();
    formData.append('action', 'queue_prescription');
    formData.append('medication', med);
    formData.append('dosage', dosage);
    formData.append('frequency', frequency);

    try {
        const response = await fetch(API_BASE_URL, {
            method: 'POST',
            body: formData
        });
        const data = await response.json();

        if (data.success) {
            alert(data.message);
            clearPrescriber();
        } else {
            alert('Prescription Error: ' + data.message);
        }
    } catch (e) {
        alert('Server communication error.');
    }
}

/**
 * Top Header Patient Live Search
 */
function initSearch() {
    const searchInput = document.querySelector('.search-bar input');
    if (!searchInput) return;

    let debounceTimer;
    searchInput.addEventListener('input', (e) => {
        clearTimeout(debounceTimer);
        const query = e.target.value.trim();

        if (query.length < 2) return;

        debounceTimer = setTimeout(async () => {
            try {
                const response = await fetch(`${API_BASE_URL}?action=search_patient&query=${encodeURIComponent(query)}`);
                const data = await response.json();
                console.log("Search Results:", data.results);
            } catch (err) {
                console.error("Search error:", err);
            }
        }, 300);
    });
}

/**
 * Global Logout Handler
 */
function handleLogout() {
    if (confirm("Are you sure you want to log out of the NHMRD System?")) {
        window.location.href = "/public_html/pages/login.html";
    }
}