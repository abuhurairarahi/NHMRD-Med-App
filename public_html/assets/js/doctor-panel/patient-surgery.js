document.addEventListener('DOMContentLoaded', () => {
    fetchPatientSurgery();
});

function fetchPatientSurgery() {
    fetch('/public_html/api/doctor-panel/patient-surgery.php?patient_id=1')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateSurgeryUI(data.surgeries);
            } else {
                console.error('Error fetching surgeries:', data.error);
            }
        })
        .catch(error => console.error('Fetch error:', error));
}

function updateSurgeryUI(surgeries) {
    const container = document.querySelector('.content-body');
    if (!container) return;

    // Clear existing static surgery cards but keep the filter bar and patient card
    const existingCards = container.querySelectorAll('.surgery-card');
    existingCards.forEach(card => card.remove());

    if (surgeries.length === 0) {
        container.insertAdjacentHTML('beforeend', '<p style="text-align:center; padding: 20px;">No surgical records found.</p>');
        return;
    }

    surgeries.forEach(surgery => {
        const opDate = new Date(surgery.operation_datetime).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
        
        const cardHTML = `
        <div class="surgery-card border-green">
          <div class="surgery-card-header">
            <div class="header-tags">
              <span class="tag tag-green">${surgery.procedure_type.toUpperCase()}</span>
              <span class="cpt-code">${surgery.primary_procedure || 'Procedure'}</span>
              <span class="meta-dot">&bull;</span>
              <span class="record-date">${opDate}</span>
            </div>

            <div class="verified-badge">
              <i class="fa-solid fa-circle-check"></i> Verified OP-Note <strong>#SURG-${surgery.surgery_id}</strong>
              <div class="facility-info">${surgery.hospital_name || 'Unknown Facility'}</div>
            </div>
          </div>

          <h3 class="surgery-title">${surgery.procedure_title}</h3>
          <p class="surgery-indication"><strong>Status:</strong> ${surgery.status.toUpperCase()}</p>

          <div class="details-grid">
            <div class="detail-col">
              <label>SURGICAL TEAM</label>
              <div class="detail-val">${surgery.surgeon_name}</div>
              <div class="detail-sub">Primary Surgeon</div>
            </div>

            <div class="detail-col">
              <label>ANESTHESIA ADMINISTERED</label>
              <div class="detail-val">${surgery.anesthesiologist_name || 'N/A'}</div>
              <div class="detail-sub">Anesthesiologist</div>
            </div>

            <div class="detail-col">
              <label>INTRA-OP BLOOD LOSS</label>
              <div class="detail-val">${surgery.estimated_blood_loss_ml || 0} mL</div>
              <div class="detail-sub">${surgery.intra_op_complication_status || 'None'}</div>
            </div>
          </div>

          <div class="findings-box">
            <p><strong>Medications Used:</strong> ${surgery.medications_used || 'N/A'}</p>
            <p><strong>Notes/Complications:</strong> ${surgery.contraindication_notes || 'Uneventful'}</p>
          </div>

          <div class="surgery-card-footer">
            <div class="technique-tags">
              <span class="tech-tag">Specimen: ${surgery.specimen_pathology || 'N/A'}</span>
            </div>
            ${surgery.report_file_url ? `<a href="${surgery.report_file_url}" class="link-btn"><i class="fa-solid fa-arrow-up-right-from-square"></i> View Full Operative Sheet</a>` : ''}
          </div>
        </div>
        `;
        
        container.insertAdjacentHTML('beforeend', cardHTML);
    });
}
