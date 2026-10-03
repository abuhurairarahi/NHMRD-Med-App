<?php
require_once __DIR__ . 'public_html/api/db.php';

$doctor_id = 1; 
// Metric 1: TODAY'S CONSULTATIONS
$stmt_consultations = $pdo->prepare("
    SELECT 
        COUNT(*) AS total_consultations,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) AS done_consultations,
        SUM(CASE WHEN status IN ('booked', 'rescheduled') THEN 1 ELSE 0 END) AS pending_consultations
    FROM appointments 
    WHERE doctor_id = :doctor_id 
      AND appointment_date = CURDATE()
");
$stmt_consultations->execute(['doctor_id' => $doctor_id]);
$consultations = $stmt_consultations->fetch();

// Metric 2: RX REVIEWS PENDING
$stmt_rx = $pdo->prepare("
    SELECT 
        COUNT(DISTINCT rx.prescription_id) AS queued_reviews,
        SUM(CASE WHEN med_counts.total_meds >= 5 THEN 1 ELSE 0 END) AS polypharmacy_check
    FROM prescriptions rx
    LEFT JOIN (
        SELECT prescription_id, COUNT(*) as total_meds 
        FROM prescription_medications 
        GROUP BY prescription_id
    ) med_counts ON rx.prescription_id = med_counts.prescription_id
    WHERE rx.doctor_id = :doctor_id 
      AND rx.status IN ('active', 'ongoing')
");
$stmt_rx->execute(['doctor_id' => $doctor_id]);
$rx_reviews = $stmt_rx->fetch();

// Metric 3: DIAGNOSTIC CRITICAL ALERTS
$stmt_alerts = $pdo->prepare("
    SELECT 
        COUNT(*) AS abnormal_alerts,
        GROUP_CONCAT(c.test_name SEPARATOR ', ') AS sub_text_tests
    FROM lab_test_results r
    JOIN lab_test_order_items i ON r.order_item_id = i.id
    JOIN lab_test_catalog c ON i.test_id = c.test_id
    JOIN lab_test_orders o ON i.order_id = o.order_id
    WHERE o.doctor_id = :doctor_id 
      AND r.result_date = CURDATE()
      AND (r.remarks LIKE '%critical%' OR r.remarks LIKE '%high%' OR r.remarks LIKE '%abnormal%')
");
$stmt_alerts->execute(['doctor_id' => $doctor_id]);
$critical_alerts = $stmt_alerts->fetch();

// Metric 4: OUTPATIENT CARE INDEX
$stmt_care = $pdo->prepare("
    SELECT 
        ROUND((SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) / NULLIF(COUNT(*), 0)) * 100, 1) AS care_index_percentage,
        'Quality Tier A' as quality_tier
    FROM appointments 
    WHERE doctor_id = :doctor_id 
      AND appointment_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
");
$stmt_care->execute(['doctor_id' => $doctor_id]);
$care_index = $stmt_care->fetch();

// ---------------------------------------------------------------------
// 2. DASHBOARD SPLIT VIEW QUERIES
// ---------------------------------------------------------------------

// Left Column 1: Patient Schedule & Encounter Queue
$stmt_schedule = $pdo->prepare("
    SELECT 
        a.time_slot,
        p.full_name AS patient_name,
        TIMESTAMPDIFF(YEAR, p.dob, CURDATE()) AS age,
        p.gender,
        a.status,
        rx.chief_complaint,
        p.blood_pressure,
        p.bmi
    FROM appointments a
    JOIN patients p ON a.patient_id = p.patient_id
    LEFT JOIN prescriptions rx ON rx.appointment_id = a.appointment_id
    WHERE a.doctor_id = :doctor_id
      AND a.appointment_date = CURDATE()
    ORDER BY a.time_slot ASC
");
$stmt_schedule->execute(['doctor_id' => $doctor_id]);
$patient_schedule = $stmt_schedule->fetchAll();

// Left Column 2: Recent Diagnostic & Lab Telemetry
$stmt_telemetry = $pdo->prepare("
    SELECT 
        p.full_name AS patient_name,
        p.health_card_no AS mrn,
        c.test_name AS test_title,
        c.category AS test_panel,
        r.remarks AS observed_value,
        o.status AS clinical_impact
    FROM lab_test_results r
    JOIN lab_test_order_items i ON r.order_item_id = i.id
    JOIN lab_test_catalog c ON i.test_id = c.test_id
    JOIN lab_test_orders o ON i.order_id = o.order_id
    JOIN patients p ON o.patient_id = p.patient_id
    WHERE o.doctor_id = :doctor_id
    ORDER BY r.result_date DESC, r.result_id DESC
    LIMIT 10
");
$stmt_telemetry->execute(['doctor_id' => $doctor_id]);
$lab_telemetry = $stmt_telemetry->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>NHMRD - Admin & Clinical Dashboard</title>
  <link rel="stylesheet" href="/public_html/assets/css/doctor-panel/dashboard.css">
  <link rel="stylesheet" href="/public_html/assets/css/default-structure.css">
  <link rel="stylesheet" href="/public_html/assets/css/doctor-panel/features/doctor-header.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>

  <div class="app-container">
    <aside class="sidebar doctor">
      <div class="logo">
        <i class="fa-solid fa-shield-halved"></i>
        <span>NHMRD</span>
      </div>

      <nav class="nav-menu">
        <a href="/public_html/pages/Doctor-panel/Dashboard.html" class="nav-item active">
          <i class="fa-solid fa-table-cells-large"></i>
          <span>Dashboard</span>
        </a>
        <a href="/public_html/pages/Doctor-panel/doctor-clinical-service-records.html" class="nav-item">
          <i class="fa-solid fa-notes-medical"></i>
          <span>Clinical Records</span>
        </a>
        <a href="/public_html/pages/Doctor-panel/patient-appointments.html" class="nav-item">
          <i class="fa-solid fa-user-clock"></i>
          <span>Patient Appointments</span>
        </a>
        <a href="/public_html/pages/doctor-panel/doctor-profile.html" class="nav-item">
          <i class="fa-solid fa-user-doctor"></i>
          <span>Doctor Profile</span>
        </a>
      </nav>

      <div class="sidebar-footer">
        <button class="logout-btn" id="logout-btn" onclick="handleLogout()">
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
          <input type="text" placeholder="Search patient MRN, name, vitals...">
        </div>

        <div class="header-right">
          <div class="header-status-pill">
            <i class="fa-regular fa-clock"></i>
            <span>Shift: Morning Clinical Rounds (07:00 - 15:30)</span>
          </div>
          <div class="header-status-pill">
            <i class="fa-solid fa-rotate"></i>
            <span>EMR Synced</span>
          </div>
          <button class="icon-btn"><i class="fa-regular fa-bell"></i></button>

          <div class="doctor-profile-badge">
            <div class="doctor-info-text">
              <span class="doctor-name">Dr. Nusrat Jahan, MD</span>
              <span class="doctor-dept">Internal Medicine & Therapeutics</span>
            </div>
            <div class="doctor-avatar">
              <img src="https://i.pravatar.cc/100?img=47" alt="Dr. Nusrat Jahan">
            </div>
          </div>
        </div>
      </header>

      <!-- Main Content Body -->
      <main class="content-body">

        <!-- Page Header Banner -->
        <div class="command-banner">
          <div class="banner-title-area">
            <div class="banner-icon-bg">
              <i class="fa-solid fa-stethoscope"></i>
            </div>
            <div>
              <div class="banner-title-row">
                <h2>Internal Medicine Clinical Command</h2>
                <span class="badge badge-green"><i class="fa-solid fa-circle text-xs"></i> ACTIVE ROUNDING</span>
              </div>
              <p class="banner-sub">Station 04 &bull; Service Ward 3B &bull; EMR Session ID: #IM-8842-DX</p>
            </div>
          </div>
          <div class="banner-actions">
            <!-- POSITION 1: Command Banner Actions -->
            <button class="btn-outline-dark" id="btn-call-triage" onclick="callTriage()"><i
                class="fa-solid fa-asterisk"></i> Call Triage</button>
            <button class="btn-emerald" id="btn-new-encounter" onclick="startNewEncounter()"><i
                class="fa-solid fa-plus font-bold"></i> New Encounter</button>
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
              <span class="metric-value">18</span>
              <span class="metric-unit">total</span>
              <span class="metric-badge green-badge"><i class="fa-solid fa-arrow-trend-up"></i> +12%</span>
            </div>
            <div class="metric-footer-pills">
              <span class="sub-pill">&bull; 11 Done</span>
              <span class="sub-pill">&bull; 7 Pending</span>
              <span class="sub-pill">1 In Exam</span>
            </div>
          </div>

          <div class="metric-card">
            <div class="metric-header">
              <span class="metric-title">RX REVIEWS PENDING</span>
              <i class="fa-solid fa-prescription metric-icon-top text-blue"></i>
            </div>
            <div class="metric-value-row">
              <span class="metric-value">14</span>
              <span class="metric-unit">queued</span>
              <span class="metric-badge alert-red-pill">! 4 Urgent</span>
            </div>
            <div class="metric-footer">
              <span>Polypharmacy check: <strong>5</strong></span>
              <a href="#" class="link-arrow" id="batch-sign-link">Batch Sign &rarr;</a>
            </div>
          </div>

          <div class="metric-card">
            <div class="metric-header">
              <span class="metric-title">DIAGNOSTIC CRITICAL ALERTS</span>
              <i class="fa-solid fa-triangle-exclamation metric-icon-top text-red"></i>
            </div>
            <div class="metric-value-row">
              <span class="metric-value text-red">3</span>
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
                <div class="dropdown-pill" id="schedule-date-filter" onclick="openScheduleFilter()">
                  <span>Today (Oct 24)</span>
                  <i class="fa-solid fa-sliders"></i>
                </div>
              </div>

              <!-- POSITION 2: Patient Queue Actions -->
              <div class="queue-item highlight-border">
                <div class="time-col">
                  <span class="time-text">09:15</span>
                  <span class="ampm-text">AM</span>
                </div>
                <div class="patient-info-col">
                  <div class="patient-head">
                    <strong class="patient-name">Arthur Pendelton</strong>
                    <span class="patient-meta">62 y/o &bull; M</span>
                    <span class="badge badge-emerald">In Progress</span>
                  </div>
                  <p class="chief-complaint">
                    <strong>Chief Complaint:</strong> Type 2 Diabetes follow-up, symptomatic hyperglycemia, mild
                    peripheral neuropathy
                  </p>
                  <div class="vitals-row">
                    <span>BP: 138/86</span>
                    <span>HR: 74 bpm</span>
                    <span class="text-red">HbA1c: 9.8% &uarr;</span>
                  </div>
                </div>
                <div class="queue-actions">
                  <button class="btn-outline-sm btn-view-records" onclick="viewPatientRecords(this)"><i
                      class="fa-solid fa-folder-open"></i> Records</button>
                  <button class="btn-emerald-sm btn-active-chart" onclick="openActiveChart(this)"><i
                      class="fa-solid fa-chart-line"></i> Active Chart</button>
                </div>
              </div>

              <div class="queue-item">
                <div class="time-col">
                  <span class="time-text">09:45</span>
                  <span class="ampm-text">AM</span>
                </div>
                <div class="patient-info-col">
                  <div class="patient-head">
                    <strong class="patient-name">Eleanor Vance-Croft</strong>
                    <span class="patient-meta">48 y/o &bull; F</span>
                    <span class="badge badge-blue-light">Waiting (Exam 2)</span>
                  </div>
                  <p class="chief-complaint">
                    <strong>Chief Complaint:</strong> Refractory Hypertension review, morning occipital headaches, pedal
                    edema
                  </p>
                  <div class="vitals-row">
                    <span class="text-red">BP: 162/98 &uarr;</span>
                    <span>HR: 88 bpm</span>
                    <span>BMI: 29.4</span>
                  </div>
                </div>
                <div class="queue-actions">
                  <button class="btn-outline-sm btn-view-records" onclick="viewPatientRecords(this)"><i
                      class="fa-solid fa-folder-open"></i> Records</button>
                </div>
              </div>
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
                  <!-- POSITION 3: Telemetry Action -->
                  <tr>
                    <td>
                      <div class="p-name">Julian Barnes</div>
                      <div class="p-mrn">MRN-7892-C</div>
                    </td>
                    <td>
                      <div class="t-title">Cardiac Troponin I</div>
                      <div class="t-sub">STAT Telemetry</div>
                    </td>
                    <td><span class="val-bold text-red">0.18 ng/mL &uarr;</span></td>
                    <td class="text-muted">&lt; 0.04 ng/mL</td>
                    <td><span class="table-tag tag-red-fill">Critical High</span></td>
                    <td class="text-right">
                      <button class="btn-table-danger" id="btn-page-cardiology" onclick="pageCardiology(this)">Page
                        Cardiology</button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

          </div>

          <!-- Right Column -->
          <div class="right-column">
            <!-- POSITION 4: Quick Prescriber Card -->
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

              <div class="prescriber-body">
                <span class="lookup-label">FAST MEDICATION LOOKUP</span>
                <div class="input-search-box">
                  <i class="fa-solid fa-magnifying-glass"></i>
                  <input type="text" id="prescriber-med-input" value="Metformin Hydrochloride">
                </div>

                <div class="form-row-2">
                  <div class="form-group">
                    <label>Dosage Form</label>
                    <div class="select-box" id="dosage-form-select">1000 mg ER Tablet</div>
                  </div>
                  <div class="form-group">
                    <label>Frequency</label>
                    <div class="select-box" id="frequency-select">Twice Daily (BID)</div>
                  </div>
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
                  <button class="btn-clear" id="btn-clear-prescriber" onclick="clearPrescriber()">Clear</button>
                  <button class="btn-queue" id="btn-queue-prescriber" onclick="queuePrescription()"><i
                      class="fa-solid fa-play"></i> Queue for Patient</button>
                </div>
              </div>
            </div>
          </div>

        </div>

      </main>
    </div>
  </div>

  <script src="/public_html/assets/js/doctor-panel/dashboard.js"></script>
</body>

</html>

