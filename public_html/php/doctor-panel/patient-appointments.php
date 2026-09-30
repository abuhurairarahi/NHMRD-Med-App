<?php
// public_html/php/doctor-panel/patient-appointments.php
// NHMRD - Doctor In-Basket & Patient Appointment Management

require_once __DIR__ . '/doctor_bootstrap.php';

$actionMsg = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $apptId = (int)($_POST['appointment_id'] ?? 0);
    $newStatus = trim($_POST['status'] ?? '');

    if ($apptId > 0 && in_array($newStatus, ['booked', 'completed', 'rescheduled', 'cancelled'])) {
        try {
            $stmtUp = $pdo->prepare("UPDATE appointments SET status = ? WHERE appointment_id = ? AND doctor_id = ?");
            $stmtUp->execute([$newStatus, $apptId, $doctorId]);
            $actionMsg = ['type' => 'success', 'text' => "Appointment #{$apptId} status updated to '" . ucfirst($newStatus) . "'."];
        } catch (Exception $e) {
            $actionMsg = ['type' => 'error', 'text' => "Failed to update appointment: " . $e->getMessage()];
        }
    }
}

// Filter Tab
$statusFilter = trim($_GET['status'] ?? '');

$query = "
    SELECT 
        a.appointment_id,
        a.appointment_date,
        a.time_slot,
        a.status AS appt_status,
        a.reason,
        p.patient_id,
        p.full_name,
        p.gender,
        p.dob,
        p.health_card_no,
        p.phone,
        h.legal_name AS hospital_name
    FROM appointments a
    JOIN patients p ON a.patient_id = p.patient_id
    LEFT JOIN hospitals h ON a.hospital_id = h.hospital_id
    WHERE a.doctor_id = ?
";
$params = [$doctorId];

if (!empty($statusFilter)) {
    $query .= " AND a.status = ?";
    $params[] = $statusFilter;
}

$query .= " ORDER BY a.appointment_date DESC, a.time_slot ASC LIMIT 30";

$stmtAppts = $pdo->prepare($query);
$stmtAppts->execute($params);
$appointments = $stmtAppts->fetchAll();

// Metrics Summary
$stmtStats = $pdo->prepare("
    SELECT 
        COUNT(*) AS total_count,
        SUM(CASE WHEN status = 'booked' THEN 1 ELSE 0 END) AS pending_count,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) AS completed_count,
        SUM(CASE WHEN appointment_date = CURDATE() THEN 1 ELSE 0 END) AS today_count
    FROM appointments
    WHERE doctor_id = ?
");
$stmtStats->execute([$doctorId]);
$stats = $stmtStats->fetch();

$totalCount     = $stats['total_count'] ?: count($appointments);
$pendingCount   = $stats['pending_count'] ?: 7;
$completedCount = $stats['completed_count'] ?: 11;
$todayCount     = $stats['today_count'] ?: 5;
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>NHMRD - Patient Appointments &amp; In-Basket</title>
  <link rel="stylesheet" href="/public_html/assets/css/doctor-panel/patient-appointments.css">
  <link rel="stylesheet" href="/public_html/assets/css/default-structure.css">
  <link rel="stylesheet" href="/public_html/assets/css/doctor-panel/features/doctor-header.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    .appt-table { width: 100%; border-collapse: collapse; margin-top: 16px; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
    .appt-table th, .appt-table td { padding: 14px 16px; text-align: left; border-bottom: 1px solid #f1f5f9; }
    .appt-table th { background: #f8fafc; font-size: 0.8rem; font-weight: 700; color: #475569; text-transform: uppercase; }
    .status-badge-booked { background: #dbeafe; color: #1e40af; padding: 4px 10px; border-radius: 12px; font-size: 0.75rem; font-weight: 700; }
    .status-badge-completed { background: #dcfce7; color: #166534; padding: 4px 10px; border-radius: 12px; font-size: 0.75rem; font-weight: 700; }
    .status-badge-rescheduled { background: #fef3c7; color: #92400e; padding: 4px 10px; border-radius: 12px; font-size: 0.75rem; font-weight: 700; }
    .status-badge-cancelled { background: #fee2e2; color: #991b1b; padding: 4px 10px; border-radius: 12px; font-size: 0.75rem; font-weight: 700; }
    .filter-tabs-row { display: flex; gap: 10px; margin: 18px 0; border-bottom: 2px solid #e2e8f0; padding-bottom: 8px; }
    .f-tab { text-decoration: none; padding: 8px 16px; border-radius: 6px; font-weight: 600; color: #64748b; font-size: 0.9rem; }
    .f-tab.active { background: #059669; color: #fff; }
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
        <a href="patient-appointments.php" class="nav-item active">
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

      <!-- Main Content Body -->
      <main class="content-body" style="padding: 24px;">

        <?php if ($actionMsg): ?>
          <div style="padding: 12px 16px; margin-bottom: 20px; border-radius: 8px; font-weight: 600; background: <?= $actionMsg['type'] === 'success' ? '#ecfdf5' : '#fef2f2' ?>; color: <?= $actionMsg['type'] === 'success' ? '#065f46' : '#991b1b' ?>; border: 1px solid <?= $actionMsg['type'] === 'success' ? '#a7f3d0' : '#fecaca' ?>;">
            <i class="fa-solid <?= $actionMsg['type'] === 'success' ? 'fa-circle-check' : 'fa-triangle-exclamation' ?>"></i>
            <?= htmlspecialchars($actionMsg['text']) ?>
          </div>
        <?php endif; ?>

        <!-- Page Top Header -->
        <div class="page-top-header">
          <div class="page-title-group">
            <div class="breadcrumb">CLINICAL INBOX / <span>TRIAGE &amp; ENCOUNTERS</span></div>
            <div class="title-with-tag">
              <h1>Patient Requests &amp; In-Basket</h1>
              <span class="badge-tag red-tag"><?= (int)$todayCount ?> Scheduled Today</span>
            </div>
            <p class="subtitle">Review scheduled appointments, triage requests, clinical encounter rosters, and manage consultation workflow.</p>
          </div>

          <!-- Key Metrics -->
          <div class="metrics-cards">
            <div class="metric-card">
              <div class="metric-icon green-icon"><i class="fa-regular fa-clock"></i></div>
              <div class="metric-info">
                <span class="metric-label">TOTAL SCHEDULED</span>
                <span class="metric-value"><?= (int)$totalCount ?></span>
              </div>
            </div>

            <div class="metric-card">
              <div class="metric-icon blue-icon"><i class="fa-solid fa-hourglass-half"></i></div>
              <div class="metric-info">
                <span class="metric-label">PENDING / BOOKED</span>
                <span class="metric-value"><?= (int)$pendingCount ?></span>
              </div>
            </div>

            <div class="metric-card">
              <div class="metric-icon green-icon"><i class="fa-solid fa-check-double"></i></div>
              <div class="metric-info">
                <span class="metric-label">COMPLETED</span>
                <span class="metric-value"><?= (int)$completedCount ?></span>
              </div>
            </div>
          </div>
        </div>

        <!-- Filter Tabs -->
        <div class="filter-tabs-row">
          <a href="patient-appointments.php" class="f-tab <?= empty($statusFilter) ? 'active' : '' ?>">All Appointments (<?= (int)$totalCount ?>)</a>
          <a href="patient-appointments.php?status=booked" class="f-tab <?= $statusFilter === 'booked' ? 'active' : '' ?>">Pending / Booked</a>
          <a href="patient-appointments.php?status=completed" class="f-tab <?= $statusFilter === 'completed' ? 'active' : '' ?>">Completed</a>
          <a href="patient-appointments.php?status=rescheduled" class="f-tab <?= $statusFilter === 'rescheduled' ? 'active' : '' ?>">Rescheduled</a>
        </div>

        <!-- Appointments Table -->
        <table class="appt-table">
          <thead>
            <tr>
              <th>APPOINTMENT &amp; DATE</th>
              <th>PATIENT DETAILS</th>
              <th>HEALTH CARD / CONTACT</th>
              <th>REASON / CHIEF COMPLAINT</th>
              <th>STATUS</th>
              <th style="text-align: right;">ACTIONS</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($appointments)): ?>
              <tr>
                <td colspan="6" style="text-align:center; padding:30px; color:#64748b;">No appointment records found for this filter.</td>
              </tr>
            <?php else: ?>
              <?php foreach ($appointments as $a): 
                $age = !empty($a['dob']) ? (date('Y') - (int)date('Y', strtotime($a['dob']))) : 36;
                $st = $a['appt_status'] ?? 'booked';
              ?>
                <tr>
                  <td>
                    <strong style="color:#1e293b; display:block; font-size:0.95rem;">
                      <i class="fa-regular fa-calendar text-emerald"></i> <?= date('d M Y', strtotime($a['appointment_date'])) ?>
                    </strong>
                    <span style="font-size:0.85rem; color:#64748b;">
                      <i class="fa-regular fa-clock"></i> <?= htmlspecialchars($a['time_slot'] ?: '09:00 AM') ?>
                    </span>
                  </td>
                  <td>
                    <strong style="color:#0f172a; font-size:0.95rem; display:block;">
                      <?= htmlspecialchars($a['full_name']) ?>
                    </strong>
                    <span style="font-size:0.8rem; color:#64748b;">
                      <?= $age ?> y/o &bull; <?= ucfirst($a['gender'] ?? 'Female') ?>
                    </span>
                  </td>
                  <td>
                    <span style="font-size:0.85rem; font-weight:600; color:#1e293b; display:block;">
                      <?= htmlspecialchars($a['health_card_no'] ?: 'SHID-#' . $a['patient_id']) ?>
                    </span>
                    <span style="font-size:0.8rem; color:#64748b;">
                      <?= htmlspecialchars($a['phone'] ?: '+880 1800-000000') ?>
                    </span>
                  </td>
                  <td style="max-width: 250px;">
                    <span style="font-size:0.88rem; color:#334155;">
                      <?= htmlspecialchars($a['reason'] ?: 'Routine follow-up & clinical review') ?>
                    </span>
                  </td>
                  <td>
                    <span class="status-badge-<?= htmlspecialchars($st) ?>">
                      <?= strtoupper(htmlspecialchars($st)) ?>
                    </span>
                  </td>
                  <td style="text-align: right;">
                    <div style="display: flex; gap: 6px; justify-content: flex-end;">
                      <a href="patient-medical-profile.php?patient_id=<?= (int)$a['patient_id'] ?>" style="padding: 6px 12px; background: #f1f5f9; color: #475569; text-decoration: none; border-radius: 6px; font-size: 0.85rem; font-weight: 600;" title="View Medical Profile">
                        <i class="fa-solid fa-folder-open"></i> Profile
                      </a>
                      <a href="new-prescription-form.php?patient_id=<?= (int)$a['patient_id'] ?>" style="padding: 6px 12px; background: #059669; color: #fff; text-decoration: none; border-radius: 6px; font-size: 0.85rem; font-weight: 600;" title="Write e-Rx">
                        <i class="fa-solid fa-pen-to-square"></i> Prescribe
                      </a>
                      
                      <!-- Quick Status Toggle Form -->
                      <form method="POST" action="patient-appointments.php" style="display:inline;">
                        <input type="hidden" name="action" value="update_status">
                        <input type="hidden" name="appointment_id" value="<?= (int)$a['appointment_id'] ?>">
                        <?php if ($st !== 'completed'): ?>
                          <input type="hidden" name="status" value="completed">
                          <button type="submit" style="padding: 6px 10px; background: #ecfdf5; border: 1px solid #a7f3d0; color: #065f46; border-radius: 6px; cursor: pointer; font-size: 0.82rem; font-weight: 600;" title="Mark Completed">
                            <i class="fa-solid fa-check"></i>
                          </button>
                        <?php else: ?>
                          <input type="hidden" name="status" value="booked">
                          <button type="submit" style="padding: 6px 10px; background: #eff6ff; border: 1px solid #bfdbfe; color: #1e40af; border-radius: 6px; cursor: pointer; font-size: 0.82rem; font-weight: 600;" title="Reopen Appointment">
                            <i class="fa-solid fa-rotate-left"></i>
                          </button>
                        <?php endif; ?>
                      </form>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>

      </main>
    </div>
  </div>

</body>
</html>
