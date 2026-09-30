<?php
// Load Data Controller
$data = require_once __DIR__ . '/../../controllers/patient/PatientInfoController.php';

$patient          = $data['patient'];
$userInitials     = $data['userInitials'];
$contacts         = $data['contacts'] ?? [];
$donors           = $data['donors'] ?? [];
$vitalsHistory    = $data['vitalsHistory'] ?? [];
$vitalAverages    = $data['vitalAverages'] ?? [];
$labTestsCount    = $data['labTestsCount'] ?? 0;
$activeRxCount    = $data['activeRxCount'] ?? 0;

// Calculate age from DOB
$birthDate = new DateTime($patient['dob']);
$todayDate = new DateTime();
$age       = $todayDate->diff($birthDate)->y;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Patient Profile & Identification - NHMRD</title>
  <link rel="stylesheet" href="/public_html/assets/css/patient-panel/patient-info.css">
  <link rel="stylesheet" href="/public_html/assets/css/default-structure.css">
  <link rel="stylesheet" href="/public_html/assets/css/patient-panel/features/patient-header.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
  <div class="app-container">
    <!-- Sidebar -->
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
        <a href="/public_html/pages/Patient-panel/prescription-record.php" class="nav-item">
          <i class="fa-solid fa-file-prescription"></i> 
          <span>Prescription Records</span>
        </a>
        <a href="/public_html/pages/Patient-panel/surgary-record.php" class="nav-item">
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
        <a href="/public_html/pages/Patient-panel/paitent-info.php" class="nav-item active">
          <i class="fa-solid fa-id-card"></i> 
          <span>Patient Info</span>
        </a>
      </nav>
      <div class="sidebar-footer">
        <button class="logout-btn" id="logoutBtn"><i class="fa-solid fa-arrow-right-from-bracket"></i> Logout</button>
      </div>
    </aside>

    <!-- Main Wrapper -->
    <div class="main-wrapper">
      <!-- Top Navigation Header -->
      <header class="patient-header">
        <div class="search-bar">
          <i class="fa-solid fa-magnifying-glass"></i>
          <input type="text" id="patientSearchInput" placeholder="Search Queries">
        </div>
        <div class="user-profile">
          <div class="user-info">
            <span class="user-name"><?= htmlspecialchars($patient['full_name']) ?></span>
          </div>
          <div class="avatar"><?= htmlspecialchars($userInitials) ?></div>
        </div>
      </header>

      <!-- Main Body Content -->
      <main class="dashboard-body">
        <!-- Sub Header -->
        <div class="page-title-section">
          <div>
            <div class="sub-title">National Health & Medical Record Directory</div>
            <h1>Patient Profile & Identification</h1>
          </div>
          <button class="btn-download-card" id="downloadHealthCardBtn">
            <i class="fa-solid fa-download"></i> Download Digital Health Card
          </button>
        </div>

        <!-- Patient Info Card -->
        <div class="card patient-header-card">
          <div class="patient-main-info">
            <img src="<?= !empty($patient['photo_url']) ? htmlspecialchars($patient['photo_url']) : 'https://via.placeholder.com/70' ?>" alt="<?= htmlspecialchars($patient['full_name']) ?>" class="patient-avatar">
            <div class="patient-details">
              <h2><?= htmlspecialchars($patient['full_name']) ?> <span class="badge-status"><?= htmlspecialchars(ucwords($patient['status'] ?? 'Active Citizen')) ?></span></h2>
              <div class="patient-meta-text">
                Patient ID: #<?= htmlspecialchars($patient['user_uid'] ?? $patient['patient_id']) ?> &bull; National ID (NID): <?= htmlspecialchars($patient['nid'] ?? 'N/A') ?> &bull; Citizen Status: Enrolled NHPS
              </div>
              <div class="pills-container">
                <span class="pill pill-danger"><i class="fa-solid fa-droplet"></i> Blood Group <?= htmlspecialchars($patient['blood_group'] ?? 'N/A') ?></span>
                <span class="pill">Age: <?= $age ?> Yrs (<?= date('d-m-Y', strtotime($patient['dob'])) ?>)</span>
                <span class="pill">Gender: <?= htmlspecialchars(ucfirst($patient['gender'])) ?></span>
                <span class="pill">Health Card: <?= htmlspecialchars($patient['health_card_no'] ?? 'N/A') ?></span>
              </div>
            </div>
          </div>
          <div class="registry-otp-box" id="authenticateOtpBox" style="cursor: pointer;">
            <i class="fa-solid fa-qrcode qr-icon"></i>
            <div class="otp-text">
              <span class="otp-title">REGISTRY OTP</span>
              <span class="otp-val">NHMRD-VALIDATED</span>
              <span class="otp-sub">Tap to authenticate</span>
            </div>
          </div>
        </div>

        <!-- 2 Grid Panel -->
        <div class="profile-grid">
          <!-- Left Column: Contact & Address & Organ Donors -->
          <div class="column-left">
            <!-- Contact Card -->
            <div class="card contact-card">
              <div class="panel-section-header">
                <h3>Contact</h3>
                <button class="btn-action-sm" id="addContactBtn">+ Add entry</button>
              </div>
              <div class="contact-item">
                <i class="fa-solid fa-envelope"></i>
                <span class="label">Registered Email</span>
                <span class="val"><?= htmlspecialchars($patient['email'] ?? 'N/A') ?></span>
              </div>
              <div class="contact-item">
                <i class="fa-solid fa-phone"></i>
                <span class="label">Mobile Phone</span>
                <span class="val"><?= htmlspecialchars($patient['phone'] ?? 'N/A') ?></span>
              </div>
              <div class="contact-item">
                <i class="fa-solid fa-phone-volume"></i>
                <span class="label">Emergency Contact</span>
                <span class="val">
                  <?= htmlspecialchars($patient['emergency_contact_name'] ?? 'N/A') ?> (<?= htmlspecialchars($patient['emergency_contact_relation'] ?? 'Emergency') ?>)<br>
                  <small class="text-muted"><?= htmlspecialchars($patient['emergency_contact_phone'] ?? 'N/A') ?></small>
                </span>
              </div>

              <!-- Dynamic Extra Contacts -->
              <?php foreach ($contacts as $contact): ?>
                <div class="contact-item">
                  <i class="fa-solid fa-address-book"></i>
                  <span class="label"><?= htmlspecialchars($contact['label']) ?></span>
                  <span class="val"><?= htmlspecialchars($contact['value']) ?></span>
                </div>
              <?php endforeach; ?>

              <div class="address-card">
                <div class="title"><i class="fa-solid fa-location-dot"></i> RESIDENTIAL & PERMANENT ADDRESS</div>
                <strong><?= htmlspecialchars($patient['address'] ?? 'No address recorded') ?></strong>
                <p class="text-muted address-sub"><?= htmlspecialchars($patient['province'] ?? '') ?> <?= htmlspecialchars($patient['postal_code'] ? '- ' . $patient['postal_code'] : '') ?></p>
              </div>
            </div>

            <!-- Organ & Blood Donors Card -->
            <div class="card">
              <div class="panel-section-header">
                <h3>Organ & Blood Donors <i class="fa-solid fa-heart-pulse text-danger"></i></h3>
                <button class="btn-action-sm btn-add-donor" id="addDonorBtn">+ Add Donor</button>
              </div>
              <p class="text-muted donor-sub">Registered for organ and blood donation</p>
              <div id="donorListContainer">
                <?php if (empty($donors)): ?>
                  <p class="text-muted" style="padding: 10px 0;">No blood/organ donors registered yet.</p>
                <?php else: ?>
                  <?php foreach ($donors as $donor): ?>
                    <div class="donor-item">
                      <div class="donor-info">
                        <span class="blood-badge"><?= htmlspecialchars($donor['blood_group']) ?></span>
                        <div>
                          <strong><?= htmlspecialchars($donor['donor_name']) ?></strong> 
                          <span class="badge-status badge-<?= strtolower($donor['verification_status']) === 'verified' ? 'verified' : 'due' ?>">
                            <?= htmlspecialchars(ucfirst($donor['verification_status'])) ?>
                          </span>
                          <div class="text-muted donor-contact">
                            <i class="fa-solid fa-phone"></i> <?= htmlspecialchars($donor['phone']) ?>
                          </div>
                        </div>
                      </div>
                    </div>
                  <?php endforeach; ?>
                <?php endif; ?>
              </div>
            </div>
          </div>

          <!-- Right Column: Vitals Baseline & History -->
          <div class="column-right">
            <div class="card">
              <div class="panel-section-header">
                <div>
                  <h3>Vitals Baseline & Bio Data</h3>
                  <span class="text-muted last-synced">Last synced: <?= !empty($patient['vitals_last_synced_at']) ? date('d-m-Y', strtotime($patient['vitals_last_synced_at'])) : 'N/A' ?></span>
                </div>
                <button class="btn-action-sm" id="updateVitalsBtn"><i class="fa-solid fa-pen"></i> Update Vitals</button>
              </div>

              <!-- Vitals Grid -->
              <div class="vitals-grid">
                <div class="vital-box">
                  <div class="vital-header">
                    <span>BLOOD PRESSURE</span>
                    <i class="fa-solid fa-heart text-danger"></i>
                  </div>
                  <div class="vital-value"><?= htmlspecialchars($patient['blood_pressure'] ?? '120/80') ?> <small class="unit-text">mmHg</small></div>
                  <div class="vital-status">&bull; Optimal / Normal</div>
                </div>
                <div class="vital-box">
                  <div class="vital-header">
                    <span>HEIGHT & WEIGHT</span>
                    <i class="fa-solid fa-ruler-vertical text-info"></i>
                  </div>
                  <div class="vital-value"><?= htmlspecialchars($patient['height_cm'] ?? '0') ?> cm &bull; <?= htmlspecialchars($patient['weight_kg'] ?? '0') ?> kg</div>
                  <div class="vital-status status-bmi">&bull; BMI <?= htmlspecialchars($patient['bmi'] ?? 'N/A') ?> (Normal)</div>
                </div>
              </div>

              <!-- Historical Vitals Table -->
              <table class="data-table">
                <thead>
                  <tr>
                    <th>Date & Time</th>
                    <th>Height</th>
                    <th>Weight</th>
                    <th>BMI</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (empty($vitalsHistory)): ?>
                    <tr>
                      <td colspan="4" style="text-align: center; color: #64748b;">No historical vitals found.</td>
                    </tr>
                  <?php else: ?>
                    <?php foreach ($vitalsHistory as $vital): ?>
                      <tr>
                        <td><?= date('d M Y • H:i', strtotime($vital['recorded_at'])) ?></td>
                        <td><?= htmlspecialchars($vital['height_cm']) ?> cm</td>
                        <td><?= htmlspecialchars($vital['weight_kg']) ?> kg</td>
                        <td><strong><?= htmlspecialchars($vital['bmi']) ?></strong></td>
                      </tr>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </tbody>
              </table>

              <!-- Averages Summary Box -->
              <div class="table-summary">
                <div>AVG HEIGHT: <strong><?= htmlspecialchars($vitalAverages['avg_height'] ?? $patient['height_cm'] ?? '0') ?> cm</strong></div>
                <div>AVG WEIGHT: <strong><?= htmlspecialchars($vitalAverages['avg_weight'] ?? $patient['weight_kg'] ?? '0') ?> kg</strong></div>
                <div>CURRENT BMI: <strong><?= htmlspecialchars($patient['bmi'] ?? 'N/A') ?></strong></div>
              </div>
            </div>
          </div>
        </div>

        <!-- Direct Access to Historical Medical Dossier -->
        <div class="dossier-banner">
          <div class="dossier-left">
            <div class="dossier-icon">
              <i class="fa-solid fa-folder-open"></i>
            </div>
            <div>
              <strong class="dossier-title">Direct Access to Historical Medical Dossier</strong>
              <p class="text-muted dossier-sub">View all authenticated laboratory tests, surgical histories, and continuous prescriptions.</p>
            </div>
          </div>
          <div class="dossier-actions">
            <button class="btn-dossier" id="viewTestRecordsBtn">All Test Records (<?= $labTestsCount ?>)</button>
            <button class="btn-dossier active-btn" id="viewPrescriptionRecordsBtn">Active Prescriptions (<?= $activeRxCount ?>)</button>
          </div>
        </div>
      </main>
    </div>
  </div>

  <script src="/public_html/assets/js/patient/paitent-info.js"></script>
</body>
</html>