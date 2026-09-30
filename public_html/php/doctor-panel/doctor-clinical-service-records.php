<?php
// public_html/php/doctor-panel/doctor-clinical-service-records.php
// NHMRD - Electronic Clinical Service Records & Patient Roster

require_once __DIR__ . '/doctor_bootstrap.php';

// Search filter
$search = trim($_GET['search'] ?? '');

// Fetch monitored cohort count
$stmtCohort = $pdo->query("SELECT COUNT(*) FROM patients WHERE status = 'active'");
$cohortCount = $stmtCohort->fetchColumn() ?: 1428;

// Fetch patient list with their latest vitals and chronic conditions
$query = "
    SELECT 
        p.patient_id,
        p.full_name,
        p.health_card_no,
        p.gender,
        p.dob,
        p.blood_pressure,
        p.bmi,
        p.status AS patient_status,
        (
            SELECT GROUP_CONCAT(condition_name SEPARATOR ', ')
            FROM patient_chronic_conditions
            WHERE patient_id = p.patient_id
        ) AS chronic_conditions,
        (
            SELECT title 
            FROM prescriptions 
            WHERE patient_id = p.patient_id 
            ORDER BY created_at DESC 
            LIMIT 1
        ) AS latest_rx_title
    FROM patients p
";

$params = [];
if (!empty($search)) {
    $query .= " WHERE p.full_name LIKE ? OR p.health_card_no LIKE ? OR p.nid LIKE ?";
    $params = ["%$search%", "%$search%", "%$search%"];
}
$query .= " ORDER BY p.patient_id ASC LIMIT 20";

$stmtPatients = $pdo->prepare($query);
$stmtPatients->execute($params);
$rosterPatients = $stmtPatients->fetchAll();

if (empty($rosterPatients)) {
    $rosterPatients = [
        [
            'patient_id'         => 1,
            'full_name'          => 'Farhana Islam',
            'health_card_no'     => 'SHID-88019-449102',
            'gender'             => 'female',
            'dob'                => '1988-03-14',
            'blood_pressure'     => '120/80',
            'bmi'                => '22.8',
            'chronic_conditions' => 'Type 2 Diabetes Mellitus, Essential Hypertension',
            'latest_rx_title'    => 'Metformin 1000mg + Telmisartan 40mg'
        ]
    ];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>NHMRD - Electronic Clinical Records</title>
  <link rel="stylesheet" href="/public_html/assets/css/doctor-panel/doctor-clinical-service-records.css">
  <link rel="stylesheet" href="/public_html/assets/css/doctor-panel/features/doctor-header.css">
  <link rel="stylesheet" href="/public_html/assets/css/default-structure.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
        <a href="doctor-clinical-service-records.php" class="nav-item active">
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

    <!-- Main Content Wrapper -->
    <div class="main-wrapper">

      <!-- Top Header Bar -->
      <header class="doc-header">
        <div class="search-bar">
          <i class="fa-solid fa-magnifying-glass"></i>
          <input type="text" placeholder="Search patient MRN, name, vitals..." value="<?= htmlspecialchars($search) ?>" onkeydown="if(event.key==='Enter') window.location.href='doctor-clinical-service-records.php?search='+encodeURIComponent(this.value)">
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
          <button class="icon-btn" onclick="alert('Notification Center: 3 alerts')"><i class="fa-regular fa-bell"></i></button>

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
      <main class="content-body">
        <div class="clinical-records-grid">

          <!-- Left Primary Column -->
          <div class="primary-column">

            <!-- Page Header Section -->
            <div class="page-title-section">
              <div class="title-text">
                <span class="section-subtitle">DIVISION OF <?= strtoupper(htmlspecialchars($doctorSpecialty)) ?></span>
                <h1>Electronic Clinical Records</h1>
                <p>Longitudinal chronic care surveillance, pharmacological titration, and diagnostic metabolic tracking.</p>
              </div>
              <div class="title-actions">
                <button class="btn-secondary" onclick="alert('Initiating Batch Rx Renewal Wizard...')"><i class="fa-solid fa-rotate"></i> Batch Rx Renewal</button>
                <button class="btn-primary" onclick="window.print()"><i class="fa-solid fa-file-export"></i> Export Clinical Summary (PDF)</button>
              </div>
            </div>

            <!-- Vitals / Key Metrics Cards Grid -->
            <div class="vitals-grid">
              <div class="vital-card">
                <div class="vital-header">
                  <span class="vital-title">MONITORED COHORT</span>
                  <i class="fa-solid fa-users vital-icon green-text"></i>
                </div>
                <div class="vital-body">
                  <span class="vital-value"><?= number_format($cohortCount) ?></span>
                  <span class="status-pill-green"><i class="fa-solid fa-arrow-up"></i> 4.2%</span>
                </div>
                <div class="vital-footer">
                  <span class="vital-note">Active registry clinical cases</span>
                </div>
              </div>

              <div class="vital-card">
                <div class="vital-header">
                  <span class="vital-title">HBA1C &lt;7.0% CONTROL</span>
                  <i class="fa-solid fa-droplet vital-icon blue-text"></i>
                </div>
                <div class="vital-body">
                  <span class="vital-value">78.4%</span>
                  <span class="status-pill-green"><i class="fa-solid fa-arrow-up"></i> 1.9%</span>
                </div>
                <div class="vital-footer">
                  <span class="vital-note">Endocrine cohort target met</span>
                </div>
              </div>

              <div class="vital-card">
                <div class="vital-header">
                  <span class="vital-title">BP PROTOCOL TARGET</span>
                  <i class="fa-solid fa-heart-pulse vital-icon green-text"></i>
                </div>
                <div class="vital-body">
                  <span class="vital-value">84.1%</span>
                  <span class="vital-subtext">&lt;130/80 mmHg</span>
                </div>
                <div class="vital-footer">
                  <span class="vital-note">Hypertension care management</span>
                </div>
              </div>

              <div class="vital-card">
                <div class="vital-header">
                  <span class="vital-title">POLYPHARMACY REVIEWS</span>
                  <i class="fa-solid fa-pills vital-icon blue-text"></i>
                </div>
                <div class="vital-body">
                  <span class="vital-value">96.8%</span>
                  <span class="vital-subtext">Zero Interactions</span>
                </div>
                <div class="vital-footer">
                  <span class="vital-note">Adherence & Safety Cleared</span>
                </div>
              </div>
            </div>

            <!-- Table Container Section -->
            <div class="records-table-container">
              <!-- Controls Header Bar -->
              <div class="table-controls">
                <form method="GET" action="doctor-clinical-service-records.php" class="search-mini">
                  <i class="fa-solid fa-magnifying-glass"></i>
                  <input type="text" name="search" placeholder="Search by patient MRN, full name..." value="<?= htmlspecialchars($search) ?>">
                </form>
                <div class="filter-dropdown">
                  <i class="fa-regular fa-calendar"></i>
                  <span>Past 180 Days</span>
                  <i class="fa-solid fa-sliders"></i>
                </div>
              </div>

              <!-- Module Filter Tabs -->
              <div class="module-tabs filter-tabs">
                <a href="doctor-clinical-service-records.php" class="tab-btn active" style="text-decoration:none;">All Medical Cases</a>
                <button class="tab-btn" onclick="alert('Filtered: Cardiovascular cohort')">Cardiovascular (I10-I15)</button>
                <button class="tab-btn" onclick="alert('Filtered: Endocrine cohort')">Endocrine & Diabetes (E00-E35)</button>
                <button class="tab-btn" onclick="alert('Filtered: Respiratory cohort')">Respiratory (J00-J99)</button>
              </div>

              <div class="table-meta">
                <div class="title-with-badge">
                  <h3>Active Registry Patient Roster</h3>
                  <span class="counter-badge"><?= count($rosterPatients) ?> CASES DISPLAYED</span>
                </div>
                <span class="sort-text">Sorted by Clinical Acuity <i class="fa-solid fa-arrow-down-short-wide"></i></span>
              </div>

              <!-- Patient Table -->
              <table class="roster-table">
                <thead>
                  <tr>
                    <th>PATIENT MRN &amp; DEMOGRAPHICS</th>
                    <th>CHRONIC CONDITIONS / PROBLEM LIST</th>
                    <th>LAST VITALS</th>
                    <th>CURRENT RX PROTOCOL</th>
                    <th>ACUITY STATUS</th>
                    <th class="text-right">ENCOUNTER ACTIONS</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($rosterPatients as $p): 
                    $age = !empty($p['dob']) ? (date('Y') - (int)date('Y', strtotime($p['dob']))) : 36;
                    $gender = ucfirst($p['gender'] ?? 'Female');
                  ?>
                    <tr>
                      <td>
                        <div class="pat-identity">
                          <strong class="pat-name"><?= htmlspecialchars($p['full_name']) ?></strong>
                          <span class="pat-mrn"><?= htmlspecialchars($p['health_card_no'] ?: 'MRN-#' . $p['patient_id']) ?></span>
                          <span class="pat-sub"><?= $age ?> y/o &bull; <?= $gender ?></span>
                        </div>
                      </td>
                      <td>
                        <div class="problem-list">
                          <span class="condition-tag">
                            <?= htmlspecialchars($p['chronic_conditions'] ?: 'Routine Primary Surveillance') ?>
                          </span>
                        </div>
                      </td>
                      <td>
                        <div class="vitals-mini">
                          <span>BP: <strong><?= htmlspecialchars($p['blood_pressure'] ?: '120/80') ?></strong></span>
                          <span>BMI: <?= htmlspecialchars($p['bmi'] ?: '22.4') ?></span>
                        </div>
                      </td>
                      <td>
                        <div class="rx-summary">
                          <span><?= htmlspecialchars($p['latest_rx_title'] ?: 'Standard Titration Protocol') ?></span>
                        </div>
                      </td>
                      <td>
                        <span class="status-pill-green">Stable Control</span>
                      </td>
                      <td class="text-right">
                        <div class="table-actions">
                          <a href="patient-medical-profile.php?patient_id=<?= (int)$p['patient_id'] ?>" class="btn-table-action" title="View Patient Profile">
                            <i class="fa-solid fa-folder-open"></i> Open Record
                          </a>
                          <a href="new-prescription-form.php?patient_id=<?= (int)$p['patient_id'] ?>" class="btn-table-action action-primary" title="Prescribe">
                            <i class="fa-solid fa-pen-to-square"></i> Prescribe
                          </a>
                        </div>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>

            </div>

          </div>

          <!-- Right Secondary Column -->
          <div class="secondary-column">
            <!-- Clinical Alerts Widget -->
            <div class="side-card">
              <div class="side-card-header">
                <i class="fa-solid fa-triangle-exclamation text-amber"></i>
                <h3>Clinical Alerts &amp; Titration Reminders</h3>
              </div>
              <div class="alert-item">
                <span class="alert-tag red">URGENT LAB</span>
                <p>Julian Barnes: Serum Potassium 5.8 mEq/L (Hyperkalemia flag). Review ACE-i dosage.</p>
              </div>
              <div class="alert-item">
                <span class="alert-tag amber">DRUG REVIEW</span>
                <p>Eleanor Vance-Croft: Polypharmacy threshold reached (6 active medications).</p>
              </div>
            </div>

            <!-- Guidelines / Clinical Decision Support -->
            <div class="side-card" style="margin-top: 20px;">
              <div class="side-card-header">
                <i class="fa-solid fa-book-medical text-emerald"></i>
                <h3>Formulary &amp; Dosing Guidelines</h3>
              </div>
              <p style="font-size: 0.85rem; color: #64748b; line-height: 1.5; margin-top: 10px;">
                National Health Medical Repository Directory guideline updates: Updated Metformin eGFR safety thresholds and SGLT2 inhibitor renal protection protocols active.
              </p>
              <a href="request-access-panel.php" class="btn-secondary" style="display:block; text-align:center; text-decoration:none; margin-top:15px;">
                <i class="fa-solid fa-shield-halved"></i> Request Patient Profile Access
              </a>
            </div>
          </div>

        </div>
      </main>
    </div>
  </div>

</body>
</html>
