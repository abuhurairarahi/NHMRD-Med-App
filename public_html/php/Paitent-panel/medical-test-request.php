<?php
// Load Patient Bootstrap
require_once __DIR__ . '/patient_bootstrap.php';

// Fetch diagnostic test catalog from DB
$catalogTests = $pdo->query("SELECT * FROM lab_test_catalog ORDER BY category, test_name ASC")->fetchAll();

// Fetch hospitals/centers
$centers = $pdo->query("SELECT hospital_id, legal_name, facility_type FROM hospitals ORDER BY legal_name ASC")->fetchAll();

// Fetch advised test names from patient's prescriptions
$stmtAdvised = $pdo->prepare("
    SELECT DISTINCT plt.test_name 
    FROM prescription_lab_tests plt
    JOIN prescriptions p ON plt.prescription_id = p.prescription_id
    WHERE p.patient_id = ?
");
$stmtAdvised->execute([$patientId]);
$advisedNames = $stmtAdvised->fetchAll(PDO::FETCH_COLUMN);

// Check if any catalog tests match advised names
$advisedCatalogIds = [];
foreach ($catalogTests as $ct) {
    foreach ($advisedNames as $an) {
        if (stripos($ct['test_name'], trim($an)) !== false || stripos($an, trim($ct['test_name'])) !== false) {
            $advisedCatalogIds[] = $ct['test_id'];
        }
    }
}

// Handle Form Submission
$successMsg = '';
$errorMsg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_test_request'])) {
    $hospitalId = !empty($_POST['hospital_id']) ? (int)$_POST['hospital_id'] : 1;
    $selectedTestIds = isset($_POST['test_ids']) && is_array($_POST['test_ids']) ? array_map('intval', $_POST['test_ids']) : [];
    $collectionFee = 200.00;

    if (!empty($selectedTestIds)) {
        $inClause = implode(',', array_fill(0, count($selectedTestIds), '?'));
        $stmtPrices = $pdo->prepare("SELECT test_id, test_name, price FROM lab_test_catalog WHERE test_id IN ($inClause)");
        $stmtPrices->execute($selectedTestIds);
        $testsToOrder = $stmtPrices->fetchAll();

        $testsTotal = 0;
        foreach ($testsToOrder as $t) {
            $testsTotal += (float)$t['price'];
        }
        $totalAmount = $testsTotal + $collectionFee;

        try {
            $pdo->beginTransaction();
            $stmtOrder = $pdo->prepare("
                INSERT INTO lab_test_orders (patient_id, hospital_id, collection_fee, total_amount, status, ordered_at)
                VALUES (?, ?, ?, ?, 'requested', NOW())
            ");
            $stmtOrder->execute([$patientId, $hospitalId, $collectionFee, $totalAmount]);
            $orderId = $pdo->lastInsertId();

            $stmtItem = $pdo->prepare("
                INSERT INTO lab_test_order_items (order_id, test_id, price, is_doctor_advised)
                VALUES (?, ?, ?, ?)
            ");
            foreach ($testsToOrder as $t) {
                $isAdv = in_array($t['test_id'], $advisedCatalogIds) ? 1 : 0;
                $stmtItem->execute([$orderId, $t['test_id'], $t['price'], $isAdv]);
            }
            $pdo->commit();
            $successMsg = "Medical test order #ORD-" . str_pad($orderId, 5, '0', STR_PAD_LEFT) . " submitted successfully! Our diagnostic phlebotomist will contact you shortly.";
        } catch (Exception $e) {
            $pdo->rollBack();
            $errorMsg = "Failed to place order: " . $e->getMessage();
        }
    } else {
        $errorMsg = "Please select at least one diagnostic test before submitting.";
    }
}
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
        <a href="vaccine-panel.php" class="nav-item">
          <i class="fa-solid fa-syringe"></i> 
          <span>Vaccine Records</span>
        </a>
        <a href="req-appointment.php" class="nav-item">
          <i class="fa-solid fa-calendar-plus"></i> 
          <span>Request Appointment</span>
        </a>
        <a href="medical-test-request.php" class="nav-item active">
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
      <header class="patient-header">
        <div class="search-bar">
          <i class="fa-solid fa-magnifying-glass"></i>
          <input type="text" placeholder="Search Queries">
        </div>

        <div class="header-right">
          <button class="icon-btn" onclick="alert('No new notifications');"><i class="fa-regular fa-bell"></i></button>
          <div class="user-badge-avatar"><?= htmlspecialchars($userInitials) ?></div>
          <span class="user-name"><?= htmlspecialchars($patient['full_name']) ?></span>
        </div>
      </header>

      <!-- Main Content Area -->
      <main class="content-body">

        <!-- Page Sub-Header / Breadcrumb -->
        <div class="page-top-bar">
          <div class="left-head">
            <div class="breadcrumb">
              <span>NHMRD</span> &rsaquo; <span>Diagnostic Services</span> &rsaquo; <span class="active">Test Request</span>
            </div>
            <h1>Request Diagnostic & Pathology Tests</h1>
            <p class="subtitle">Book certified home sample collection or direct hospital laboratory walk-in testing.</p>
          </div>
        </div>

        <?php if (!empty($successMsg)): ?>
          <div style="background: #dcfce7; border: 1px solid #86efac; color: #166534; padding: 14px 18px; border-radius: 8px; margin-bottom: 20px; font-weight: 600;">
            <i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($successMsg) ?>
          </div>
        <?php endif; ?>

        <?php if (!empty($errorMsg)): ?>
          <div style="background: #fee2e2; border: 1px solid #fca5a5; color: #991b1b; padding: 14px 18px; border-radius: 8px; margin-bottom: 20px; font-weight: 600;">
            <i class="fa-solid fa-triangle-exclamation"></i> <?= htmlspecialchars($errorMsg) ?>
          </div>
        <?php endif; ?>

        <form id="medicalTestForm" method="POST" action="">
          <input type="hidden" name="submit_test_request" value="1">

          <!-- 2-Column Grid Layout -->
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
                  <label for="hospital_id">SAMPLE COLLECTION CENTER / BRANCH</label>
                  <div class="select-wrapper">
                    <select name="hospital_id" id="hospital_id">
                      <option value="1">Home Sample Collection (Doorstep Phlebotomy - DMCH Lab Unit)</option>
                      <?php foreach ($centers as $center): ?>
                        <option value="<?= $center['hospital_id'] ?>">
                          <?= htmlspecialchars($center['legal_name']) ?> (<?= htmlspecialchars(ucfirst($center['facility_type'])) ?>)
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
                      <strong>Doorstep Phlebotomist Visit</strong>
                      <p><?= htmlspecialchars($patient['address'] ?? 'Dhanmondi, Dhaka') ?> (Patient Profile Address)</p>
                    </div>
                  </div>
                  <div class="fee-tag">Fee: &#2547;200</div>
                </div>
              </div>

              <!-- Step 2: Select Diagnostic Tests -->
              <div class="card step-card">
                <div class="step-card-header">
                  <div class="step-title">
                    <span class="step-number">2</span>
                    <h2>Select Diagnostic Tests</h2>
                  </div>
                  <a href="#" class="link-btn" onclick="selectAdvisedTests(event)">Select Advised</a>
                </div>

                <!-- Test Search Bar -->
                <div class="test-search-box">
                  <i class="fa-solid fa-magnifying-glass"></i>
                  <input type="text" placeholder="Search tests by name, keyword or code..." oninput="filterTests(event)">
                </div>

                <!-- Test List Selection -->
                <div class="test-list">
                  <?php if (empty($catalogTests)): ?>
                    <p style="padding: 15px; color: #64748b;">No diagnostic tests currently loaded in the catalog.</p>
                  <?php else: ?>
                    <?php foreach ($catalogTests as $idx => $t): ?>
                      <?php 
                        $isAdvised = in_array($t['test_id'], $advisedCatalogIds);
                        $defaultChecked = ($idx === 0 || $isAdvised);
                      ?>
                      <div class="test-item <?= $defaultChecked ? 'selected' : '' ?>">
                        <div class="test-checkbox">
                          <input type="checkbox" name="test_ids[]" id="test_<?= $t['test_id'] ?>" value="<?= $t['test_id'] ?>" <?= $defaultChecked ? 'checked' : '' ?> onchange="updateSummaryTotal()">
                        </div>
                        <div class="test-details">
                          <div class="test-header-line">
                            <label for="test_<?= $t['test_id'] ?>" class="test-title-text"><?= htmlspecialchars($t['test_name']) ?></label>
                            <?php if ($isAdvised): ?>
                              <span class="pill-badge doctor-advised">Doctor Advised</span>
                            <?php elseif ($t['category'] === 'hematology' || $t['category'] === 'biochemistry'): ?>
                              <span class="pill-badge popular"><?= ucfirst($t['category']) ?></span>
                            <?php endif; ?>
                            <span class="test-price">&#2547;<?= number_format($t['price'], 0) ?></span>
                          </div>
                          <p class="test-subdesc">Standard diagnostic evaluation code: <?= htmlspecialchars($t['test_code'] ?? 'HAEM-01') ?> &bull; Accredited quality controls.</p>
                          <div class="test-meta">
                            <span><i class="fa-regular fa-clock"></i> Routine Fasting</span>
                            <span><i class="fa-solid fa-vial"></i> <?= htmlspecialchars(ucfirst($t['category'])) ?> Sample</span>
                            <span class="report-time">Same-Day STAT</span>
                          </div>
                        </div>
                      </div>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </div>

                <!-- Test Notice Bar -->
                <div class="info-banner-gray">
                  <i class="fa-regular fa-circle-question"></i>
                  <span>Some radiology/imaging procedures require visiting the specialized facility.</span>
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
                    <label>PREFERRED DATE</label>
                    <div class="input-with-icon">
                      <input type="date" name="preferred_date" value="<?= date('Y-m-d', strtotime('+1 day')) ?>" min="<?= date('Y-m-d') ?>">
                    </div>
                  </div>
                  <div class="form-group flex-1">
                    <label>PREFERRED TIME SLOT</label>
                    <div class="input-with-icon">
                      <select name="time_slot" style="padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; width: 100%;">
                        <option value="08:00 AM - 09:30 AM" selected>08:00 AM - 09:30 AM (Fasting)</option>
                        <option value="09:30 AM - 11:00 AM">09:30 AM - 11:00 AM</option>
                        <option value="11:00 AM - 01:00 PM">11:00 AM - 01:00 PM</option>
                        <option value="04:00 PM - 06:00 PM">04:00 PM - 06:00 PM</option>
                      </select>
                    </div>
                  </div>
                </div>

                <div class="info-banner-gray fasting-note">
                  <i class="fa-regular fa-circle-question"></i>
                  <span>10–12 hours overnight water-only fasting is recommended for accurate metabolic and lipid evaluations.</span>
                </div>
              </div>

            </div>

            <!-- Right Column: Summary & Prescription -->
            <div class="request-right-col">
              
              <!-- Doctor's Prescription Card -->
              <div class="card prescription-card">
                <div class="presc-header">
                  <h2>Doctor's Prescription</h2>
                  <span class="linked-badge">Linked</span>
                </div>

                <div class="presc-file-box">
                  <i class="fa-regular fa-file-lines presc-icon"></i>
                  <div class="presc-info">
                    <strong>NHMRD_Official_ePrescription.pdf</strong>
                    <p>Linked to active clinical record &bull; Verified</p>
                  </div>
                  <button type="button" class="remove-btn" onclick="removePrescriptionFile(event)"><i class="fa-solid fa-xmark"></i></button>
                </div>

                <button type="button" class="btn-upload-more" onclick="uploadPrescriptionFile()">
                  <i class="fa-regular fa-file-arrow-up"></i> Upload additional prescription file
                </button>
              </div>

              <!-- Request Summary Card -->
              <div class="card summary-card">
                <h2>Request Summary</h2>

                <div class="summary-box">
                  <div class="summary-row">
                    <span>Selected Tests</span>
                    <span class="val">&#2547;0.00</span>
                  </div>
                  <div class="summary-row">
                    <span>Home Sample Collection Fee</span>
                    <span class="val">&#2547;200.00</span>
                  </div>
                  <div class="summary-row subsidy">
                    <span>NHMRD Portal Digital Surcharge</span>
                    <span class="val">Waived (Govt. Subsidy)</span>
                  </div>

                  <div class="summary-total-row">
                    <span>Total Payable</span>
                    <span class="total-amount">&#2547;200.00</span>
                  </div>
                </div>

                <button type="submit" class="btn-submit-request" id="submitBtn">
                  <i class="fa-solid fa-puzzle-piece"></i> Submit Test Request
                </button>

                <p class="confirmation-note">
                  Confirmed request ID & collection timing will be sent via SMS to <?= htmlspecialchars($patient['phone'] ?? 'your phone') ?>.
                </p>
              </div>

            </div>

          </div>
        </form>

      </main>
    </div>
  </div>

  <script src="/public_html/assets/js/paitent/med-test-req.js"></script>
  <script>
    // Ensure form submits smoothly when button clicked
    document.addEventListener('DOMContentLoaded', () => {
      updateSummaryTotal();
    });
  </script>
</body>
</html>
