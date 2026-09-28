// 1. Filter Prescriptions by Tab Status (All, Active, Past, Chronic)
function filterByTab(buttonElement, status) {
  // Update active pill state
  const tabs = document.querySelectorAll('.tab-pill');
  tabs.forEach(tab => tab.classList.remove('active'));
  buttonElement.classList.add('active');

  const selectedStatus = status.toUpperCase().trim();
  const rxCards = document.querySelectorAll('.rx-card');

  rxCards.forEach(card => {
    const badge = card.querySelector('.status-badge');
    const badgeText = badge ? badge.textContent.trim().toUpperCase() : '';

    if (selectedStatus === 'ALL') {
      card.style.display = 'flex';
    } else if (selectedStatus === 'ACTIVE' && badgeText === 'ACTIVE') {
      card.style.display = 'flex';
    } else if (selectedStatus === 'PAST' && badgeText === 'COMPLETED') {
      card.style.display = 'flex';
    } else if (selectedStatus === 'CHRONIC') {
      // Check if prescription card contains 'Long Term / Ongoing'
      const isOngoing = card.textContent.includes('Long Term / Ongoing');
      card.style.display = isOngoing ? 'flex' : 'none';
    } else {
      card.style.display = 'none';
    }
  });
}

// 2. Filter Prescriptions by Keyword Search
function filterByKeyword(event) {
  const query = event.target.value.toLowerCase().trim();
  const rxCards = document.querySelectorAll('.rx-card');

  rxCards.forEach(card => {
    const cardText = card.textContent.toLowerCase();
    card.style.display = cardText.includes(query) ? 'flex' : 'none';
  });
}

// 3. Global Header Search Filter
function handleGlobalSearch(event) {
  filterByKeyword(event);
}

// 4. Print Roster
function printRoster() {
  window.print();
}

// 5. Download Full History PDF
function downloadFullHistory() {
  const patientNameHeading = document.querySelector('.patient-details h2');
  const patientName = patientNameHeading ? patientNameHeading.textContent.trim() : 'Patient';
  alert(`Generating full medical dossier PDF for ${patientName}... Download starting.`);
}

// 6. View Digital Prescription
function viewDigitalRx(rxId) {
  alert(`Opening digital record modal for Prescription ID: ${rxId}`);
}

// 7. Order Medicine Refill
function orderRefill(rxId) {
  alert(`Refill request for Prescription ${rxId} submitted successfully to the partner pharmacy.`);
}

// 8. Download Individual Prescription PDF
function downloadPrescriptionPDF(rxId) {
  alert(`Downloading PDF for Prescription ID: ${rxId}...`);
}

// 9. Logout Confirmation
function handleLogout() {
  if (confirm('Are you sure you want to log out of NHMRD?')) {
    window.location.href = '/public_html/pages/login.html';
  }
}