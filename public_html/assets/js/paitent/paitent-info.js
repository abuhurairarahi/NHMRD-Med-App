/**
 * Patient Info Page Module
 * Asset Path: /public_html/assets/js/patient/paitent-info.js
 */

document.addEventListener('DOMContentLoaded', () => {
  // 1. Download Health Card Action
  const downloadBtn = document.getElementById('downloadHealthCardBtn');
  if (downloadBtn) {
    downloadBtn.addEventListener('click', () => {
      const nameElem = document.querySelector('.patient-details h2');
      const patientName = nameElem ? nameElem.childNodes[0].textContent.trim() : 'Patient';
      alert(`Preparing download for ${patientName}'s Digital Health Card...`);
    });
  }

  // 2. Registry OTP Verification
  const otpBox = document.getElementById('authenticateOtpBox');
  if (otpBox) {
    otpBox.addEventListener('click', () => {
      const otpVal = otpBox.querySelector('.otp-val');
      const statusText = otpVal ? otpVal.textContent.trim() : 'VALIDATED';
      alert(`Registry Status: ${statusText}\nAuthentication verified via NHMRD Database.`);
    });
  }

  // 3. Add Contact Entry
  const addContactBtn = document.getElementById('addContactBtn');
  if (addContactBtn) {
    addContactBtn.addEventListener('click', () => {
      const label = prompt('Enter contact label (e.g., Alternate Phone, Work Email):');
      if (!label) return;
      const value = prompt(`Enter value for "${label}":`);
      if (!value) return;

      const contactCard = document.querySelector('.contact-card');
      const addressCard = contactCard ? contactCard.querySelector('.address-card') : null;

      if (contactCard && addressCard) {
        const newContact = document.createElement('div');
        newContact.className = 'contact-item';
        newContact.innerHTML = `
          <i class="fa-solid fa-address-book"></i>
          <span class="label">${escapeHTML(label)}</span>
          <span class="val">${escapeHTML(value)}</span>
        `;
        contactCard.insertBefore(newContact, addressCard);
      }
    });
  }

  // 4. Add Donor Entry
  const addDonorBtn = document.getElementById('addDonorBtn');
  if (addDonorBtn) {
    addDonorBtn.addEventListener('click', () => {
      const donorName = prompt('Enter Donor Name:');
      if (!donorName) return;
      const bloodGroup = prompt('Enter Blood Group (e.g., A+, O-, AB+):');
      if (!bloodGroup) return;
      const phone = prompt('Enter Phone Number:');
      if (!phone) return;

      const container = document.getElementById('donorListContainer');
      if (container) {
        // Remove empty state message if present
        const emptyMsg = container.querySelector('p.text-muted');
        if (emptyMsg) emptyMsg.remove();

        const newDonor = document.createElement('div');
        newDonor.className = 'donor-item';
        newDonor.innerHTML = `
          <div class="donor-info">
            <span class="blood-badge">${escapeHTML(bloodGroup)}</span>
            <div>
              <strong>${escapeHTML(donorName)}</strong> 
              <span class="badge-status badge-due">Pending</span>
              <div class="text-muted donor-contact">
                <i class="fa-solid fa-phone"></i> ${escapeHTML(phone)}
              </div>
            </div>
          </div>
        `;
        container.appendChild(newDonor);
      }
    });
  }

  // 5. Dynamic Vitals Update
  const updateVitalsBtn = document.getElementById('updateVitalsBtn');
  if (updateVitalsBtn) {
    updateVitalsBtn.addEventListener('click', () => {
      const height = parseFloat(prompt('Enter Height in cm:', '175'));
      if (isNaN(height)) return;

      const weight = parseFloat(prompt('Enter Weight in kg:', '74'));
      if (isNaN(weight)) return;

      const bp = prompt('Enter Blood Pressure (e.g., 120/80):', '120/80');
      if (!bp) return;

      // Calculate BMI
      const heightM = height / 100;
      const bmi = (weight / (heightM * heightM)).toFixed(1);

      // Update Vitals Box elements on UI
      const vitalBoxes = document.querySelectorAll('.vital-box');
      if (vitalBoxes.length >= 2) {
        vitalBoxes[0].querySelector('.vital-value').innerHTML = `${escapeHTML(bp)} <small class="unit-text">mmHg</small>`;
        vitalBoxes[1].querySelector('.vital-value').textContent = `${height} cm • ${weight} kg`;
        vitalBoxes[1].querySelector('.vital-status').textContent = `• BMI ${bmi} (Normal)`;
      }

      // Update Last Synced timestamp
      const now = new Date();
      const dateFormatted = `${String(now.getDate()).padStart(2, '0')}-${String(now.getMonth() + 1).padStart(2, '0')}-${now.getFullYear()}`;
      const lastSynced = document.querySelector('.last-synced');
      if (lastSynced) {
        lastSynced.textContent = `Last synced: ${dateFormatted}`;
      }
    });
  }

  // 6. Real-time Search Filter
  const searchInput = document.getElementById('patientSearchInput');
  if (searchInput) {
    searchInput.addEventListener('input', (e) => {
      const query = e.target.value.toLowerCase().trim();
      const cards = document.querySelectorAll('.profile-grid .card');

      cards.forEach((card) => {
        const text = card.textContent.toLowerCase();
        card.style.display = text.includes(query) ? '' : 'none';
      });
    });
  }

  // 7. Dossier Navigation Buttons
  const testRecordsBtn = document.getElementById('viewTestRecordsBtn');
  if (testRecordsBtn) {
    testRecordsBtn.addEventListener('click', () => {
      window.location.href = '/public_html/pages/Patient-panel/lab-test.php';
    });
  }

  const prescriptionRecordsBtn = document.getElementById('viewPrescriptionRecordsBtn');
  if (prescriptionRecordsBtn) {
    prescriptionRecordsBtn.addEventListener('click', () => {
      window.location.href = '/public_html/pages/Patient-panel/prescription-record.php';
    });
  }

  // 8. Logout Confirmation
  const logoutBtn = document.getElementById('logoutBtn');
  if (logoutBtn) {
    logoutBtn.addEventListener('click', () => {
      if (confirm('Are you sure you want to log out?')) {
        window.location.href = '/public_html/pages/login.html';
      }
    });
  }
});

/**
 * Utility function to prevent XSS during DOM insertions
 */
function escapeHTML(str) {
  return String(str)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}