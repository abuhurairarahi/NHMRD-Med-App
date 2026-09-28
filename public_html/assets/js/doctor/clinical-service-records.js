// 1. Universal Table Row Click Handler
    function loadPatientDetails(row) {
      if (!row) return;

      // Update active selection UI
      const tbody = row.closest('tbody');
      if (tbody) {
        tbody.querySelectorAll('tr').forEach(r => r.classList.remove('selected-row'));
      }
      row.classList.add('selected-row');

      // Live DOM extraction
      const cells = row.querySelectorAll('td');
      if (cells.length < 5) return;

      const nameElem = cells[0].querySelector('strong');
      const mrnElem = cells[0].querySelector('.mrn-code');
      const avatarElem = cells[0].querySelector('.avatar-circle');

      const patientName = nameElem ? nameElem.textContent.trim() : 'Unknown Patient';
      const mrn = mrnElem ? mrnElem.textContent.trim() : 'N/A';
      const initials = avatarElem ? avatarElem.textContent.trim() : 'P';
      const avatarBgClass = avatarElem ? avatarElem.className : 'avatar-circle green-bg';

      const rawDemoText = cells[1].innerText.trim();
      const demoParts = rawDemoText.split('\n');
      const ageText = demoParts[0] ? demoParts[0].replace('yrs', 'y').trim() : '';
      const genderText = demoParts[1] ? demoParts[1].trim() : '';

      const diagnosisElem = cells[2].querySelector('strong');
      const icdElem = cells[2].querySelector('.icd-tag');
      const diagnosis = diagnosisElem ? diagnosisElem.textContent.trim() : '';
      const icdCode = icdElem ? icdElem.textContent.trim() : '';

      const regimenRaw = cells[3].innerText.trim().split('\n');
      const medications = regimenRaw.filter(med => med.trim() !== '');

      const bpElem = cells[4].querySelector('strong');
      const bpValue = bpElem ? bpElem.textContent.trim() : 'N/A';
      const vitalsText = cells[4].innerText.trim().replace(/\n/g, ' ');

      // Populate Panel Header
      const panel = document.querySelector('.patient-detail-panel');
      if (!panel) return;

      const panelHeader = panel.querySelector('.detail-header-card');
      if (panelHeader) {
        const avatarWrap = panelHeader.querySelector('.patient-avatar-wrap');
        if (avatarWrap) {
          avatarWrap.innerHTML = `<div class="${avatarBgClass}" style="width:52px; height:52px; font-size:1.2rem; display:flex; align-items:center; justify-content:center; border-radius:50%; font-weight:bold; color:#fff;">${initials}</div>`;
        }
        const nameHeader = panelHeader.querySelector('.patient-title-row h3');
        if (nameHeader) nameHeader.textContent = patientName;

        const mrnSpan = panelHeader.querySelector('.mrn-code');
        if (mrnSpan) mrnSpan.textContent = `${mrn} • ${ageText} (${genderText})`;
      }

      // Populate Active Pharmacotherapy
      const medList = panel.querySelector('.med-cards-list');
      if (medList) {
        medList.innerHTML = medications.map(med => `
          <div class="med-item mini-item">
            <div class="med-info">
              <strong class="med-title">${med}</strong>
              <div class="med-details">Prescribed Active Dose</div>
            </div>
            <div class="med-status align-right">
              <span class="badge-pill green-bg">Active</span>
            </div>
          </div>
        `).join('');
      }

      // Populate Diagnostic Labs
      const labGrid = panel.querySelector('.lab-grid');
      if (labGrid) {
        labGrid.innerHTML = `
          <div class="lab-mini-card">
            <span class="vital-title">BLOOD PRESSURE</span>
            <span class="lab-value">${bpValue}</span>
            <span class="vital-note">mmHg</span>
          </div>
          <div class="lab-mini-card">
            <span class="vital-title">PRIMARY DIAGNOSIS</span>
            <span class="lab-value" style="font-size: 0.9rem;">${icdCode}</span>
            <span class="vital-note">${diagnosis}</span>
          </div>
        `;
      }

      // Populate Clinical SOAP Note
      const soapBlocks = panel.querySelectorAll('.soap-block p');
      if (soapBlocks.length >= 4) {
        soapBlocks[0].textContent = `Patient ${patientName} (${mrn}) presents for routine evaluation of ${diagnosis}. Currently managed on active oral regimen.`;
        soapBlocks[1].textContent = `Latest Vitals Recorded: ${vitalsText}.`;
        soapBlocks[2].textContent = `${diagnosis} (${icdCode}) currently displaying steady progress under active management.`;
        soapBlocks[3].textContent = `Continue active regimen: ${medications.join(', ')}. Schedule regular surveillance.`;
      }
    }

    // 2. Action Buttons Handlers
    function handleLogout() {
      if (confirm('Are you sure you want to log out of the EMR system?')) {
        alert('Logging out...');
      }
    }

    function openNotifications() {
      alert('Notifications:\n- 3 critical lab results awaiting review.\n- 1 prescription approval pending.');
    }

    function batchRxRenewal() {
      alert('Batch Rx Renewal initiated for selected roster patients.');
    }

    function exportClinicalSummary() {
      alert('Generating HL7/PDF Clinical Summary report for export...');
    }

    // 3. Dynamic Filter & Search Handlers
    function filterTable(query) {
      const q = query.toLowerCase().trim();
      const rows = document.querySelectorAll('.roster-table tbody tr');
      let visibleCount = 0;

      rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        if (q === '' || text.includes(q)) {
          row.style.display = '';
          visibleCount++;
        } else {
          row.style.display = 'none';
        }
      });

      const counterBadge = document.querySelector('.counter-badge');
      if (counterBadge) {
        counterBadge.textContent = `${visibleCount} CASES DISPLAYED`;
      }
    }

    function selectFilterTab(tabBtn, categoryKeyword) {
      const filterTabs = document.querySelectorAll('.module-tabs .tab-btn');
      filterTabs.forEach(t => t.classList.remove('active'));
      tabBtn.classList.add('active');
      filterTable(categoryKeyword);
    }

    // 4. Pagination & Append Note Handlers
    function switchPage(btn) {
      const paginationBtns = document.querySelectorAll('.pagination-buttons button');
      paginationBtns.forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
    }

    function nextPage() {
      alert('Navigating to next page of patient records...');
    }

    function appendFollowUpNote() {
      const newNote = prompt('Enter follow-up note to append to current clinical record:');
      if (newNote && newNote.trim() !== '') {
        const soapBox = document.querySelector('.soap-content-box');
        if (soapBox) {
          const appendedBlock = document.createElement('div');
          appendedBlock.className = 'soap-block';
          appendedBlock.innerHTML = `<strong>ADDENDUM (${new Date().toLocaleDateString()}):</strong><p>${newNote}</p>`;
          soapBox.appendChild(appendedBlock);
          alert('Follow-up note appended successfully.');
        }
      }
    }