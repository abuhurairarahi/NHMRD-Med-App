/* ==========================================================================
   1. Clinical Acuity Selector & Surgical Consult Flagging
   ========================================================================== */
function initAcuitySelector() {
  const acuityOptions = document.querySelectorAll('.acuity-option');
  const consultBanner = document.querySelector('.consultation-banner');

  acuityOptions.forEach((option) => {
    option.addEventListener('click', () => {
      // Clear active classes from all options
      acuityOptions.forEach((opt) => opt.classList.remove('active-acuity'));
      option.classList.add('active-acuity');

      const acuityText = option.querySelector('.acuity-title')?.textContent.trim();

      // Show surgical consultation banner if "Surgical Needed" is picked
      if (acuityText === 'Surgical Needed' || option.classList.contains('purple-acuity')) {
        if (consultBanner) consultBanner.style.display = 'block';
      } else {
        if (consultBanner) consultBanner.style.display = 'none';
      }

      updateEncounterSummary();
    });
  });
}

/* ==========================================================================
   2. Symptom Tags Management
   ========================================================================== */
function initSymptomTags() {
  const tagsRow = document.querySelector('.symptom-tags-row');
  const addBtn = document.querySelector('.btn-add-tag');

  if (!tagsRow) return;

  // Delegate remove tag functionality
  tagsRow.addEventListener('click', (e) => {
    if (e.target.classList.contains('fa-xmark')) {
      const tag = e.target.closest('.symptom-tag');
      if (tag) tag.remove();
    }
  });

  // Add new symptom tag prompt
  if (addBtn) {
    addBtn.addEventListener('click', () => {
      const newTagText = prompt('Enter new symptom tag:');
      if (newTagText && newTagText.trim() !== '') {
        const newSpan = document.createElement('span');
        newSpan.className = 'symptom-tag';
        newSpan.innerHTML = `${escapeHTML(newTagText.trim())} <i class="fa-solid fa-xmark"></i>`;
        tagsRow.insertBefore(newSpan, addBtn);
      }
    });
  }
}

/* ==========================================================================
   3. Draft Boxes (Medication & Diagnostic Test Creation)
   ========================================================================== */
function initDraftToggle() {
  const addButtons = document.querySelectorAll('.sec-header .btn-add-primary');

  addButtons.forEach((btn) => {
    btn.addEventListener('click', (e) => {
      const card = e.target.closest('.form-section-card');
      const draftBox = card.querySelector('.draft-med-box');
      if (draftBox) {
        draftBox.style.display = 'block';
        draftBox.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
      }
    });
  });

  // Handle Draft Box Action Buttons
  document.addEventListener('click', (e) => {
    // Cancel Draft
    if (e.target.classList.contains('btn-cancel')) {
      e.preventDefault();
      const draftBox = e.target.closest('.draft-med-box');
      if (draftBox) {
        draftBox.style.display = 'none';
        clearDraftInputs(draftBox);
      }
    }

    // Confirm Add Draft
    if (e.target.classList.contains('btn-confirm-add') || e.target.closest('.btn-confirm-add')) {
      e.preventDefault();
      const draftBox = e.target.closest('.draft-med-box');
      const card = e.target.closest('.form-section-card');

      if (draftBox && card) {
        const inputs = draftBox.querySelectorAll('input');
        const nameVal = inputs[0]?.value.trim();

        if (!nameVal) {
          alert('Please specify a name for the order item.');
          return;
        }

        // Determine if adding a Medication or Diagnostic Test
        const isMedication = card.querySelector('.med-order-card') !== null || card.innerHTML.includes('Prescribed Medications');

        if (isMedication) {
          appendMedicationCard(card, draftBox, inputs);
        } else {
          appendLabCard(card, draftBox, inputs);
        }

        draftBox.style.display = 'none';
        clearDraftInputs(draftBox);
        updateEncounterSummary();
      }
    }
  });
}

function clearDraftInputs(draftBox) {
  draftBox.querySelectorAll('input').forEach((input) => (input.value = ''));
}

/* Append New Prescribed Medication HTML Card */
function appendMedicationCard(parentCard, draftBox, inputs) {
  const name = inputs[0]?.value || 'New Medication';
  const dose = inputs[1]?.value || 'N/A';
  const route = inputs[2]?.value || 'Oral (PO)';
  const dispense = inputs[3]?.value || '1 Bottle';
  const sig = draftBox.querySelector('textarea')?.value || inputs[4]?.value || 'Take as directed.';

  const newCard = document.createElement('div');
  newCard.className = 'med-order-card';
  newCard.innerHTML = `
    <div class="med-card-header">
      <div class="med-title-row">
        <span class="med-badge-type bg-sky">+</span>
        <h4 class="med-name">${escapeHTML(name)}</h4>
        <span class="med-tag tag-teal">New Order</span>
      </div>
      <div class="med-actions">
        <button class="icon-action"><i class="fa-solid fa-pen"></i></button>
        <button class="icon-action danger"><i class="fa-regular fa-trash-can"></i></button>
      </div>
    </div>
    <div class="med-ndc">Custom Order &bull; Prescribed Item</div>
    <div class="med-grid-4col">
      <div class="med-field"><span class="m-lbl">DOSE & STRENGTH</span><span class="m-val">${escapeHTML(dose)}</span></div>
      <div class="med-field"><span class="m-lbl">ROUTE & FREQUENCY</span><span class="m-val">${escapeHTML(route)}</span></div>
      <div class="med-field"><span class="m-lbl">DISPENSE / REFILLS</span><span class="m-val">${escapeHTML(dispense)}</span></div>
      <div class="med-field"><span class="m-lbl">SAFETY NOTE</span><span class="m-val text-blue">Standard Protocol</span></div>
    </div>
    <div class="med-instructions-box">
      <i class="fa-solid fa-circle-info"></i>
      <span><strong>SIG / INSTRUCTIONS:</strong> ${escapeHTML(sig)}</span>
    </div>
  `;

  parentCard.insertBefore(newCard, draftBox);
}

/* Append New Laboratory Diagnostic Test Card */
function appendLabCard(parentCard, draftBox, inputs) {
  const name = inputs[0]?.value || 'Diagnostic Test';
  const code = inputs[1]?.value || 'CPT-GENERIC';
  const sig = inputs[4]?.value || 'Routine monitoring.';

  const newCard = document.createElement('div');
  newCard.className = 'lab-order-card';
  newCard.innerHTML = `
    <div class="lab-card-top">
      <div class="lab-title-row">
        <span class="lab-icon-box bg-teal"><i class="fa-solid fa-vial"></i></span>
        <div>
          <h4 class="lab-name">${escapeHTML(name)}</h4>
          <span class="loinc-tag">${escapeHTML(code)}</span>
          <span class="lab-status-badge">New Test Order</span>
        </div>
      </div>
      <div class="med-actions">
        <button class="icon-action danger"><i class="fa-regular fa-trash-can"></i></button>
      </div>
    </div>
    <p class="lab-desc">${escapeHTML(sig)}</p>
    <div class="lab-card-footer">
      <span>Priority: <strong>Routine</strong></span>
    </div>
  `;

  parentCard.insertBefore(newCard, draftBox);
}

/* ==========================================================================
   4. Item Removal Handling
   ========================================================================== */
function initItemRemovers() {
  document.addEventListener('click', (e) => {
    const trashBtn = e.target.closest('.icon-action.danger');
    if (trashBtn) {
      const card = trashBtn.closest('.med-order-card, .lab-order-card');
      if (card && confirm('Are you sure you want to remove this order item?')) {
        card.remove();
        updateEncounterSummary();
      }
    }
  });
}

/* ==========================================================================
   5. Clinical Advice Template Insertion
   ========================================================================== */
function initTemplateInserter() {
  const templateBtn = document.querySelector('.btn-template-insert');
  const adviceBox = document.querySelector('.advice-list-box');

  if (templateBtn && adviceBox) {
    templateBtn.addEventListener('click', () => {
      const templateItems = [
        '<strong>Hydration Standard:</strong> Drink at least 3.0 Liters of water daily.',
        '<strong>Migraine Environment Protocol:</strong> Rest in a low-light, noise-controlled environment at symptom onset.',
        '<strong>Dietary Hygiene:</strong> Limit caffeine intake and avoid aged cheeses, nitrates, and artificial preservatives.',
        '<strong>Sleep Routine:</strong> Maintain consistent sleep/wake schedules even on weekends.'
      ];

      adviceBox.innerHTML = templateItems
        .map((item, idx) => `
          <div class="advice-item">
            <span class="item-num">${idx + 1}.</span>
            <p>${item}</p>
          </div>
        `).join('');
    });
  }
}

/* ==========================================================================
   6. Dynamic Encounter Sidebar Summary Synchronization
   ========================================================================== */
function updateEncounterSummary() {
  const activeAcuity = document.querySelector('.acuity-option.active-acuity .acuity-title')?.textContent.trim() || 'Moderate';
  const medCards = document.querySelectorAll('.med-order-card');
  const labCards = document.querySelectorAll('.lab-order-card');

  // Update Summary Stats Box
  const statBoxes = document.querySelectorAll('.summary-stats-grid .stat-box');
  if (statBoxes.length >= 2) {
    // Update Medication Count & Names
    statBoxes[0].querySelector('.st-val').textContent = `${medCards.length} Active`;
    const medNames = Array.from(medCards)
      .map((c) => c.querySelector('.med-name')?.textContent.split(' ')[0])
      .filter(Boolean)
      .join(', ');
    statBoxes[0].querySelector('.st-sub').textContent = medNames || 'None specified';

    // Update Lab Test Count
    statBoxes[1].querySelector('.st-val').textContent = `${labCards.length} Ordered`;
    const labNames = Array.from(labCards)
      .map((c) => c.querySelector('.lab-name')?.textContent.split(' ')[0])
      .filter(Boolean)
      .join(', ');
    statBoxes[1].querySelector('.st-sub').textContent = labNames || 'None specified';
  }

  // Sync Acuity Display
  const accVal = document.querySelector('.acuity-status-box .acc-val');
  if (accVal) {
    accVal.innerHTML = `<i class="fa-solid fa-triangle-exclamation"></i> ${escapeHTML(activeAcuity)} Condition`;
  }
}

/* ==========================================================================
   7. Form Save, Print & Action Handlers
   ========================================================================== */
function initFormActions() {
  const issueBtn = document.querySelector('.btn-save-issue');
  const printBtn = document.querySelector('.btn-print-order');

  if (issueBtn) {
    issueBtn.addEventListener('click', () => {
      alert('Prescription successfully validated, digitally signed, and saved to EMR!');
    });
  }

  if (printBtn) {
    printBtn.addEventListener('click', () => {
      window.print();
    });
  }
}

/* Helper function for XSS prevention */
function escapeHTML(str) {
  return str.replace(/[&<>'"]/g, 
    tag => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;' }[tag] || tag)
  );
}