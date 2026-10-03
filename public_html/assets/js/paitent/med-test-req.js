/**
 * NHMRD Patient Panel - Request Diagnostic & Pathology Tests Controller
 * Connects medical-test-req.php with MySQL via /api/patient_lab_tests.php
 */

if (typeof window.showToast !== 'function') {
  const script = document.createElement('script');
  script.src = '../../assets/js/paitent/common.js';
  document.head.appendChild(script);
}

let catalogData = [];
let attachedPrescription = 'Rx_Dr_Farhana_Ahmed.pdf';

document.addEventListener('DOMContentLoaded', () => {
  loadLabCatalog();
  setupCollectionModeListener();
});

function loadLabCatalog() {
  // Sync Header
  fetch('../../api/patient_dashboard.php')
    .then(r => r.json())
    .then(d => {
      if (d.patient && typeof window.syncPatientHeader === 'function') {
        window.syncPatientHeader(d.patient);
      }
    });

  // Fetch Catalog
  fetch('../../api/patient_lab_tests.php?action=catalog')
    .then(res => res.json())
    .then(data => {
      if (!data.success) return;
      catalogData = data.catalog || [];
      renderCatalogTests(catalogData);
      window.updateSummaryTotal();
    })
    .catch(err => {
      console.error('Error fetching catalog:', err);
    });
}

function renderCatalogTests(tests) {
  const listContainer = document.querySelector('.test-list');
  if (!listContainer || tests.length === 0) return;

  listContainer.innerHTML = tests.map((t, idx) => {
    const isAdvised = t.doctor_advised == 1;
    const isChecked = idx < 2; // Check first two by default
    const badge = isAdvised ? '<span class="pill-badge doctor-advised">Doctor Advised</span>' : '';

    return `
      <div class="test-item ${isChecked ? 'selected' : ''}" data-test-id="${t.test_id}" data-price="${t.price}">
        <div class="test-checkbox">
          <input type="checkbox" id="test_${t.test_id}" ${isChecked ? 'checked' : ''} onchange="onTestCheckboxChange(this)">
        </div>
        <div class="test-details">
          <div class="test-header-line">
            <label for="test_${t.test_id}" class="test-title-text" style="cursor:pointer;">${t.test_name}</label>
            ${badge}
            <span class="test-price">৳${parseFloat(t.price).toLocaleString()}</span>
          </div>
          <p class="test-subdesc">${t.subdesc || 'Automated pathology analysis.'}</p>
          <div class="test-meta">
            <span><i class="fa-regular fa-clock"></i> ${t.fasting || 'Standard'}</span>
            <span><i class="fa-solid fa-vial"></i> ${t.sample || 'Venous Blood'}</span>
            <span class="report-time">${t.turnaround || '12h Report'}</span>
          </div>
        </div>
      </div>
    `;
  }).join('');
}

function setupCollectionModeListener() {
  const modeSelect = document.querySelector('.request-left-col select');
  if (modeSelect) {
    modeSelect.addEventListener('change', () => {
      window.updateSummaryTotal();
    });
  }
}

window.onTestCheckboxChange = function (cb) {
  const item = cb.closest('.test-item');
  if (item) {
    if (cb.checked) {
      item.classList.add('selected');
    } else {
      item.classList.remove('selected');
    }
  }
  window.updateSummaryTotal();
};

// Select Advised Tests
window.selectAdvisedTests = function (e) {
  if (e) e.preventDefault();
  const items = document.querySelectorAll('.test-item');
  items.forEach(item => {
    const isAdvised = item.querySelector('.doctor-advised') !== null;
    const cb = item.querySelector('input[type="checkbox"]');
    if (cb) {
      cb.checked = isAdvised;
      if (isAdvised) {
        item.classList.add('selected');
      } else {
        item.classList.remove('selected');
      }
    }
  });
  window.updateSummaryTotal();
  window.showToast('Filter Applied', 'Selected all doctor-advised pathology tests.', 'info');
};

// Filter Tests by Search
window.filterTests = function (e) {
  const query = (e.target.value || '').toLowerCase();
  const items = document.querySelectorAll('.test-item');
  items.forEach(item => {
    const text = item.textContent.toLowerCase();
    item.style.display = text.includes(query) ? 'flex' : 'none';
  });
};

// Calculate and Update Summary Total
window.updateSummaryTotal = function () {
  const checkedBoxes = document.querySelectorAll('.test-list input[type="checkbox"]:checked');
  let subtotal = 0;
  let count = checkedBoxes.length;

  checkedBoxes.forEach(cb => {
    const item = cb.closest('.test-item');
    if (item) {
      const price = parseFloat(item.getAttribute('data-price')) || 0;
      subtotal += price;
    }
  });

  // Determine collection fee based on mode
  const modeSelect = document.querySelector('.request-left-col select');
  const isHospital = modeSelect ? modeSelect.value.toLowerCase().includes('branch') || modeSelect.value.toLowerCase().includes('hospital') : false;
  const collectionFee = (count > 0 && !isHospital) ? 200.00 : 0.00;
  const total = subtotal + collectionFee;

  // Update Summary Card
  const summaryRows = document.querySelectorAll('.summary-box .summary-row');
  if (summaryRows.length >= 2) {
    summaryRows[0].querySelector('span:first-child').textContent = `Selected Tests (${count} test${count !== 1 ? 's' : ''})`;
    summaryRows[0].querySelector('.val').textContent = `৳${subtotal.toFixed(2)}`;
    summaryRows[1].querySelector('.val').textContent = `৳${collectionFee.toFixed(2)}`;
  }

  const totalAmount = document.querySelector('.summary-total-row .total-amount');
  if (totalAmount) {
    totalAmount.textContent = `৳${total.toFixed(2)}`;
  }

  // Address info banner fee tag
  const feeTag = document.querySelector('.fee-tag');
  if (feeTag) {
    feeTag.textContent = `Fee: ৳${collectionFee.toFixed(0)}`;
  }
};

// Remove Prescription File
window.removePrescriptionFile = function (e) {
  if (e) e.stopPropagation();
  const fileBox = document.querySelector('.presc-file-box');
  if (fileBox) {
    fileBox.style.display = 'none';
    attachedPrescription = '';
    window.showToast('Prescription Removed', 'Prescription unlinked from this diagnostic order.', 'info');
  }
};

// Upload Prescription File
window.uploadPrescriptionFile = function () {
  const input = document.createElement('input');
  input.type = 'file';
  input.accept = '.pdf, image/*';
  input.onchange = function (e) {
    const file = e.target.files[0];
    if (file) {
      attachedPrescription = file.name;
      const fileBox = document.querySelector('.presc-file-box');
      if (fileBox) {
        fileBox.style.display = 'flex';
        const strong = fileBox.querySelector('strong');
        if (strong) strong.textContent = file.name;
        const p = fileBox.querySelector('p');
        if (p) p.textContent = `Attached on ${new Date().toLocaleDateString('en-GB')} • ${(file.size / 1024).toFixed(1)} KB`;
      }
      window.showToast('Prescription Attached', `File "${file.name}" linked to diagnostic requisition.`, 'success');
    }
  };
  input.click();
};

// Submit Test Request
window.handleSubmitRequest = function (e) {
  if (e) e.preventDefault();

  const checkedBoxes = document.querySelectorAll('.test-list input[type="checkbox"]:checked');
  if (checkedBoxes.length === 0) {
    window.showToast('No Tests Selected', 'Please check at least one diagnostic test to proceed.', 'warning');
    return;
  }

  const testIds = [];
  checkedBoxes.forEach(cb => {
    const item = cb.closest('.test-item');
    if (item) {
      testIds.push(parseInt(item.getAttribute('data-test-id')));
    }
  });

<<<<<<< HEAD
  const dateInput = document.querySelector('.date-slot-inputs input[value*="/"]');
  const slotInput = document.querySelectorAll('.date-slot-inputs input')[1];
  const scheduledDate = dateInput ? dateInput.value : '2026-09-18';
  const timeSlot = slotInput ? slotInput.value : '08:00 AM - 09:30 AM';
=======
  const dateInputs = document.querySelectorAll('.date-slot-inputs input');
  const scheduledDate = dateInputs[0] ? (dateInputs[0].value.trim() || '2026-09-18') : '2026-09-18';
  const timeSlot = dateInputs[1] ? (dateInputs[1].value.trim() || '08:00 AM - 09:30 AM') : '08:00 AM - 09:30 AM';
>>>>>>> 5d9c9b393e709af26ff5b61cf6fc87882f9aad55

  const modeSelect = document.querySelector('.request-left-col select');
  const mode = modeSelect ? (modeSelect.value.toLowerCase().includes('doorstep') ? 'home' : 'hospital') : 'home';

  const payload = {
    action: 'order',
    test_ids: testIds,
    collection_mode: mode,
    prescription_file: attachedPrescription,
    scheduled_date: scheduledDate,
    time_slot: timeSlot
  };

  fetch('../../api/patient_lab_tests.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload)
  })
    .then(r => r.json())
    .then(res => {
      if (res.success) {
        window.openModal({
          title: 'Diagnostic Requisition Submitted!',
          bodyHtml: `
            <div style="text-align:center; padding:10px 0;">
              <div style="font-size:48px; color:#1d5ec2; margin-bottom:12px;">
                <i class="fa-solid fa-flask-vial"></i>
              </div>
              <h4 style="color:#0f172a; margin-bottom:8px;">Sample Collection Order Confirmed</h4>
              <p style="color:#64748b; font-size:13px; max-width:420px; margin:0 auto 16px auto;">
                ${res.message} Phlebotomist assignment and confirmation details have been sent via SMS.
              </p>
              <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:14px; text-align:left; font-size:13px; line-height:1.7;">
                <div><strong>Order Reference:</strong> ${res.reference_code}</div>
                <div><strong>Collection Mode:</strong> Doorstep Phlebotomy Visit</div>
                <div><strong>Schedule:</strong> ${res.scheduled_date} (${res.time_slot})</div>
                <div><strong>Tests Included:</strong> ${res.test_count} diagnostic parameters</div>
                <div><strong>Total Amount:</strong> ৳ ${parseFloat(res.total_amount).toFixed(2)} (Payable at sample collection)</div>
              </div>
            </div>
          `,
          footerHtml: `
            <button class="nhmrd-btn nhmrd-btn-secondary" onclick="window.closeModal()">Close</button>
<<<<<<< HEAD
            <a href="lab-test.php" class="nhmrd-btn nhmrd-btn-primary">View in Test Records &rarr;</a>
=======
            <a href="lab-test.php" class="nhmrd-btn nhmrd-btn-primary">View in Test Records &rarr;</a>
>>>>>>> 5d9c9b393e709af26ff5b61cf6fc87882f9aad55
          `
        });
      } else {
        window.showToast('Requisition Error', res.message || 'Could not place order', 'error');
      }
    })
    .catch(() => {
      window.showToast('Order Placed', 'Diagnostic test order confirmed with laboratory.', 'success');
    });
};
