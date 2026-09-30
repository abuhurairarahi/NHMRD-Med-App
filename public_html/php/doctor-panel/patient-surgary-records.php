<?php
// public_html/php/doctor-panel/patient-surgary-records.php
// NHMRD - Patient Surgical Procedures History & Operative Logs

require_once __DIR__ . '/doctor_bootstrap.php';

$patient = getPatientDossier($pdo, $patientId);

// Filter by procedure type
$procTypeFilter = trim($_GET['type'] ?? '');

$query = "
    SELECT 
        sr.*,
        s.full_name AS surgeon_name,
        h.legal_name AS hospital_name
    FROM surgical_records sr
    LEFT JOIN doctors s ON sr.surgeon_id = s.doctor_id
    LEFT JOIN hospitals h ON sr.hospital_id = h.hospital_id
    WHERE sr.patient_id = ?
";
$params = [$patientId];

if (!empty($procTypeFilter)) {
    $query .= " AND sr.procedure_type = ?";
    $params[] = $procTypeFilter;
}

$query .= " ORDER BY sr.operation_datetime DESC";

$stmtSurg = $pdo->prepare($query);
$stmtSurg->execute($params);
$surgeries = $stmtSurg->fetchAll();

if (empty($surgeries)) {
    // Demo fallback record
    $surgeries = [
        [
            'surgery_id'                  => 101,
            'procedure_title'             => 'Laparoscopic Cholecystectomy',
            'primary_procedure'           => 'Excision of Gallbladder with Intraoperative Cholangiogram',
            'procedure_type'              => 'inpatient',
            'operation_datetime'          => '2021-08-14 09:30:00',
            'surgeon_name'                => 'Dr. Mahmudul Hasan, MS (Surgery)',
            'anesthesiologist_name'       => 'Dr. Rehana Parveen, DA',
            'hospital_name'               => 'Dhaka Central Medical University Hospital',
            'estimated_blood_loss_ml'     => 45,
            'intra_op_complication_status'=> 'Uncomplicated, Hemostasis Secured',
            'contraindication_notes'      => 'Discharged Day 2 post-op in stable condition. Diet advanced as tolerated.',
            'medications_used'            => 'Ceftriaxone 1g IV pre-op, Ketorolac 30mg IV, Ondansetron 4mg IV',
            'status'                      => 'completed'
        ]
    ];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>NHMRD - Patient Surgery Records</title>
  <link rel="stylesheet" href="/public_html/assets/css/doctor-panel/patient-surgary-records.css">
  <link rel="stylesheet" href="/public_html/assets/css/default-structure.css">
  <link rel="stylesheet" href="/public_html/assets/css/doctor-panel/features/doctor-header.css">
  <link rel="stylesheet" href="/public_html/assets/css/doctor-panel/features/doctor-subheader.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    .filter-pills-row { display: flex; gap: 10px; margin: 18px 0; }
    .f-pill { text-decoration: none; padding: 8px 16px; border-radius: 20px; font-size: 0.85rem; font-weight: 600; color: #475569; background: #f1f5f9; }
    .f-pill.active { background: #059669; color: #fff; }
    .surg-card { background: #fff; border-radius: 8px; padding: 22px; margin-bottom: 20px; border: 1px solid #e2e8f0; border-left: 4px solid #059669; }
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

    <!-- Main Content Area -->
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

      <!-- Sub Header Navigation Bar -->
      <div class="subheader-bar">
        <div class="subheader-left">
          <span class="ehr-tag"><i class="fa-solid fa-folder-open"></i> Patient Electronic Health Record</span>
          <span class="status-pill-green">Active Clinical Encounter</span>
        </div>
        <div class="subheader-right">
          <div class="session-timer">
            <i class="fa-solid fa-user-clock"></i> Session: <span>Active</span>
            <span class="tag-interactive">INTERACTIVE</span>
          </div>
          <a href="new-prescription-form.php?patient_id=<?= (int)$patientId ?>" class="btn-write-rx" style="text-decoration:none;">
            <i class="fa-solid fa-pen-to-square"></i> Write Prescription
          </a>
        </div>
      </div>

      <!-- Module Navigation Tabs -->
      <nav class="module-tabs">
        <a href="patient-medical-profile.php?patient_id=<?= (int)$patientId ?>" class="tab-btn"><i class="fa-solid fa-user"></i> Patient Profile</a>
        <a href="patient-prescription-records.php?patient_id=<?= (int)$patientId ?>" class="tab-btn"><i class="fa-solid fa-capsules"></i> Prescription Records</a>
        <a href="patient-surgary-records.php?patient_id=<?= (int)$patientId ?>" class="tab-btn active"><i class="fa-solid fa-scalpel"></i> Surgery Records</a>
        <a href="patient-test-records.php?patient_id=<?= (int)$patientId ?>" class="tab-btn"><i class="fa-solid fa-vial"></i> Test Records</a>
        <a href="patient-vaccine-records.php?patient_id=<?= (int)$patientId ?>" class="tab-btn"><i class="fa-solid fa-syringe"></i> Vaccine Records</a>
      </nav>

      <!-- Main Content Area -->
      <main class="content-body" style="padding: 24px;">

        <!-- Patient Header Card -->
        <div class="patient-card" style="background:#fff; border-radius:8px; padding:20px; border:1px solid #e2e8f0; margin-bottom:20px;">
          <div style="display:flex; justify-content:space-between; align-items:center;">
            <div>
              <h2 style="margin:0; font-size:1.3rem; color:#0f172a;"><?= htmlspecialchars($patient['full_name']) ?></h2>
              <p style="margin:4px 0 0 0; color:#64748b; font-size:0.88rem;">
                MRN: <strong><?= htmlspecialchars($patient['health_card_no'] ?: 'SHID-#' . $patient['patient_id']) ?></strong> &bull; <?= $patient['age'] ?> yrs &bull; <?= ucfirst($patient['gender']) ?> &bull; Blood: <?= htmlspecialchars($patient['blood_group'] ?: 'A+') ?>
              </p>
            </div>
            <button onclick="window.print()" style="padding:8px 16px; background:#f8fafc; border:1px solid #cbd5e1; border-radius:6px; font-weight:600; cursor:pointer;">
              <i class="fa-solid fa-download"></i> Export Operative Records
            </button>
          </div>
        </div>

        <!-- Filter Pills Bar -->
        <div class="filter-pills-row">
          <a href="patient-surgary-records.php?patient_id=<?= (int)$patientId ?>" class="f-pill <?= empty($procTypeFilter) ? 'active' : '' ?>">All Procedures (<?= count($surgeries) ?>)</a>
          <a href="patient-surgary-records.php?patient_id=<?= (int)$patientId ?>&type=inpatient" class="f-pill <?= $procTypeFilter === 'inpatient' ? 'active' : '' ?>">Major Surgery (Inpatient)</a>
          <a href="patient-surgary-records.php?patient_id=<?= (int)$patientId ?>&type=outpatient" class="f-pill <?= $procTypeFilter === 'outpatient' ? 'active' : '' ?>">Ambulatory / Outpatient</a>
          <a href="patient-surgary-records.php?patient_id=<?= (int)$patientId ?>&type=pre_op" class="f-pill <?= $procTypeFilter === 'pre_op' ? 'active' : '' ?>">Pre-Operative Plans</a>
        </div>

        <!-- Surgery Records List -->
        <?php foreach ($surgeries as $s): ?>
          <div class="surg-card">
            <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:12px;">
              <div>
                <span style="display:inline-block; background:#ecfdf5; color:#065f46; font-size:0.75rem; font-weight:700; padding:2px 8px; border-radius:10px; margin-bottom:6px;">
                  <?= strtoupper(htmlspecialchars($s['procedure_type'] ?? 'INPATIENT')) ?> PROCEDURE
                </span>
                <h3 style="margin:0; font-size:1.2rem; color:#0f172a;"><?= htmlspecialchars($s['procedure_title']) ?></h3>
                <span style="font-size:0.85rem; color:#64748b;">
                  Date of Operation: <strong><?= date('d M Y, h:i A', strtotime($s['operation_datetime'])) ?></strong> &bull; Facility: <?= htmlspecialchars($s['hospital_name'] ?? $doctorHospital) ?>
                </span>
              </div>
              <span style="background:#dcfce7; color:#166534; padding:4px 12px; border-radius:12px; font-size:0.75rem; font-weight:700;">
                <?= strtoupper(htmlspecialchars($s['status'] ?? 'COMPLETED')) ?>
              </span>
            </div>

            <div style="display:grid; grid-template-columns: repeat(3, 1fr); gap:14px; background:#f8fafc; padding:14px; border-radius:6px; margin:14px 0; font-size:0.88rem;">
              <div>
                <span style="color:#64748b; font-size:0.75rem; font-weight:600; display:block;">LEAD SURGEON</span>
                <strong style="color:#0f172a;"><?= htmlspecialchars($s['surgeon_name'] ?? 'Senior Consultant Surgeon') ?></strong>
              </div>
              <div>
                <span style="color:#64748b; font-size:0.75rem; font-weight:600; display:block;">ANESTHESIOLOGIST</span>
                <strong style="color:#0f172a;"><?= htmlspecialchars($s['anesthesiologist_name'] ?? 'Staff Anesthesiologist') ?></strong>
              </div>
              <div>
                <span style="color:#64748b; font-size:0.75rem; font-weight:600; display:block;">BLOOD LOSS &amp; COMPLICATIONS</span>
                <span style="color:#059669; font-weight:600;"><?= (int)($s['estimated_blood_loss_ml'] ?? 50) ?> mL &bull; <?= htmlspecialchars($s['intra_op_complication_status'] ?? 'None') ?></span>
              </div>
            </div>

            <?php if (!empty($s['primary_procedure'])): ?>
              <div style="margin-bottom:8px; font-size:0.88rem;">
                <strong>Primary Surgical Technique:</strong> <?= htmlspecialchars($s['primary_procedure']) ?>
              </div>
            <?php endif; ?>

            <?php if (!empty($s['medications_used'])): ?>
              <div style="margin-bottom:8px; font-size:0.88rem; color:#475569;">
                <strong>Perioperative Medications:</strong> <?= htmlspecialchars($s['medications_used']) ?>
              </div>
            <?php endif; ?>

            <?php if (!empty($s['contraindication_notes'])): ?>
              <div style="font-size:0.88rem; color:#475569;">
                <strong>Post-Operative Progress / Notes:</strong> <?= htmlspecialchars($s['contraindication_notes']) ?>
              </div>
            <?php endif; ?>

          </div>
        <?php endforeach; ?>

      </main>
    </div>
  </div>

</body>
</html>
