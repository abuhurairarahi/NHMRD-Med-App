document.addEventListener('DOMContentLoaded', () => {
    fetchClinicalRecords();
});

function fetchClinicalRecords() {
    fetch('/public_html/api/doctor-panel/clinical-records.php?doctor_id=1')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateClinicalRecordsUI(data.records);
            } else {
                console.error('Error fetching clinical records:', data.error);
            }
        })
        .catch(error => console.error('Fetch error:', error));
}

function updateClinicalRecordsUI(records) {
    const tbody = document.querySelector('.roster-table tbody');
    if (!tbody) return;

    // Clear existing rows
    tbody.innerHTML = '';

    const counterBadge = document.querySelector('.counter-badge');
    if (counterBadge) counterBadge.textContent = `${records.length} CASES DISPLAYED`;

    if (records.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" style="text-align:center; padding: 20px;">No clinical records found.</td></tr>';
        return;
    }

    records.forEach((record, index) => {
        const dob = new Date(record.dob);
        const ageDifMs = Date.now() - dob.getTime();
        const ageDate = new Date(ageDifMs);
        const age = Math.abs(ageDate.getUTCFullYear() - 1970);
        const genderChar = record.gender ? record.gender.charAt(0).toUpperCase() : 'U';
        
        const initials = record.full_name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();
        const bgColors = ['green-bg', 'blue-bg', 'amber-bg'];
        const bgColor = bgColors[index % bgColors.length];

        const rowHTML = `
        <tr data-patient-id="${record.patient_id || ''}" onclick="loadPatientDetails && loadPatientDetails(this)">
            <td>
                <div class="patient-cell flex-align">
                    <span class="avatar-circle ${bgColor}">${initials}</span>
                    <div>
                        <strong>${record.full_name}</strong>
                        <span class="mrn-code">MRN-${record.appointment_id}</span>
                    </div>
                </div>
            </td>
            <td>${age} yrs<br><span class="text-muted">${record.gender || 'Unknown'}</span></td>
            <td>
                <strong>${record.chief_complaint || 'General Checkup'}</strong><br>
                <span class="icd-tag">Status: ${record.status.toUpperCase()}</span>
            </td>
            <td>${record.visit_type || 'Office Visit'}</td>
            <td class="vitals-cell">
                <strong>--/--</strong> <small>mmHg</small><br>
                <span class="text-muted">Blood Group: ${record.blood_group || 'N/A'}</span>
            </td>
        </tr>
        `;
        tbody.insertAdjacentHTML('beforeend', rowHTML);
    });
}
