document.addEventListener('DOMContentLoaded', () => {
    fetchPatientPrescriptions();
});

function fetchPatientPrescriptions() {
    fetch('/public_html/api/doctor-panel/patient-prescriptions.php?patient_id=1&doctor_id=1')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updatePatientPrescriptionsUI(data);
            } else {
                console.error('Error fetching prescriptions:', data.error);
            }
        })
        .catch(error => console.error('Fetch error:', error));
}

function updatePatientPrescriptionsUI(data) {
    // 1. Patient Header
    const pt = data.patient;
    const dob = new Date(pt.dob);
    const ageDifMs = Date.now() - dob.getTime();
    const ageDate = new Date(ageDifMs);
    const age = Math.abs(ageDate.getUTCFullYear() - 1970);
    
    document.querySelector('.patient-details h2').textContent = pt.full_name;
    document.querySelector('.patient-avatar-wrap img').src = pt.photo_url || 'https://i.pravatar.cc/120?img=32';
    
    const metaRow = document.querySelector('.patient-meta-row');
    metaRow.innerHTML = `<span>${age} y/o ${pt.gender}</span> &bull; <span>DOB: ${pt.dob}</span> &bull; <span>Blood: <strong>${pt.blood_group || 'Unknown'}</strong></span>`;

    // 2. Allergies
    const allergyBox = document.querySelector('.allergy-warning-box');
    if (data.allergies && data.allergies.length > 0) {
        allergyBox.style.display = 'flex';
        const allergy = data.allergies[0];
        allergyBox.querySelector('.warning-text p').textContent = `${allergy.allergy_name} (${allergy.reaction_severity} - ${allergy.reaction_description})`;
    } else {
        allergyBox.style.display = 'none';
    }

    // 3. Prescriptions List
    const rxContainer = document.querySelector('.rx-records-stack');
    if (rxContainer && data.prescriptions.length > 0) {
        // Clear all except the first card if it's a contraindicated allergy card, or clear all
        // For simplicity, we clear all and render the active ones
        rxContainer.innerHTML = '';

        data.prescriptions.forEach(rx => {
            const rxDate = new Date(rx.created_at).toLocaleDateString('en-US', { month: 'short', year: 'numeric' });
            
            const cardHTML = `
            <div class="rx-card">
              <div class="rx-card-top flex-between">
                <div class="rx-main-info flex-row">
                  <div class="rx-icon green-icon-bg"><i class="fa-solid fa-capsules"></i></div>
                  <div>
                    <div class="rx-title-group">
                      <h3 class="rx-title">${rx.medication_name} ${rx.dose_strength}</h3>
                      <span class="status-tag green-tag">${rx.status.toUpperCase()}</span>
                    </div>
                    <p class="rx-sig">
                      <strong>Sig:</strong> ${rx.sig_instructions || rx.route_frequency}
                    </p>
                  </div>
                </div>
  
                <div class="rx-actions-vertical">
                  <button class="btn-link"><i class="fa-solid fa-sliders"></i> Adjust Dose</button>
                  <button class="btn-link"><i class="fa-solid fa-rotate-right"></i> Request Refill</button>
                </div>
              </div>
  
              <div class="rx-card-bottom grid-4-col">
                <div>
                  <span class="col-label">COURSE</span>
                  <strong class="col-value">${rxDate} - Present</strong>
                </div>
                <div>
                  <span class="col-label">PRESCRIBER</span>
                  <strong class="col-value">${rx.prescriber_name}</strong>
                </div>
                <div>
                  <span class="col-label">DISPENSER</span>
                  <strong class="col-value">NHMRD Pharmacy</strong>
                </div>
                <div>
                  <span class="col-label">REFILLS LEFT</span>
                  <strong class="col-value green-text">${rx.refills} refills remaining</strong>
                </div>
              </div>
            </div>
            `;
            rxContainer.insertAdjacentHTML('beforeend', cardHTML);
        });
    }
}
