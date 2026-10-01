<?php
// lab-test.php
require_once 'db_connect.php';

// Session patient context (Defaulted for demo matching ID #48291 / User UID '20421201')
$patient_id = $_SESSION['patient_id'] ?? 1;

// 1. Fetch Patient Info & Vitals Summary
$stmt = $pdo->prepare("SELECT * FROM v_patient_overview WHERE patient_id = ?");
$stmt->execute([$patient_id]);
$patient = $stmt->fetch();

if (!$patient) {
    die("Patient record not found.");
}

// 2. Fetch Summary Statistics
$stmtCount = $pdo->prepare("SELECT COUNT(*) AS total_files FROM lab_test_orders WHERE patient_id = ?");
$stmtCount->execute([$patient_id]);
$total_files = $stmtCount->fetchColumn();

$stmtLabs = $pdo->prepare("SELECT COUNT(DISTINCT hospital_id) FROM lab_test_orders WHERE patient_id = ?");
$stmtLabs->execute([$patient_id]);
$total_labs = $stmtLabs->fetchColumn();

// 3. Fetch All Lab Orders with Items and Results
$query = "
    SELECT 
        o.order_id,
        o.status AS order_status,
        o.ordered_at,
        h.legal_name AS hospital_name,
        d.full_name AS doctor_name,
        i.id AS item_id,
        i.price,
        c.test_name,
        c.category,
        c.test_code,
        r.result_file_url,
        r.result_date,
        r.remarks
    FROM lab_test_orders o
    LEFT JOIN hospitals h ON o.hospital_id = h.hospital_id
    LEFT JOIN doctors d ON o.doctor_id = d.doctor_id
    JOIN lab_test_order_items i ON o.order_id = i.order_id
    JOIN lab_test_catalog c ON i.test_id = c.test_id
    LEFT JOIN lab_test_results r ON r.order_item_id = i.id
    WHERE o.patient_id = ?
    ORDER BY o.ordered_at DESC
";
$stmtOrders = $pdo->prepare($query);
$stmtOrders->execute([$patient_id]);
$orders = $stmtOrders->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Diagnostic & Lab Test Records - NHMRD</title>
  <link rel="stylesheet" href="/public_html/assets/css/patient-panel/lab-test.css">
  <link rel="stylesheet" href="/public_html/assets/css/default-structure.css">
  <link rel="stylesheet" href="/public_html/assets/css/patient-panel/features/patient-header.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

  <div class="app-container">
    <!-- Sidebar Navigation -->
    <aside class="sidebar patient">
      <div class="logo">
        <i class="fa-solid fa-shield-halved"></i>
        <span>NHMRD</span>
      </div>

      <nav class="nav-menu">
        <a href="dashboard.php" class="nav-item"><i class="fa-solid fa-table-cells-large"></i> <span>Dashboard</span></a>
        <a href="prescription-record.php" class="nav-item"><i class="fa-solid fa-file-prescription"></i> <span>Prescription Records</span></a>
        <a href="surgary-record.php" class="nav-item"><i class="fa-solid fa-scalpel"></i> <span>Surgery Records</span></a>
        <a href="lab-test.php" class="nav-item active"><i class="fa-solid fa-vial"></i> <span>Test Records</span></a>
        <a href="vaccine-panel.php" class="nav-item"><i class="fa-solid fa-syringe"></i> <span>Vaccine Records</span></a>
        <a href="req-appointment.php" class="nav-item"><i class="fa-solid fa-calendar-plus"></i> <span>Request Appointment</span></a>
        <a href="medical-test-req.php" class="nav-item"><i class="fa-solid fa-notes-medical"></i> <span>Request Medical Test</span></a>
        <a href="req-vaccine.php" class="nav-item"><i class="fa-solid fa-shield-virus"></i> <span>Request Vaccine</span></a>
        <a href="patient-info.php" class="nav-item"><i class="fa-solid fa-id-card"></i> <span>Patient Info</span></a>
      </nav>

      <div class="sidebar-footer">
        <button class="logout-btn" onclick="handleUserLogout()">
          <i class="fa-solid fa-arrow-right-from-bracket"></i> <span>Logout</span>
        </button>
      </div>
    </aside>

    <!-- Main Content Wrapper -->
    <div class="main-wrapper">
      <!-- Top Header Bar -->
      <header class="patient-header">
        <div class="search-bar">
          <i class="fa-solid fa-magnifying-glass"></i>
          <input type="text" placeholder="Search Queries" oninput="filterTestRecords(event)">
        </div>

        <div class="header-right">
          <button class="icon-btn" onclick="handleNotificationClick()"><i class="fa-regular fa-bell"></i></button>
          <div class="user-badge-avatar"><?= htmlspecialchars(strtoupper(substr($patient['full_name'], 0, 2))) ?></div>
          <div class="user-info-text">
            <span class="user-name"><?= htmlspecialchars($patient['full_name']) ?></span>
            <span class="patient-id">Patient ID #<?= htmlspecialchars($patient['patient_id']) ?></span>
          </div>
        </div>
      </header>

      <!-- Main Content Body -->
      <main class="content-body">

        <!-- Page Banner -->
        <div class="banner-card">
          <div class="banner-left">
            <div class="banner-icon"><i class="fa-solid fa-flask"></i></div>
            <div>
              <div class="registry-tag">NATIONAL HEALTH RECORDS &bull; Synchronized</div>
              <h2>Diagnostic & Lab Test Records</h2>
              <p class="banner-sub">Consolidated laboratory, pathology, and diagnostic imaging for <?= htmlspecialchars($patient['full_name']) ?> (ID #<?= htmlspecialchars($patient['patient_id']) ?>)</p>
            </div>
          </div>
          <div class="banner-actions">
            <button class="btn-outline" onclick="exportAllRecordsPDF()"><i class="fa-solid fa-download"></i> Export All Records (PDF)</button>
            <button class="btn-primary-blue" onclick="orderNewTest()"><i class="fa-solid fa-circle-plus"></i> Order New Diagnostic Test</button>
          </div>
        </div>

        <!-- Summary Stats Grid -->
        <div class="stats-grid">
          <div class="stat-card">
            <div class="stat-text">
              <span class="stat-label">TOTAL DIAGNOSTIC FILES</span>
              <strong class="stat-val"><?= sprintf("%02d", $total_files) ?> <span class="stat-unit">Verified Records</span></strong>
              <span class="stat-sub">From <?= $total_labs ?> Authorized Labs</span>
            </div>
            <div class="stat-icon blue-light"><i class="fa-regular fa-file-lines"></i></div>
          </div>

          <div class="stat-card">
            <div class="stat-text">
              <span class="stat-label">RECENT ABNORMAL FLAGS</span>
              <strong class="stat-val text-red">01 <span class="stat-unit text-red">Requires Review</span></strong>
              <span class="stat-sub text-red">Mild ALT/SGPT elevation (52 U/L)</span>
            </div>
            <div class="stat-icon red-light"><i class="fa-solid fa-triangle-exclamation"></i></div>
          </div>

          <div class="stat-card">
            <div class="stat-text">
              <span class="stat-label">PENDING TEST RESULTS</span>
              <strong class="stat-val">00 <span class="stat-unit">All Completed</span></strong>
              <span class="stat-sub">Next scheduled testing in Oct 2026</span>
            </div>
            <div class="stat-icon check-light"><i class="fa-regular fa-circle-check"></i></div>
          </div>
        </div>

        <!-- Category Filter Tabs -->
        <div class="filter-row">
          <div class="filter-tabs">
            <button class="tab-btn active" onclick="filterByCategory(event)">All Diagnostics (<?= count($orders) ?>)</button>
            <button class="tab-btn" onclick="filterByCategory(event)">Biochemistry</button>
            <button class="tab-btn" onclick="filterByCategory(event)">Hematology</button>
            <button class="tab-btn" onclick="filterByCategory(event)">Radiology & USG</button>
            <button class="tab-btn" onclick="filterByCategory(event)">Serology</button>
          </div>
          <div class="date-filter">
            <span class="filter-label">Range:</span>
            <select class="dropdown-select">
              <option>Last 6 Months (2026)</option>
              <option>Last 1 Year</option>
              <option>All Time</option>
            </select>
          </div>
        </div>

        <!-- Dynamic Test Records List -->
        <?php if (!empty($orders)): ?>
          <?php foreach ($orders as $row): ?>
            <div class="card test-card" data-category="<?= htmlspecialchars($row['category']) ?>">
              <div class="card-header">
                <div class="title-group">
                  <div class="test-icon blue-bg"><i class="fa-solid fa-vial"></i></div>
                  <div>
                    <div class="test-title-line">
                      <h3><?= htmlspecialchars($row['test_name']) ?></h3>
                      <span class="status-tag green"><?= ucfirst(htmlspecialchars($row['order_status'])) ?></span>
                    </div>
                    <p class="test-meta">
                      <i class="fa-regular fa-calendar"></i> Publish Date: <strong><?= date('d-m-Y', strtotime($row['ordered_at'])) ?></strong> &bull; 
                      <i class="fa-regular fa-hospital"></i> Hospital: <strong><?= htmlspecialchars($row['hospital_name'] ?? 'N/A') ?></strong> &bull; 
                      Prescribed by: <strong><?= htmlspecialchars($row['doctor_name'] ?? 'Self-Requested') ?></strong> &bull; 
                      Lab ID: <span><?= htmlspecialchars($row['test_code'] ?? ('ORD-' . $row['order_id'])) ?></span>
                    </p>
                  </div>
                </div>
                <button class="btn-primary-blue btn-sm" onclick="viewTestReport(event)" data-report="<?= htmlspecialchars($row['result_file_url'] ?? '') ?>">
                  <i class="fa-regular fa-file-pdf"></i> View Full Report
                </button>
              </div>

              <?php if (!empty($row['remarks'])): ?>
                <div class="note-box">
                  <p><i class="fa-solid fa-notes-medical note-icon"></i> <strong>Clinical Notes / Remarks:</strong> <?= htmlspecialchars($row['remarks']) ?></p>
                </div>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <div class="card test-card"><p style="padding: 20px;">No diagnostic records found for this patient.</p></div>
        <?php endif; ?>

      </main>
    </div>
  </div>

  <script src="/public_html/assets/js/paitent/text_lab.js"></script>
</body>
</html>