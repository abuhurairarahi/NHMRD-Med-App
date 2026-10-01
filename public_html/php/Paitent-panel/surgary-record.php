<?php
// Load Data Controller
$data = require_once __DIR__ . '/../../controllers/patient/DashboardController.php';

$patient            = $data['patient'];
$userInitials       = $data['userInitials'];
$surgeries          = $data['surgeries'];
$totalSurgeries     = $data['totalSurgeries'];
$inpatientCount     = $data['inpatientCount'];
$outpatientCount    = $data['outpatientCount'];
$preOpCount         = $data['preOpCount'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Surgery & Procedure Records - NHMRD</title>
  <link rel="stylesheet" href="/public_html/assets/css/patient-panel/surgary-record.css">
  <link rel="stylesheet" href="/public_html/assets/css/default-structure.css">
  <link rel="stylesheet" href="/public_html/assets/css/patient-panel/features/patient-header.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

  <div class="app-container">
    <aside class="sidebar patient">
      <div class="logo">
        <i class="fa-solid fa-shield-halved"></i>
        <span>NHMRD</span>
      </div>

      <nav class="nav-menu">
        <a href="/public_html/pages/Patient-panel/dashboard.php" class="nav-item">
          <i class="fa-solid fa-table-cells-large"></i> 
          <span>Dashboard</span>
        </a>
        <a href="/public_html/pages/Patient-panel/prescription-record.php" class="nav-item">
          <i class="fa-solid fa-file-prescription"></i> 
          <span>Prescription Records</span>
        </a>
        <a href="/public_html/pages/Patient-panel/surgary-record.php" class="nav-item active">
          <i class="fa-solid fa-scalpel"></i> 
          <span>Surgery Records</span>
        </a>
        <a href="/public_html/pages/Patient-panel/lab-test.php" class="nav-item">
          <i class="fa-solid fa-vial"></i> 
          <span>Test Records</span>
        </a>
        <a href="/public_html/pages/Patient-panel/vaccine-panel.php" class="nav-item">
          <i class="fa-solid fa-syringe"></i> 
          <span>Vaccine Records</span>
        </a>
        <a href="/public_html/pages/Patient-panel/req-appointment.php" class="nav-item">
          <i class="fa-solid fa-calendar-plus"></i> 
          <span>Request Appointment</span>
        </a>
        <a href="/public_html/pages/Patient-panel/medical-test-req.php" class="nav-item">
          <i class="fa-solid fa-notes-medical"></i> 
          <span>Request Medical Test</span>
        </a>
        <a href="/public_html/pages/Patient-panel/req-vaccine.php" class="nav-item">
          <i class="fa-solid fa-shield-virus"></i> 
          <span>Request Vaccine</span>
        </a>
        <a href="/public_html/pages/Patient-panel/paitent-info.php" class="nav-item">
          <i class="fa-solid fa-id-card"></i> 
          <span>Patient Info</span>
        </a>
      </nav>

      <div class="sidebar-footer">
        <button class="logout-btn" id="logoutBtn" onclick="handleLogout()">
          <i class="fa-solid fa-arrow-right-from-bracket"></i> 
          <span>Logout</span>
        </button>
      </div>
    </aside>

    <div class="main-wrapper">
      <header class="patient-header">
        <div class="search-bar">
          <i class="fa-solid fa-magnifying-glass"></i>
          <input type="text" id="procedureSearchInput" placeholder="Search Queries" oninput="handleGlobalSearch(event)">
        </div>

        <div class="header-right">
          <button class="icon-btn"><i class="fa-regular fa-bell"></i></button>
          <div class="user-badge-avatar"><?= htmlspecialchars($userInitials) ?></div>
          <span class="user-name"><?= htmlspecialchars($patient['full_name']) ?></span>
        </div>
      </header>

      <main class="content-body">

        <div class="user-banner">
          <div class="user-info-left">
            <div class="user-photo">
              <i class="fa-solid fa-user"></i>
            </div>
            <div class="user-details">
              <div class="name-badge-row">
                <h2><?= htmlspecialchars($patient['full_name']) ?></h2>
                <span class="verified-pill">Verified Record</span>
              </div>
              <p class="user-ids">
                Patient ID: #<?= htmlspecialchars($patient['user_uid']) ?> &bull; 
                NID: <?= htmlspecialchars($patient['nid'] ?? 'N/A') ?> &bull; 
                <span class="blood-group">Blood Group: <?= htmlspecialchars($patient['blood_group'] ?? 'N/A') ?></span>
              </p>
            </div>
          </div>
          <div class="banner-actions">
            <button class="btn-secondary" onclick="handleExportLog()"><i class="fa-solid fa-arrow-up-from-bracket"></i> Export Log</button>
            <button class="btn-primary" onclick="handleUploadReport()"><i class="fa-solid fa-file-medical"></i> Upload External Report</button>
          </div>
        </div>

        <div class="section-top-bar">
          <div>
            <h1>Surgery & Procedure Records</h1>
            <p class="subtitle">Comprehensive operative history, clinical anesthesia notes, and surgical clearances.</p>
          </div>
          <div class="filter-tabs">
            <button class="tab-btn active" data-filter="all" onclick="handleTabFilter(this)">All Surgeries (<?= $totalSurgeries ?>)</button>
            <button class="tab-btn" data-filter="inpatient" onclick="handleTabFilter(this)">Inpatient (<?= $inpatientCount ?>)</button>
            <button class="tab-btn" data-filter="outpatient" onclick="handleTabFilter(this)">Outpatient (<?= $outpatientCount ?>)</button>
            <button class="tab-btn" data-filter="pre_op" onclick="handleTabFilter(this)">Pre-op Assessments (<?= $preOpCount ?>)</button>
          </div>
        </div>

        <!-- Clinical Vitals Overview Grid -->
        <div class="vitals-grid">
          <div class="vital-card">
            <div class="vital-icon blood"><i class="fa-solid fa-droplet"></i></div>
            <div class="vital-info">
              <span class="vital-label">BLOOD GROUP</span>
              <strong class="vital-val"><?= htmlspecialchars($patient['blood_group'] ?? 'N/A') ?></strong>
              <span class="vital-sub">Verified Profile</span>
            </div>
          </div>

          <div class="vital-card">
            <div class="vital-icon risk"><i class="fa-solid fa-shield-heart"></i></div>
            <div class="vital-info">
              <span class="vital-label">ANESTHESIA RISK</span>
              <strong class="vital-val">ASA Class I</strong>
              <span class="vital-sub">Normal healthy status</span>
            </div>
          </div>

          <div class="vital-card">
            <div class="vital-icon allergy"><i class="fa-solid fa-triangle-exclamation"></i></div>
            <div class="vital-info">
              <span class="vital-label">ALLERGIES / ADVERSE</span>
              <strong class="vital-val">NKDA</strong>
              <span class="vital-sub">No known drug allergies</span>
            </div>
          </div>

          <div class="vital-card">
            <div class="vital-icon airway"><i class="fa-solid fa-lungs"></i></div>
            <div class="vital-info">
              <span class="vital-label">INTUBATION HISTORY</span>
              <strong class="vital-val">Mallampati I</strong>
              <span class="vital-sub">Easy airway access verified</span>
            </div>
          </div>
        </div>

        <!-- Dynamic Surgeries Container -->
        <div id="surgeriesContainer">
          <?php if (empty($surgeries)): ?>
            <div class="card procedure-card">
              <p style="padding: 20px; text-align: center; color: #64748b;">No surgical or procedure records available on file.</p>
            </div>
          <?php else: ?>
            <?php foreach ($surgeries as $surg): ?>
              <?php 
                $procTypeClass = strtolower($surg['procedure_type']);
                $badgeClass = ($surg['status'] === 'completed') ? 'success' : 'dark-blue';
                $statusText = ($surg['status'] === 'completed') ? 'Successful / Healed' : ucfirst($surg['status']);
              ?>
              <div class="card procedure-card" data-category="<?= htmlspecialchars($procTypeClass) ?>">
                <div class="procedure-header">
                  <div class="proc-title-left">
                    <div class="proc-icon blue-bg"><i class="fa-solid fa-briefcase-medical"></i></div>
                    <div>
                      <div class="proc-title-line">
                        <h3><?= htmlspecialchars($surg['procedure_title']) ?></h3>
                        <?php if (!empty($surg['primary_procedure'])): ?>
                          <span class="sub-name">(<?= htmlspecialchars($surg['primary_procedure']) ?>)</span>
                        <?php endif; ?>
                      </div>
                      <p class="proc-meta-id">Procedure ID: SUR-<?= date('Y', strtotime($surg['operation_datetime'])) ?>-<?= sprintf('%04d', $surg['surgery_id']) ?></p>
                    </div>
                  </div>
                  <div class="proc-tags">
                    <span class="status-badge <?= $badgeClass ?>"><i class="fa-solid fa-circle-check"></i> <?= $statusText ?></span>
                    <span class="type-badge <?= $procTypeClass ?>"><?= ucfirst($surg['procedure_type']) ?></span>
                  </div>
                </div>

                <div class="meta-columns-grid">
                  <div class="meta-col">
                    <span class="col-label">DATE OF OPERATION</span>
                    <strong class="col-val"><?= date('d-m-Y', strtotime($surg['operation_datetime'])) ?></strong>
                    <span class="col-sub"><?= date('h:i A', strtotime($surg['operation_datetime'])) ?></span>
                  </div>
                  <div class="meta-col">
                    <span class="col-label">HOSPITAL & FACILITY</span>
                    <strong class="col-val"><?= htmlspecialchars($surg['hospital_name'] ?? 'N/A') ?></strong>
                  </div>
                  <div class="meta-col">
                    <span class="col-label">LEAD SURGEON</span>
                    <strong class="col-val"><?= htmlspecialchars($surg['lead_surgeon'] ?? 'N/A') ?></strong>
                    <span class="col-sub"><?= htmlspecialchars($surg['surgeon_qualifications'] ?? '') ?></span>
                  </div>
                  <div class="meta-col">
                    <span class="col-label">BLOOD LOSS / SPECIMEN</span>
                    <strong class="col-val"><?= htmlspecialchars($surg['estimated_blood_loss_ml'] ?? '0') ?> mL</strong>
                    <span class="col-sub"><?= htmlspecialchars($surg['specimen_pathology'] ?? 'N/A') ?></span>
                  </div>
                </div>

                <div class="proc-body-grid">
                  <div class="narrative-box">
                    <h4><i class="fa-solid fa-file-lines"></i> Surgical Narrative & Post-Op Recovery</h4>
                    <p><?= htmlspecialchars($surg['contraindication_notes'] ?? 'No surgical contraindications or complications noted during the procedure.') ?></p>
                    <div class="narrative-pills">
                      <span class="info-pill"><i class="fa-regular fa-user"></i> Anesthesiologist: <?= htmlspecialchars($surg['anesthesiologist_name'] ?? 'N/A') ?></span>
                      <span class="info-pill"><i class="fa-solid fa-pills"></i> Medications: <?= htmlspecialchars($surg['medications_used'] ?? 'N/A') ?></span>
                    </div>
                  </div>

                  <div class="documents-box">
                    <h4><i class="fa-solid fa-shield-check"></i> Verified Documents</h4>
                    <div class="doc-list">
                      <?php if (!empty($surg['report_file_url'])): ?>
                        <div class="doc-item">
                          <i class="fa-regular fa-file-pdf doc-icon"></i>
                          <div class="doc-info">
                            <strong>Operative_Report_SUR<?= $surg['surgery_id'] ?>.pdf</strong>
                            <p>Certified Digital Record</p>
                          </div>
                          <button class="doc-dl-btn" onclick="handleDownloadDocument('<?= htmlspecialchars($surg['report_file_url']) ?>')">
                            <i class="fa-solid fa-download"></i>
                          </button>
                        </div>
                      <?php else: ?>
                        <p style="color: #94a3b8; font-size: 0.85rem;">No attachment files linked.</p>
                      <?php endif; ?>
                    </div>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>

      </main>
    </div>
  </div>

  <!-- JavaScript File Link -->
  <script src="/public_html/assets/js/patient/surgary.js"></script>
</body>
</html>