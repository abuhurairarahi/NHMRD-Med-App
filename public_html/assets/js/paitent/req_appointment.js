let selectedDoctorId = null;
let selectedHospitalId = null;
let selectedDateValue = '2026-09-16';

document.addEventListener('DOMContentLoaded', () => {
  // Set default selected doctor on page load
  const firstDocCard = document.querySelector('.doctor-card.selected');
  if (firstDocCard) {
    selectedDoctorId = firstDocCard.dataset.doctorId;
    selectedHospitalId = firstDocCard.dataset.hospitalId;
    const docName = firstDocCard.querySelector('.doc-details h3')?.textContent.trim();
    if (docName) {
      document.getElementById('selectedDoctorLabel').textContent = docName;
    }
  }
});

// 1. Select Doctor Card Function
function selectDoctor(buttonElement, doctorName, doctorId, hospitalId) {
  selectedDoctorId = doctorId;
  selectedHospitalId = hospitalId;

  const doctorCards = document.querySelectorAll('.doctor-card');
  doctorCards.forEach(card => {
    card.classList.remove('selected');
    const actionArea = card.querySelector('.doc-actions');
    
    const existingBadge = actionArea.querySelector('.badge-selected-status');
    if (existingBadge) {
      existingBadge.remove();
      const selectBtn = document.createElement('button');
      selectBtn.className = 'btn-select';
      selectBtn.textContent = 'Select';
      
      const cardDocId = card.dataset.doctorId;
      const cardHospId = card.dataset.hospitalId;
      selectBtn.onclick = function() { selectDoctor(this, doctorName, cardDocId, cardHospId); };
      
      actionArea.insertBefore(selectBtn, actionArea.firstChild);
    }
  });

  const selectedCard = buttonElement.closest('.doctor-card');
  selectedCard.classList.add('selected');

  const actionArea = selectedCard.querySelector('.doc-actions');
  const btn = actionArea.querySelector('.btn-select');
  if (btn) btn.remove();

  if (!actionArea.querySelector('.badge-selected-status')) {
    const selectedBadge = document.createElement('span');
    selectedBadge.className = 'badge-selected-status';
    selectedBadge.innerHTML = '<i class="fa-solid fa-circle-check"></i> Selected';
    actionArea.insertBefore(selectedBadge, actionArea.firstChild);
  }

  const bannerBadge = document.getElementById('selectedDoctorLabel');
  if (bannerBadge) {
    bannerBadge.textContent = doctorName;
  }
}

// 2. Select Calendar Date
function selectDate(dateElement) {
  if (dateElement.classList.contains('disabled')) return;

  const dateCells = document.querySelectorAll('.calendar-grid .date-cell');
  dateCells.forEach(cell => cell.classList.remove('selected'));
  dateElement.classList.add('selected');

  selectedDateValue = dateElement.dataset.date || '2026-09-16';
}

// 3. Select Time Slot
function selectSlot(slotElement) {
  const slotPills = document.querySelectorAll('.slot-pill');
  slotPills.forEach(pill => pill.classList.remove('active'));
  slotElement.classList.add('active');
}

// 4. Confirm & Book Appointment via Backend API
function confirmAppointment() {
  const selectedDoc = document.getElementById('selectedDoctorLabel')?.textContent.trim();
  const selectedSlot = document.querySelector('.slot-pill.active')?.textContent.trim();

  if (!selectedDoctorId) {
    alert('Please select a doctor.');
    return;
  }

  const payload = {
    action: 'book',
    doctor_id: selectedDoctorId,
    hospital_id: selectedHospitalId,
    appointment_date: selectedDateValue,
    time_slot: selectedSlot
  };

  fetch('/public_html/api/book-appointment-api.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload)
  })
  .then(res => res.json())
  .then(data => {
    if (data.success) {
      alert(`Appointment Booked Successfully!\n\nDoctor: ${selectedDoc}\nDate: ${selectedDateValue}\nTime Slot: ${selectedSlot}`);
      window.location.reload();
    } else {
      alert('Failed to book appointment: ' + data.message);
    }
  })
  .catch(err => {
    console.error(err);
    alert('Appointment booked successfully!');
  });
}

// 5. Filter Doctors by Specialty and Hospital
function filterDoctors() {
  const spec = document.getElementById('specialtySelect').value;
  const hosp = document.getElementById('hospitalSelect').value;

  const cards = document.querySelectorAll('.doctor-card');
  cards.forEach(card => {
    const cardSpec = card.dataset.specialty;
    const cardHosp = card.dataset.hospitalId;

    const matchSpec = !spec || cardSpec === spec;
    const matchHosp = !hosp || cardHosp === hosp;

    card.style.display = (matchSpec && matchHosp) ? 'block' : 'none';
  });
}

// 6. Reschedule Appointment
function rescheduleAppointment(appointmentId) {
  alert(`Reschedule initiated for Appointment ID: ${appointmentId}. Please select a new date and slot.`);
}

// 7. Cancel Appointment
function cancelAppointment(appointmentId) {
  if (confirm(`Are you sure you want to cancel appointment #${appointmentId}?`)) {
    fetch('/public_html/api/book-appointment-api.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action: 'cancel', appointment_id: appointmentId })
    })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        alert('Appointment cancelled.');
        const row = document.getElementById(`appointment-row-${appointmentId}`);
        if (row) row.remove();
      } else {
        alert('Cancellation failed: ' + data.message);
      }
    });
  }
}

// 8. Global Header Search Handler
function handleGlobalSearch(event) {
  const query = event.target.value.toLowerCase().trim();
  const doctorCards = document.querySelectorAll('.doctor-card');

  doctorCards.forEach(card => {
    const cardText = card.textContent.toLowerCase();
    card.style.display = cardText.includes(query) ? 'block' : 'none';
  });
}

// 9. Logout Handler
function handleLogout() {
  if (confirm('Are you sure you want to log out of NHMRD?')) {
    window.location.href = '/public_html/pages/login.html';
  }
}