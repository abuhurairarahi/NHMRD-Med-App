document.addEventListener('DOMContentLoaded', () => {
    fetchAppointmentsData();
});

function fetchAppointmentsData() {
    fetch('/public_html/api/doctor-panel/appointments.php?doctor_id=1')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateAppointmentsUI(data.appointments);
            } else {
                console.error('Error fetching appointments:', data.error);
            }
        })
        .catch(error => console.error('Fetch error:', error));
}

function updateAppointmentsUI(appointments) {
    const listContainer = document.querySelector('.requests-list');
    if (listContainer && appointments.length > 0) {
        listContainer.innerHTML = ''; // Clear static content

        appointments.forEach(appt => {
            const dob = new Date(appt.dob);
            const ageDifMs = Date.now() - dob.getTime();
            const ageDate = new Date(ageDifMs);
            const age = Math.abs(ageDate.getUTCFullYear() - 1970);
            const genderChar = appt.gender ? appt.gender.charAt(0).toUpperCase() : 'U';

            const timeParts = appt.appointment_time.split(':');
            const hours = parseInt(timeParts[0]);
            const minutes = timeParts[1];
            const ampm = hours >= 12 ? 'PM' : 'AM';
            const displayHours = hours % 12 || 12;

            const statusClass = appt.status === 'confirmed' ? 'green-dot' : 'amber-dot';

            const itemHTML = `
            <div class="request-card">
              <div class="card-main-info">
                <div class="patient-header">
                  <div class="patient-title-group">
                    <input type="checkbox" class="card-checkbox">
                    <img src="${appt.photo_url || 'https://i.pravatar.cc/100?img=' + Math.floor(Math.random() * 70)}" alt="${appt.full_name}" class="patient-thumb">
                    <div>
                      <h2 class="patient-name">${appt.full_name}, ${age}${genderChar}</h2>
                      <div class="patient-meta-row">
                        <span class="mrn-badge">MRN-${appt.appointment_id}</span>
                        <span>DOB: ${appt.dob}</span>
                      </div>
                      <div class="patient-condition">Chief Complaint: ${appt.chief_complaint || 'N/A'}</div>
                    </div>
                  </div>
  
                  <div class="card-status-info">
                    <span class="status-dot ${statusClass}">&bull; ${appt.status.toUpperCase()}</span>
                    <span class="tag-status">${appt.visit_type}</span>
                  </div>
                </div>
  
                <!-- Inner Card Details -->
                <div class="card-inner-box flex-between">
                  <div class="inner-left-content">
                    <div class="card-category-tag">OFFICE VISIT</div>
                    
                    <div class="detail-row">
                      <div>
                        <span class="detail-label">Scheduled Time</span>
                        <strong class="detail-value">${appt.appointment_date} &bull; ${displayHours}:${minutes} ${ampm}</strong>
                      </div>
                    </div>
                  </div>
  
                  <div class="action-buttons-group">
                    <button class="btn-action btn-green"><i class="fa-solid fa-check"></i> Accept</button>
                    <button class="btn-action btn-outline"><i class="fa-regular fa-clock"></i> Reschedule</button>
                  </div>
                </div>
              </div>
            </div>
            `;
            listContainer.insertAdjacentHTML('beforeend', itemHTML);
        });
    }
}
