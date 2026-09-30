function handleSearch(event) {
  const query = event.target.value.toLowerCase().trim();
  const vaccineCards = document.querySelectorAll('.vaccine-card');

  vaccineCards.forEach(card => {
    const textContent = card.textContent.toLowerCase();
    card.style.display = textContent.includes(query) ? 'flex' : 'none';
  });
}

function handleAddExternalCertificate() {
  alert('Opening upload modal for external vaccination certificate...');
}

function handleDigitalPassport() {
  alert('Generating digital vaccine passport QR code...');
}

function handleRequestVaccine() {
  window.location.href = 'req-vaccine.php';
}

function handleScheduleBooster(vaccineName) {
  window.location.href = 'req-vaccine.php';
}

function handleLogout() {
  if (confirm('Are you sure you want to log out of NHMRD?')) {
    window.location.href = '/public_html/api/logout.php';
  }
}