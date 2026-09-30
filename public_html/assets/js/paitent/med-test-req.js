// 1. Dynamic Calculation of Selected Tests and Total Payable Amount
function updateSummaryTotal() {
  const checkboxes = document.querySelectorAll('.test-list .test-checkbox input[type="checkbox"]');
  let selectedCount = 0;
  let testsTotal = 0;

  checkboxes.forEach((checkbox) => {
    const testItem = checkbox.closest('.test-item');
    if (checkbox.checked) {
      testItem.classList.add('selected');
      selectedCount++;

      // Parse price from string format: "৳1,200" -> 1200
      const priceText = testItem.querySelector('.test-price').textContent;
      const priceValue = parseFloat(priceText.replace(/[^0-9.]/g, '')) || 0;
      testsTotal += priceValue;
    } else {
      testItem.classList.remove('selected');
    }
  });

  // Base Collection Fee (From Step 1 Banner: ৳200)
  const collectionFee = 200;

  // Update UI Elements
  const countLabel = document.querySelector('.summary-row:nth-child(1) span:first-child');
  const testsTotalLabel = document.querySelector('.summary-row:nth-child(1) .val');
  const feeLabel = document.querySelector('.summary-row:nth-child(2) .val');
  const grandTotalLabel = document.querySelector('.total-amount');

  if (countLabel) countLabel.textContent = `Selected Tests (${selectedCount} test${selectedCount !== 1 ? 's' : ''})`;
  if (testsTotalLabel) testsTotalLabel.textContent = `৳${testsTotal.toLocaleString('en-US')}.00`;
  if (feeLabel) feeLabel.textContent = `৳${collectionFee}.00`;

  const grandTotal = selectedCount > 0 ? testsTotal + collectionFee : 0;
  if (grandTotalLabel) grandTotalLabel.textContent = `৳${grandTotal.toLocaleString('en-US')}.00`;
}

// 2. Test Search Filter
function filterTests(event) {
  const searchTerm = event.target.value.toLowerCase().trim();
  const testItems = document.querySelectorAll('.test-list .test-item');

  testItems.forEach((item) => {
    const textContent = item.textContent.toLowerCase();
    item.style.display = textContent.includes(searchTerm) ? 'flex' : 'none';
  });
}

// 3. Select All Advised Tests
function selectAdvisedTests(event) {
  event.preventDefault();
  const testItems = document.querySelectorAll('.test-list .test-item');

  testItems.forEach((item) => {
    const isAdvised = item.querySelector('.pill-badge.doctor-advised');
    const checkbox = item.querySelector('.test-checkbox input[type="checkbox"]');
    if (checkbox) {
      checkbox.checked = !!isAdvised;
    }
  });

  updateSummaryTotal();
}

// 4. Handle Prescription File Upload
function uploadPrescriptionFile() {
  const fileInput = document.createElement('input');
  fileInput.type = 'file';
  fileInput.accept = '.pdf,.jpg,.png,.doc,.docx';

  fileInput.onchange = (e) => {
    const file = e.target.files[0];
    if (file) {
      alert(`File "${file.name}" attached successfully.`);
    }
  };

  fileInput.click();
}

// 5. Remove Prescription File Attachment
function removePrescriptionFile(event) {
  const fileBox = event.currentTarget.closest('.presc-file-box');
  if (fileBox) {
    if (confirm('Are you sure you want to remove this attached prescription?')) {
      fileBox.style.display = 'none';
    }
  }
}

// 6. Submit Medical Test Request
function handleSubmitRequest(event) {
  event.preventDefault();

  const selectedCount = document.querySelectorAll('.test-list .test-checkbox input[type="checkbox"]:checked').length;
  if (selectedCount === 0) {
    alert('Please select at least one diagnostic test before submitting your request.');
    return;
  }

  const grandTotal = document.querySelector('.total-amount').textContent;
  alert(`Test request submitted successfully!\nTotal Payable: ${grandTotal}\nA confirmation SMS has been sent.`);
}

// 7. Header Notifications
function handleNotificationClick() {
  alert('You have no new notifications.');
}

// 8. Logout Confirmation
function handleUserLogout() {
  if (confirm('Are you sure you want to log out?')) {
    window.location.href = '/login.html';
  }
}

// Event Listeners Initialization
document.addEventListener('DOMContentLoaded', () => {
  // Checkbox Event Listeners
  const checkboxes = document.querySelectorAll('.test-list .test-checkbox input[type="checkbox"]');
  checkboxes.forEach((checkbox) => {
    checkbox.addEventListener('change', updateSummaryTotal);
  });

  // Search Filter
  const testSearchInput = document.querySelector('.test-search-box input');
  if (testSearchInput) {
    testSearchInput.addEventListener('input', filterTests);
  }

  // Select Advised Link
  const selectAdvisedBtn = document.querySelector('.step-card-header .link-btn');
  if (selectAdvisedBtn) {
    selectAdvisedBtn.addEventListener('click', selectAdvisedTests);
  }

  // Upload Prescription
  const uploadBtn = document.querySelector('.btn-upload-more');
  if (uploadBtn) {
    uploadBtn.addEventListener('click', uploadPrescriptionFile);
  }

  // Remove Prescription
  const removePrescBtn = document.querySelector('.remove-btn');
  if (removePrescBtn) {
    removePrescBtn.addEventListener('click', removePrescriptionFile);
  }

  // Submit Request Button
  const submitBtn = document.querySelector('.btn-submit-request');
  if (submitBtn) {
    submitBtn.addEventListener('click', handleSubmitRequest);
  }

  // Notification Bell
  const notifBtn = document.querySelector('.header-right .icon-btn');
  if (notifBtn) {
    notifBtn.addEventListener('click', handleNotificationClick);
  }

  // Sidebar Logout
  const logoutBtn = document.querySelector('.logout-btn');
  if (logoutBtn) {
    logoutBtn.addEventListener('click', handleUserLogout);
  }

  // Run initial calculation
  updateSummaryTotal();
});