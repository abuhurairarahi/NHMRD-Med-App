<?php
// public_html/php/doctor-panel/patient-prescription-records.php
// NHMRD - Patient Prescription Records History & Detail View

require_once __DIR__ . '/doctor_bootstrap.php';

$patient = getPatientDossier($pdo, $patientId);

// Filter tab
$rxStatusFilter = trim($_GET['status'] ?? '');

$query = "
    SELECT 
        pr.*, 
        d.full_name AS doctor_name, 
        d.qualifications AS doctor_qualifications,
        h.legal_name AS hospital_name
    FROM prescriptions pr
    LEFT JOIN doctors d ON pr.doctor_id = d.doctor_id
    LEFT JOIN hospitals h ON d.hospital_id = h.hospital_id
    WHERE pr.patient_id = ?
";
$params = [$patientId];

if ($rxStatusFilter === 'chronic') {
    $query .= " AND pr.is_long_term = 1";
} elseif (!empty($rxStatusFilter)) {
    $query .= " AND pr.status = ?";
    $params[] = $rxStatusFilter;
}

$query .= " ORDER BY pr.created_at DESC";

$stmtRx = $pdo->prepare($query);
$stmtRx->execute($params);
$prescriptions = $stmtRx->fetchAll();

// For each prescription, fetch its medications and advised lab tests
foreach ($prescriptions as &$rx) {
    $stmtM = $pdo->prepare("SELECT * FROM prescription_medications WHERE prescription_id = ?");
    $stmtM->execute([$rx['prescription_id']]);
    $rx['medications'] = $stmtM->fetchAll();

    $stmtL = $pdo->prepare("SELECT * FROM prescription_lab_tests WHERE prescription_id = ?");
    $stmtL->execute([$rx['prescription_id']]);
    $rx['lab_tests'] = $stmtL->fetchAll();
}
unset($rx);
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>NHMRD - Patient Prescription Records</title>
  <link rel="stylesheet" href="/public_html/assets/css/doctor-panel/patient-prescription-records.css">
  <link rel="stylesheet" href="/public_html/assets/css/default-structure.css">
  <link rel="stylesheet" href="/public_html/assets/css/doctor-panel/features/doctor-header.css">
  <link rel="stylesheet" href="/public_html/assets/css/doctor-panel/features/doctor-subheader.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    .filter-tab-bar { display: flex; gap: 10px; margin-bottom: 20px; border-bottom: 2px solid #e2e8f0; padding-bottom: 8px; }
    .tab-pill-btn { text-decoration: none; padding: 8px 16px; font-weight: 600; color: #64748b; border-radius: 6px; font-size: 0.9rem; }
    .tab-pill-btn.active { background: #059669; color: #fff; }
    .rx-record-card { background: #fff; border-radius: 8px; padding: 22px; margin-bottom: 20px; border: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0,0,0,0.04); }
    .rx-card-top { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px; }
    .med-tbl { width: 100%; border-collapse: collapse; margin-top: 14px; font-size: 0.88rem; }
    .med-tbl th, .med-tbl td { padding: 9px 12px; text-align: left; border-bottom: 1px solid #f1f5f9; }
    .med-tbl th { background: #f8fafc; color: #475569; font-weight: 700; font-size: 0.78rem; text-transform: uppercase; }
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
        <a href="patient-prescription-records.php?patient_id=<?= (int)$patientId ?>" class="tab-btn active"><i class="fa-solid fa-capsules"></i> Prescription Records</a>
        <a href="patient-surgary-records.php?patient_id=<?= (int)$patientId ?>" class="tab-btn"><i class="fa-solid fa-scalpel"></i> Surgery Records</a>
        <a href="patient-test-records.php?patient_id=<?= (int)$patientId ?>" class="tab-btn"><i class="fa-solid fa-vial"></i> Test Records</a>
        <a href="patient-vaccine-records.php?patient_id=<?= (int)$patientId ?>" class="tab-btn"><i class="fa-solid fa-syringe"></i> Vaccine Records</a>
      </nav>

      <!-- Main Content Body -->
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
            <a href="new-prescription-form.php?patient_id=<?= (int)$patientId ?>" style="padding:10px 18px; background:#059669; color:#fff; text-decoration:none; border-radius:6px; font-weight:700; font-size:0.9rem;">
              <i class="fa-solid fa-plus"></i> New Prescription
            </a>
          </div>
        </div>

        <!-- Filter Tab Bar -->
        <div class="filter-tab-bar">
          <a href="patient-prescription-records.php?patient_id=<?= (int)$patientId ?>" class="tab-pill-btn <?= empty($rxStatusFilter) ? 'active' : '' ?>">All Prescriptions (<?= count($prescriptions) ?>)</a>
          <a href="patient-prescription-records.php?patient_id=<?= (int)$patientId ?>&status=active" class="tab-pill-btn <?= $rxStatusFilter === 'active' ? 'active' : '' ?>">Active Protocols</a>
          <a href="patient-prescription-records.php?patient_id=<?= (int)$patientId ?>&status=chronic" class="tab-pill-btn <?= $rxStatusFilter === 'chronic' ? 'active' : '' ?>">Chronic Care</a>
          <a href="patient-prescription-records.php?patient_id=<?= (int)$patientId ?>&status=completed" class="tab-pill-btn <?= $rxStatusFilter === 'completed' ? 'active' : '' ?>">Historical</a>
        </div>

        <!-- Prescriptions List -->
        <?php if (empty($prescriptions)): ?>
          <div style="background:#fff; border-radius:8px; padding:40px; text-align:center; border:1px solid #e2e8f0; color:#64748b;">
            <i class="fa-solid fa-file-prescription" style="font-size:2.5rem; color:#cbd5e1; margin-bottom:12px;"></i>
            <h3>No prescription records found for this patient under this filter.</h3>
            <p>Click "New Prescription" to initiate e-Prescribing protocol.</p>
          </div>
        <?php else: ?>
          <?php foreach ($prescriptions as $p): ?>
            <div class="rx-record-card">
              <div class="rx-card-top">
                <div>
                  <h3 style="margin:0 0 6px 0; font-size:1.15rem; color:#0f172a;">
                    <?= htmlspecialchars($p['title']) ?>
                  </h3>
                  <p style="margin:0; font-size:0.85rem; color:#64748b;">
                    Prescribed by <strong><?= htmlspecialchars($p['doctor_name'] ?: 'Attending Physician') ?></strong> &bull; <?= date('d M Y, h:i A', strtotime($p['created_at'])) ?>
                  </p>
                </div>
                <div style="text-align:right;">
                  <span style="display:inline-block; padding:4px 10px; border-radius:12px; font-size:0.75rem; font-weight:700; background:<?= $p['status'] === 'active' ? '#dcfce7' : '#f1f5f9' ?>; color:<?= $p['status'] === 'active' ? '#166534' : '#475569' ?>;">
                    <?= strtoupper(htmlspecialchars($p['status'])) ?>
                  </span>
                  <?php if (!empty($p['is_long_term'])): ?>
                    <span style="display:inline-block; padding:4px 10px; border-radius:12px; font-size:0.75rem; font-weight:700; background:#fef3c7; color:#92400e; margin-left:6px;">
                      CHRONIC PROTOCOL
                    </span>
                  <?php endif; ?>
                </div>
              </div>

              <!-- Complaints and Assessment -->
              <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; background:#f8fafc; padding:12px; border-radius:6px; margin-top:12px; font-size:0.88rem;">
                <div>
                  <strong style="color:#475569; display:block; font-size:0.75rem;">CHIEF COMPLAINT:</strong>
                  <span style="color:#1e293b;"><?= htmlspecialchars($p['chief_complaint'] ?: 'Routine follow-up') ?></span>
                </div>
                <div>
                  <strong style="color:#475569; display:block; font-size:0.75rem;">CURRENT ASSESSMENT / CONDITION:</strong>
                  <span style="color:#1e293b;"><?= htmlspecialchars($p['current_condition'] ?: 'Clinical maintenance') ?></span>
                </div>
              </div>

              <!-- Medications Table -->
              <?php if (!empty($p['medications'])): ?>
                <table class="med-tbl">
                  <thead>
                    <tr>
                      <th>MEDICATION</th>
                      <th>DOSE</th>
                      <th>FREQUENCY &amp; ROUTE</th>
                      <th>QTY</th>
                      <th>SIG / INSTRUCTIONS</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($p['medications'] as $m): ?>
                      <tr>
                        <td><strong><?= htmlspecialchars($m['medication_name']) ?></strong></td>
                        <td><?= htmlspecialchars($m['dose_strength']) ?></td>
                        <td><?= htmlspecialchars($m['route_frequency']) ?></td>
                        <td><?= htmlspecialchars($m['dispense_quantity']) ?></td>
                        <td><?= htmlspecialchars($m['sig_instructions']) ?></td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              <?php endif; ?>

              <!-- Advised Lab Tests -->
              <?php if (!empty($p['lab_tests'])): ?>
                <div style="margin-top:14px; padding-top:10px; border-top:1px dashed #e2e8f0;">
                  <strong style="font-size:0.8rem; color:#475569;">ADVISED DIAGNOSTIC TESTS:</strong>
                  <div style="display:flex; flex-wrap:wrap; gap:8px; margin-top:6px;">
                    <?php foreach ($p['lab_tests'] as $lt): ?>
                      <span style="background:#e0f2fe; color:#0369a1; padding:3px 10px; border-radius:12px; font-size:0.8rem; font-weight:600;">
                        <i class="fa-solid fa-vial"></i> <?= htmlspecialchars($lt['test_name']) ?>
                      </span>
                    <?php endforeach; ?>
                  </div>
                </div>
              <?php endif; ?>

              <!-- Instructions & Footer -->
              <?php if (!empty($p['doctors_statement'])): ?>
                <div style="margin-top:12px; font-size:0.85rem; color:#475569;">
                  <strong>Advice:</strong> <?= htmlspecialchars($p['doctors_statement']) ?>
                </div>
              <?php endif; ?>

              <div style="display:flex; justify-content:space-between; align-items:center; margin-top:16px; padding-top:12px; border-top:1px solid #f1f5f9;">
                <span style="font-size:0.85rem; color:#64748b;">
                  Next Review: <strong><?= !empty($p['next_visit_date']) ? date('d M Y', strtotime($p['next_visit_date'])) : 'As advised' ?></strong>
                </span>
                <div style="display:flex; gap:10px;">
                  <button onclick="window.print()" style="padding:6px 14px; background:#fff; border:1px solid #cbd5e1; border-radius:6px; cursor:pointer; font-size:0.85rem;">
                    <i class="fa-solid fa-print"></i> Print Rx
                  </button>
                  <a href="new-prescription-form.php?patient_id=<?= (int)$patientId ?>" style="padding:6px 14px; background:#f0fdf4; border:1px solid #86efac; color:#166534; border-radius:6px; text-decoration:none; font-weight:600; font-size:0.85rem;">
                    <i class="fa-solid fa-repeat"></i> Renew Protocol
                  </a>
                </div>
              </div>

            </div>
          <?php endforeach; ?>
        <?php endif; ?>

      </main>
    </div>
  </div>

</body>
</html>
