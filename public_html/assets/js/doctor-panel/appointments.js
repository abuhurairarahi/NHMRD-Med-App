let currentEditingCard = null; // Stores reference to the card being edited
let currentEditingId = null;

document.addEventListener('DOMContentLoaded', () => {
    fetchAppointments();
});

function fetchAppointments() {
    // We assume doctor_id = 1 for demo purposes unless available in session
    fetch('../../api/doctor-panel/appointments.php?doctor_id=1')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderAppointments(data.appointments);
            } else {
                console.error("Failed to load appointments:", data.message);
            }
        })
        .catch(err => console.error("Error loading appointments:", err));
}

function renderAppointments(appointments) {
    const listContainer = document.querySelector('.requests-list');
    listContainer.innerHTML = ''; // clear static list

    if(appointments.length === 0) {
        listContainer.innerHTML = '<p>No pending appointments.</p>';
        return;
    }

    appointments.forEach(app => {
        const dateObj = new Date(app.appointment_date);
        const dobObj = app.dob ? new Date(app.dob) : null;
        let age = '';
        if (dobObj) {
            const diff = Date.now() - dobObj.getTime();
            const ageDate = new Date(diff); 
            age = Math.abs(ageDate.getUTCFullYear() - 1970);
        }

        const formattedDate = dateObj.toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric' });
        
        const cardHTML = `
          <div class="request-card" data-id="${app.appointment_id}">
            <div class="card-main-info">
              <div class="patient-header">
                <div class="patient-title-group">
                  <input type="checkbox" class="card-checkbox">
                  <img src="${app.photo_url || 'https://i.pravatar.cc/100?img=12'}" alt="${app.full_name}" class="patient-thumb">
                  <div>
                    <h2 class="patient-name">${app.full_name}, ${age}${app.gender === 'male' ? 'M' : (app.gender === 'female' ? 'F' : '')}</h2>
                    <div class="patient-meta-row">
                      <span class="mrn-badge">MRN-${app.patient_id || 'N/A'}</span>
                      <span>DOB: ${app.dob || 'N/A'}</span>
                    </div>
                    <div class="patient-condition">${app.reason || 'Routine Checkup'}</div>
                  </div>
                </div>

                <div class="card-status-info">
                  <span class="status-dot ${app.status === 'booked' ? 'red-dot' : 'green-dot'}">&bull; ${app.status}</span>
                  <span class="tag-status">Scheduled</span>
                </div>
              </div>

              <div class="card-inner-box flex-between">
                <div class="inner-left-content">
                  <div class="card-category-tag">OFFICE VISIT</div>
                  <div class="request-desc">${app.reason || 'Follow-up visit'}</div>
                  
                  <div class="detail-row">
                    <div>
                      <span class="detail-label">Preferred date</span>
                      <strong class="detail-value">${formattedDate} &bull; ${app.time_slot}</strong>
                    </div>
                  </div>
                </div>

                <div class="action-buttons-group">
                  <button class="btn-action btn-green" onclick="approveAppointment(this, ${app.appointment_id})"><i class="fa-solid fa-check"></i> Approve Visit</button>
                  <button class="btn-action btn-outline" onclick="openProposeModal(this, ${app.appointment_id})"><i class="fa-regular fa-clock"></i> Propose Alternative time</button>
                  <button class="btn-action btn-red" onclick="requestAccess(this)"><i class="fa-solid fa-lock"></i> Request Profile Access</button>
                </div>
              </div>
            </div>
          </div>
        `;
        listContainer.insertAdjacentHTML('beforeend', cardHTML);
    });
}

window.approveAppointment = function(btn, id) {
    const card = btn.closest('.request-card');
    const outlineBtn = card.querySelector('.btn-outline');
    if (outlineBtn) {
        outlineBtn.disabled = true;
        outlineBtn.style.opacity = '0.5';
        outlineBtn.style.cursor = 'not-allowed';
    }
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Approving...';
    
    fetch('../../api/doctor-panel/appointments.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'approve', appointment_id: id })
    })
    .then(res => res.json())
    .then(data => {
        if(data.success) {
            btn.innerHTML = '<i class="fa-solid fa-check-double"></i> Approved';
            btn.disabled = true;
            const statusDot = card.querySelector('.status-dot');
            if (statusDot) {
                statusDot.className = 'status-dot green-dot';
                statusDot.innerHTML = '&bull; approved';
            }
        } else {
            alert('Error approving appointment: ' + data.error);
            btn.innerHTML = '<i class="fa-solid fa-check"></i> Approve Visit';
        }
    })
    .catch(err => {
        console.error(err);
        btn.innerHTML = '<i class="fa-solid fa-check"></i> Approve Visit';
    });
}

window.openProposeModal = function(btn, id) {
    currentEditingCard = btn.closest('.request-card');
    currentEditingId = id;
    document.getElementById('modal-propose-time').style.display = 'flex';
}

window.requestAccess = function(btn) {
    const card = btn.closest('.request-card');
    const patientName = card.querySelector('.patient-name').innerText.split(',')[0];
    const mrn = card.querySelector('.mrn-badge').innerText;
    const dob = card.querySelector('.patient-meta-row span:nth-child(2)').innerText.replace('DOB: ', '');
    const condition = card.querySelector('.patient-condition').innerText;
    const imgSrc = card.querySelector('.patient-thumb').src;

    const patientData = { name: patientName, mrn: mrn, dob: dob, condition: condition, img: imgSrc };
    localStorage.setItem('accessRequestData', JSON.stringify(patientData));

    window.location.href = 'request-access-panel.php';
}

function closeProposeModal() {
    document.getElementById('modal-propose-time').style.display = 'none';
}
window.closeProposeModal = closeProposeModal;

function saveProposedTime() {
    const day = document.getElementById('prop-day').value.trim();
    const time = document.getElementById('prop-time').value.trim();
    const reason = document.getElementById('prop-reason').value.trim();

    if (!day || !time) return alert("Day and time are required.");

    if (currentEditingCard) {
        const btnGreen = currentEditingCard.querySelector('.btn-green');
        const btnRed = currentEditingCard.querySelector('.btn-red');
        if (btnGreen) { btnGreen.disabled = true; btnGreen.style.opacity = '0.5'; }
        if (btnRed) { btnRed.disabled = true; btnRed.style.opacity = '0.5'; }

        const innerLeft = currentEditingCard.querySelector('.inner-left-content');
        const proposalHTML = `
            <div style="margin-top:15px; padding:10px; background:#f0f9ff; border-left:4px solid #0284c7; border-radius:4px;">
                <strong><i class="fa-solid fa-calendar-plus"></i> Proposed New Time:</strong><br>
                <span>${day} | ${time}</span><br>
                <small><em>Reason: ${reason}</em></small>
            </div>
        `;
        innerLeft.insertAdjacentHTML('beforeend', proposalHTML);
        
        fetch('../../api/doctor-panel/appointments.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ 
                action: 'propose', 
                appointment_id: currentEditingId,
                day: day,
                time: time,
                reason: reason
            })
        })
        .then(res => res.json())
        .then(data => {
            if(!data.success) {
                alert('Error updating appointment: ' + data.error);
            } else {
                const statusDot = currentEditingCard.querySelector('.status-dot');
                if (statusDot) {
                    statusDot.className = 'status-dot blue-dot';
                    statusDot.innerHTML = '&bull; rescheduled';
                }
            }
        })
        .catch(err => console.error(err));
    }
    
    document.getElementById('prop-day').value = '';
    document.getElementById('prop-time').value = '';
    document.getElementById('prop-reason').value = '';
    closeProposeModal();
}
window.saveProposedTime = saveProposedTime;

window.handleLogout = function () {
  if (confirm('Are you sure you want to log out of the NHMRD Provider Portal?')) {
    fetch('../../api/logout.php')
      .then(res => res.json())
      .then(data => {
        window.location.href = data.redirect || '../../../index.php';
      })
      .catch(() => {
        window.location.href = '../../../index.php';
      });
  }
};
