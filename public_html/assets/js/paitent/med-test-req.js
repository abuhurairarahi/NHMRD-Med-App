document.addEventListener('DOMContentLoaded', () => {
  const hospitalSelect = document.getElementById('hospitalSelect');
  const centerModeTitle = document.getElementById('centerModeTitle');
  const collectionFeeTag = document.getElementById('collectionFeeTag');
  const collectionFeeVal = document.getElementById('collectionFeeVal');
  const testListContainer = document.getElementById('testListContainer');
  const testSearchInput = document.getElementById('testSearchInput');
  const selectAdvisedBtn = document.getElementById('selectAdvisedBtn');
  const prescFileBox = document.getElementById('prescFileBox');
  const removePrescBtn = document.getElementById('removePrescBtn');
  const uploadPrescBtn = document.getElementById('uploadPrescBtn');
  const prescriptionFileInput = document.getElementById('prescriptionFileInput');
  const uploadedFileName = document.getElementById('uploadedFileName');
  const testRequestForm = document.getElementById('testRequestForm');

  // 1. Calculate Summary Totals
  function updateSummaryTotal() {
    const checkboxes = testListContainer.querySelectorAll('input[type="checkbox"]:checked');
    let selectedCount = 0;
    let testsTotal = 0;

    checkboxes.forEach((checkbox) => {
      const testItem = checkbox.closest('.test-item');
      if (testItem) {
        testItem.classList.add('selected');
      }
      selectedCount++;
      const price = parseFloat(checkbox.getAttribute('data-price')) || 0;
      testsTotal += price;
    });

    // Uncheck styling
    const uncheckedBoxes = testListContainer.querySelectorAll('input[type="checkbox"]:not(:checked)');
    uncheckedBoxes.forEach((checkbox) => {
      const testItem = checkbox.closest('.test-item');
      if (testItem) {
        testItem.classList.remove('selected');
      }
    });

    // Collection fee logic
    const collectionFee = (hospitalSelect && hospitalSelect.value === 'home') ? 200 : 0;

    // Update UI Elements
    document.getElementById('selectedCountLabel').textContent = `Selected Tests (${selectedCount} test${selectedCount !== 1 ? 's' : ''})`;
    document.getElementById('testsTotalVal').textContent = `৳${testsTotal.toLocaleString('en-US')}.00`;
    collectionFeeVal.textContent = `৳${collectionFee}.00`;

    const grandTotal = selectedCount > 0 ? testsTotal + collectionFee : 0;
    document.getElementById('grandTotalVal').textContent = `৳${grandTotal.toLocaleString('en-US')}.00`;
  }

  // 2. Collection Center Toggle
  if (hospitalSelect) {
    hospitalSelect.addEventListener('change', (e) => {
      if (e.target.value === 'home') {
        centerModeTitle.textContent = 'Doorstep Phlebotomist Visit';
        collectionFeeTag.textContent = 'Fee: ৳200';
      } else {
        const selectedText = e.target.options[e.target.selectedIndex].text;
        centerModeTitle.textContent = `Walk-in Visit: ${selectedText}`;
        collectionFeeTag.textContent = 'Fee: ৳0';
      }
      updateSummaryTotal();
    });
  }

  // 3. Search Filter
  if (testSearchInput) {
    testSearchInput.addEventListener('input', (e) => {
      const searchTerm = e.target.value.toLowerCase().trim();
      const testItems = testListContainer.querySelectorAll('.test-item');

      testItems.forEach((item) => {
        const textContent = item.textContent.toLowerCase();
        item.style.display = textContent.includes(searchTerm) ? 'flex' : 'none';
      });
    });
  }

  // 4. Select Advised Tests
  if (selectAdvisedBtn) {
    selectAdvisedBtn.addEventListener('click', (e) => {
      e.preventDefault();
      const testItems = testListContainer.querySelectorAll('.test-item');

      testItems.forEach((item) => {
        const isAdvised = item.querySelector('.pill-badge.doctor-advised');
        const checkbox = item.querySelector('input[type="checkbox"]');
        if (checkbox) {
          checkbox.checked = !!isAdvised;
        }
      });
      updateSummaryTotal();
    });
  }

  // 5. Checkbox Change Listeners
  if (testListContainer) {
    testListContainer.addEventListener('change', (e) => {
      if (e.target.matches('input[type="checkbox"]')) {
        updateSummaryTotal();
      }
    });
  }

  // 6. Prescription Upload & Remove
  if (uploadPrescBtn && prescriptionFileInput) {
    uploadPrescBtn.addEventListener('click', () => prescriptionFileInput.click());

    prescriptionFileInput.addEventListener('change', (e) => {
      const file = e.target.files[0];
      if (file) {
        uploadedFileName.textContent = `Attached: ${file.name}`;
      }
    });
  }

  if (removePrescBtn && prescFileBox) {
    removePrescBtn.addEventListener('click', () => {
      if (confirm('Are you sure you want to remove this linked prescription?')) {
        prescFileBox.style.display = 'none';
        const prescInput = document.getElementById('prescription_id');
        if (prescInput) prescInput.value = '';
      }
    });
  }

  // 7. Form Submission Handler via Fetch AJAX
  if (testRequestForm) {
    testRequestForm.addEventListener('submit', async (e) => {
      e.preventDefault();

      const checkedCount = testListContainer.querySelectorAll('input[type="checkbox"]:checked').length;
      if (checkedCount === 0) {
        alert('Please select at least one diagnostic test before submitting your request.');
        return;
      }

      const formData = new FormData(testRequestForm);
      const submitBtn = document.getElementById('submitBtn');
      submitBtn.disabled = true;
      submitBtn.textContent = 'Submitting...';

      try {
        const response = await fetch('/api/patient/submit-test-request.php', {
          method: 'POST',
          body: formData
        });

        const result = await response.json();

        if (result.success) {
          alert(`Test request submitted successfully!\nOrder ID: #${result.order_id}\nTotal Payable: ৳${result.total}\nA confirmation SMS has been sent.`);
          window.location.href = '/public_html/pages/Patient-panel/lab-test.php';
        } else {
          alert(`Error: ${result.message}`);
        }
      } catch (err) {
        alert('An unexpected error occurred. Please try again.');
        console.error(err);
      } finally {
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="fa-solid fa-puzzle-piece"></i> Submit Test Request';
      }
    });
  }

  // Run initial calculation
  updateSummaryTotal();
});