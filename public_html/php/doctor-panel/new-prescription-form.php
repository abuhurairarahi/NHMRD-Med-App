<?php
// public_html/php/doctor-panel/new-prescription-form.php
// NHMRD - Electronic Prescription Order Entry (e-Rx)

require_once __DIR__ . '/doctor_bootstrap.php';

$patient = getPatientDossier($pdo, $patientId);
$submitMessage = null;

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_prescription') {
    $rxTitle         = trim($_POST['title'] ?? '');
    $chiefComplaint  = trim($_POST['chief_complaint'] ?? '');
    $symptoms        = trim($_POST['symptoms'] ?? '');
    $condition       = trim($_POST['current_condition'] ?? '');
    $visitType       = trim($_POST['visit_type'] ?? 'in_person');
    $doctorStatement = trim($_POST['doctors_statement'] ?? '');
    $nextVisitDate   = !empty($_POST['next_visit_date']) ? $_POST['next_visit_date'] : null;
    $isLongTerm      = isset($_POST['is_long_term']) ? 1 : 0;

    $medNames        = $_POST['med_name'] ?? [];
    $medDoses        = $_POST['med_dose'] ?? [];
    $medFreqs        = $_POST['med_freq'] ?? [];
    $medQuants       = $_POST['med_quantity'] ?? [];
    $medSigs         = $_POST['med_sig'] ?? [];

    $labTests        = $_POST['lab_test_names'] ?? [];

    if (empty($rxTitle)) {
        $rxTitle = "Clinical Encounter Protocol - " . date('M d, Y');
    }

    try {
        $pdo->beginTransaction();

        // 1. Insert Prescription Header
        $stmtRx = $pdo->prepare("
            INSERT INTO prescriptions (
                patient_id, doctor_id, title, chief_complaint, symptoms, 
                current_condition, doctors_statement, next_visit_date, 
                visit_type, status, is_long_term, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', ?, NOW())
        ");
        $stmtRx->execute([
            $patientId,
            $doctorId,
            $rxTitle,
            $chiefComplaint,
            $symptoms,
            $condition,
            $doctorStatement,
            $nextVisitDate,
            $visitType,
            $isLongTerm
        ]);
        $newPrescriptionId = (int)$pdo->lastInsertId();

        // 2. Insert Medications
        if (!empty($medNames) && is_array($medNames)) {
            $stmtMed = $pdo->prepare("
                INSERT INTO prescription_medications (
                    prescription_id, medication_name, dose_strength, route_frequency, dispense_quantity, sig_instructions
                ) VALUES (?, ?, ?, ?, ?, ?)
            ");
            for ($i = 0; $i < count($medNames); $i++) {
                $name = trim($medNames[$i] ?? '');
                if (!empty($name)) {
                    $dose  = trim($medDoses[$i] ?? 'Standard');
                    $freq  = trim($medFreqs[$i] ?? 'Daily');
                    $qty   = trim($medQuants[$i] ?? '1 Pack');
                    $sig   = trim($medSigs[$i] ?? 'As directed');
                    $stmtMed->execute([$newPrescriptionId, $name, $dose, $freq, $qty, $sig]);
                }
            }
        }

        // 3. Insert Lab Tests (if advised)
        if (!empty($labTests) && is_array($labTests)) {
            $stmtLab = $pdo->prepare("
                INSERT INTO prescription_lab_tests (prescription_id, test_name, test_code, instructions)
                VALUES (?, ?, 'LOINC-STD', 'Advised during outpatient clinical encounter')
            ");
            foreach ($labTests as $testName) {
                $tName = trim($testName);
                if (!empty($tName)) {
                    $stmtLab->execute([$newPrescriptionId, $tName]);
                }
            }
        }

        $pdo->commit();
        $submitMessage = [
            'type' => 'success',
            'text' => "Prescription #{$newPrescriptionId} successfully signed, recorded, and transmitted to patient dossier."
        ];
    } catch (Exception $e) {
        $pdo->rollBack();
        $submitMessage = [
            'type' => 'error',
            'text' => "Failed to save prescription: " . $e->getMessage()
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>NHMRD - Electronic Prescription Order Entry (e-Rx)</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="/public_html/assets/css/doctor-panel/new-prescription-form.css">
  <link rel="stylesheet" href="/public_html/assets/css/default-structure.css">
  <link rel="stylesheet" href="/public_html/assets/css/doctor-panel/features/doctor-header.css">
  <style>
    .med-row { display: grid; grid-template-columns: 2fr 1fr 1.5fr 1fr 2fr 40px; gap: 10px; margin-bottom: 10px; align-items: center; }
    .btn-remove-row { background: #fee2e2; color: #dc2626; border: none; border-radius: 6px; height: 38px; cursor: pointer; display: flex; align-items: center; justify-content: center; }
    .btn-add-row { background: #f0fdf4; color: #16a34a; border: 1px dashed #86efac; border-radius: 6px; padding: 8px 16px; cursor: pointer; font-weight: 600; margin-top: 8px; }
  </style>
</head>

<body>

  <div class="app-container">
    <!-- Sidebar Navigation -->
    <aside class="sidebar doctor">
      <div class="logo">
        <i class="fa-solid fa-shield-halved"></i>
        <span>NHMRD</span>
      </div>

      <nav class="nav-menu">
        <a href="dashboard.php" class="nav-item">
          <i class="fa-solid fa-table-cells-large"></i>
          <span>Dashboard</span>
        </a>
        <a href="doctor-clinical-service-records.php" class="nav-item">
          <i class="fa-solid fa-notes-medical"></i>
          <span>Clinical Records</span>
        </a>
        <a href="patient-appointments.php" class="nav-item">
          <i class="fa-solid fa-user-clock"></i>
          <span>Patient Appointments</span>
        </a>
        <a href="doctor-profile.php" class="nav-item">
          <i class="fa-solid fa-user-doctor"></i>
          <span>Doctor Profile</span>
        </a>
      </nav>

      <div class="sidebar-footer">
        <button class="logout-btn" onclick="window.location.href='/public_html/pages/login.html'">
          <i class="fa-solid fa-arrow-right-from-bracket"></i>
          <span>Logout</span>
        </button>
      </div>
    </aside>

    <!-- Main Content Wrapper -->
    <div class="main-wrapper">

      <!-- Top Header Bar -->
      <header class="doc-header">
        <div class="search-bar">
          <i class="fa-solid fa-magnifying-glass"></i>
          <input type="text" placeholder="Search patient MRN, name, vitals...">
        </div>

        <div class="header-right">
          <div class="header-status-pill">
            <i class="fa-regular fa-clock"></i>
            <span>Shift: <?= htmlspecialchars($doctorShift) ?></span>
          </div>
          <div class="header-status-pill">
            <i class="fa-solid fa-rotate"></i>
            <span>EMR Synced</span>
          </div>
          <button class="icon-btn"><i class="fa-regular fa-bell"></i></button>

          <a href="doctor-profile.php" class="doctor-profile-badge" style="text-decoration:none; color:inherit;">
            <div class="doctor-info-text">
              <span class="doctor-name"><?= htmlspecialchars($doctorName) ?></span>
              <span class="doctor-dept"><?= htmlspecialchars($doctorSpecialty) ?></span>
            </div>
            <div class="doctor-avatar">
              <img src="<?= htmlspecialchars($doctorAvatar) ?>" alt="<?= htmlspecialchars($doctorName) ?>">
            </div>
          </a>
        </div>
      </header>

      <!-- Patient Header Strip -->
      <div class="patient-strip">
        <div class="patient-strip-main">
          <img src="https://i.pravatar.cc/100?img=32" alt="<?= htmlspecialchars($patient['full_name']) ?>" class="patient-strip-avatar">
          <div class="patient-strip-details">
            <div class="patient-strip-line1">
              <h2 class="patient-strip-name"><?= htmlspecialchars($patient['full_name']) ?></h2>
              <span class="patient-strip-badge"><?= ucfirst($patient['gender']) ?></span>
              <span class="patient-strip-dob">DOB: <?= htmlspecialchars($patient['dob']) ?> (<?= $patient['age'] ?> yrs)</span>
              <span class="patient-strip-badge-blue">Active Patient</span>
            </div>
            <div class="patient-strip-line2">
              <span>MRN: <strong><?= htmlspecialchars($patient['health_card_no'] ?: '#' . $patient['patient_id']) ?></strong></span>
              <span class="sep">&bull;</span>
              <span>Blood Group: <strong><?= htmlspecialchars($patient['blood_group'] ?? 'A+') ?></strong></span>
              <span class="sep">&bull;</span>
              <span>Attending: <strong><?= htmlspecialchars($doctorName) ?></strong></span>
            </div>
          </div>
        </div>
        <div class="patient-strip-vitals">
          <div class="vital-item">
            <span class="vital-lbl">BP</span>
            <span class="vital-val"><?= htmlspecialchars($patient['blood_pressure'] ?: '120/80') ?></span>
          </div>
          <div class="vital-item">
            <span class="vital-lbl">BMI</span>
            <span class="vital-val"><?= htmlspecialchars($patient['bmi'] ?: '22.8') ?></span>
          </div>
          <div class="vital-item">
            <span class="vital-lbl">Allergies</span>
            <span class="vital-val text-red">
              <?= !empty($patient['allergies']) ? htmlspecialchars($patient['allergies'][0]['allergy_name']) : 'None Recorded' ?>
            </span>
          </div>
        </div>
      </div>

      <!-- Main Content Body -->
      <main class="content-body" style="padding: 24px;">

        <?php if ($submitMessage): ?>
          <div style="padding: 16px 20px; margin-bottom: 24px; border-radius: 8px; font-weight: 600; font-size: 1rem; background: <?= $submitMessage['type'] === 'success' ? '#ecfdf5' : '#fef2f2' ?>; color: <?= $submitMessage['type'] === 'success' ? '#065f46' : '#991b1b' ?>; border: 1px solid <?= $submitMessage['type'] === 'success' ? '#a7f3d0' : '#fecaca' ?>;">
            <i class="fa-solid <?= $submitMessage['type'] === 'success' ? 'fa-circle-check' : 'fa-triangle-exclamation' ?>"></i>
            <?= htmlspecialchars($submitMessage['text']) ?>
            <?php if ($submitMessage['type'] === 'success'): ?>
              <div style="margin-top: 10px;">
                <a href="patient-prescription-records.php?patient_id=<?= (int)$patientId ?>" style="color: #059669; text-decoration: underline;">
                  &rarr; View Patient Prescription Records
                </a>
              </div>
            <?php endif; ?>
          </div>
        <?php endif; ?>

        <!-- e-Rx Order Entry Form -->
        <form method="POST" action="new-prescription-form.php?patient_id=<?= (int)$patientId ?>">
          <input type="hidden" name="action" value="save_prescription">

          <!-- Section 1: Clinical Encounter Overview -->
          <div class="rx-card" style="background:#fff; border-radius:8px; padding:24px; border:1px solid #e2e8f0; margin-bottom:24px;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:18px;">
              <h3 style="font-size:1.15rem; color:#1e293b; display:flex; align-items:center; gap:8px;">
                <i class="fa-solid fa-file-prescription text-emerald"></i> 1. Clinical Diagnosis &amp; Encounter Details
              </h3>
              <label style="display:flex; align-items:center; gap:8px; font-size:0.9rem; font-weight:600; color:#475569; cursor:pointer;">
                <input type="checkbox" name="is_long_term" value="1">
                Mark as Chronic Protocol (Long-Term)
              </label>
            </div>

            <div style="display:grid; grid-template-columns: 2fr 1fr; gap:16px; margin-bottom:16px;">
              <div>
                <label style="display:block; font-size:0.85rem; font-weight:600; color:#475569; margin-bottom:6px;">Prescription Title / Primary Diagnosis</label>
                <input type="text" name="title" value="Hypertension & Metabolic Titration Protocol" style="width:100%; padding:10px; border:1px solid #cbd5e1; border-radius:6px; font-size:0.95rem;" required>
              </div>
              <div>
                <label style="display:block; font-size:0.85rem; font-weight:600; color:#475569; margin-bottom:6px;">Visit Type</label>
                <select name="visit_type" style="width:100%; padding:10px; border:1px solid #cbd5e1; border-radius:6px; font-size:0.95rem;">
                  <option value="in_person" selected>In-Person Consultation</option>
                  <option value="telehealth">Telehealth / Remote Review</option>
                </select>
              </div>
            </div>

            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:16px;">
              <div>
                <label style="display:block; font-size:0.85rem; font-weight:600; color:#475569; margin-bottom:6px;">Chief Complaints &amp; History</label>
                <textarea name="chief_complaint" rows="3" style="width:100%; padding:10px; border:1px solid #cbd5e1; border-radius:6px; font-size:0.9rem;">Occasional morning occipital headache, persistent fatigue, routine blood pressure review.</textarea>
              </div>
              <div>
                <label style="display:block; font-size:0.85rem; font-weight:600; color:#475569; margin-bottom:6px;">Current Clinical Assessment &amp; Symptoms</label>
                <textarea name="current_condition" rows="3" style="width:100%; padding:10px; border:1px solid #cbd5e1; border-radius:6px; font-size:0.9rem;">Mild pedal edema noted. Blood pressure slightly elevated above target (140/88 mmHg).</textarea>
              </div>
            </div>
          </div>

          <!-- Section 2: Medication Order Entry -->
          <div class="rx-card" style="background:#fff; border-radius:8px; padding:24px; border:1px solid #e2e8f0; margin-bottom:24px;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:18px;">
              <h3 style="font-size:1.15rem; color:#1e293b; display:flex; align-items:center; gap:8px;">
                <i class="fa-solid fa-pills text-emerald"></i> 2. Medication Orders (Formulary Titration)
              </h3>
            </div>

            <div id="medicationContainer">
              <!-- Default Med Row 1 -->
              <div class="med-row">
                <div>
                  <label style="font-size:0.75rem; font-weight:600; color:#64748b;">MEDICATION NAME (GENERIC/BRAND)</label>
                  <input type="text" name="med_name[]" value="Metformin Hydrochloride" style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:6px;" required>
                </div>
                <div>
                  <label style="font-size:0.75rem; font-weight:600; color:#64748b;">DOSE / STRENGTH</label>
                  <input type="text" name="med_dose[]" value="1000 mg" style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:6px;">
                </div>
                <div>
                  <label style="font-size:0.75rem; font-weight:600; color:#64748b;">ROUTE &amp; FREQUENCY</label>
                  <input type="text" name="med_freq[]" value="Oral (PO) - Twice Daily (BID)" style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:6px;">
                </div>
                <div>
                  <label style="font-size:0.75rem; font-weight:600; color:#64748b;">QUANTITY</label>
                  <input type="text" name="med_quantity[]" value="60 Tablets" style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:6px;">
                </div>
                <div>
                  <label style="font-size:0.75rem; font-weight:600; color:#64748b;">SIG / SPECIAL INSTRUCTIONS</label>
                  <input type="text" name="med_sig[]" value="Take with breakfast and dinner" style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:6px;">
                </div>
                <div>
                  <label>&nbsp;</label>
                  <button type="button" class="btn-remove-row" onclick="removeMedRow(this)" title="Remove item"><i class="fa-solid fa-trash-can"></i></button>
                </div>
              </div>

              <!-- Default Med Row 2 -->
              <div class="med-row">
                <div>
                  <input type="text" name="med_name[]" value="Telmisartan" style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:6px;">
                </div>
                <div>
                  <input type="text" name="med_dose[]" value="40 mg" style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:6px;">
                </div>
                <div>
                  <input type="text" name="med_freq[]" value="Oral (PO) - Once Daily (OD)" style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:6px;">
                </div>
                <div>
                  <input type="text" name="med_quantity[]" value="30 Tablets" style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:6px;">
                </div>
                <div>
                  <input type="text" name="med_sig[]" value="Take morning with water" style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:6px;">
                </div>
                <div>
                  <button type="button" class="btn-remove-row" onclick="removeMedRow(this)"><i class="fa-solid fa-trash-can"></i></button>
                </div>
              </div>
            </div>

            <button type="button" class="btn-add-row" onclick="addMedRow()">
              <i class="fa-solid fa-plus"></i> Add Another Medication
            </button>
          </div>

          <!-- Section 3: Advised Diagnostic Lab Tests -->
          <div class="rx-card" style="background:#fff; border-radius:8px; padding:24px; border:1px solid #e2e8f0; margin-bottom:24px;">
            <h3 style="font-size:1.15rem; color:#1e293b; margin-bottom:16px; display:flex; align-items:center; gap:8px;">
              <i class="fa-solid fa-vial text-emerald"></i> 3. Advised Diagnostic &amp; Pathology Tests
            </h3>
            <p style="font-size:0.85rem; color:#64748b; margin-bottom:14px;">Select recommended lab investigations for follow-up:</p>

            <div style="display:grid; grid-template-columns: repeat(4, 1fr); gap:12px;">
              <label style="display:flex; align-items:center; gap:8px; font-size:0.9rem; cursor:pointer;">
                <input type="checkbox" name="lab_test_names[]" value="Complete Blood Count (CBC)">
                <span>CBC with ESR</span>
              </label>
              <label style="display:flex; align-items:center; gap:8px; font-size:0.9rem; cursor:pointer;">
                <input type="checkbox" name="lab_test_names[]" value="Serum Creatinine & eGFR" checked>
                <span>Serum Creatinine &amp; eGFR</span>
              </label>
              <label style="display:flex; align-items:center; gap:8px; font-size:0.9rem; cursor:pointer;">
                <input type="checkbox" name="lab_test_names[]" value="HbA1c Glycated Hemoglobin" checked>
                <span>HbA1c</span>
              </label>
              <label style="display:flex; align-items:center; gap:8px; font-size:0.9rem; cursor:pointer;">
                <input type="checkbox" name="lab_test_names[]" value="Lipid Profile (Fasting)">
                <span>Lipid Profile</span>
              </label>
              <label style="display:flex; align-items:center; gap:8px; font-size:0.9rem; cursor:pointer;">
                <input type="checkbox" name="lab_test_names[]" value="Serum Electrolytes (Na+, K+, Cl-)">
                <span>Electrolytes Panel</span>
              </label>
              <label style="display:flex; align-items:center; gap:8px; font-size:0.9rem; cursor:pointer;">
                <input type="checkbox" name="lab_test_names[]" value="12-Lead Electrocardiogram (ECG)">
                <span>12-Lead ECG</span>
              </label>
              <label style="display:flex; align-items:center; gap:8px; font-size:0.9rem; cursor:pointer;">
                <input type="checkbox" name="lab_test_names[]" value="Chest Radiograph (CXR PA View)">
                <span>Chest X-Ray</span>
              </label>
              <label style="display:flex; align-items:center; gap:8px; font-size:0.9rem; cursor:pointer;">
                <input type="checkbox" name="lab_test_names[]" value="Urine Routine Examination (R/M/E)">
                <span>Urine R/M/E</span>
              </label>
            </div>
          </div>

          <!-- Section 4: Clinical Advice & Follow-Up -->
          <div class="rx-card" style="background:#fff; border-radius:8px; padding:24px; border:1px solid #e2e8f0; margin-bottom:24px;">
            <h3 style="font-size:1.15rem; color:#1e293b; margin-bottom:16px; display:flex; align-items:center; gap:8px;">
              <i class="fa-solid fa-notes-medical text-emerald"></i> 4. Clinical Advice &amp; Follow-up Schedule
            </h3>

            <div style="display:grid; grid-template-columns: 2fr 1fr; gap:16px;">
              <div>
                <label style="display:block; font-size:0.85rem; font-weight:600; color:#475569; margin-bottom:6px;">Doctor's Advice &amp; Lifestyle Modifications</label>
                <textarea name="doctors_statement" rows="3" style="width:100%; padding:10px; border:1px solid #cbd5e1; border-radius:6px; font-size:0.9rem;">Maintain low-sodium diet (&lt;2g/day). 30 minutes brisk walking 5 days a week. Record home BP diary twice weekly.</textarea>
              </div>
              <div>
                <label style="display:block; font-size:0.85rem; font-weight:600; color:#475569; margin-bottom:6px;">Next Clinical Visit Date</label>
                <input type="date" name="next_visit_date" value="<?= date('Y-m-d', strtotime('+30 days')) ?>" style="width:100%; padding:10px; border:1px solid #cbd5e1; border-radius:6px; font-size:0.95rem;">
                <span style="display:block; font-size:0.75rem; color:#64748b; margin-top:4px;">Default: 30 days review</span>
              </div>
            </div>
          </div>

          <!-- Action Buttons -->
          <div style="display:flex; justify-content:space-between; align-items:center;">
            <a href="patient-medical-profile.php?patient_id=<?= (int)$patientId ?>" style="padding:12px 24px; background:#f1f5f9; color:#475569; text-decoration:none; border-radius:6px; font-weight:600;">
              &larr; Back to Patient Profile
            </a>
            <div style="display:flex; gap:12px;">
              <button type="reset" style="padding:12px 24px; background:#fff; border:1px solid #cbd5e1; border-radius:6px; font-weight:600; cursor:pointer;">
                Discard Changes
              </button>
              <button type="submit" style="padding:12px 28px; background:#059669; color:#fff; border:none; border-radius:6px; font-weight:700; font-size:1rem; cursor:pointer; display:flex; align-items:center; gap:8px;">
                <i class="fa-solid fa-signature"></i> Sign &amp; Transmit e-Prescription
              </button>
            </div>
          </div>

        </form>

      </main>
    </div>
  </div>

  <script>
    function addMedRow() {
      const container = document.getElementById('medicationContainer');
      const div = document.createElement('div');
      div.className = 'med-row';
      div.innerHTML = `
        <div><input type="text" name="med_name[]" placeholder="Drug name" style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:6px;" required></div>
        <div><input type="text" name="med_dose[]" placeholder="Dose (e.g. 500mg)" style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:6px;"></div>
        <div><input type="text" name="med_freq[]" placeholder="Frequency" style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:6px;"></div>
        <div><input type="text" name="med_quantity[]" placeholder="Qty" style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:6px;"></div>
        <div><input type="text" name="med_sig[]" placeholder="Instructions" style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:6px;"></div>
        <div><button type="button" class="btn-remove-row" onclick="removeMedRow(this)"><i class="fa-solid fa-trash-can"></i></button></div>
      `;
      container.appendChild(div);
    }
    function removeMedRow(btn) {
      const rows = document.querySelectorAll('.med-row');
      if (rows.length > 1) {
        btn.closest('.med-row').remove();
      } else {
        alert('Prescription must have at least one medication order.');
      }
    }
  </script>
</body>
</html>
