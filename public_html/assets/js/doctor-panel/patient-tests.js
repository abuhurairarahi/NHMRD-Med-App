document.addEventListener('DOMContentLoaded', () => {
    fetchPatientTests();
});

function fetchPatientTests() {
    fetch('/public_html/api/doctor-panel/patient-tests.php?patient_id=1&doctor_id=1')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updatePatientTestsUI(data);
            } else {
                console.error('Error fetching tests:', data.error);
            }
        })
        .catch(error => console.error('Fetch error:', error));
}

function updatePatientTestsUI(data) {
    // 1. Patient Header
    const pt = data.patient;
    const dob = new Date(pt.dob);
    const ageDifMs = Date.now() - dob.getTime();
    const ageDate = new Date(ageDifMs);
    const age = Math.abs(ageDate.getUTCFullYear() - 1970);
    
    document.querySelector('.patient-name').textContent = pt.full_name;
    document.querySelector('.blood-badge').textContent = `Blood: ${pt.blood_group || 'Unknown'}`;
    
    const submeta = document.querySelector('.patient-submeta');
    if(submeta) {
        submeta.innerHTML = `
            <span>${age} y/o ${pt.gender} &bull; DOB: ${pt.dob}</span>
            <span class="meta-sep">&bull;</span>
            <span>Attending: ${pt.attending_doctor || 'Unknown'}</span>
        `;
    }

    // 2. Tests List
    const testContainer = document.querySelector('.content-body');
    if (testContainer && data.tests.length > 0) {
        // Clear old static test cards
        const staticCards = testContainer.querySelectorAll('.test-card');
        staticCards.forEach(c => c.remove());

        data.tests.forEach(test => {
            const testDate = new Date(test.ordered_at).toLocaleString('en-US', { month: 'short', day: 'numeric', year: 'numeric', hour: 'numeric', minute: 'numeric', hour12: true });
            
            const cardHTML = `
            <div class="test-card">
              <div class="test-card-header">
                <div class="test-title-area">
                  <div class="test-icon-box blue-icon">
                    <i class="fa-solid fa-flask"></i>
                  </div>
                  <div>
                    <div class="test-title-flex">
                      <h3 class="test-title">${test.test_name}</h3>
                      <span class="status-tag tag-green">${test.status.toUpperCase()}</span>
                    </div>
                    <p class="test-subtitle">Category: ${test.category} &bull; Order ID: #LAB-${test.order_id}</p>
                  </div>
                </div>
    
                <div class="test-timestamp">
                  <span class="time-label">Collected &amp; Finalized</span>
                  <strong class="time-value">${testDate}</strong>
                </div>
              </div>
    
              <!-- Lab Results Grid (Placeholder for actual results) -->
              <div class="results-grid">
                <div class="result-cell">
                  <span class="res-label">Status</span>
                  <div class="res-value">${test.status}</div>
                </div>
              </div>
    
              <!-- Test Footer -->
              <div class="test-card-footer">
                <div class="review-status">
                  <i class="fa-regular fa-circle-check"></i> Ordered on ${testDate}
                </div>
                <div class="footer-actions">
                  <button class="btn-action-text"><i class="fa-solid fa-download"></i> Download PDF</button>
                </div>
              </div>
            </div>
            `;
            testContainer.insertAdjacentHTML('beforeend', cardHTML);
        });
    }
}
