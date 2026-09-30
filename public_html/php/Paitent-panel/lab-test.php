<?php
// public_html/php/paitent-panel/lab-test.php
// NHMRD - Patient Diagnostic & Lab Test Records

require_once __DIR__ . '/patient_bootstrap.php';

// Fetch All Lab Orders with Items and Results for active patient
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
$stmtOrders->execute([$patientId]);
$orders = $stmtOrders->fetchAll();

$stmtCount = $pdo->prepare("SELECT COUNT(*) AS total_files FROM lab_test_orders WHERE patient_id = ?");
$stmtCount->execute([$patientId]);
$total_files = $stmtCount->fetchColumn() ?: count($orders);

$stmtLabs = $pdo->prepare("SELECT COUNT(DISTINCT hospital_id) FROM lab_test_orders WHERE patient_id = ?");
$stmtLabs->execute([$patientId]);
$total_labs = $stmtLabs->fetchColumn() ?: 1;
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
        <a href="prescription-records.php" class="nav-item"><i class="fa-solid fa-file-prescription"></i> <span>Prescription Records</span></a>
        <a href="surgary-record.php" class="nav-item"><i class="fa-solid fa-scalpel"></i> <span>Surgery Records</span></a>
        <a href="lab-test.php" class="nav-item active"><i class="fa-solid fa-vial"></i> <span>Test Records</span></a>
        <a href="vaccine-panel.php" class="nav-item"><i class="fa-solid fa-syringe"></i> <span>Vaccine Records</span></a>
        <a href="req-appointment.php" class="nav-item"><i class="fa-solid fa-calendar-plus"></i> <span>Request Appointment</span></a>
        <a href="medical-test-request.php" class="nav-item"><i class="fa-solid fa-notes-medical"></i> <span>Request Medical Test</span></a>
        <a href="req-vaccine.php" class="nav-item"><i class="fa-solid fa-shield-virus"></i> <span>Request Vaccine</span></a>
        <a href="paitent-info.php" class="nav-item"><i class="fa-solid fa-id-card"></i> <span>Patient Info</span></a>
      </nav>

      <div class="sidebar-footer">
        <button class="logout-btn" onclick="window.location.href='/public_html/api/logout.php'">
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
          <button class="icon-btn" onclick="alert('Notification Center: 0 unread alerts.')"><i class="fa-regular fa-bell"></i></button>
          <div class="user-badge-avatar"><?= htmlspecialchars($userInitials) ?></div>
          <div class="user-info-text">
            <span class="user-name"><?= htmlspecialchars($patient['full_name']) ?></span>
            <span class="patient-id">Health Card: <?= htmlspecialchars($patient['health_card_no'] ?: '#' . $patient['patient_id']) ?></span>
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
              <p class="banner-sub">Consolidated laboratory, pathology, and diagnostic imaging for <?= htmlspecialchars($patient['full_name']) ?></p>
            </div>
          </div>
          <div class="banner-actions">
            <button class="btn-outline" onclick="window.print()"><i class="fa-solid fa-download"></i> Export Records (PDF)</button>
            <a href="medical-test-request.php" class="btn-primary-blue" style="text-decoration:none;"><i class="fa-solid fa-circle-plus"></i> Order New Diagnostic Test</a>
          </div>
        </div>

        <!-- Summary Stats Grid -->
        <div class="stats-grid">
          <div class="stat-card">
            <div class="stat-text">
              <span class="stat-label">TOTAL DIAGNOSTIC FILES</span>
              <strong class="stat-val"><?= sprintf("%02d", (int)$total_files) ?> <span class="stat-unit">Verified Records</span></strong>
              <span class="stat-sub">From <?= (int)$total_labs ?> Authorized Labs</span>
            </div>
            <div class="stat-icon blue-light"><i class="fa-regular fa-file-lines"></i></div>
          </div>

          <div class="stat-card">
            <div class="stat-text">
              <span class="stat-label">VERIFIED REPORTS</span>
              <strong class="stat-val text-green"><?= sprintf("%02d", count($orders)) ?> <span class="stat-unit text-green">Available</span></strong>
              <span class="stat-sub text-green">Digital signatures verified</span>
            </div>
            <div class="stat-icon check-light"><i class="fa-regular fa-circle-check"></i></div>
          </div>

          <div class="stat-card">
            <div class="stat-text">
              <span class="stat-label">PENDING TEST RESULTS</span>
              <strong class="stat-val">00 <span class="stat-unit">All Completed</span></strong>
              <span class="stat-sub">Regular monitoring active</span>
            </div>
            <div class="stat-icon check-light"><i class="fa-solid fa-clock"></i></div>
          </div>
        </div>

        <!-- Category Filter Tabs -->
        <div class="filter-row">
          <div class="filter-tabs">
            <button class="tab-btn active">All Diagnostics (<?= count($orders) ?>)</button>
            <button class="tab-btn">Biochemistry</button>
            <button class="tab-btn">Hematology</button>
            <button class="tab-btn">Radiology &amp; USG</button>
            <button class="tab-btn">Serology</button>
          </div>
        </div>

        <!-- Dynamic Test Records List -->
        <?php if (!empty($orders)): ?>
          <?php foreach ($orders as $row): ?>
            <div class="card test-card" data-category="<?= htmlspecialchars($row['category'] ?? '') ?>">
              <div class="card-header">
                <div class="title-group">
                  <div class="test-icon blue-bg"><i class="fa-solid fa-vial"></i></div>
                  <div>
                    <div class="test-title-line">
                      <h3><?= htmlspecialchars($row['test_name']) ?></h3>
                      <span class="status-tag green"><?= ucfirst(htmlspecialchars($row['order_status'])) ?></span>
                    </div>
                    <p class="test-meta">
                      <i class="fa-regular fa-calendar"></i> Ordered: <strong><?= date('d-m-Y', strtotime($row['ordered_at'])) ?></strong> &bull; 
                      <i class="fa-regular fa-hospital"></i> Facility: <strong><?= htmlspecialchars($row['hospital_name'] ?? 'National Central Lab') ?></strong> &bull; 
                      Prescribed by: <strong><?= htmlspecialchars($row['doctor_name'] ?? 'Self-Requested') ?></strong> &bull; 
                      Test Code: <span><?= htmlspecialchars($row['test_code'] ?? ('ORD-' . $row['order_id'])) ?></span>
                    </p>
                  </div>
                </div>
                <button class="btn-primary-blue btn-sm" onclick="alert('Opening signed digital pathology report...')">
                  <i class="fa-regular fa-file-pdf"></i> View Full Report
                </button>
              </div>

              <?php if (!empty($row['remarks'])): ?>
                <div class="note-box" style="margin-top:12px; background:#f8fafc; padding:12px; border-radius:6px;">
                  <p style="margin:0;"><i class="fa-solid fa-notes-medical note-icon text-emerald"></i> <strong>Clinical Notes / Remarks:</strong> <?= htmlspecialchars($row['remarks']) ?></p>
                </div>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <div class="card test-card" style="padding: 30px; text-align:center; color:#64748b;">
            <p>No diagnostic test records found for this patient.</p>
            <a href="medical-test-request.php" class="btn-primary-blue" style="text-decoration:none; display:inline-block; margin-top:10px;">Request a Test Now</a>
          </div>
        <?php endif; ?>

      </main>
    </div>
  </div>

  <script src="/public_html/assets/js/paitent/text_lab.js"></script>
</body>
</html>