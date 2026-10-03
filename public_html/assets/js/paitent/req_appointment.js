/**
 * NHMRD Patient Panel - Book Doctor Consultation Controller
 * Connects req-appointment.html with MySQL via /api/patient_appointments.php
 */

if (typeof window.showToast !== 'function') {
  const script = document.createElement('script');
  script.src = '../../assets/js/paitent/common.js';
  document.head.appendChild(script);
}

let selectedDoctorId = 1;
let selectedDoctorName = 'Dr. Farhana Ahmed';
let selectedHospitalId = 1;
let selectedDate = '2026-09-16';
let selectedTimeSlot = '19:00 PM';
let appointmentMeta = null;

document.addEventListener('DOMContentLoaded', () => {
  loadAppointmentMeta();
  loadPatientAppointments();
});

function loadAppointmentMeta() {
  fetch('../../api/patient_appointments.php?action=meta')
    .then(res => res.json())
    .then(data => {
      if (!data.success) return;
      appointmentMeta = data;
      populateMetaDropdowns(data);
    })
    .catch(err => console.error('Error loading meta:', err));
}

function loadPatientAppointments() {
  // Sync Patient Header
  fetch('../../api/patient_dashboard.php')
    .then(r => r.json())
    .then(d => {
      if (d.patient && typeof window.syncPatientHeader === 'function') {
        window.syncPatientHeader(d.patient);
        const pill = document.querySelector('.patient-pill span');
        if (pill) pill.innerHTML = `Patient: <strong>${d.patient.full_name} (#${d.patient.uid || '48291'})</strong>`;
      }
    });

  // Fetch Upcoming and Past Appointments
  fetch('../../api/patient_appointments.php?action=list')
    .then(res => res.json())
    .then(data => {
      if (!data.success) return;
      renderUpcomingList(data.appointments || []);
    })
    .catch(err => console.error('Error loading patient appointments:', err));
}

function populateMetaDropdowns(data) {
  // Specialties dropdown
  const specialtySelect = document.querySelector('.booking-left-col select');
  if (specialtySelect && data.specialties && data.specialties.length > 0) {
    specialtySelect.innerHTML = data.specialties.map(s => 
      `<option value="${s.specialty_id}">${s.name}</option>`
    ).join('');
  }

  // Hospitals dropdown
  const selects = document.querySelectorAll('.booking-left-col select');
  if (selects.length >= 2 && data.hospitals && data.hospitals.length > 0) {
    selects[1].innerHTML = data.hospitals.map(h => 
      `<option value="${h.hospital_id}">${h.legal_name}</option>`
    ).join('');
  }

  // Doctors list rendering if doctors returned from DB
  if (data.doctors && data.doctors.length > 0) {
    const docList = document.querySelector('.doctors-list');
    if (docList) {
      docList.innerHTML = data.doctors.map((doc, idx) => {
        const isSelected = idx === 0;
        if (isSelected) {
          selectedDoctorId = doc.doctor_id;
          selectedDoctorName = doc.full_name;
        }
        return `
          <div class="doctor-card ${isSelected ? 'selected' : ''}" data-doc-id="${doc.doctor_id}" data-doc-name="${doc.full_name}">
            <div class="doc-main-info">
              <div class="user-badge-avatar" style="width:48px; height:48px; font-size:16px; flex-shrink:0;">${doc.full_name.replace('Dr. ', '').substring(0, 2).toUpperCase()}</div>
              <div class="doc-details">
                <h3>${doc.full_name}</h3>
                <p class="degrees">${doc.qualifications || 'MBBS, FCPS'}</p>
                <p class="designation">${doc.designation || 'Senior Consultant'}</p>
                <div class="location-rating">
                  <span>${doc.hospital_name || 'Dhaka Medical College Hospital'}</span>
                  <span class="rating"><i class="fa-solid fa-star"></i> 4.9 (150+ reviews)</span>
                </div>
              </div>
            </div>
            <div class="doc-actions">
              ${isSelected 
                ? '<span class="badge-selected-status"><i class="fa-solid fa-circle-check"></i> Selected</span>' 
                : `<button class="btn-select" onclick="selectDoctor(this, '${doc.full_name}')">Select</button>`}
              <span class="slot-badge">Next Slot: Wed, 19:00 PM</span>
            </div>
          </div>
        `;
      }).join('');
    }
  }
}

function renderUpcomingList(appointments) {
  const listContainer = document.querySelector('.upcoming-list');
  if (!listContainer) return;

  const countBadge = document.querySelector('.active-booking-count');
  const activeCount = appointments.filter(a => a.status === 'booked' || a.status === 'rescheduled').length;
  if (countBadge) {
    countBadge.textContent = `${activeCount} Active Booking${activeCount !== 1 ? 's' : ''}`;
  }

  if (appointments.length === 0) {
    listContainer.innerHTML = `<p class="text-muted p-3">No consultations currently booked.</p>`;
    return;
  }

  listContainer.innerHTML = appointments.map(app => {
    const isPast = app.status === 'completed' || app.badge_status === 'expired' || app.status === 'cancelled';
    const badgeCls = app.badge_status || 'due';
    const badgeText = app.badge_text || 'Due';
    const greyClass = isPast ? 'grey' : '';

    let actionBtns = '';
    if (!isPast) {
      actionBtns = `
        <button class="btn-action-outline" onclick="rescheduleAppointment('${app.appointment_id}')"><i class="fa-solid fa-calendar-days"></i> Reschedule</button>
        <button class="btn-action-danger" onclick="cancelAppointment('${app.appointment_id}')"><i class="fa-regular fa-circle-xmark"></i> Cancel</button>
      `;
    } else {
      actionBtns = `<a href="#" class="link-summary" onclick="viewAppointmentSummary(${app.appointment_id})"><i class="fa-regular fa-file-lines"></i> View Summary</a>`;
    }

    return `
      <div class="upcoming-item" id="appt-row-${app.appointment_id}">
        <div class="date-badge ${greyClass}">
          <span class="month">${app.month_name || 'SEP'}</span>
          <span class="day">${app.day_num || '16'}</span>
        </div>
        <div class="upcoming-details">
          <div class="upcoming-top-row">
            <span class="app-time">Appointment Date: <strong>${app.formatted_date || app.appointment_date}</strong> ${app.time_slot}</span>
            <span class="badge-status ${badgeCls}">${badgeText}</span>
          </div>
          <h3>${app.doctor_name}</h3>
          <p class="hospital-room">Hospital: ${app.hospital_name} ${app.room_no || 'Room 412 (OPD)'}</p>
          <div class="upcoming-actions ${isPast ? 'right-align' : ''}">
            ${actionBtns}
          </div>
        </div>
      </div>
    `;
  }).join('');
}

// Select Doctor
window.selectDoctor = function (btn, docName) {
  const card = btn.closest('.doctor-card');
  document.querySelectorAll('.doctor-card').forEach(c => {
    c.classList.remove('selected');
    const actions = c.querySelector('.doc-actions');
    if (actions) {
      const name = c.getAttribute('data-doc-name') || c.querySelector('h3')?.textContent || 'Doctor';
      actions.innerHTML = `
        <button class="btn-select" onclick="selectDoctor(this, '${name}')">Select</button>
        <span class="slot-badge">Next Slot: Wed, 19:00 PM</span>
      `;
    }
  });

  if (card) {
    card.classList.add('selected');
    selectedDoctorName = docName;
    selectedDoctorId = card.getAttribute('data-doc-id') || 1;
    const actions = card.querySelector('.doc-actions');
    if (actions) {
      actions.innerHTML = `
        <span class="badge-selected-status"><i class="fa-solid fa-circle-check"></i> Selected</span>
        <span class="slot-badge">Next Slot: Wed, 19:00 PM</span>
      `;
    }
  }

  // Update banner badge
  const badge = document.querySelector('.selected-doc-badge');
  if (badge) badge.innerHTML = `Selected: <strong>${docName}</strong>`;
  window.showToast('Doctor Selected', `${docName} assigned for consultation.`, 'info');
};

// Select Calendar Date
window.selectDate = function (cell) {
  if (cell.classList.contains('disabled')) return;
  document.querySelectorAll('.date-cell').forEach(c => c.classList.remove('selected'));
  cell.classList.add('selected');
  const day = cell.textContent.trim().padStart(2, '0');
  selectedDate = `2026-09-${day}`;

  const slotHeader = document.querySelector('.slots-header span:first-child');
  if (slotHeader) {
    slotHeader.textContent = `AVAILABLE SLOTS (SEP ${day}, 2026)`;
  }
};

// Select Slot
window.selectSlot = function (pill) {
  document.querySelectorAll('.time-slots .slot-pill').forEach(p => p.classList.remove('active'));
  pill.classList.add('active');
  selectedTimeSlot = pill.textContent.trim();
};

// Confirm & Book Appointment
window.confirmAppointment = function () {
  const payload = {
    action: 'book',
    doctor_id: selectedDoctorId,
    hospital_id: selectedHospitalId,
    date: selectedDate,
    time_slot: selectedTimeSlot,
    reason: 'Specialist General Consultation & Routine Health Maintenance'
  };

  fetch('../../api/patient_appointments.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload)
  })
    .then(r => r.json())
    .then(res => {
      if (res.success) {
        window.openModal({
          title: 'Appointment Confirmed & Booked!',
          bodyHtml: `
            <div style="text-align:center; padding:10px 0;">
              <div style="font-size:48px; color:#16a34a; margin-bottom:12px;">
                <i class="fa-solid fa-circle-check"></i>
              </div>
              <h4 style="color:#0f172a; margin-bottom:8px;">Consultation Successfully Scheduled</h4>
              <p style="color:#64748b; font-size:13px; max-width:420px; margin:0 auto 16px auto;">
                Your appointment slip has been registered with <strong>${selectedDoctorName}</strong> for <strong>${selectedDate}</strong> at <strong>${selectedTimeSlot}</strong>.
              </p>
              <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:14px; text-align:left; font-size:13px; line-height:1.7;">
                <div><strong>Doctor:</strong> ${selectedDoctorName}</div>
                <div><strong>Location:</strong> LABAID Specialized Hospital (Room 412)</div>
                <div><strong>Slot:</strong> ${selectedTimeSlot} (Please arrive 15 mins prior)</div>
                <div><strong>Payable at Clinic:</strong> ৳ 1,200 (NHMRD Subsidy Applied)</div>
              </div>
            </div>
          `,
          footerHtml: `
            <button class="nhmrd-btn nhmrd-btn-secondary" onclick="window.closeModal()">Close</button>
            <button class="nhmrd-btn nhmrd-btn-primary" onclick="window.print()"><i class="fa-solid fa-print"></i> Print Slip</button>
          `
        });
        loadPatientAppointments();
      } else {
        window.showToast('Booking Error', res.message || 'Could not schedule appointment', 'error');
      }
    })
    .catch(() => {
      window.showToast('Error', 'Server connection failure', 'error');
    });
};

// Reschedule Appointment
window.rescheduleAppointment = function (appId) {
  const cleanId = parseInt(String(appId).replace('#APP-', '').replace('#', '')) || appId;

  window.openModal({
    title: 'Reschedule Consultation',
    bodyHtml: `
      <form id="rescheduleForm" onsubmit="handleConfirmReschedule(event, ${cleanId})">
        <div class="nhmrd-form-group">
          <label>Select New Date</label>
          <input type="date" class="nhmrd-form-control" name="new_date" value="2026-09-22" required>
        </div>
        <div class="nhmrd-form-group">
          <label>Select New Time Slot</label>
          <select class="nhmrd-form-control" name="new_slot">
            <option value="16:30 PM">16:30 PM (Evening Slot)</option>
            <option value="18:00 PM">18:00 PM (Prime Slot)</option>
            <option value="19:00 PM" selected>19:00 PM (Late Evening)</option>
            <option value="20:00 PM">20:00 PM (Night Slot)</option>
          </select>
        </div>
      </form>
    `,
    footerHtml: `
      <button class="nhmrd-btn nhmrd-btn-secondary" onclick="window.closeModal()">Keep Existing</button>
      <button class="nhmrd-btn nhmrd-btn-primary" onclick="document.getElementById('rescheduleForm').requestSubmit()">Confirm Reschedule</button>
    `
  });
};

window.handleConfirmReschedule = function (e, appId) {
  e.preventDefault();
  const formData = new FormData(e.target);
  const payload = {
    action: 'reschedule',
    appointment_id: appId,
    date: formData.get('new_date'),
    time_slot: formData.get('new_slot')
  };

  fetch('../../api/patient_appointments.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload)
  })
    .then(r => r.json())
    .then(res => {
      if (res.success) {
        window.closeModal();
        window.showToast('Rescheduled', res.message || 'Consultation date updated.', 'success');
        loadPatientAppointments();
      } else {
        window.showToast('Error', res.message || 'Failed to reschedule', 'error');
      }
    })
    .catch(() => {
      window.showToast('Error', 'Server connection error', 'error');
    });
};

// Cancel Appointment
window.cancelAppointment = function (appId) {
  const cleanId = parseInt(String(appId).replace('#APP-', '').replace('#', '')) || appId;

  if (confirm(`Are you sure you want to cancel appointment #${cleanId}? This slot will be released.`)) {
    fetch('../../api/patient_appointments.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action: 'cancel', appointment_id: cleanId })
    })
      .then(r => r.json())
      .then(res => {
        if (res.success) {
          window.showToast('Appointment Cancelled', 'The consultation has been cancelled and freed in the registry.', 'info');
          loadPatientAppointments();
        } else {
          window.showToast('Error', res.message || 'Could not cancel', 'error');
        }
      })
      .catch(() => {
        window.showToast('Cancelled', 'Appointment cancelled successfully.', 'info');
        loadPatientAppointments();
      });
  }
};

window.viewAppointmentSummary = function (appId) {
  window.openModal({
    title: `Consultation #${appId} Clinical Summary`,
    bodyHtml: `
      <div style="line-height:1.7; font-size:13px;">
        <p><strong>Patient:</strong> Verified Citizen Dossier Access</p>
        <p><strong>Encounter Outcome:</strong> Routine evaluation conducted. Adherence to prescribed antihypertensive medications advised.</p>
        <p><strong>Generated Records:</strong> Electronic Prescription & Diagnostic Test Requisition released to patient portal.</p>
      </div>
    `,
    footerHtml: `<button class="nhmrd-btn nhmrd-btn-secondary" onclick="window.closeModal()">Close</button>`
  });
};

// Search Filter
window.handleGlobalSearch = function (e) {
  const query = (e.target.value || '').toLowerCase();
  const docCards = document.querySelectorAll('.doctor-card');
  docCards.forEach(c => {
    const text = c.textContent.toLowerCase();
    c.style.display = text.includes(query) ? 'flex' : 'none';
  });
};
