document.addEventListener('DOMContentLoaded', () => {
    // Check if there is a patient_id in URL, else default to 1
    const urlParams = new URLSearchParams(window.location.search);
    const patient_id = urlParams.get('patient_id') || 1;
    fetchPatientProfile(patient_id);
});

function fetchPatientProfile(patient_id) {
    fetch(`/public_html/api/doctor-panel/patient-profile.php?patient_id=${patient_id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updatePatientProfileUI(data);
            } else {
                console.error('Error fetching patient profile:', data.error);
            }
        })
        .catch(error => console.error('Fetch error:', error));
}

function updatePatientProfileUI(data) {
    const pt = data.patient;
    const dob = new Date(pt.dob);
    const ageDifMs = Date.now() - dob.getTime();
    const ageDate = new Date(ageDifMs);
    const age = Math.abs(ageDate.getUTCFullYear() - 1970);
    
    // Update basic info
    const nameElems = document.querySelectorAll('.patient-name-row h2');
    nameElems.forEach(el => el.textContent = pt.full_name);
    
    const mrnElems = document.querySelectorAll('.mrn-code');
    mrnElems.forEach(el => el.textContent = 'MRN-' + pt.patient_id);

    const metaGrid = document.querySelector('.patient-meta-grid');
    if (metaGrid) {
        metaGrid.innerHTML = `
            <span><i class="fa-regular fa-calendar"></i> DOB: ${pt.dob} (${age} y/o)</span>
            <span><i class="fa-solid fa-venus"></i> ${pt.gender}</span>
            <span><i class="fa-solid fa-phone"></i> ${pt.phone}</span>
        `;
    }

    const attendingTag = document.querySelector('.attending-tag strong');
    if (attendingTag) attendingTag.textContent = pt.attending_doctor || 'Unassigned';

    const allergyAlert = document.querySelector('.allergy-alert');
    if (allergyAlert) {
        if (data.allergies.length > 0) {
            allergyAlert.innerHTML = `<i class="fa-solid fa-triangle-exclamation"></i> Allergy Alert: ${data.allergies[0].allergy_name}`;
            allergyAlert.style.display = 'inline-block';
        } else {
            allergyAlert.style.display = 'none';
        }
    }

    // Medications list
    const medContainer = document.querySelector('.med-cards-list');
    if (medContainer) {
        medContainer.innerHTML = '';
        
        const countBadge = document.querySelector('.counter-badge');
        if (countBadge) countBadge.textContent = `${data.medications.length} TOTAL LIFETIME COURSES`;

        if (data.medications.length === 0) {
            medContainer.innerHTML = '<p>No medications found.</p>';
            return;
        }

        data.medications.forEach(med => {
            const medDate = new Date(med.created_at).toLocaleDateString('en-US', { month: 'short', year: 'numeric' });
            const borderClass = med.status === 'active' ? 'green-border' : 'gray-border';
            const iconClass = med.status === 'active' ? 'green-bg' : 'gray-bg';
            const textClass = med.status === 'active' ? '' : 'text-muted';

            const cardHTML = `
            <div class="med-item ${borderClass}">
              <div class="med-icon ${iconClass}"><i class="fa-solid fa-pills"></i></div>
              <div class="med-info">
                <div class="med-title ${textClass}">${med.medication_name} ${med.dose_strength || ''}</div>
                <div class="med-details ${textClass}">Indication: ${med.title} &bull; ${med.sig_instructions || med.route_frequency}</div>
                <div class="med-meta">Prescribed: ${medDate} &bull; Prescriber: ${med.prescriber} &bull; Status: ${med.status.toUpperCase()}</div>
              </div>
            </div>
            `;
            medContainer.insertAdjacentHTML('beforeend', cardHTML);
        });
    }
}
