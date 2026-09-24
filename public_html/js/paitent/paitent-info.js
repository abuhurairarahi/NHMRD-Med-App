// 1. Download Digital Health Card
function downloadHealthCard() {
  const patientName = document.querySelector('.patient-details h2').childNodes[0].textContent.trim();
  alert(`Preparing download for ${patientName}'s Digital Health Card...`);
}

// 2. Authenticate Registry OTP
function authenticateOTP() {
  const otpStatus = document.querySelector('.otp-val').textContent;
  alert(`Registry Status: ${otpStatus}\nAuthentication verified via NHMRD Database.`);
}

// 3. Add Contact Entry Modal/Prompt
function addContactEntry() {
  const label = prompt("Enter contact type (e.g., Secondary Phone, Alternate Email):");
  if (!label) return;
  const value = prompt(`Enter details for "${label}":`);
  if (!value) return;

  const contactCard = document.querySelector('.contact-card');
  const addressCard = contactCard.querySelector('.address-card');

  const newContact = document.createElement('div');
  newContact.className = 'contact-item';
  newContact.innerHTML = `
    <i class="fa-solid fa-address-book"></i>
    <span class="label">${label}</span>
    <span class="val">${value}</span>
  `;

  contactCard.insertBefore(newContact, addressCard);
}

// 4. Add Donor Modal/Prompt
function addDonor() {
  const donorName = prompt("Enter Donor Name:");
  if (!donorName) return;
  const bloodGroup = prompt("Enter Donor Blood Group (e.g., A+, O-, AB+):");
  if (!bloodGroup) return;
  const phone = prompt("Enter Donor Phone Number:");
  if (!phone) return;

  const donorCard = document.querySelector('.donor-item').parentElement;

  const newDonor = document.createElement('div');
  newDonor.className = 'donor-item';
  newDonor.innerHTML = `
    <div class="donor-info">
      <span class="blood-badge">${bloodGroup}</span>
      <div>
        <strong>${donorName}</strong> <span class="badge-status badge-verified">Pending</span>
        <div class="text-muted donor-contact">
          <i class="fa-solid fa-phone"></i> ${phone}
        </div>
      </div>
    </div>
  `;

  donorCard.appendChild(newDonor);
}

// 5. Update Vitals Baseline & Calculate BMI
function updateVitals() {
  const height = parseFloat(prompt("Enter Height in cm:", "175"));
  if (isNaN(height)) return;

  const weight = parseFloat(prompt("Enter Weight in kg:", "74"));
  if (isNaN(weight)) return;

  const bp = prompt("Enter Blood Pressure (e.g., 120/80):", "120/80");
  if (!bp) return;

  // Calculate BMI: weight (kg) / (height (m) ^ 2)
  const heightInMeters = height / 100;
  const bmi = (weight / (heightInMeters * heightInMeters)).toFixed(1);

  // Update UI display
  const vitalBoxes = document.querySelectorAll('.vital-box');
  vitalBoxes[0].querySelector('.vital-value').innerHTML = `${bp} <small class="unit-text">mmHg</small>`;
  vitalBoxes[1].querySelector('.vital-value').textContent = `${height} cm • ${weight} kg`;
  vitalBoxes[1].querySelector('.vital-status').textContent = `• BMI ${bmi}`;

  // Update Last Synced timestamp
  const now = new Date();
  const dateStr = `${now.getDate().toString().padStart(2, '0')}-${(now.getMonth()+1).toString().padStart(2, '0')}-${now.getFullYear()}`;
  document.querySelector('.last-synced').textContent = `Last synced: ${dateStr}`;
}

// 6. Navigation Link Redirects
function handleNavigation(event, pageUrl) {
  event.preventDefault();
  window.location.href = pageUrl;
}

// 7. Search Queries Filter
function handleSearch(event) {
  const query = event.target.value.toLowerCase().trim();
  const cards = document.querySelectorAll('.card');

  cards.forEach(card => {
    const text = card.textContent.toLowerCase();
    card.style.display = text.includes(query) ? '' : 'none';
  });
}

// 8. View Medical Dossier Records
function viewDossierRecords(type) {
  if (type === 'test') {
    window.location.href = '/public_html/pages/Patient-panel/lab-test.html';
  } else if (type === 'prescription') {
    window.location.href = '/public_html/pages/Patient-panel/prescription-record.html';
  }
}

// 9. Logout Confirmation
function handleLogout() {
  if (confirm('Are you sure you want to log out?')) {
    window.location.href = '/public_html/pages/login.html';
  }
}

// Event Listeners Initialization
document.addEventListener('DOMContentLoaded', () => {
  // Download Button
  const downloadBtn = document.querySelector('.btn-download-card');
  if (downloadBtn) downloadBtn.addEventListener('click', downloadHealthCard);

  // OTP Box Click
  const otpBox = document.querySelector('.registry-otp-box');
  if (otpBox) otpBox.addEventListener('click', authenticateOTP);

  // Add Contact Button
  const addContactBtn = document.querySelector('.contact-card .btn-action-sm');
  if (addContactBtn) addContactBtn.addEventListener('click', addContactEntry);

  // Add Donor Button
  const addDonorBtn = document.querySelector('.btn-add-donor');
  if (addDonorBtn) addDonorBtn.addEventListener('click', addDonor);

  // Update Vitals Button
  const updateVitalsBtn = document.querySelector('.column-right .btn-action-sm');
  if (updateVitalsBtn) updateVitalsBtn.addEventListener('click', updateVitals);

  // Search Input
  const searchInput = document.querySelector('.patient-header .search-bar input');
  if (searchInput) searchInput.addEventListener('input', handleSearch);

  // Logout Button
  const logoutBtn = document.querySelector('.logout-btn');
  if (logoutBtn) logoutBtn.addEventListener('click', handleLogout);

  // Dossier Buttons
  const dossierBtns = document.querySelectorAll('.dossier-actions .btn-dossier');
  if (dossierBtns.length >= 2) {
    dossierBtns[0].addEventListener('click', () => viewDossierRecords('test'));
    dossierBtns[1].addEventListener('click', () => viewDossierRecords('prescription'));
  }
});