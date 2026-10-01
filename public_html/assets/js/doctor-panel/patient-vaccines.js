document.addEventListener('DOMContentLoaded', () => {
    fetchPatientVaccines();
});

function fetchPatientVaccines() {
    fetch('/public_html/api/doctor-panel/patient-vaccines.php?patient_id=1')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateVaccineUI(data.vaccines);
            } else {
                console.error('Error fetching vaccines:', data.error);
            }
        })
        .catch(error => console.error('Fetch error:', error));
}

function updateVaccineUI(vaccines) {
    const container = document.querySelector('.section-card');
    if (!container) return;

    // Clear existing static vaccine rows but keep the header
    const existingCards = container.querySelectorAll('.vaccine-row-card');
    existingCards.forEach(card => card.remove());

    const badge = document.querySelector('.active-count-badge');
    if (badge) badge.textContent = `${vaccines.length} Active Records`;

    if (vaccines.length === 0) {
        container.insertAdjacentHTML('beforeend', '<p style="padding: 20px;">No vaccine records found.</p>');
        return;
    }

    vaccines.forEach(vac => {
        const vacDate = new Date(vac.administered_date).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
        
        const cardHTML = `
        <div class="vaccine-row-card">
          <div class="vac-icon-box bg-teal-light text-teal">
            <i class="fa-solid fa-syringe"></i>
          </div>
          <div class="vac-details">
            <div class="vac-title-row">
              <h4 class="vac-name">${vac.vaccine_name}</h4>
              <span class="status-tag tag-emerald">Dose ${vac.dose_number}</span>
            </div>
            <div class="vac-sub">Target: ${vac.disease_target || 'General Immunization'}</div>
            <div class="vac-meta-row">
              <span>Administered: ${vacDate}</span>
              <span>Source: ${vac.source.replace('_', ' ')}</span>
              <span>Provider: ${vac.administered_by || 'Unknown'}</span>
            </div>
          </div>
          <div class="vac-right-meta">
            <span class="ndc-code">Cert: ${vac.certificate_no || 'N/A'}</span>
            <i class="fa-regular fa-circle-info info-icon"></i>
          </div>
        </div>
        `;
        
        container.insertAdjacentHTML('beforeend', cardHTML);
    });
}
