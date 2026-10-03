<?php
// =====================================================================
// NHMRD Clinical Dashboard - Backend Integration
// Integrates DB schema (nhmrd.sql) with UI (dashboard.html)
// =====================================================================

$host = '127.0.0.1';
$db   = 'nhmrd';
$user = 'root'; 
$pass = '';     
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Mock logged-in doctor ID (In production, retrieve from session)
$doctor_id = 1; 

// ---------------------------------------------------------------------
// 1. TOP METRICS CARDS QUERIES
// ---------------------------------------------------------------------

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
    <!-- Main Content Area -->
    <div class="main-wrapper">
      <main class="content-body">
        
        <!-- Top Metrics Cards -->
        <div class="metrics-grid">
          <div class="metric-card">
            <div class="metric-header">
              <span class="metric-title">TODAY'S CONSULTATIONS</span>
              <i class="fa-regular fa-calendar-check metric-icon-top text-emerald"></i>
            </div>
            <div class="metric-value-row">
              <span class="metric-value"><?php echo $consultations['total_consultations'] ?? 0; ?></span>
              <span class="metric-unit">total</span>
            </div>
            <div class="metric-footer-pills">
              <span class="sub-pill">&bull; <?php echo $consultations['done_consultations'] ?? 0; ?> Done</span>
              <span class="sub-pill">&bull; <?php echo $consultations['pending_consultations'] ?? 0; ?> Pending</span>
            </div>
          </div>

          <div class="metric-card">
            <div class="metric-header">
              <span class="metric-title">RX REVIEWS PENDING</span>
              <i class="fa-solid fa-prescription metric-icon-top text-blue"></i>
            </div>
            <div class="metric-value-row">
              <span class="metric-value"><?php echo $rx_reviews['queued_reviews'] ?? 0; ?></span>
              <span class="metric-unit">queued</span>
            </div>
            <div class="metric-footer">
              <span>Polypharmacy check: <strong><?php echo $rx_reviews['polypharmacy_check'] ?? 0; ?></strong></span>
            </div>
          </div>

          <div class="metric-card">
            <div class="metric-header">
              <span class="metric-title">DIAGNOSTIC CRITICAL ALERTS</span>
              <i class="fa-solid fa-triangle-exclamation metric-icon-top text-red"></i>
            </div>
            <div class="metric-value-row">
              <span class="metric-value text-red"><?php echo $critical_alerts['abnormal_alerts'] ?? 0; ?></span>
              <span class="metric-unit text-red">abnormal</span>
            </div>
            <p class="metric-sub-text text-red"><?php echo htmlspecialchars($critical_alerts['sub_text_tests'] ?? 'No alerts'); ?></p>
          </div>

          <div class="metric-card">
            <div class="metric-header">
              <span class="metric-title">OUTPATIENT CARE INDEX</span>
              <i class="fa-regular fa-thumbs-up metric-icon-top text-teal"></i>
            </div>
            <div class="metric-value-row">
              <span class="metric-value"><?php echo $care_index['care_index_percentage'] ?? '0.0'; ?>%</span>
              <span class="metric-unit">completion</span>
            </div>
            <p class="metric-sub-text"><span class="text-semibold"><?php echo $care_index['quality_tier'] ?? 'N/A'; ?></span></p>
          </div>
        </div>

        <!-- Main Dashboard Split View -->
        <div class="dashboard-split">
          <div class="left-column">
            
            <!-- Patient Schedule -->
            <div class="card">
              <div class="card-header">
                <div>
                  <div class="header-title-flex">
                    <i class="fa-solid fa-calendar-day text-emerald"></i>
                    <h3>Patient Schedule & Encounter Queue</h3>
                  </div>
                </div>
              </div>
              
              <?php foreach($patient_schedule as $patient): ?>
              <div class="queue-item <?php echo ($patient['status'] == 'in_progress') ? 'highlight-border' : ''; ?>">
                <div class="time-col">
                  <span class="time-text"><?php echo htmlspecialchars($patient['time_slot']); ?></span>
                </div>
                <div class="patient-info-col">
                  <div class="patient-head">
                    <strong class="patient-name"><?php echo htmlspecialchars($patient['patient_name']); ?></strong>
                    <span class="patient-meta"><?php echo $patient['age']; ?> y/o &bull; <?php echo htmlspecialchars(ucfirst($patient['gender'])); ?></span>
                    <span class="badge badge-emerald"><?php echo htmlspecialchars(ucfirst($patient['status'])); ?></span>
                  </div>
                  <p class="chief-complaint">
                    <strong>Chief Complaint:</strong> <?php echo htmlspecialchars($patient['chief_complaint'] ?? 'No data'); ?>
                  </p>
                  <div class="vitals-row">
                    <span>BP: <?php echo htmlspecialchars($patient['blood_pressure'] ?? 'N/A'); ?></span>
                    <span>BMI: <?php echo htmlspecialchars($patient['bmi'] ?? 'N/A'); ?></span>
                  </div>
                </div>
                <div class="queue-actions">
                  <button class="btn-outline-sm btn-view-records"><i class="fa-solid fa-folder-open"></i> Records</button>
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
                </div>
              </div>

              <table class="telemetry-table">
                <thead>
                  <tr>
                    <th>PATIENT & MRN</th>
                    <th>TEST PANEL</th>
                    <th>OBSERVED VALUE</th>
                    <th>CLINICAL IMPACT</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach($lab_telemetry as $telemetry): ?>
                  <tr>
                    <td>
                      <div class="p-name"><?php echo htmlspecialchars($telemetry['patient_name']); ?></div>
                      <div class="p-mrn"><?php echo htmlspecialchars($telemetry['mrn'] ?? 'N/A'); ?></div>
                    </td>
                    <td>
                      <div class="t-title"><?php echo htmlspecialchars($telemetry['test_title']); ?></div>
                      <div class="t-sub"><?php echo htmlspecialchars(ucfirst($telemetry['test_panel'])); ?></div>
                    </td>
                    <td><span class="val-bold"><?php echo htmlspecialchars($telemetry['observed_value']); ?></span></td>
                    <td><span class="table-tag"><?php echo htmlspecialchars(ucfirst($telemetry['clinical_impact'])); ?></span></td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>

          </div>
        </div>

      </main>
    </div>
  </div>
</body>
</html>