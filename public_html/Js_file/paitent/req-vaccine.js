// 1. Select Vaccine Function
function selectVaccine(cardElement, vaccineName, batchNumber) {
  // Deselect all cards
  const allCards = document.querySelectorAll('.vaccine-card');
  allCards.forEach(card => {
    card.classList.remove('selected');
    const radioIcon = card.querySelector('.radio-icon');
    if (radioIcon) {
      radioIcon.className = 'fa-regular fa-circle radio-icon';
    }
  });

  // Select clicked card
  cardElement.classList.add('selected');
  const clickedRadio = cardElement.querySelector('.radio-icon');
  if (clickedRadio) {
    clickedRadio.className = 'fa-solid fa-circle-check radio-icon checked';
  }

  // Update Selected Immunization Summary Box
  const summaryName = document.querySelector('.selected-imm-box .imm-name');
  const summaryMeta = document.querySelector('.selected-imm-box .imm-meta');

  if (summaryName) summaryName.textContent = vaccineName;
  if (summaryMeta) summaryMeta.textContent = `Batch: ${batchNumber} • IM 0.5 mL`;
}

// 2. Change/Focus Vaccine Selection
function changeVaccineSelection() {
  const vaccineList = document.querySelector('.vaccine-list');
  if (vaccineList) {
    vaccineList.scrollIntoView({ behavior: 'smooth', block: 'start' });
  }
}

// 3. Confirm Vaccine Appointment
function confirmVaccineAppointment() {
  const declCheck = document.getElementById('declCheck');
  if (declCheck && !declCheck.checked) {
    alert('Please confirm the health declaration before proceeding.');
    return;
  }

  const selectedVaccine = document.querySelector('.selected-imm-box .imm-name')?.textContent || 'Vaccine';
  const medicalCenter = document.querySelector('.scheduling-card select')?.value || 'Center';
  const targetDate = document.querySelector('.datetime-grid input')?.value || 'Date';
  const timeSlot = document.querySelectorAll('.scheduling-card select')[1]?.value || 'Time Slot';

  alert(`Vaccine Appointment Confirmed Successfully!\n\nVaccine: ${selectedVaccine}\nFacility: ${medicalCenter}\nDate: ${targetDate}\nSlot: ${timeSlot}`);
}

// 4. Global Search Handler
function handleGlobalSearch(event) {
  const query = event.target.value.toLowerCase().trim();
  const cards = document.querySelectorAll('.vaccine-card');

  cards.forEach(card => {
    const text = card.textContent.toLowerCase();
    card.style.display = text.includes(query) ? 'flex' : 'none';
  });
}

// 5. Logout Handler
function handleLogout() {
  if (confirm('Are you sure you want to log out of NHMRD?')) {
    window.location.href = '/public_html/pages/login.html';
  }
}