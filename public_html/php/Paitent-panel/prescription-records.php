<?php
// Load Patient Bootstrap
require_once __DIR__ . '/patient_bootstrap.php';

// Fetch prescriptions for current patient
$stmtRx = $pdo->prepare("
    SELECT rx.*, 
           d.full_name AS doctor_name, 
           d.bmdc_registration_no,
           s.name AS specialty_name,
           h.legal_name AS hospital_name
    FROM prescriptions rx
    LEFT JOIN doctors d ON rx.doctor_id = d.doctor_id
    LEFT JOIN specialties s ON d.primary_specialty_id = s.specialty_id
    LEFT JOIN hospitals h ON d.hospital_id = h.hospital_id
    WHERE rx.patient_id = ?
    ORDER BY rx.created_at DESC
");
$stmtRx->execute([$patientId]);
$prescriptions = $stmtRx->fetchAll();

if (!empty($prescriptions)) {
    $rxIds = array_column($prescriptions, 'prescription_id');
    $placeholders = implode(',', array_fill(0, count($rxIds), '?'));
    
    // Medications
    $stmtMeds = $pdo->prepare("SELECT * FROM prescription_medications WHERE prescription_id IN ($placeholders)");
    $stmtMeds->execute($rxIds);
    $allMeds = $stmtMeds->fetchAll();
    
    $medsByRx = [];
    foreach ($allMeds as $med) {
        $medsByRx[$med['prescription_id']][] = $med;
    }
    
    // Lab Tests
    $stmtLabs = $pdo->prepare("SELECT * FROM prescription_lab_tests WHERE prescription_id IN ($placeholders)");
    $stmtLabs->execute($rxIds);
    $allLabs = $stmtLabs->fetchAll();
    
    $labsByRx = [];
    foreach ($allLabs as $lab) {
        $labsByRx[$lab['prescription_id']][] = $lab;
    }
    
    foreach ($prescriptions as &$rx) {
        $rx['medications'] = $medsByRx[$rx['prescription_id']] ?? [];
        $rx['lab_tests'] = $labsByRx[$rx['prescription_id']] ?? [];
    }
    unset($rx);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>NHMRD - Prescription Records</title>
  <link rel="stylesheet" href="/public_html/assets/css/patient-panel/dashboard.css">
  <link rel="stylesheet" href="/public_html/assets/css/default-structure.css">
  <link rel="stylesheet" href="/public_html/assets/css/patient-panel/features/patient-header.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    /* Inline helper styling for tab filtering & prescription cards */
    .filter-tab-bar { display: flex; gap: 10px; margin-bottom: 20px; border-bottom: 2px solid #e2e8f0; padding-bottom: 8px; }
    .tab-btn { background: none; border: none; padding: 8px 16px; font-weight: 600; color: #64748b; cursor: pointer; border-radius: 6px; transition: all 0.2s; }
    .tab-btn.active { background: #2563eb; color: #fff; }
    .rx-card { background: #fff; border-radius: 8px; padding: 20px; margin-bottom: 20px; border: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
    .rx-card-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px; }
    .rx-title { font-size: 1.15rem; font-weight: 700; color: #1e293b; margin: 0 0 4px 0; }
    .rx-doc { font-size: 0.9rem; color: #475569; font-weight: 600; }
    .badge-active { background-color: #dcfce7; color: #166534; padding: 4px 10px; border-radius: 12px; font-size: 0.75rem; font-weight: 700; }
    .badge-chronic { background-color: #fef3c7; color: #92400e; padding: 4px 10px; border-radius: 12px; font-size: 0.75rem; font-weight: 700; }
    .badge-completed { background-color: #f1f5f9; color: #475569; padding: 4px 10px; border-radius: 12px; font-size: 0.75rem; font-weight: 700; }
    .meds-table { width: 100%; border-collapse: collapse; margin-top: 12px; font-size: 0.88rem; }
    .meds-table th, .meds-table td { text-align: left; padding: 8px 12px; border-bottom: 1px solid #f1f5f9; }
    .meds-table th { background-color: #f8fafc; color: #475569; }
    .rx-footer { display: flex; justify-content: space-between; align-items: center; margin-top: 15px; pt: 10px; border-top: 1px dashed #e2e8f0; font-size: 0.85rem; color: #64748b; }
    .modal-backdrop { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center; }
    .modal-content { background: #fff; width: 90%; max-width: 650px; border-radius: 8px; padding: 24px; max-height: 85vh; overflow-y: auto; }
  </style>
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
        <a href="prescription-records.php" class="nav-item active">
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
          <input type="text" id="prescriptionSearch" placeholder="Search prescriptions or medications...">
        </div>

        <div class="header-right">
          <button class="icon-btn" id="notificationBtn"><i class="fa-regular fa-bell"></i></button>
          
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

            <div class="header-actions">
              <button class="btn-secondary" id="updateDataBtn">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16">
                  <path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8" />
                  <polyline points="16 6 12 2 8 6" />
                  <line x1="12" y1="2" x2="12" y2="15" />
                </svg>
                Update Data
              </button>
              <button class="btn-primary" id="printCardBtn">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16">
                  <polyline points="6 9 6 2 18 2 18 9" />
                  <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2" />
                  <rect x="6" y="14" width="12" height="8" />
                </svg>
                Print List
              </button>
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

        <!-- Stat Cards -->
        <div class="stats-grid">
          <div class="card stat-card">
            <div class="stat-icon-wrapper icon-blue">
              <i class="fa-solid fa-file-prescription"></i>
            </div>
            <div>
              <div class="stat-number"><?= $totalPrescriptions ?></div>
              <div class="stat-label">PRESCRIPTIONS</div>
            </div>
          </div>

          <div class="card stat-card">
            <div class="stat-icon-wrapper icon-cyan">
              <i class="fa-solid fa-vial"></i>
            </div>
            <div>
              <div class="stat-number"><?= $totalLabTests ?></div>
              <div class="stat-label">LAB TESTS</div>
            </div>
          </div>

          <div class="card stat-card">
            <div class="stat-icon-wrapper icon-pink">
              <i class="fa-solid fa-syringe"></i>
            </div>
            <div>
              <div class="stat-number"><?= $totalVaccines ?></div>
              <div class="stat-label">VACCINES</div>
            </div>
          </div>

          <div class="card stat-card">
            <div class="stat-icon-wrapper icon-indigo">
              <i class="fa-solid fa-scalpel"></i>
            </div>
            <div>
              <div class="stat-number"><?= $totalSurgeries ?></div>
              <div class="stat-label">MAJOR SURGERY</div>
            </div>
          </div>
        </div>

        <!-- Filter Tab Navigation -->
        <div class="filter-tab-bar">
          <button class="tab-btn active" data-filter="all">All Prescriptions (<?= count($prescriptions) ?>)</button>
          <button class="tab-btn" data-filter="active">Active Treatments</button>
          <button class="tab-btn" data-filter="chronic">Chronic / Long-Term</button>
          <button class="tab-btn" data-filter="completed">Completed</button>
        </div>

        <!-- Prescription Records List -->
        <div id="prescriptionList">
          <?php if (empty($prescriptions)): ?>
            <div class="card" style="padding: 30px; text-align: center; color: #64748b;">
              <i class="fa-solid fa-folder-open" style="font-size: 2.5rem; margin-bottom: 10px;"></i>
              <p>No prescription records found in your medical history.</p>
            </div>
          <?php else: ?>
            <?php foreach ($prescriptions as $rx): ?>
              <?php 
                $filterType = 'active';
                $badgeClass = 'badge-active';
                $badgeText  = 'Active';

                if ($rx['is_long_term']) {
                    $filterType = 'chronic';
                    $badgeClass = 'badge-chronic';
                    $badgeText  = 'Chronic / Long-Term';
                } elseif ($rx['status'] === 'completed') {
                    $filterType = 'completed';
                    $badgeClass = 'badge-completed';
                    $badgeText  = 'Completed';
                }
              ?>
              <div class="rx-card" data-category="<?= $filterType ?>" data-rx-id="<?= $rx['prescription_id'] ?>">
                <div class="rx-card-header">
                  <div>
                    <h3 class="rx-title"><?= htmlspecialchars($rx['title']) ?></h3>
                    <div class="rx-doc">
                      <i class="fa-solid fa-user-doctor"></i> Dr. <?= htmlspecialchars($rx['doctor_name']) ?> 
                      <small>(BMDC: <?= htmlspecialchars($rx['bmdc_registration_no'] ?? 'N/A') ?> &bull; <?= htmlspecialchars($rx['specialty_name'] ?? 'General') ?>)</small>
                    </div>
                  </div>
                  <span class="<?= $badgeClass ?>"><?= $badgeText ?></span>
                </div>

                <div style="font-size: 0.88rem; color: #475569; margin-bottom: 10px;">
                  <strong>Hospital:</strong> <?= htmlspecialchars($rx['hospital_name'] ?? 'National Health Registry Partner') ?>
                </div>

                <?php if (!empty($rx['chief_complaint'])): ?>
                  <div style="font-size: 0.88rem; color: #334155; margin-bottom: 12px; background: #f8fafc; padding: 8px 12px; border-left: 3px solid #2563eb; border-radius: 0 4px 4px 0;">
                    <strong>Chief Complaint:</strong> <?= htmlspecialchars($rx['chief_complaint']) ?>
                  </div>
                <?php endif; ?>

                <!-- Medication Table -->
                <?php if (!empty($rx['medications'])): ?>
                  <table class="meds-table">
                    <thead>
                      <tr>
                        <th>Medication</th>
                        <th>Dose / Strength</th>
                        <th>Frequency / Route</th>
                        <th>Quantity</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($rx['medications'] as $med): ?>
                        <tr>
                          <td><strong><?= htmlspecialchars($med['medication_name']) ?></strong></td>
                          <td><?= htmlspecialchars($med['dose_strength'] ?? '-') ?></td>
                          <td><?= htmlspecialchars($med['route_frequency'] ?? '-') ?></td>
                          <td><?= htmlspecialchars($med['dispense_quantity'] ?? '-') ?></td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                <?php endif; ?>

                <div class="rx-footer">
                  <div>
                    <i class="fa-regular fa-calendar"></i> Prescribed Date: <?= date('d-m-Y', strtotime($rx['created_at'])) ?>
                    <?php if ($rx['next_visit_date']): ?>
                      &bull; <span class="text-primary"><i class="fa-regular fa-clock"></i> Next Visit: <?= date('d-m-Y', strtotime($rx['next_visit_date'])) ?></span>
                    <?php endif; ?>
                  </div>
                  <div>
                    <button class="btn-secondary view-rx-detail-btn" data-rx-id="<?= $rx['prescription_id'] ?>" style="padding: 4px 10px; font-size: 0.8rem;">
                      <i class="fa-solid fa-eye"></i> View Full Rx
                    </button>
                    <button class="btn-primary print-rx-btn" data-rx-id="<?= $rx['prescription_id'] ?>" style="padding: 4px 10px; font-size: 0.8rem;">
                      <i class="fa-solid fa-print"></i> Print
                    </button>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>

      </main>
    </div>
  </div>

  <!-- Prescription Detail Modal -->
  <div class="modal-backdrop" id="rxModal">
    <div class="modal-content">
      <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #e2e8f0; padding-bottom: 10px; margin-bottom: 15px;">
        <h3 id="modalRxTitle" style="margin: 0; color: #1e293b;">Prescription Details</h3>
        <button id="closeModalBtn" style="background: none; border: none; font-size: 1.2rem; cursor: pointer; color: #64748b;">&times;</button>
      </div>
      <div id="modalRxBody" style="font-size: 0.9rem; color: #334155; line-height: 1.5;">
        <!-- Dynamic Modal Data Injected by JS -->
      </div>
    </div>
  </div>

  <!-- Dynamic JS Data & App Handler -->
  <script>
    window.PRESCRIPTIONS_DATA = <?= json_encode($prescriptions) ?>;
  </script>
  <script src="/public_html/assets/js/patient/prescription-record.js"></script>
</body>
</html>