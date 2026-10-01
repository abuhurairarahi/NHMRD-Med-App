<?php
// Load Data Controller
$data = require_once __DIR__ . '/../../controllers/patient/MedicalTestReqController.php';

$patient            = $data['patient'];
$userInitials       = $data['userInitials'];
$hospitals          = $data['hospitals'];
$labTests           = $data['labTests'];
$latestPrescription = $data['latestPrescription'];
$advisedTestIds     = $data['advisedTestIds'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Request Diagnostic & Pathology Tests - NHMRD</title>
  <link rel="stylesheet" href="/public_html/assets/css/patient-panel/medical-test-req.css">
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
        <a href="/public_html/pages/Patient-panel/dashboard.php" class="nav-item">
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
        <a href="/public_html/pages/Patient-panel/medical-test-req.php" class="nav-item active">
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
        <button class="logout-btn" id="logoutBtn">
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
          <input type="text" placeholder="Search Queries">
        </div>

        <div class="header-right">
          <button class="icon-btn" id="notificationBtn"><i class="fa-regular fa-bell"></i></button>
          <div class="user-badge-avatar"><?= htmlspecialchars($userInitials) ?></div>
          <span class="user-name"><?= htmlspecialchars($patient['full_name']) ?></span>
        </div>
      </header>

      <!-- Main Content Area -->
      <main class="content-body">

        <div class="page-top-bar">
          <div class="left-head">
            <div class="breadcrumb">
              <span>NHMRD</span> &rsaquo; <span>Diagnostic Services</span> &rsaquo; <span class="active">Test Request</span>
            </div>
            <h1>Request Diagnostic & Pathology Tests</h1>
            <p class="subtitle">Book certified home sample collection or direct hospital laboratory walk-in testing.</p>
          </div>
        </div>

        <form id="testRequestForm" enctype="multipart/form-data">
          <input type="hidden" id="patient_id" name="patient_id" value="<?= $patient['patient_id'] ?>">

          <div class="request-grid">

            <!-- Left Column: Form & Steps -->
            <div class="request-left-col">
              
              <!-- Step 1: Collection Center & Mode -->
              <div class="card step-card">
                <div class="step-card-header">
                  <div class="step-title">
                    <span class="step-number">1</span>
                    <h2>Select Collection Center & Mode</h2>
                  </div>
                </div>
                <p class="step-subtext">Choose between doorstep home sample collection or visiting an accredited diagnostic branch.</p>

                <div class="form-group">
                  <label>SAMPLE COLLECTION CENTER / BRANCH</label>
                  <div class="select-wrapper">
                    <select name="hospital_id" id="hospitalSelect">
                      <option value="home" selected>Home Sample Collection (Doorstep Phlebotomy)</option>
                      <?php foreach ($hospitals as $hospital): ?>
                        <option value="<?= $hospital['hospital_id'] ?>">
                          <?= htmlspecialchars($hospital['legal_name']) ?>
                        </option>
                      <?php endforeach; ?>
                    </select>
                    <i class="fa-solid fa-chevron-down select-icon"></i>
                  </div>
                </div>

                <!-- Address Info Banner -->
                <div class="info-banner-blue">
                  <div class="info-banner-left">
                    <i class="fa-solid fa-house-medical icon-blue"></i>
                    <div>
                      <strong id="centerModeTitle">Doorstep Phlebotomist Visit</strong>
                      <p id="centerModeDesc"><?= htmlspecialchars($patient['address'] ?? 'Patient Profile Address Registered') ?></p>
                    </div>
                  </div>
                  <div class="fee-tag" id="collectionFeeTag">Fee: ৳200</div>
                </div>
              </div>

              <!-- Step 2: Select Diagnostic Tests -->
              <div class="card step-card">
                <div class="step-card-header">
                  <div class="step-title">
                    <span class="step-number">2</span>
                    <h2>Select Diagnostic Tests</h2>
                  </div>
                  <a href="#" class="link-btn" id="selectAdvisedBtn">Select Advised</a>
                </div>

                <!-- Test Search Bar -->
                <div class="test-search-box">
                  <i class="fa-solid fa-magnifying-glass"></i>
                  <input type="text" id="testSearchInput" placeholder="Search tests by name, keyword or code...">
                </div>

                <!-- Test List Selection -->
                <div class="test-list" id="testListContainer">
                  <?php if (empty($labTests)): ?>
                    <p style="padding:15px; color:#64748b;">No diagnostic tests currently available.</p>
                  <?php else: ?>
                    <?php foreach ($labTests as $test): 
                      $isAdvised = in_array($test['test_id'], $advisedTestIds);
                    ?>
                      <div class="test-item <?= $isAdvised ? 'selected' : '' ?>">
                        <div class="test-checkbox">
                          <input type="checkbox" name="test_ids[]" value="<?= $test['test_id'] ?>" id="test_<?= $test['test_id'] ?>" data-price="<?= $test['price'] ?>" <?= $isAdvised ? 'checked' : '' ?>>
                        </div>
                        <div class="test-details">
                          <div class="test-header-line">
                            <label for="test_<?= $test['test_id'] ?>" class="test-title-text"><?= htmlspecialchars($test['test_name']) ?></label>
                            <?php if ($isAdvised): ?>
                              <span class="pill-badge doctor-advised">Doctor Advised</span>
                            <?php endif; ?>
                            <span class="test-price">৳<?= number_format($test['price'], 0) ?></span>
                          </div>
                          <p class="test-subdesc">Category: <?= htmlspecialchars(ucfirst($test['category'])) ?> <?= $test['test_code'] ? '('.htmlspecialchars($test['test_code']).')' : '' ?></p>
                          <div class="test-meta">
                            <span><i class="fa-solid fa-vial"></i> Clinical Pathology</span>
                            <span class="report-time">Standard Delivery</span>
                          </div>
                        </div>
                      </div>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </div>

                <div class="info-banner-gray">
                  <i class="fa-regular fa-circle-question"></i>
                  <span>Some tests are not available for home sample collection and require hospital visit.</span>
                </div>
              </div>

              <!-- Step 3: Appointment Date & Slot -->
              <div class="card step-card">
                <div class="step-card-header">
                  <div class="step-title">
                    <span class="step-number">3</span>
                    <h2>Appointment Date & Slot</h2>
                  </div>
                </div>

                <div class="date-slot-inputs">
                  <div class="form-group flex-1">
                    <label>DATE</label>
                    <div class="input-with-icon">
                      <input type="date" name="preferred_date" id="preferred_date" value="<?= date('Y-m-d', strtotime('+1 day')) ?>" required>
                    </div>
                  </div>
                  <div class="form-group flex-1">
                    <label>TIME SLOT</label>
                    <div class="input-with-icon">
                      <select name="preferred_slot" id="preferred_slot" required style="width:100%; padding: 10px; border-radius: 6px; border: 1px solid #cbd5e1;">
                        <option value="08:00 AM - 09:30 AM">08:00 AM - 09:30 AM</option>
                        <option value="09:30 AM - 11:00 AM">09:30 AM - 11:00 AM</option>
                        <option value="02:00 PM - 04:00 PM">02:00 PM - 04:00 PM</option>
                        <option value="05:00 PM - 07:00 PM">05:00 PM - 07:00 PM</option>
                      </select>
                    </div>
                  </div>
                </div>

                <div class="info-banner-gray fasting-note">
                  <i class="fa-regular fa-circle-question"></i>
                  <span>10–12 hours overnight water-only fasting is required for selected liver & lipid profiles.</span>
                </div>
              </div>

            </div>

            <!-- Right Column: Summary & Prescription -->
            <div class="request-right-col">
              
              <!-- Doctor's Prescription Card -->
              <div class="card prescription-card">
                <div class="presc-header">
                  <h2>Doctor's Prescription</h2>
                  <span class="linked-badge"><?= $latestPrescription ? 'Linked' : 'Optional' ?></span>
                </div>

                <?php if ($latestPrescription): ?>
                  <div class="presc-file-box" id="prescFileBox">
                    <i class="fa-regular fa-file-lines presc-icon"></i>
                    <div class="presc-info">
                      <strong><?= htmlspecialchars($latestPrescription['title']) ?></strong>
                      <p>Prescribed on <?= date('d-m-Y', strtotime($latestPrescription['created_at'])) ?></p>
                    </div>
                    <button type="button" class="remove-btn" id="removePrescBtn"><i class="fa-solid fa-xmark"></i></button>
                  </div>
                  <input type="hidden" name="prescription_id" id="prescription_id" value="<?= $latestPrescription['prescription_id'] ?>">
                <?php else: ?>
                  <p style="font-size:13px; color:#64748b; margin-bottom:10px;">No linked digital prescription found.</p>
                <?php endif; ?>

                <input type="file" name="prescription_file" id="prescriptionFileInput" accept=".pdf,.jpg,.png,.doc,.docx" style="display: none;">
                <button type="button" class="btn-upload-more" id="uploadPrescBtn">
                  <i class="fa-regular fa-file-arrow-up"></i> Upload prescription document
                </button>
                <small id="uploadedFileName" style="display:block; color:#2563eb; margin-top:5px; font-weight:600;"></small>
              </div>

              <!-- Request Summary Card -->
              <div class="card summary-card">
                <h2>Request Summary</h2>

                <div class="summary-box">
                  <div class="summary-row">
                    <span id="selectedCountLabel">Selected Tests (0 tests)</span>
                    <span class="val" id="testsTotalVal">৳0.00</span>
                  </div>
                  <div class="summary-row">
                    <span>Collection Fee</span>
                    <span class="val" id="collectionFeeVal">৳200.00</span>
                  </div>
                  <div class="summary-row subsidy">
                    <span>NHMRD Portal Digital Surcharge</span>
                    <span class="val">Waived (Govt. Subsidy)</span>
                  </div>

                  <div class="summary-total-row">
                    <span>Total Payable</span>
                    <span class="total-amount" id="grandTotalVal">৳200.00</span>
                  </div>
                </div>

                <button type="submit" class="btn-submit-request" id="submitBtn">
                  <i class="fa-solid fa-puzzle-piece"></i> Submit Test Request
                </button>

                <p class="confirmation-note">
                  Confirmed request ID & collection timing will be sent via SMS.
                </p>
              </div>

            </div>

          </div>
        </form>

      </main>
    </div>
  </div>

  <script src="/public_html/assets/js/patient/med-test-req.js"></script>
</body>
</html>