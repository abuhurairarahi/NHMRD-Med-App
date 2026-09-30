
    /* 1. HEADER & SEARCH */
    function handleGlobalSearch(event) {
      if (event.key === 'Enter') {
        const searchInput = document.getElementById('global-search-input');
        const query = searchInput ? searchInput.value.trim() : '';
        if (query) {
          console.log(`[EMR Search] Executing query: ${query}`);
          alert(`Searching EMR Database for: "${query}"`);
        }
      }
    }

    function showNotifications() {
      alert('System Notifications:\n• 3 Critical Diagnostic Alerts\n• 4 RX Reviews Pending');
    }

    /* 2. COMMAND BANNER */
    function callTriage() {
      if (confirm('Initiate emergency audio link with Station 04 Triage Desk?')) {
        console.log('[Triage] Link established with Station 04.');
        alert('Connecting to Station 04 Triage Desk...');
      }
    }

    function startNewEncounter() {
      const mrn = prompt('Enter Patient MRN');
      if (mrn) {
        alert(`New encounter initialized for MRN: ${mrn}`);
      }
    }

    /* 3. PATIENT QUEUE & ENCOUNTER ROSTER */
    function viewPatientRecords(button) {
      const queueItem = button.closest('.queue-item');
      const patientName = queueItem?.querySelector('.patient-name')?.textContent || 'Patient';
      alert(`Loading full clinical records for: ${patientName}`);
    }

    function openActiveChart(button) {
      const queueItem = button.closest('.queue-item');
      const patientName = queueItem?.querySelector('.patient-name')?.textContent || 'Patient';
      alert(`Opening active charting session for: ${patientName}`);
    }

    function openScheduleFilter() {
      alert('Opening Schedule & Roster Filter Panel...');
    }

    /* 4. DIAGNOSTIC & LAB TELEMETRY */
    function pageCardiology(button) {
      const isConfirmed = confirm(
        'STAT ALERT: Page On-Call Cardiology Attending for Julian Barnes (MRN-7892-C)?\n\n' +
        'Observed Troponin I: 0.18 ng/mL (Ref: < 0.04 ng/mL)'
      );

      if (isConfirmed && button) {
        button.disabled = true;
        button.textContent = 'Paged...';
        alert('STAT Page transmitted to Cardiology Attending.');
      }
    }

    /* 5. QUICK PRESCRIBER & TITRATION */
    function clearPrescriber() {
      const medInput = document.getElementById('prescriber-med-input');
      if (medInput) {
        medInput.value = '';
        medInput.focus();
      }
    }

    function queuePrescription() {
      const medInput = document.getElementById('prescriber-med-input');
      const dosageBox = document.getElementById('dosage-form-select');
      const frequencyBox = document.getElementById('frequency-select');

      const medication = medInput?.value.trim();
      const dosage = dosageBox?.textContent || 'Unspecified';
      const frequency = frequencyBox?.textContent || 'Unspecified';

      if (!medication) {
        alert('Please specify a medication name.');
        return;
      }

      alert(
        `Prescription Queued:\n\n` +
        `• Drug: ${medication}\n` +
        `• Dosage: ${dosage}\n` +
        `• Frequency: ${frequency}\n` +
        `• Safety Check: Cleared (eGFR 72 mL/min)`
      );
    }

    /* 6. USER LOGOUT */
    function handleLogout() {
      if (confirm('Are you sure you want to log out of the NHMRD Clinical Panel?')) {
        console.log('[Auth] User logged out.');
        // window.location.href = '/login.html';
        alert('Session terminated.');
      }
    }
