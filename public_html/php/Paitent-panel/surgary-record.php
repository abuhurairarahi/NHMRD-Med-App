<?php
// Load Patient Bootstrap
require_once __DIR__ . '/patient_bootstrap.php';

// Fetch surgical records for patient
$stmtSurgRec = $pdo->prepare("
    SELECT s.*, 
           d.full_name AS surgeon_name, 
           d.bmdc_registration_no,
           d.qualifications AS surgeon_qualifications,
           h.legal_name AS hospital_name
    FROM surgical_records s
    LEFT JOIN doctors d ON s.surgeon_id = d.doctor_id
    LEFT JOIN hospitals h ON s.hospital_id = h.hospital_id
    WHERE s.patient_id = ?
    ORDER BY s.operation_datetime DESC
");
$stmtSurgRec->execute([$patientId]);
$surgeries = $stmtSurgRec->fetchAll();

$totalSurgeriesCount = count($surgeries);
$inpatientCount = 0;
$outpatientCount = 0;
$preOpCount = 0;

foreach ($surgeries as $s) {
    $ptype = strtolower($s['procedure_type'] ?? 'inpatient');
    if ($ptype === 'inpatient') $inpatientCount++;
    elseif ($ptype === 'outpatient') $outpatientCount++;
    elseif ($ptype === 'pre_op') $preOpCount++;
}
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
        <a href="dashboard.php" class="nav-item">
          <i class="fa-solid fa-table-cells-large"></i> 
          <span>Dashboard</span>
        </a>
        <a href="prescription-records.php" class="nav-item">
          <i class="fa-solid fa-file-prescription"></i> 
          <span>Prescription Records</span>
        </a>
        <a href="surgary-record.php" class="nav-item active">
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
        <button class="logout-btn" onclick="location.href='/public_html/api/logout.php'">
          <i class="fa-solid fa-arrow-right-from-bracket"></i> 
          <span>Logout</span>
        </button>
      </div>
    </aside>

    <div class="main-wrapper">
      <header class="patient-header">
        <div class="search-bar">
          <i class="fa-solid fa-magnifying-glass"></i>
          <input type="text" placeholder="Search Queries" oninput="handleGlobalSearch(event)">
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
                Patient ID: #<?= htmlspecialchars($patient['user_uid'] ?? $patient['patient_id']) ?> &bull; NID: <?= htmlspecialchars($patient['nid'] ?? 'N/A') ?> &bull; <span class="blood-group">Blood Group: <?= htmlspecialchars($patient['blood_group'] ?? 'N/A') ?></span>
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
            <button class="tab-btn active" data-filter="all" onclick="handleTabFilter(this)">All Surgeries (<?= $totalSurgeriesCount ?>)</button>
            <button class="tab-btn" data-filter="inpatient" onclick="handleTabFilter(this)">Inpatient (<?= $inpatientCount ?>)</button>
            <button class="tab-btn" data-filter="outpatient" onclick="handleTabFilter(this)">Outpatient (<?= $outpatientCount ?>)</button>
            <button class="tab-btn" data-filter="pre-op" onclick="handleTabFilter(this)">Pre-op Assessments (<?= $preOpCount ?>)</button>
          </div>
        </div>

        <div class="vitals-grid">
          <div class="vital-card">
            <div class="vital-icon blood"><i class="fa-solid fa-droplet"></i></div>
            <div class="vital-info">
              <span class="vital-label">BLOOD GROUP</span>
              <strong class="vital-val"><?= htmlspecialchars($patient['blood_group'] ?? 'N/A') ?></strong>
              <span class="vital-sub">Verified Blood Profile</span>
            </div>
          </div>

          <div class="vital-card">
            <div class="vital-icon risk"><i class="fa-solid fa-shield-heart"></i></div>
            <div class="vital-info">
              <span class="vital-label">ANESTHESIA RISK</span>
              <strong class="vital-val">ASA Class I-II</strong>
              <span class="vital-sub">Surgical Clearance Active</span>
            </div>
          </div>

          <div class="vital-card">
            <div class="vital-icon allergy"><i class="fa-solid fa-triangle-exclamation"></i></div>
            <div class="vital-info">
              <span class="vital-label">ALLERGIES</span>
              <strong class="vital-val"><?= !empty($patientAllergies) ? count($patientAllergies) . ' Documented' : 'NKDA' ?></strong>
              <span class="vital-sub"><?= !empty($patientAllergies) ? htmlspecialchars($patientAllergies[0]['allergy_name']) : 'No known drug allergies' ?></span>
            </div>
          </div>

          <div class="vital-card">
            <div class="vital-icon airway"><i class="fa-solid fa-lungs"></i></div>
            <div class="vital-info">
              <span class="vital-label">AIRWAY ASSESSMENT</span>
              <strong class="vital-val">Mallampati I</strong>
              <span class="vital-sub">Easy airway access verified</span>
            </div>
          </div>
        </div>

        <?php if (empty($surgeries)): ?>
          <div class="card" style="padding: 40px; text-align: center; color: #64748b; margin-top: 20px;">
            <i class="fa-solid fa-scalpel" style="font-size: 2.5rem; margin-bottom: 12px; color: #94a3b8;"></i>
            <h3>No Surgical Records Found</h3>
            <p>There are no past or upcoming surgical procedures recorded in your national registry.</p>
          </div>
        <?php else: ?>
          <?php foreach ($surgeries as $sug): ?>
            <?php 
              $procType = strtolower($sug['procedure_type'] ?? 'inpatient');
              $opDate = !empty($sug['operation_datetime']) ? date('d-m-Y', strtotime($sug['operation_datetime'])) : 'N/A';
              $opTime = !empty($sug['operation_datetime']) ? date('h:i A', strtotime($sug['operation_datetime'])) : '';
              $status = strtolower($sug['status'] ?? 'completed');
              $statusClass = $status === 'completed' ? 'success' : ($status === 'scheduled' ? 'dark-blue' : 'warning');
              $statusLabel = $status === 'completed' ? 'Successful / Healed' : ucfirst($status);
            ?>
            <div class="card procedure-card">
              <div class="procedure-header">
                <div class="proc-title-left">
                  <div class="proc-icon blue-bg"><i class="fa-solid fa-briefcase-medical"></i></div>
                  <div>
                    <div class="proc-title-line">
                      <h3><?= htmlspecialchars($sug['procedure_title']) ?></h3>
                      <?php if (!empty($sug['primary_procedure'])): ?>
                        <span class="sub-name">(<?= htmlspecialchars($sug['primary_procedure']) ?>)</span>
                      <?php endif; ?>
                    </div>
                    <p class="proc-meta-id">Procedure ID: SUR-<?= str_pad($sug['surgery_id'], 4, '0', STR_PAD_LEFT) ?> &bull; Department of Surgery</p>
                  </div>
                </div>
                <div class="proc-tags">
                  <span class="status-badge <?= $statusClass ?>"><i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($statusLabel) ?></span>
                  <span class="type-badge"><?= ucfirst($procType) ?></span>
                </div>
              </div>

              <div class="meta-columns-grid">
                <div class="meta-col">
                  <span class="col-label">DATE OF OPERATION</span>
                  <strong class="col-val"><?= $opDate ?></strong>
                  <span class="col-sub"><?= $opTime ?></span>
                </div>
                <div class="meta-col">
                  <span class="col-label">HOSPITAL & FACILITY</span>
                  <strong class="col-val"><?= htmlspecialchars($sug['hospital_name'] ?? 'National Hospital Registry') ?></strong>
                  <span class="col-sub">Main OT Suite</span>
                </div>
                <div class="meta-col">
                  <span class="col-label">LEAD SURGEON</span>
                  <strong class="col-val"><?= !empty($sug['surgeon_name']) ? 'Dr. ' . htmlspecialchars($sug['surgeon_name']) : 'Chief Surgeon' ?></strong>
                  <span class="col-sub"><?= htmlspecialchars($sug['surgeon_qualifications'] ?? 'Consultant Surgeon') ?></span>
                </div>
                <div class="meta-col">
                  <span class="col-label">PROCEDURE STATUS</span>
                  <strong class="col-val"><?= htmlspecialchars(ucfirst($sug['status'] ?? 'Completed')) ?></strong>
                  <span class="col-sub">Blood Loss: <?= htmlspecialchars($sug['estimated_blood_loss_ml'] ?? '0') ?> ml</span>
                </div>
              </div>

              <div class="proc-body-grid">
                <div class="narrative-box">
                  <h4><i class="fa-solid fa-file-lines"></i> Surgical Narrative & Post-Op Recovery</h4>
                  <p>
                    <?= !empty($sug['intra_op_complication_status']) ? htmlspecialchars($sug['intra_op_complication_status']) : 'Standard procedure conducted under general anesthesia. Operating field prepared and draped in standard sterile fashion. Hemostasis verified. Patient tolerated the procedure well and transferred to recovery unit in stable condition.' ?>
                  </p>
                  <div class="narrative-pills">
                    <?php if (!empty($sug['anesthesiologist_name'])): ?>
                      <span class="info-pill"><i class="fa-regular fa-user"></i> Anesthesiologist: <?= htmlspecialchars($sug['anesthesiologist_name']) ?></span>
                    <?php endif; ?>
                    <?php if (!empty($sug['medications_used'])): ?>
                      <span class="info-pill"><i class="fa-solid fa-pills"></i> Meds: <?= htmlspecialchars($sug['medications_used']) ?></span>
                    <?php endif; ?>
                    <?php if (!empty($sug['specimen_pathology'])): ?>
                      <span class="info-pill"><i class="fa-solid fa-microscope"></i> Pathology: <?= htmlspecialchars($sug['specimen_pathology']) ?></span>
                    <?php endif; ?>
                  </div>
                </div>

                <div class="documents-box">
                  <h4><i class="fa-solid fa-shield-check"></i> Verified Documents</h4>
                  <div class="doc-list">
                    <div class="doc-item">
                      <i class="fa-regular fa-file-pdf doc-icon"></i>
                      <div class="doc-info">
                        <strong>Operative_Note_SUR<?= $sug['surgery_id'] ?>.pdf</strong>
                        <p>Certified Clinical Record</p>
                      </div>
                      <button class="doc-dl-btn" onclick="handleDownloadDocument('Operative_Note_SUR<?= $sug['surgery_id'] ?>.pdf')"><i class="fa-solid fa-download"></i></button>
                    </div>

                    <div class="doc-item">
                      <i class="fa-regular fa-file-pdf doc-icon"></i>
                      <div class="doc-info">
                        <strong>Discharge_Summary_SUR<?= $sug['surgery_id'] ?>.pdf</strong>
                        <p>Official Hospital Summary</p>
                      </div>
                      <button class="doc-dl-btn" onclick="handleDownloadDocument('Discharge_Summary_SUR<?= $sug['surgery_id'] ?>.pdf')"><i class="fa-solid fa-download"></i></button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>

      </main>
    </div>
  </div>
<script src="/public_html/assets/js/paitent/surgary.js"></script>
</body>
</html>
