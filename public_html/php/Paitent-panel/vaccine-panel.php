<?php
// Load Patient Bootstrap
require_once __DIR__ . '/patient_bootstrap.php';

// Fetch Vaccination Records for current patient
$stmtVac = $pdo->prepare("
    SELECT vr.*, 
           vc.vaccine_name, vc.batch_number AS default_batch, vc.dose_ml, vc.route,
           h.legal_name AS hospital_name, h.dghs_code,
           d.full_name AS doctor_name
    FROM vaccination_records vr
    JOIN vaccine_catalog vc ON vr.vaccine_id = vc.vaccine_id
    LEFT JOIN hospitals h ON vr.hospital_id = h.hospital_id
    LEFT JOIN doctors d ON vr.administered_by_doctor_id = d.doctor_id
    WHERE vr.patient_id = ?
    ORDER BY vr.administered_date ASC
");
$stmtVac->execute([$patientId]);
$vaccineRecords = $stmtVac->fetchAll();

// Group doses by vaccine_id
$groupedVaccines = [];
foreach ($vaccineRecords as $rec) {
    $vId = $rec['vaccine_id'];
    if (!isset($groupedVaccines[$vId])) {
        $groupedVaccines[$vId] = [
            'vaccine_id'     => $vId,
            'vaccine_name'   => $rec['vaccine_name'],
            'dose_ml'        => $rec['dose_ml'],
            'route'          => $rec['route'],
            'hospital_name'  => $rec['hospital_name'],
            'certificate_no' => $rec['certificate_no'],
            'serology_titer' => $rec['serology_titer'],
            'doses'          => []
        ];
    }
    $groupedVaccines[$vId]['doses'][] = $rec;
}

// Fetch upcoming scheduled vaccine appointments
$stmtUpVac = $pdo->prepare("
    SELECT va.*, vc.vaccine_name, h.legal_name AS hospital_name
    FROM vaccine_appointments va
    JOIN vaccine_catalog vc ON va.vaccine_id = vc.vaccine_id
    LEFT JOIN hospitals h ON va.hospital_id = h.hospital_id
    WHERE va.patient_id = ? AND va.status = 'scheduled'
    ORDER BY va.scheduled_date ASC
");
$stmtUpVac->execute([$patientId]);
$upcomingVaccines = $stmtUpVac->fetchAll();

$completedVaccinesCount = count($groupedVaccines);
$upcomingBoostersCount  = count($upcomingVaccines);

// Fetch immunization partner hospitals
$partnerHospitals = $pdo->query("SELECT legal_name, dghs_code FROM hospitals ORDER BY legal_name ASC LIMIT 5")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Vaccine Records - NHMRD</title>
  <link rel="stylesheet" href="/public_html/assets/css/patient-panel/vaccine-panel.css">
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
        <a href="vaccine-panel.php" class="nav-item active">
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
        <button class="logout-btn" onclick="location.href='/public_html/api/logout.php'">
          <i class="fa-solid fa-arrow-right-from-bracket"></i> 
          <span>Logout</span>
        </button>
      </div>
    </aside>

    <!-- Main Content Wrapper -->
    <div class="main-wrapper">
      <!-- Top Header Bar -->
      <header class="patient-header">
        <div class="search-bar">
          <i class="fa-solid fa-magnifying-glass"></i>
          <input type="text" placeholder="Search Inoculations..." oninput="handleSearch(event)">
        </div>

        <div class="header-right">
          <button class="icon-btn" onclick="alert('No new notifications');"><i class="fa-regular fa-bell"></i></button>
          <div class="user-badge-avatar"><?= htmlspecialchars($userInitials) ?></div>
          <span class="user-name"><?= htmlspecialchars($patient['full_name']) ?></span>
        </div>
      </header>

      <!-- Main Content Body -->
      <main class="content-body">

        <!-- Page Header -->
        <div class="page-title-row">
          <div>
            <div class="registry-tag">IMMUNIZATION REGISTRY &bull; DGHS National Record</div>
            <h1>Vaccination & Immunization History</h1>
            <p class="subtitle">Official verified inoculation schedule and digital credentials for <?= htmlspecialchars($patient['full_name']) ?></p>
          </div>
          <div class="title-actions">
            <button class="btn-outline" onclick="handleAddExternalCertificate()"><i class="fa-regular fa-file-lines"></i> Add External Certificate</button>
            <button class="btn-primary-dark" onclick="handleDigitalPassport()"><i class="fa-solid fa-qrcode"></i> Digital Vaccine Passport</button>
          </div>
        </div>

        <!-- User Profile Card -->
        <div class="card user-profile-card">
          <div class="user-info-left">
            <div class="user-avatar-sm">
              <i class="fa-solid fa-user"></i>
            </div>
            <div class="user-text">
              <div class="user-name-row">
                <h2><?= htmlspecialchars($patient['full_name']) ?></h2>
                <span class="verified-tag"><i class="fa-solid fa-circle-check"></i> Verified Citizen</span>
              </div>
              <p class="user-meta">
                Patient ID: #<?= htmlspecialchars($patient['user_uid'] ?? $patient['patient_id']) ?> &bull; NID: <?= htmlspecialchars($patient['nid'] ?? 'N/A') ?> &bull; DOB: <?= date('d-m-Y', strtotime($patient['dob'])) ?> &bull; <span class="blood-group">Blood Group: <?= htmlspecialchars($patient['blood_group'] ?? 'N/A') ?></span>
              </p>
            </div>
          </div>
          <button class="btn-primary-blue" onclick="location.href='req-vaccine.php'"><i class="fa-solid fa-circle-plus"></i> Request New Vaccine / Booster</button>
        </div>

        <!-- Stats Grid (3 Columns) -->
        <div class="stats-grid">
          <div class="stat-card">
            <div class="stat-info">
              <span class="stat-label">COMPLETED VACCINES</span>
              <strong class="stat-value"><?= $completedVaccinesCount ?> <span class="stat-unit">Primary series</span></strong>
              <span class="stat-sub"><i class="fa-regular fa-circle-check"></i> 100% Core coverage</span>
            </div>
            <div class="stat-icon blue-light"><i class="fa-solid fa-syringe"></i></div>
          </div>

          <div class="stat-card">
            <div class="stat-info">
              <span class="stat-label">SCHEDULED / BOOSTERS</span>
              <strong class="stat-value"><?= $upcomingBoostersCount ?> <span class="stat-unit">Upcoming</span></strong>
              <span class="stat-sub"><i class="fa-regular fa-clock"></i> <?= !empty($upcomingVaccines) ? htmlspecialchars($upcomingVaccines[0]['vaccine_name']) : 'All Up to Date' ?></span>
            </div>
            <div class="stat-icon purple-light"><i class="fa-regular fa-bell"></i></div>
          </div>

          <div class="stat-card">
            <div class="stat-info">
              <span class="stat-label">REGISTRY VERIFICATION</span>
              <strong class="stat-value text-green">DGHS <span class="stat-unit text-dark">Sync</span></strong>
              <span class="stat-sub">Active &bull; Health Card: <?= htmlspecialchars($patient['health_card_no'] ?? 'N/A') ?></span>
            </div>
            <div class="stat-icon cyan-light"><i class="fa-solid fa-rotate"></i></div>
          </div>
        </div>

        <!-- Section Title: Registered Inoculation Logs -->
        <div class="section-heading-row">
          <h3><i class="fa-regular fa-square-check"></i> Registered Inoculation Logs</h3>
          <span class="count-label">Displaying <?= count($vaccineRecords) ?> Recorded Doses across <?= $completedVaccinesCount ?> Vaccines</span>
        </div>

        <?php if (empty($groupedVaccines)): ?>
          <div class="card" style="padding: 30px; text-align: center; color: #64748b;">
            <i class="fa-solid fa-shield-virus" style="font-size: 2.5rem; margin-bottom: 10px; color: #94a3b8;"></i>
            <h3>No Inoculation Records Found</h3>
            <p>You have not registered any administered vaccines in the national directory.</p>
          </div>
        <?php else: ?>
          <?php foreach ($groupedVaccines as $vac): ?>
            <div class="card vaccine-card">
              <div class="vaccine-header">
                <div class="vaccine-title-group">
                  <div class="vaccine-icon blue-bg"><i class="fa-solid fa-virus-slash"></i></div>
                  <div>
                    <div class="vaccine-name-row">
                      <h3><?= htmlspecialchars($vac['vaccine_name']) ?></h3>
                      <span class="vaccine-badge border-blue"><?= htmlspecialchars($vac['dose_ml']) ?> mL &bull; <?= htmlspecialchars($vac['route']) ?></span>
                    </div>
                    <p class="vaccine-location">
                      <i class="fa-regular fa-hospital"></i> <?= htmlspecialchars($vac['hospital_name'] ?? 'Accredited Civil Hospital') ?> &bull; 
                      <span class="cert-id"># Certificate ID: <strong><?= htmlspecialchars($vac['certificate_no'] ?? 'VAC-VERIFIED-' . $vac['vaccine_id']) ?></strong></span>
                    </p>
                    <?php if (!empty($vac['serology_titer'])): ?>
                      <p class="vaccine-subtext" style="font-size: 0.85rem; color: #047857; margin-top: 4px;">
                        <i class="fa-solid fa-microscope"></i> Titer Assessment: <strong><?= htmlspecialchars($vac['serology_titer']) ?></strong>
                      </p>
                    <?php endif; ?>
                  </div>
                </div>
                <span class="status-badge success"><i class="fa-regular fa-circle-check"></i> Verified Complete</span>
              </div>

              <!-- Dosage Progression Grid -->
              <div class="dosage-section">
                <span class="section-sub-title">DOSAGE PROGRESSION</span>
                <div class="dosage-grid">
                  <?php foreach ($vac['doses'] as $d): ?>
                    <div class="dose-card">
                      <div class="dose-info">
                        <span class="dose-label">DOSE <?= str_pad($d['dose_number'], 2, '0', STR_PAD_LEFT) ?></span>
                        <strong class="dose-date"><?= date('d-m-Y', strtotime($d['administered_date'])) ?></strong>
                        <span class="dose-batch">Batch #<?= htmlspecialchars($d['default_batch'] ?? 'BATCH-' . $d['record_id']) ?></span>
                      </div>
                      <i class="fa-regular fa-circle-check check-icon"></i>
                    </div>
                  <?php endforeach; ?>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>

        <!-- Official Immunization Centers Box -->
        <div class="card official-centers-card">
          <div class="centers-header">
            <h4><i class="fa-solid fa-hospital-user"></i> Official Immunization Centers</h4>
            <p>All administered doses are synced directly via authorized civil hospitals with the Directorate General of Health Services.</p>
          </div>

          <div class="centers-list">
            <?php foreach ($partnerHospitals as $ph): ?>
              <div class="center-row">
                <span class="center-name"><?= htmlspecialchars($ph['legal_name']) ?></span>
                <span class="facility-id">Facility Code: <strong><?= htmlspecialchars($ph['dghs_code'] ?? 'DGHS-HOSP') ?></strong></span>
              </div>
            <?php endforeach; ?>
          </div>

          <div class="centers-footer">
            <span class="sync-status">Last Registry Synced: <?= date('d M Y') ?> &bull; 04:30 AM</span>
            <span class="live-status"><span class="dot"></span> Live DGHS Gateway</span>
          </div>
        </div>

      </main>
    </div>
  </div>

  <script src="/public_html/assets/js/paitent/vaccine-panel.js"></script>
</body>
</html>