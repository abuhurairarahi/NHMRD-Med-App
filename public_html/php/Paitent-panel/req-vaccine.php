<?php
// Load Patient Bootstrap
require_once __DIR__ . '/patient_bootstrap.php';

// Fetch vaccine catalog
$vaccines = $pdo->query("SELECT * FROM vaccine_catalog ORDER BY vaccine_name ASC")->fetchAll();

// Fetch hospitals
$hospitals = $pdo->query("SELECT hospital_id, legal_name FROM hospitals ORDER BY legal_name ASC")->fetchAll();

// Handle direct POST fallback
$successMsg = '';
$errorMsg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['direct_submit'])) {
    $vaccineId     = (int)($_POST['vaccine_id'] ?? 0);
    $hospitalId    = (int)($_POST['hospital_id'] ?? 0);
    $scheduledDate = $_POST['scheduled_date'] ?? date('Y-m-d', strtotime('+2 days'));
    $timeSlot      = $_POST['time_slot'] ?? '09:00 AM - 11:00 AM';
    $declaration   = isset($_POST['health_declaration_confirmed']) ? 1 : 0;

    if ($vaccineId > 0 && $hospitalId > 0 && $declaration) {
        try {
            $stmtInsert = $pdo->prepare("
                INSERT INTO vaccine_appointments 
                (patient_id, vaccine_id, hospital_id, scheduled_date, time_slot, health_declaration_confirmed, status) 
                VALUES (?, ?, ?, ?, ?, ?, 'scheduled')
            ");
            $stmtInsert->execute([$patientId, $vaccineId, $hospitalId, $scheduledDate, $timeSlot, $declaration]);
            $successMsg = "Vaccination appointment requested successfully! Ref ID: #" . $pdo->lastInsertId();
        } catch (Exception $e) {
            $errorMsg = "Database error: " . $e->getMessage();
        }
    } else {
        $errorMsg = "Please fill all required fields and confirm health declaration.";
    }
}

// Fetch Vaccine Appointments for current patient
$stmtVAppts = $pdo->prepare("
    SELECT va.*, vc.vaccine_name, h.legal_name AS hospital_name
    FROM vaccine_appointments va
    JOIN vaccine_catalog vc ON va.vaccine_id = vc.vaccine_id
    JOIN hospitals h ON va.hospital_id = h.hospital_id
    WHERE va.patient_id = ?
    ORDER BY va.scheduled_date DESC
");
$stmtVAppts->execute([$patientId]);
$vaccineAppointments = $stmtVAppts->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>NHMRD - Request Vaccine</title>
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
        <a href="dashboard.php" class="nav-item">
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
        <a href="req-vaccine.php" class="nav-item active">
          <i class="fa-solid fa-shield-virus"></i> 
          <span>Request Vaccine</span>
        </a>
        <a href="paitent-info.php" class="nav-item">
          <i class="fa-solid fa-id-card"></i> 
          <span>Patient Info</span>
        </a>
      </nav>

      <div class="sidebar-footer">
        <button class="logout-btn" id="logoutBtn" onclick="location.href='/public_html/api/logout.php'">
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
          <button class="icon-btn" id="notificationBtn" onclick="alert('No new notifications');"><i class="fa-regular fa-bell"></i></button>
          
          <div class="user-badge-avatar"><?= htmlspecialchars($userInitials) ?></div>
          
          <div class="user-info-text">
            <span class="user-name"><?= htmlspecialchars($patient['full_name']) ?></span>
            <span class="patient-id">Patient ID #<?= htmlspecialchars($patient['user_uid'] ?? $patient['patient_id']) ?></span>
          </div>
        </div>
      </header>

      <!-- Main Body Container -->
      <main class="content-body">

        <!-- Patient Header Card -->
        <div class="card patient-header-card">
          <div class="patient-card-top">
            <div class="id-card-preview">
              <div class="id-card-inner">
                <div class="id-photo">
                  <svg viewBox="0 0 24 24" fill="#cbd5e1">
                    <circle cx="12" cy="8" r="4" />
                    <path d="M12 14c-6.1 0-8 4-8 4v2h16v-2s-1.9-4-8-4z" />
                  </svg>
                </div>
                <div class="id-lines">
                  <div class="line"></div>
                  <div class="line short"></div>
                  <div class="pill-badge">
                    <svg viewBox="0 0 24 24" fill="#2563eb" width="10" height="10">
                      <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
                    </svg>
                  </div>
                </div>
              </div>
            </div>

            <div class="patient-main-details">
              <div class="patient-title-row">
                <h2><?= htmlspecialchars($patient['full_name']) ?></h2>
                <span class="badge-status">Citizen Patient</span>
              </div>
              <p class="subtitle-text">National Health Identification &bull; Regular Outpatient Profile</p>
            </div>
          </div>

          <div class="patient-info-grid">
            <div class="info-item"><span class="label">NID:</span> <span class="value"><?= htmlspecialchars($patient['nid'] ?? 'N/A') ?></span></div>
            <div class="info-item"><span class="label">Email:</span> <span class="value"><?= htmlspecialchars($patient['email'] ?? 'N/A') ?></span></div>
            <div class="info-item"><span class="label">Phone:</span> <span class="value"><?= htmlspecialchars($patient['phone'] ?? 'N/A') ?></span></div>
            <div class="info-item"><span class="label">Gender:</span> <span class="value"><?= htmlspecialchars(ucfirst($patient['gender'])) ?></span></div>
            <div class="info-item"><span class="label">Date of Birth:</span> <span class="value"><?= date('d-m-Y', strtotime($patient['dob'])) ?></span></div>
            <div class="info-item"><span class="label">Blood Group:</span> <span class="value text-danger"><?= htmlspecialchars($patient['blood_group'] ?? 'N/A') ?></span></div>
          </div>
        </div>

        <?php if (!empty($successMsg)): ?>
          <div style="background: #dcfce7; border: 1px solid #86efac; color: #166534; padding: 14px 18px; border-radius: 8px; margin-bottom: 20px; font-weight: 600;">
            <i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($successMsg) ?>
          </div>
        <?php endif; ?>

        <?php if (!empty($errorMsg)): ?>
          <div style="background: #fee2e2; border: 1px solid #fca5a5; color: #991b1b; padding: 14px 18px; border-radius: 8px; margin-bottom: 20px; font-weight: 600;">
            <i class="fa-solid fa-triangle-exclamation"></i> <?= htmlspecialchars($errorMsg) ?>
          </div>
        <?php endif; ?>

        <!-- 2 Panels: Request Form + History Table -->
        <div class="panels-grid">

          <!-- Left Panel: Vaccine Request Form -->
          <div class="card panel-card">
            <div class="panel-header">
              <div class="panel-title">
                <i class="fa-solid fa-syringe"></i> Request Vaccine Appointment
              </div>
            </div>

            <form id="reqVaccineForm" method="POST" action="" style="padding: 20px;">
              <input type="hidden" name="direct_submit" value="1">
              <input type="hidden" name="patient_id" value="<?= $patient['patient_id'] ?>">

              <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px; font-weight: bold;">Select Vaccine</label>
                <select name="vaccine_id" id="vaccine_id" required style="width: 100%; padding: 10px; border-radius: 6px; border: 1px solid #cbd5e1;">
                  <option value="">-- Choose Vaccine --</option>
                  <?php foreach ($vaccines as $vac): ?>
                    <option value="<?= $vac['vaccine_id'] ?>"><?= htmlspecialchars($vac['vaccine_name']) ?> (<?= $vac['dose_ml'] ?> ml &bull; <?= $vac['route'] ?>)</option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px; font-weight: bold;">Select Hospital / Facility</label>
                <select name="hospital_id" id="hospital_id" required style="width: 100%; padding: 10px; border-radius: 6px; border: 1px solid #cbd5e1;">
                  <option value="">-- Choose Preferred Hospital --</option>
                  <?php foreach ($hospitals as $hosp): ?>
                    <option value="<?= $hosp['hospital_id'] ?>"><?= htmlspecialchars($hosp['legal_name']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px; font-weight: bold;">Preferred Date</label>
                <input type="date" name="scheduled_date" id="scheduled_date" min="<?= date('Y-m-d') ?>" value="<?= date('Y-m-d', strtotime('+2 days')) ?>" required style="width: 100%; padding: 10px; border-radius: 6px; border: 1px solid #cbd5e1;">
              </div>

              <div style="margin-bottom: 15px;">
                <label style="display: block; margin-bottom: 5px; font-weight: bold;">Time Slot</label>
                <select name="time_slot" id="time_slot" required style="width: 100%; padding: 10px; border-radius: 6px; border: 1px solid #cbd5e1;">
                  <option value="09:00 AM - 11:00 AM">09:00 AM - 11:00 AM</option>
                  <option value="11:00 AM - 01:00 PM">11:00 AM - 01:00 PM</option>
                  <option value="02:00 PM - 04:00 PM">02:00 PM - 04:00 PM</option>
                </select>
              </div>

              <div style="margin-bottom: 20px;">
                <label style="display: flex; align-items: center; gap: 10px; font-size: 14px;">
                  <input type="checkbox" name="health_declaration_confirmed" value="1" required checked>
                  I confirm that I am currently fit and free of major acute sickness.
                </label>
              </div>

              <button type="submit" id="submitBtn" class="btn-primary" style="width: 100%; padding: 12px; cursor: pointer;">
                Submit Vaccine Request
              </button>
            </form>
          </div>

          <!-- Right Panel: Requested Vaccine Appointments History -->
          <div class="card panel-card">
            <div class="panel-header">
              <div class="panel-title">
                <i class="fa-solid fa-clock-rotate-left"></i> Scheduled Requests (<?= count($vaccineAppointments) ?>)
              </div>
            </div>

            <div class="appointment-list" style="padding: 15px;">
              <?php if (empty($vaccineAppointments)): ?>
                <p style="color: #64748b; padding: 20px; text-align: center;">No scheduled vaccine requests found.</p>
              <?php else: ?>
                <?php foreach ($vaccineAppointments as $vAppt): ?>
                  <div class="appointment-item" style="padding: 12px; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center;">
                    <div>
                      <div class="meta-date" style="font-size: 12px; color: #64748b;">
                        Date: <?= date('d-m-Y', strtotime($vAppt['scheduled_date'])) ?> &bull; <?= htmlspecialchars($vAppt['time_slot']) ?>
                      </div>
                      <h3 style="margin: 4px 0; font-size: 16px;"><?= htmlspecialchars($vAppt['vaccine_name']) ?></h3>
                      <div style="font-size: 13px; color: #475569;">At: <strong><?= htmlspecialchars($vAppt['hospital_name']) ?></strong></div>
                    </div>
                    <div>
                      <span class="badge-status-pill badge-due" style="background: #e0f2fe; color: #0369a1; padding: 4px 10px; border-radius: 12px; font-size: 0.78rem; font-weight: 700;"><?= ucfirst($vAppt['status']) ?></span>
                    </div>
                  </div>
                <?php endforeach; ?>
              <?php endif; ?>
            </div>
          </div>

        </div>

      </main>
    </div>
  </div>

  <script src="/public_html/assets/js/paitent/req-vaccine.js"></script>
</body>
</html>