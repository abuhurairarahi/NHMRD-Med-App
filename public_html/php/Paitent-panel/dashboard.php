<?php
// public_html/php/paitent-panel/dashboard.php
// NHMRD - Patient Portal Dashboard

require_once __DIR__ . '/patient_bootstrap.php';

// Fetch Recent Appointments
$stmtAppointments = $pdo->prepare("
    SELECT a.*, d.full_name AS doctor_name, h.legal_name AS hospital_name 
    FROM appointments a
    JOIN doctors d ON a.doctor_id = d.doctor_id
    LEFT JOIN hospitals h ON a.hospital_id = h.hospital_id
    WHERE a.patient_id = ?
    ORDER BY a.appointment_date DESC
    LIMIT 3
");
$stmtAppointments->execute([$patientId]);
$appointments = $stmtAppointments->fetchAll();

// Fetch Recent Medical Activities (Lab Test Orders)
$stmtActivities = $pdo->prepare("
    SELECT lto.order_id, lto.ordered_at, ltc.test_name, d.full_name AS doctor_name
    FROM lab_test_orders lto
    JOIN lab_test_order_items ltoi ON lto.order_id = ltoi.order_id
    JOIN lab_test_catalog ltc ON ltoi.test_id = ltc.test_id
    LEFT JOIN doctors d ON lto.doctor_id = d.doctor_id
    WHERE lto.patient_id = ?
    ORDER BY lto.ordered_at DESC
    LIMIT 3
");
$stmtActivities->execute([$patientId]);
$activities = $stmtActivities->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>NHMRD - Patient Dashboard</title>
  <link rel="stylesheet" href="/public_html/assets/css/patient-panel/dashboard.css">
  <link rel="stylesheet" href="/public_html/assets/css/default-structure.css">
  <link rel="stylesheet" href="/public_html/assets/css/patient-panel/features/patient-header.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

  <div class="app-container">
    <!-- Sidebar Navigation -->
    <aside class="sidebar">
      <div class="logo">
        <i class="fa-solid fa-shield-halved"></i>
        <span>NHMRD</span>
      </div>

      <nav class="nav-menu">
        <a href="dashboard.php" class="nav-item active">
          <i class="fa-solid fa-table-cells-large"></i> 
          <span>Dashboard</span>
        </a>
        <a href="prescription-records.php" class="nav-item">
          <i class="fa-solid fa-file-prescription"></i> 
          <span>Prescription Records</span>
        </a>
        <a href="surgary-record.php" class="nav-item">
          <i class="fa-solid fa-scalpel"></i> 
          <span>Surgery Records</span>
        </a>
        <a href="lab-test.php" class="nav-item">
          <i class="fa-solid fa-vial"></i> 
          <span>Test Records</span>
        </a>
        <a href="vaccine-panel.php" class="nav-item">
          <i class="fa-solid fa-syringe"></i> 
          <span>Vaccine Records</span>
        </a>
        <a href="req-appointment.php" class="nav-item">
          <i class="fa-solid fa-calendar-plus"></i> 
          <span>Request Appointment</span>
        </a>
        <a href="medical-test-request.php" class="nav-item">
          <i class="fa-solid fa-notes-medical"></i> 
          <span>Request Medical Test</span>
        </a>
        <a href="req-vaccine.php" class="nav-item">
          <i class="fa-solid fa-shield-virus"></i> 
          <span>Request Vaccine</span>
        </a>
        <a href="paitent-info.php" class="nav-item">
          <i class="fa-solid fa-id-card"></i> 
          <span>Patient Info</span>
        </a>
      </nav>

      <div class="sidebar-footer">
        <button class="logout-btn" id="logoutBtn" onclick="window.location.href='/public_html/api/logout.php'">
          <i class="fa-solid fa-arrow-right-from-bracket"></i>
          <span>Logout</span>
        </button>
      </div>
    </aside>

    <!-- Main Content Wrapper -->
    <div class="main-wrapper">
      <!-- Top Header Bar -->
      <header class="top-header">
        <div class="search-bar">
          <i class="fa-solid fa-magnifying-glass"></i>
          <input type="text" id="dashboardSearch" placeholder="Search Queries">
        </div>

        <div class="header-right">
          <button class="icon-btn" id="notificationBtn" onclick="alert('Notification Center: Records synchronized with National EMR.')"><i class="fa-regular fa-bell"></i></button>
          
          <!-- Dynamic User Initials Badge -->
          <div class="user-badge-avatar"><?= htmlspecialchars($userInitials) ?></div>
          
          <div class="user-info-text">
            <span class="user-name"><?= htmlspecialchars($patient['full_name']) ?></span>
            <span class="patient-id">Health Card: <?= htmlspecialchars($patient['health_card_no'] ?: 'ID #' . $patient['patient_id']) ?></span>
          </div>
        </div>
      </header>

      <!-- Main Content Area -->
      <main class="content-body">
        
        <!-- Welcome Banner -->
        <div class="welcome-banner">
          <div class="welcome-text">
            <h1>Welcome, <?= htmlspecialchars($patient['full_name']) ?></h1>
            <p>Your comprehensive electronic health record, appointments, and diagnostic telemetry.</p>
          </div>
          <div class="banner-actions">
            <a href="req-appointment.php" class="btn-primary" style="text-decoration:none;"><i class="fa-solid fa-calendar-plus"></i> Book Appointment</a>
            <a href="medical-test-request.php" class="btn-secondary" style="text-decoration:none;"><i class="fa-solid fa-vial"></i> Order Tests</a>
          </div>
        </div>

        <!-- Metric Stat Cards -->
        <div class="stats-grid">
          <div class="stat-card">
            <div class="stat-icon-wrap bg-blue-light">
              <i class="fa-solid fa-file-prescription text-blue"></i>
            </div>
            <div class="stat-data">
              <span class="stat-label">PRESCRIPTIONS</span>
              <strong class="stat-value"><?= (int)$totalPrescriptions ?></strong>
            </div>
          </div>

          <div class="stat-card">
            <div class="stat-icon-wrap bg-emerald-light">
              <i class="fa-solid fa-vial text-emerald"></i>
            </div>
            <div class="stat-data">
              <span class="stat-label">LAB ORDERS</span>
              <strong class="stat-value"><?= (int)$totalLabTests ?></strong>
            </div>
          </div>

          <div class="stat-card">
            <div class="stat-icon-wrap bg-teal-light">
              <i class="fa-solid fa-syringe text-teal"></i>
            </div>
            <div class="stat-data">
              <span class="stat-label">VACCINATIONS</span>
              <strong class="stat-value"><?= (int)$totalVaccines ?></strong>
            </div>
          </div>

          <div class="stat-card">
            <div class="stat-icon-wrap bg-purple-light">
              <i class="fa-solid fa-scalpel text-purple"></i>
            </div>
            <div class="stat-data">
              <span class="stat-label">SURGERIES</span>
              <strong class="stat-value"><?= (int)$totalSurgeries ?></strong>
            </div>
          </div>
        </div>

        <!-- 2-Column Split View -->
        <div class="dashboard-split" style="display:grid; grid-template-columns: 1fr 1fr; gap:24px; margin-top:24px;">

          <!-- Left Column: Upcoming Appointments -->
          <div class="card" style="background:#fff; border-radius:8px; padding:20px; border:1px solid #e2e8f0;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
              <h3 style="margin:0; font-size:1.15rem; color:#0f172a;">
                <i class="fa-solid fa-calendar-check text-emerald"></i> Upcoming Appointments
              </h3>
              <a href="req-appointment.php" style="color:#059669; font-size:0.85rem; font-weight:600; text-decoration:none;">Book New &rarr;</a>
            </div>

            <?php if (empty($appointments)): ?>
              <div style="text-align:center; padding:30px; color:#64748b;">
                <p>No upcoming appointments found.</p>
                <a href="req-appointment.php" style="display:inline-block; padding:8px 16px; background:#059669; color:#fff; text-decoration:none; border-radius:6px; font-weight:600; font-size:0.85rem;">Schedule an Appointment</a>
              </div>
            <?php else: ?>
              <div style="display:flex; flex-direction:column; gap:12px;">
                <?php foreach ($appointments as $apt): ?>
                  <div style="padding:14px; border-radius:6px; border:1px solid #f1f5f9; background:#f8fafc; display:flex; justify-content:space-between; align-items:center;">
                    <div>
                      <strong style="display:block; color:#0f172a; font-size:0.95rem;"><?= htmlspecialchars($apt['doctor_name']) ?></strong>
                      <span style="font-size:0.85rem; color:#64748b;"><?= htmlspecialchars($apt['hospital_name'] ?? 'Hospital Facility') ?></span>
                      <p style="margin:4px 0 0 0; font-size:0.82rem; color:#475569;">
                        <?= htmlspecialchars($apt['reason'] ?? 'Consultation') ?>
                      </p>
                    </div>
                    <div style="text-align:right;">
                      <span style="display:block; font-size:0.88rem; font-weight:700; color:#059669;">
                        <?= date('d M Y', strtotime($apt['appointment_date'])) ?>
                      </span>
                      <span style="font-size:0.8rem; color:#64748b;"><?= htmlspecialchars($apt['time_slot'] ?? '09:00 AM') ?></span>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>

          <!-- Right Column: Recent Lab Test Activities -->
          <div class="card" style="background:#fff; border-radius:8px; padding:20px; border:1px solid #e2e8f0;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
              <h3 style="margin:0; font-size:1.15rem; color:#0f172a;">
                <i class="fa-solid fa-vial text-blue"></i> Recent Lab Test Orders
              </h3>
              <a href="lab-test.php" style="color:#0284c7; font-size:0.85rem; font-weight:600; text-decoration:none;">View All &rarr;</a>
            </div>

            <?php if (empty($activities)): ?>
              <div style="text-align:center; padding:30px; color:#64748b;">
                <p>No recent lab investigations ordered.</p>
                <a href="medical-test-request.php" style="display:inline-block; padding:8px 16px; background:#0284c7; color:#fff; text-decoration:none; border-radius:6px; font-weight:600; font-size:0.85rem;">Order a Test</a>
              </div>
            <?php else: ?>
              <div style="display:flex; flex-direction:column; gap:12px;">
                <?php foreach ($activities as $act): ?>
                  <div style="padding:14px; border-radius:6px; border:1px solid #f1f5f9; background:#f8fafc; display:flex; justify-content:space-between; align-items:center;">
                    <div>
                      <strong style="display:block; color:#0f172a; font-size:0.95rem;"><?= htmlspecialchars($act['test_name']) ?></strong>
                      <span style="font-size:0.82rem; color:#64748b;">Advised by: <?= htmlspecialchars($act['doctor_name'] ?? 'Attending Physician') ?></span>
                    </div>
                    <div style="text-align:right;">
                      <span style="display:block; font-size:0.85rem; font-weight:600; color:#0284c7;">
                        <?= date('d M Y', strtotime($act['ordered_at'])) ?>
                      </span>
                      <a href="lab-test.php" style="font-size:0.8rem; color:#64748b; text-decoration:underline;">View Details</a>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>

        </div>

      </main>
    </div>
  </div>

  <script src="/public_html/assets/js/patient/dashboard.js"></script>
</body>
</html>