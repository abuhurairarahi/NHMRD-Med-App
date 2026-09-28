// 1. Select Doctor Card Function
function selectDoctor(buttonElement, doctorName) {
  // Reset all doctor card selections
  const doctorCards = document.querySelectorAll('.doctor-card');
  doctorCards.forEach(card => {
    card.classList.remove('selected');
    const actionArea = card.querySelector('.doc-actions');
    
    // Convert status badge back to "Select" button if needed
    const existingBadge = actionArea.querySelector('.badge-selected-status');
    if (existingBadge) {
      existingBadge.remove();
      const selectBtn = document.createElement('button');
      selectBtn.className = 'btn-select';
      selectBtn.textContent = 'Select';
      
      // Get doctor name from current card's heading
      const docHeading = card.querySelector('.doc-details h3');
      const name = docHeading ? docHeading.textContent.trim() : doctorName;
      selectBtn.onclick = function() { selectDoctor(this, name); };
      
      actionArea.insertBefore(selectBtn, actionArea.firstChild);
    }
  });

  // Mark clicked card as selected
  const selectedCard = buttonElement.closest('.doctor-card');
  selectedCard.classList.add('selected');

  // Update button in selected card to "Selected" badge
  const actionArea = selectedCard.querySelector('.doc-actions');
  const btn = actionArea.querySelector('.btn-select');
  if (btn) btn.remove();

  if (!actionArea.querySelector('.badge-selected-status')) {
    const selectedBadge = document.createElement('span');
    selectedBadge.className = 'badge-selected-status';
    selectedBadge.innerHTML = '<i class="fa-solid fa-circle-check"></i> Selected';
    actionArea.insertBefore(selectedBadge, actionArea.firstChild);
  }

  // Update Selected Doctor Banner Tag
  const bannerBadge = document.querySelector('.selected-doc-badge strong');
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
}

// 3. Select Time Slot
function selectSlot(slotElement) {
  const slotPills = document.querySelectorAll('.slot-pill');
  slotPills.forEach(pill => pill.classList.remove('active'));
  slotElement.classList.add('active');
}

// 4. Confirm & Book Appointment
function confirmAppointment() {
  const selectedDoc = document.querySelector('.selected-doc-badge strong')?.textContent.trim() || 'Selected Doctor';
  const selectedDate = document.querySelector('.date-cell.selected')?.textContent.trim() || 'Selected Date';
  const selectedSlot = document.querySelector('.slot-pill.active')?.textContent.trim() || 'Selected Time';

  alert(`Appointment Booked Successfully!\n\nDoctor: ${selectedDoc}\nDate: Sep ${selectedDate}, 2026\nTime Slot: ${selectedSlot}`);
}

// 5. Reschedule Appointment
function rescheduleAppointment(appointmentId) {
  alert(`Reschedule initiated for Appointment ${appointmentId}. Please pick a new date and time slot above.`);
}

// 6. Cancel Appointment
function cancelAppointment(appointmentId) {
  if (confirm(`Are you sure you want to cancel appointment ${appointmentId}?`)) {
    alert(`Appointment ${appointmentId} has been cancelled.`);
  }
}

// 7. Global Header Search Handler
function handleGlobalSearch(event) {
  const query = event.target.value.toLowerCase().trim();
  const doctorCards = document.querySelectorAll('.doctor-card');

  doctorCards.forEach(card => {
    const cardText = card.textContent.toLowerCase();
    card.style.display = cardText.includes(query) ? 'block' : 'none';
  });
}

// 8. Logout Handler
function handleLogout() {
  if (confirm('Are you sure you want to log out of NHMRD?')) {
    window.location.href = '/public_html/pages/login.html';
  }
}