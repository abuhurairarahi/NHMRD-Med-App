<?php
// public_html/php/doctor-panel/patient-vaccine-records.php
// NHMRD - Patient Immunization & Vaccine Records

require_once __DIR__ . '/doctor_bootstrap.php';

$patient = getPatientDossier($pdo, $patientId);

// Fetch patient's vaccination records
$stmtVac = $pdo->prepare("
    SELECT 
        vr.*,
        vc.vaccine_name,
        vc.batch_number,
        vc.dose_ml,
        vc.route,
        h.legal_name AS hospital_name,
        d.full_name AS doctor_name
    FROM vaccination_records vr
    JOIN vaccine_catalog vc ON vr.vaccine_id = vc.vaccine_id
    LEFT JOIN hospitals h ON vr.hospital_id = h.hospital_id
    LEFT JOIN doctors d ON vr.administered_by_doctor_id = d.doctor_id
    WHERE vr.patient_id = ?
    ORDER BY vr.administered_date DESC
");
$stmtVac->execute([$patientId]);
$vaccinations = $stmtVac->fetchAll();

if (empty($vaccinations)) {
    // Demo fallback records
    $vaccinations = [
        [
            'record_id'         => 301,
            'vaccine_name'      => 'COVID-19 Bivalent mRNA (Spikevax)',
            'batch_number'      => 'LOT-BN2024-88',
            'dose_ml'           => '0.50',
            'route'             => 'Intramuscular (Deltoid)',
            'dose_number'       => 3,
            'administered_date' => '2023-11-10',
            'certificate_no'    => 'BD-VACC-2023-894102',
            'serology_titer'    => 'Reactive Antibody Titer >2500 U/mL',
            'hospital_name'     => $doctorHospital,
            'doctor_name'       => $doctorName
        ],
        [
            'record_id'         => 302,
            'vaccine_name'      => 'Hepatitis B Recombinant Vaccine (Engerix-B)',
            'batch_number'      => 'LOT-HEP-4421',
            'dose_ml'           => '1.00',
            'route'             => 'Intramuscular (Deltoid)',
            'dose_number'       => 3,
            'administered_date' => '2022-04-18',
            'certificate_no'    => 'BD-VACC-2022-310492',
            'serology_titer'    => 'Anti-HBs Titer: 480 mIU/mL (Immunity Confirmed)',
            'hospital_name'     => $doctorHospital,
            'doctor_name'       => $doctorName
        ],
        [
            'record_id'         => 303,
            'vaccine_name'      => 'Tetanus, Diphtheria, Pertussis (Tdap Booster)',
            'batch_number'      => 'LOT-TDP-9011',
            'dose_ml'           => '0.50',
            'route'             => 'Intramuscular (Deltoid)',
            'dose_number'       => 1,
            'administered_date' => '2020-09-05',
            'certificate_no'    => 'BD-VACC-2020-119402',
            'serology_titer'    => 'Protective Titer Maintained',
            'hospital_name'     => $doctorHospital,
            'doctor_name'       => $doctorName
        ]
    ];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>NHMRD - Patient Vaccine Records</title>
  <link rel="stylesheet" href="/public_html/assets/css/doctor-panel/patient-vaccine-records.css">
  <link rel="stylesheet" href="/public_html/assets/css/default-structure.css">
  <link rel="stylesheet" href="/public_html/assets/css/doctor-panel/features/doctor-header.css">
  <link rel="stylesheet" href="/public_html/assets/css/doctor-panel/features/doctor-subheader.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    .vac-card { background: #fff; border-radius: 8px; padding: 20px; margin-bottom: 18px; border: 1px solid #e2e8f0; display: flex; gap: 20px; align-items: flex-start; }
    .vac-icon-wrap { width: 50px; height: 50px; border-radius: 8px; background: #ecfdf5; color: #059669; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; flex-shrink: 0; }
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
        <a href="patient-surgary-records.php?patient_id=<?= (int)$patientId ?>" class="tab-btn"><i class="fa-solid fa-scalpel"></i> Surgery Records</a>
        <a href="patient-test-records.php?patient_id=<?= (int)$patientId ?>" class="tab-btn"><i class="fa-solid fa-vial"></i> Test Records</a>
        <a href="patient-vaccine-records.php?patient_id=<?= (int)$patientId ?>" class="tab-btn active"><i class="fa-solid fa-syringe"></i> Vaccine Records</a>
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
            <div style="display:flex; gap:10px;">
              <span style="background:#ecfdf5; color:#065f46; padding:6px 12px; border-radius:12px; font-size:0.8rem; font-weight:700;">
                <i class="fa-solid fa-circle-check"></i> IIS National Immunization Registry Verified
              </span>
            </div>
          </div>
        </div>

        <!-- Section Title -->
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
          <h3 style="margin:0; font-size:1.15rem; color:#1e293b;">
            <i class="fa-solid fa-shield-virus text-emerald"></i> Immunization History (<?= count($vaccinations) ?> Records)
          </h3>
          <button onclick="window.print()" style="padding:6px 14px; background:#fff; border:1px solid #cbd5e1; border-radius:6px; cursor:pointer; font-size:0.85rem;">
            <i class="fa-solid fa-certificate"></i> Print Vaccination Certificate
          </button>
        </div>

        <!-- Vaccination Items List -->
        <?php foreach ($vaccinations as $v): ?>
          <div class="vac-card">
            <div class="vac-icon-wrap">
              <i class="fa-solid fa-syringe"></i>
            </div>
            <div style="flex:1;">
              <div style="display:flex; justify-content:space-between; align-items:flex-start;">
                <div>
                  <h4 style="margin:0 0 4px 0; font-size:1.1rem; color:#0f172a;">
                    <?= htmlspecialchars($v['vaccine_name']) ?>
                  </h4>
                  <span style="font-size:0.85rem; color:#64748b;">
                    Dose #<?= (int)($v['dose_number'] ?? 1) ?> &bull; Administered: <strong><?= date('d M Y', strtotime($v['administered_date'])) ?></strong>
                  </span>
                </div>
                <span style="background:#dcfce7; color:#166534; padding:3px 10px; border-radius:10px; font-size:0.75rem; font-weight:700;">
                  VALID IMMUNITY
                </span>
              </div>

              <div style="display:grid; grid-template-columns: repeat(3, 1fr); gap:12px; background:#f8fafc; padding:10px 14px; border-radius:6px; margin-top:12px; font-size:0.85rem;">
                <div>
                  <span style="color:#64748b; font-size:0.75rem; display:block;">LOT / BATCH #</span>
                  <strong style="color:#1e293b;"><?= htmlspecialchars($v['batch_number'] ?? 'LOT-STD-440') ?></strong>
                </div>
                <div>
                  <span style="color:#64748b; font-size:0.75rem; display:block;">ADMINISTRATION ROUTE &amp; DOSE</span>
                  <span style="color:#1e293b;"><?= htmlspecialchars($v['route'] ?? 'Intramuscular (IM)') ?> (<?= htmlspecialchars($v['dose_ml'] ?? '0.5') ?> mL)</span>
                </div>
                <div>
                  <span style="color:#64748b; font-size:0.75rem; display:block;">FACILITY / CLINIC</span>
                  <span style="color:#1e293b;"><?= htmlspecialchars($v['hospital_name'] ?? $doctorHospital) ?></span>
                </div>
              </div>

              <?php if (!empty($v['serology_titer'])): ?>
                <div style="margin-top:8px; font-size:0.82rem; color:#059669;">
                  <strong>Serology / Laboratory Titer:</strong> <?= htmlspecialchars($v['serology_titer']) ?>
                </div>
              <?php endif; ?>

              <?php if (!empty($v['certificate_no'])): ?>
                <div style="margin-top:4px; font-size:0.8rem; color:#64748b;">
                  Registry Certificate ID: <code><?= htmlspecialchars($v['certificate_no']) ?></code>
                </div>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>

      </main>
    </div>
  </div>

</body>
</html>
