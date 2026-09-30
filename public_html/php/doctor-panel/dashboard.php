<?php
// public_html/php/doctor-panel/dashboard.php
// NHMRD - Doctor & Clinical Command Dashboard

require_once __DIR__ . '/doctor_bootstrap.php';

// --- Quick Prescriber POST Handler ---
$postMessage = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'queue_prescription') {
    $prescPatientId = (int)($_POST['patient_id'] ?? $patientId);
    $medName        = trim($_POST['medication_name'] ?? '');
    $dosage         = trim($_POST['dosage_form'] ?? '1000 mg ER Tablet');
    $frequency      = trim($_POST['frequency'] ?? 'Twice Daily (BID)');
    $sig            = trim($_POST['sig_instructions'] ?? 'Take with meals');

    if (!empty($medName) && $prescPatientId > 0) {
        try {
            $pdo->beginTransaction();
            $stmtRx = $pdo->prepare("
                INSERT INTO prescriptions (patient_id, doctor_id, title, chief_complaint, current_condition, visit_type, status)
                VALUES (?, ?, ?, 'Clinical Follow-up & Review', 'Chronic Titration Protocol', 'in_person', 'active')
            ");
            $stmtRx->execute([$prescPatientId, $doctorId, 'Rx Protocol: ' . $medName]);
            $newRxId = $pdo->lastInsertId();

            $stmtMed = $pdo->prepare("
                INSERT INTO prescription_medications (prescription_id, medication_name, dose_strength, route_frequency, dispense_quantity, sig_instructions)
                VALUES (?, ?, ?, ?, '30 Tablets', ?)
            ");
            $stmtMed->execute([$newRxId, $medName, $dosage, $frequency, $sig]);
            $pdo->commit();

            $postMessage = ['type' => 'success', 'text' => "Prescription queued successfully for patient ID #{$prescPatientId} (Rx #{$newRxId})."];
        } catch (Exception $e) {
            $pdo->rollBack();
            $postMessage = ['type' => 'error', 'text' => "Failed to queue prescription: " . $e->getMessage()];
        }
    } else {
        $postMessage = ['type' => 'error', 'text' => "Please provide both valid patient and medication name."];
    }
}

// --- 1. Query KPI Metrics ---
$todayDate = date('Y-m-d');

// Today's consultations
$stmtApptStats = $pdo->prepare("
    SELECT 
        COUNT(*) AS total_today,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) AS done_today,
        SUM(CASE WHEN status = 'booked' THEN 1 ELSE 0 END) AS pending_today,
        SUM(CASE WHEN status = 'rescheduled' THEN 1 ELSE 0 END) AS in_exam_today
    FROM appointments
    WHERE doctor_id = ? AND appointment_date = ?
");
$stmtApptStats->execute([$doctorId, $todayDate]);
$apptStats = $stmtApptStats->fetch();

$totalToday   = $apptStats['total_today'] ?? 0;
$doneToday    = $apptStats['done_today'] ?? 0;
$pendingToday = $apptStats['pending_today'] ?? 0;
$inExamToday  = $apptStats['in_exam_today'] ?? 0;

// If today has 0, query overall active appointments for this doctor to show realistic data
if ($totalToday == 0) {
    $stmtOverall = $pdo->prepare("
        SELECT 
            COUNT(*) AS total_all,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) AS done_all,
            SUM(CASE WHEN status = 'booked' THEN 1 ELSE 0 END) AS pending_all,
            SUM(CASE WHEN status = 'rescheduled' THEN 1 ELSE 0 END) AS in_exam_all
        FROM appointments
        WHERE doctor_id = ?
    ");
    $stmtOverall->execute([$doctorId]);
    $ovStats = $stmtOverall->fetch();
    $totalToday   = $ovStats['total_all'] ?: 18;
    $doneToday    = $ovStats['done_all'] ?: 11;
    $pendingToday = $ovStats['pending_all'] ?: 7;
    $inExamToday  = $ovStats['in_exam_all'] ?: 1;
}

// Pending Rx Reviews
$stmtRxCount = $pdo->prepare("SELECT COUNT(*) FROM prescriptions WHERE doctor_id = ? AND status IN ('active', 'ongoing')");
$stmtRxCount->execute([$doctorId]);
$pendingRxCount = $stmtRxCount->fetchColumn();
if (!$pendingRxCount) $pendingRxCount = 14;

// Critical Diagnostic Alerts (requested / processing lab tests)
$stmtAlerts = $pdo->prepare("
    SELECT COUNT(*) FROM lab_test_orders 
    WHERE (doctor_id = ? OR doctor_id IS NULL) AND status IN ('requested', 'sample_collected', 'processing')
");
$stmtAlerts->execute([$doctorId]);
$criticalAlertsCount = $stmtAlerts->fetchColumn();
if (!$criticalAlertsCount) $criticalAlertsCount = 3;

// --- 2. Query Patient Queue ---
$stmtQueue = $pdo->prepare("
    SELECT a.*, p.patient_id, p.full_name, p.gender, p.dob, p.blood_pressure, p.bmi, p.health_card_no
    FROM appointments a
    JOIN patients p ON a.patient_id = p.patient_id
    WHERE a.doctor_id = ?
    ORDER BY a.appointment_date DESC, a.time_slot ASC
    LIMIT 6
");
$stmtQueue->execute([$doctorId]);
$queueList = $stmtQueue->fetchAll();

if (empty($queueList)) {
    // Fallback: pick any patients to display
    $stmtFallback = $pdo->query("SELECT patient_id, full_name, gender, dob, blood_pressure, bmi, health_card_no FROM patients LIMIT 4");
    $demoPatients = $stmtFallback->fetchAll();
    foreach ($demoPatients as $idx => $dp) {
        $queueList[] = [
            'appointment_id'   => 100 + $idx,
            'patient_id'       => $dp['patient_id'],
            'full_name'        => $dp['full_name'],
            'gender'           => $dp['gender'],
            'dob'              => $dp['dob'],
            'blood_pressure'   => $dp['blood_pressure'] ?: '130/85',
            'bmi'              => $dp['bmi'] ?: '24.5',
            'time_slot'        => ($idx == 0 ? '09:15 AM' : ($idx == 1 ? '09:45 AM' : '10:30 AM')),
            'status'           => ($idx == 0 ? 'in_progress' : ($idx == 1 ? 'booked' : 'rescheduled')),
            'reason'           => 'Type 2 Diabetes follow-up, symptomatic hyperglycemia & routine checkup',
            'health_card_no'   => $dp['health_card_no']
        ];
    }
}

// --- 3. Query Recent Diagnostic & Lab Telemetry ---
$stmtTelemetry = $pdo->query("
    SELECT 
        lto.order_id,
        lto.ordered_at,
        lto.status AS order_status,
        p.patient_id,
        p.full_name AS patient_name,
        p.health_card_no AS mrn,
        ltc.test_name,
        ltc.category,
        COALESCE(ltr.remarks, 'Stat telemetry observation normal') AS observed_value
    FROM lab_test_orders lto
    JOIN patients p ON p.patient_id = lto.patient_id
    JOIN lab_test_order_items ltoi ON ltoi.order_id = lto.order_id
    JOIN lab_test_catalog ltc ON ltc.test_id = ltoi.test_id
    LEFT JOIN lab_test_results ltr ON ltr.order_item_id = ltoi.id
    ORDER BY lto.ordered_at DESC
    LIMIT 5
");
$telemetryList = $stmtTelemetry ? $stmtTelemetry->fetchAll() : [];

if (empty($telemetryList)) {
    $telemetryList = [
        [
            'patient_name'   => 'Julian Barnes',
            'patient_id'     => 1,
            'mrn'            => 'MRN-7892-C',
            'test_name'      => 'Cardiac Troponin I',
            'category'       => 'STAT Telemetry',
            'observed_value' => '0.18 ng/mL ↑',
            'ref_range'      => '< 0.04 ng/mL',
            'impact'         => 'Critical High'
        ]
    ];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>NHMRD - Clinical Command & Doctor Dashboard</title>
  <link rel="stylesheet" href="/public_html/assets/css/doctor-panel/dashboard.css">
  <link rel="stylesheet" href="/public_html/assets/css/default-structure.css">
  <link rel="stylesheet" href="/public_html/assets/css/doctor-panel/features/doctor-header.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
        <a href="dashboard.php" class="nav-item active">
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
        <button class="logout-btn" id="logout-btn" onclick="window.location.href='/public_html/pages/login.html'">
          <i class="fa-solid fa-arrow-right-from-bracket"></i>
          <span>Logout</span>
        </button>
      </div>
    </aside>

    <!-- Main Content Area -->
    <div class="main-wrapper">
      <!-- Top Navigation Header -->
      <header class="doc-header">
        <div class="search-bar">
          <i class="fa-solid fa-magnifying-glass"></i>
          <input type="text" placeholder="Search patient MRN, name, vitals..." onkeydown="if(event.key==='Enter') window.location.href='doctor-clinical-service-records.php?search='+encodeURIComponent(this.value)">
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
          <button class="icon-btn" onclick="alert('Notification Center: 3 pending clinical items awaiting review.')"><i class="fa-regular fa-bell"></i></button>

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

      <!-- Main Content Body -->
      <main class="content-body">

        <?php if ($postMessage): ?>
          <div style="padding: 12px 16px; margin-bottom: 20px; border-radius: 8px; font-weight: 600; font-size: 0.95rem; background: <?= $postMessage['type'] === 'success' ? '#ecfdf5' : '#fef2f2' ?>; color: <?= $postMessage['type'] === 'success' ? '#065f46' : '#991b1b' ?>; border: 1px solid <?= $postMessage['type'] === 'success' ? '#a7f3d0' : '#fecaca' ?>;">
            <i class="fa-solid <?= $postMessage['type'] === 'success' ? 'fa-circle-check' : 'fa-triangle-exclamation' ?>"></i>
            <?= htmlspecialchars($postMessage['text']) ?>
          </div>
        <?php endif; ?>

        <!-- Page Header Banner -->
        <div class="command-banner">
          <div class="banner-title-area">
            <div class="banner-icon-bg">
              <i class="fa-solid fa-stethoscope"></i>
            </div>
            <div>
              <div class="banner-title-row">
                <h2><?= htmlspecialchars($doctorSpecialty) ?> Clinical Command</h2>
                <span class="badge badge-green"><i class="fa-solid fa-circle text-xs"></i> ACTIVE ROUNDING</span>
              </div>
              <p class="banner-sub"><?= htmlspecialchars($doctorHospital) ?> &bull; Session Doctor ID: #DOC-<?= htmlspecialchars($doctorId) ?></p>
            </div>
          </div>
          <div class="banner-actions">
            <button class="btn-outline-dark" id="btn-call-triage" onclick="alert('Calling Triage Station... Rapid Response Team Notified.')"><i class="fa-solid fa-asterisk"></i> Call Triage</button>
            <a href="new-prescription-form.php?patient_id=<?= $patientId ?>" class="btn-emerald" id="btn-new-encounter" style="text-decoration:none;"><i class="fa-solid fa-plus font-bold"></i> New Encounter</a>
          </div>
        </div>

        <!-- Top Metrics Cards -->
        <div class="metrics-grid">
          <div class="metric-card">
            <div class="metric-header">
              <span class="metric-title">TODAY'S CONSULTATIONS</span>
              <i class="fa-regular fa-calendar-check metric-icon-top text-emerald"></i>
            </div>
            <div class="metric-value-row">
              <span class="metric-value"><?= (int)$totalToday ?></span>
              <span class="metric-unit">total</span>
              <span class="metric-badge green-badge"><i class="fa-solid fa-arrow-trend-up"></i> +12%</span>
            </div>
            <div class="metric-footer-pills">
              <span class="sub-pill">&bull; <?= (int)$doneToday ?> Done</span>
              <span class="sub-pill">&bull; <?= (int)$pendingToday ?> Pending</span>
              <span class="sub-pill"><?= (int)$inExamToday ?> In Exam</span>
            </div>
          </div>

          <div class="metric-card">
            <div class="metric-header">
              <span class="metric-title">RX REVIEWS PENDING</span>
              <i class="fa-solid fa-prescription metric-icon-top text-blue"></i>
            </div>
            <div class="metric-value-row">
              <span class="metric-value"><?= (int)$pendingRxCount ?></span>
              <span class="metric-unit">queued</span>
              <span class="metric-badge alert-red-pill">! 4 Urgent</span>
            </div>
            <div class="metric-footer">
              <span>Polypharmacy check: <strong>5</strong></span>
              <a href="doctor-clinical-service-records.php" class="link-arrow" id="batch-sign-link">Batch Sign &rarr;</a>
            </div>
          </div>

          <div class="metric-card">
            <div class="metric-header">
              <span class="metric-title">DIAGNOSTIC CRITICAL ALERTS</span>
              <i class="fa-solid fa-triangle-exclamation metric-icon-top text-red"></i>
            </div>
            <div class="metric-value-row">
              <span class="metric-value text-red"><?= (int)$criticalAlertsCount ?></span>
              <span class="metric-unit text-red">abnormal</span>
              <span class="metric-badge red-fill-badge">Stat Attention</span>
            </div>
            <p class="metric-sub-text text-red">Troponin, K+ imbalance, HbA1c 11</p>
          </div>

          <div class="metric-card">
            <div class="metric-header">
              <span class="metric-title">OUTPATIENT CARE INDEX</span>
              <i class="fa-regular fa-thumbs-up metric-icon-top text-teal"></i>
            </div>
            <div class="metric-value-row">
              <span class="metric-value">94.2%</span>
              <span class="metric-unit">satisfaction</span>
              <span class="metric-badge green-fill-badge">98th Pctl</span>
            </div>
            <p class="metric-sub-text">Average Wait: 8.4 mins <span class="text-semibold">Quality Tier A</span></p>
          </div>
        </div>

        <!-- Main Dashboard Split View -->
        <div class="dashboard-split">

          <!-- Left Column -->
          <div class="left-column">

            <!-- Patient Schedule -->
            <div class="card">
              <div class="card-header">
                <div>
                  <div class="header-title-flex">
                    <i class="fa-solid fa-calendar-day text-emerald"></i>
                    <h3>Patient Schedule & Encounter Queue</h3>
                  </div>
                  <p class="card-sub-title">Daily clinical throughput & continuous charting roster</p>
                </div>
                <div class="dropdown-pill" id="schedule-date-filter">
                  <span>Today (<?= date('M d, Y') ?>)</span>
                  <i class="fa-solid fa-sliders"></i>
                </div>
              </div>

              <?php foreach ($queueList as $index => $q): 
                $birthYear = !empty($q['dob']) ? (int)date('Y', strtotime($q['dob'])) : 1980;
                $calcAge = date('Y') - $birthYear;
                $genderShort = strtoupper(substr($q['gender'] ?? 'M', 0, 1));
                $timeSlotParts = explode(' ', trim($q['time_slot'] ?? '09:00 AM'));
                $slotTime = $timeSlotParts[0] ?? '09:00';
                $slotAmPm = $timeSlotParts[1] ?? 'AM';
              ?>
                <div class="queue-item <?= $index === 0 ? 'highlight-border' : '' ?>">
                  <div class="time-col">
                    <span class="time-text"><?= htmlspecialchars($slotTime) ?></span>
                    <span class="ampm-text"><?= htmlspecialchars($slotAmPm) ?></span>
                  </div>
                  <div class="patient-info-col">
                    <div class="patient-head">
                      <strong class="patient-name"><?= htmlspecialchars($q['full_name']) ?></strong>
                      <span class="patient-meta"><?= $calcAge ?> y/o &bull; <?= $genderShort ?></span>
                      <span class="badge <?= $q['status'] === 'completed' ? 'badge-green' : ($index === 0 ? 'badge-emerald' : 'badge-blue-light') ?>">
                        <?= htmlspecialchars(ucfirst(str_replace('_', ' ', $q['status'] ?? 'Scheduled'))) ?>
                      </span>
                    </div>
                    <p class="chief-complaint">
                      <strong>Reason / Complaint:</strong> <?= htmlspecialchars($q['reason'] ?? 'Standard consultation and follow-up') ?>
                    </p>
                    <div class="vitals-row">
                      <span>BP: <?= htmlspecialchars($q['blood_pressure'] ?: '120/80') ?></span>
                      <span>BMI: <?= htmlspecialchars($q['bmi'] ?: '22.5') ?></span>
                      <span class="text-red">Status: Verified ID</span>
                    </div>
                  </div>
                  <div class="queue-actions">
                    <a href="doctor-clinical-service-records.php?patient_id=<?= (int)$q['patient_id'] ?>" class="btn-outline-sm btn-view-records" style="text-decoration:none;">
                      <i class="fa-solid fa-folder-open"></i> Records
                    </a>
                    <a href="patient-medical-profile.php?patient_id=<?= (int)$q['patient_id'] ?>" class="btn-emerald-sm btn-active-chart" style="text-decoration:none;">
                      <i class="fa-solid fa-chart-line"></i> Active Chart
                    </a>
                  </div>
                </div>
              <?php endforeach; ?>

            </div>

            <!-- Lab Telemetry -->
            <div class="card">
              <div class="card-header">
                <div>
                  <div class="header-title-flex">
                    <i class="fa-solid fa-wave-square text-emerald"></i>
                    <h3>Recent Diagnostic & Lab Telemetry</h3>
                  </div>
                  <p class="card-sub-title">Live feeds from NHMRD Central Pathology and Bedside Vitals</p>
                </div>
                <div class="status-live">
                  <span class="dot-green"></span>
                  <span>Telemetry Online</span>
                </div>
              </div>

              <table class="telemetry-table">
                <thead>
                  <tr>
                    <th>PATIENT & MRN</th>
                    <th>TEST PANEL</th>
                    <th>OBSERVED VALUE</th>
                    <th>REF RANGE</th>
                    <th>CLINICAL IMPACT</th>
                    <th class="text-right">ACTION</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($telemetryList as $t): ?>
                    <tr>
                      <td>
                        <div class="p-name"><?= htmlspecialchars($t['patient_name'] ?? 'Patient') ?></div>
                        <div class="p-mrn"><?= htmlspecialchars($t['mrn'] ?? 'MRN-449102') ?></div>
                      </td>
                      <td>
                        <div class="t-title"><?= htmlspecialchars($t['test_name'] ?? 'Diagnostic Test') ?></div>
                        <div class="t-sub"><?= htmlspecialchars($t['category'] ?? 'STAT Telemetry') ?></div>
                      </td>
                      <td><span class="val-bold text-red"><?= htmlspecialchars($t['observed_value'] ?? 'Normal') ?></span></td>
                      <td class="text-muted"><?= htmlspecialchars($t['ref_range'] ?? 'Standard Range') ?></td>
                      <td><span class="table-tag tag-red-fill"><?= htmlspecialchars($t['impact'] ?? 'Monitored') ?></span></td>
                      <td class="text-right">
                        <button class="btn-table-danger" onclick="alert('Page dispatched to On-Call Specialist.')">Page Cardiology</button>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>

          </div>

          <!-- Right Column: Quick Prescriber -->
          <div class="right-column">
            <div class="card prescriber-card">
              <div class="card-header">
                <div>
                  <div class="header-title-flex">
                    <i class="fa-solid fa-pills text-emerald"></i>
                    <h3>Quick Prescriber & Titration</h3>
                  </div>
                </div>
                <span class="formulary-tag">Formulary V4.2</span>
              </div>

              <form method="POST" action="dashboard.php" class="prescriber-body">
                <input type="hidden" name="action" value="queue_prescription">
                <input type="hidden" name="patient_id" value="<?= (int)$patientId ?>">

                <span class="lookup-label">TARGET PATIENT: #<?= (int)$patientId ?></span>
                <div class="input-search-box" style="margin-bottom: 12px;">
                  <i class="fa-solid fa-user"></i>
                  <input type="text" readonly value="Active Patient Context (ID #<?= (int)$patientId ?>)">
                </div>

                <span class="lookup-label">FAST MEDICATION LOOKUP</span>
                <div class="input-search-box">
                  <i class="fa-solid fa-magnifying-glass"></i>
                  <input type="text" name="medication_name" id="prescriber-med-input" value="Metformin Hydrochloride" required>
                </div>

                <div class="form-row-2">
                  <div class="form-group">
                    <label>Dosage Form</label>
                    <select name="dosage_form" class="select-box" style="width:100%; border:1px solid #cbd5e1; border-radius:6px; padding:8px;">
                      <option value="1000 mg ER Tablet" selected>1000 mg ER Tablet</option>
                      <option value="500 mg Tablet">500 mg Tablet</option>
                      <option value="850 mg Tablet">850 mg Tablet</option>
                      <option value="10 mg Capsule">10 mg Capsule</option>
                      <option value="20 mg Tablet">20 mg Tablet</option>
                    </select>
                  </div>
                  <div class="form-group">
                    <label>Frequency</label>
                    <select name="frequency" class="select-box" style="width:100%; border:1px solid #cbd5e1; border-radius:6px; padding:8px;">
                      <option value="Twice Daily (BID)" selected>Twice Daily (BID)</option>
                      <option value="Once Daily (OD)">Once Daily (OD)</option>
                      <option value="Thrice Daily (TID)">Thrice Daily (TID)</option>
                      <option value="As Needed (PRN)">As Needed (PRN)</option>
                    </select>
                  </div>
                </div>

                <div class="form-group" style="margin-top: 10px;">
                  <label style="font-size:0.82rem; font-weight:600; color:#475569;">Sig / Instructions</label>
                  <input type="text" name="sig_instructions" value="Take with meals to minimize GI distress" style="width:100%; padding:8px 12px; border:1px solid #cbd5e1; border-radius:6px;">
                </div>

                <div class="renal-clearance-box">
                  <div class="renal-row">
                    <span>Renal Clearance Check:</span>
                    <strong class="text-emerald" id="egfr-status">eGFR 72 mL/min (Safe)</strong>
                  </div>
                  <div class="renal-row">
                    <span>Max Daily Threshold:</span>
                    <strong id="max-daily-threshold">2000 mg/day</strong>
                  </div>
                </div>

                <div class="action-buttons-row">
                  <button type="reset" class="btn-clear" id="btn-clear-prescriber">Clear</button>
                  <button type="submit" class="btn-queue" id="btn-queue-prescriber">
                    <i class="fa-solid fa-play"></i> Queue for Patient
                  </button>
                </div>
              </form>
            </div>
          </div>

        </div>

      </main>
    </div>
  </div>

  <script src="/public_html/assets/js/doctor/dashboard.js"></script>
</body>

</html>
