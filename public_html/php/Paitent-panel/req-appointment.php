<?php
// Load Patient Bootstrap
require_once __DIR__ . '/patient_bootstrap.php';

// Fetch Specialties & Hospitals
$specialties = $pdo->query("SELECT * FROM specialties ORDER BY name ASC")->fetchAll();
$hospitals   = $pdo->query("SELECT hospital_id, legal_name FROM hospitals ORDER BY legal_name ASC")->fetchAll();

// Fetch Active Doctors
$doctors = $pdo->query("
    SELECT d.*, s.name AS specialty_name, h.legal_name AS hospital_name
    FROM doctors d
    LEFT JOIN specialties s ON d.primary_specialty_id = s.specialty_id
    LEFT JOIN hospitals h ON d.hospital_id = h.hospital_id
    WHERE d.status = 'active'
    ORDER BY d.full_name ASC
")->fetchAll();

$successMsg = '';
$errorMsg   = '';

// Handle Appointment Cancellation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'cancel_appointment') {
    $cancelId = (int)($_POST['appointment_id'] ?? 0);
    if ($cancelId > 0) {
        $stmtCancel = $pdo->prepare("UPDATE appointments SET status = 'cancelled' WHERE appointment_id = ? AND patient_id = ?");
        $stmtCancel->execute([$cancelId, $patientId]);
        $successMsg = "Appointment #APP-" . str_pad($cancelId, 4, '0', STR_PAD_LEFT) . " has been successfully cancelled.";
    }
}

// Handle Appointment Booking
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'book_appointment') {
    $doctorId = (int)($_POST['doctor_id'] ?? 0);
    $hospitalId = !empty($_POST['hospital_id']) ? (int)$_POST['hospital_id'] : 1;
    $appointmentDate = !empty($_POST['appointment_date']) ? $_POST['appointment_date'] : date('Y-m-d', strtotime('+1 day'));
    $timeSlot = !empty($_POST['time_slot']) ? trim($_POST['time_slot']) : '10:30 AM';
    $reason = trim($_POST['reason'] ?? 'Clinical consultation & follow-up');

    if ($doctorId > 0) {
        try {
            $stmtBook = $pdo->prepare("
                INSERT INTO appointments (patient_id, doctor_id, hospital_id, appointment_date, time_slot, reason, status)
                VALUES (?, ?, ?, ?, ?, ?, 'booked')
            ");
            $stmtBook->execute([$patientId, $doctorId, $hospitalId, $appointmentDate, $timeSlot, $reason]);
            $newApptId = $pdo->lastInsertId();
            $successMsg = "Appointment #APP-" . str_pad($newApptId, 4, '0', STR_PAD_LEFT) . " confirmed successfully for " . date('d M Y', strtotime($appointmentDate)) . " at " . htmlspecialchars($timeSlot) . "!";
        } catch (Exception $e) {
            $errorMsg = "Unable to book slot. It may already be reserved: " . $e->getMessage();
        }
    } else {
        $errorMsg = "Please choose a doctor before submitting your appointment request.";
    }
}

// Fetch Patient Appointments
$stmtAppts = $pdo->prepare("
    SELECT a.*, d.full_name AS doctor_name, d.designation AS doctor_designation, h.legal_name AS hospital_name, s.name AS specialty_name
    FROM appointments a
    LEFT JOIN doctors d ON a.doctor_id = d.doctor_id
    LEFT JOIN hospitals h ON a.hospital_id = h.hospital_id
    LEFT JOIN specialties s ON d.primary_specialty_id = s.specialty_id
    WHERE a.patient_id = ?
    ORDER BY a.appointment_date DESC, a.appointment_id DESC
");
$stmtAppts->execute([$patientId]);
$appointments = $stmtAppts->fetchAll();

$firstDoctor = !empty($doctors) ? $doctors[0] : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Book a Doctor Consultation - NHMRD</title>
  <link rel="stylesheet" href="/public_html/assets/css/patient-panel/request-appointment.css">
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
        <a href="req-appointment.php" class="nav-item active">
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

    <!-- Main Content Wrapper -->
    <div class="main-wrapper">
      <header class="patient-header">
        <div class="search-bar">
          <i class="fa-solid fa-magnifying-glass"></i>
          <input type="text" placeholder="Search Queries" oninput="handleGlobalSearch(event)">
        </div>

        <div class="header-right">
          <button class="icon-btn" onclick="alert('No new notifications');"><i class="fa-regular fa-bell"></i></button>
          <div class="user-badge-avatar"><?= htmlspecialchars($userInitials) ?></div>
          <span class="user-name"><?= htmlspecialchars($patient['full_name']) ?></span>
        </div>
      </header>

      <!-- Main Scrollable Content Body -->
      <main class="content-body">

        <!-- Page Sub-Header / Breadcrumb Area -->
        <div class="page-top-bar">
          <div class="left-head">
            <div class="breadcrumb">
              <span>NHMRD Portal</span> &rsaquo; <span>Appointments</span> &rsaquo; <span class="active">Book Consultation</span>
            </div>
            <h1>Book a Doctor Consultation</h1>
            <p class="subtitle">Schedule an in-person hospital visit or accredited clinical telehealth session.</p>
          </div>
          <div class="patient-pill">
            <i class="fa-solid fa-circle text-primary-blue"></i>
            <span>Patient: <strong><?= htmlspecialchars($patient['full_name']) ?> (#<?= htmlspecialchars($patient['user_uid'] ?? $patient['patient_id']) ?>)</strong></span>
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

        <!-- 2-Column Booking Workspace -->
        <form id="appointmentForm" method="POST" action="">
          <input type="hidden" name="action" value="book_appointment">
          <input type="hidden" name="doctor_id" id="selectedDoctorId" value="<?= $firstDoctor ? $firstDoctor['doctor_id'] : '1' ?>">
          <input type="hidden" name="time_slot" id="selectedTimeSlot" value="10:30 AM">

          <div class="booking-grid">

            <!-- Left Column: Selection Flow -->
            <div class="booking-left-col">
              
              <!-- Step 1: Facility & Specialty Card -->
              <div class="card step-card">
                <div class="step-card-header">
                  <div class="step-title">
                    <span class="step-number">1</span>
                    <h2>Facility & Specialty</h2>
                  </div>
                  <span class="step-indicator">STEP 1 OF 3</span>
                </div>

                <div class="form-group">
                  <label for="deptSelect">SPECIALTY DEPARTMENT</label>
                  <div class="select-wrapper">
                    <select id="deptSelect" name="specialty_id" onchange="filterDoctorsBySpecialty(this.value)">
                      <option value="all">All Clinical Specialties</option>
                      <?php foreach ($specialties as $sp): ?>
                        <option value="<?= $sp['specialty_id'] ?>"><?= htmlspecialchars($sp['name']) ?></option>
                      <?php endforeach; ?>
                    </select>
                    <i class="fa-solid fa-chevron-down select-icon"></i>
                  </div>
                </div>

                <div class="form-group">
                  <label for="hospitalSelect">ACCREDITED HOSPITAL</label>
                  <div class="select-wrapper">
                    <select id="hospitalSelect" name="hospital_id">
                      <?php foreach ($hospitals as $hosp): ?>
                        <option value="<?= $hosp['hospital_id'] ?>"><?= htmlspecialchars($hosp['legal_name']) ?></option>
                      <?php endforeach; ?>
                    </select>
                    <i class="fa-solid fa-hospital hospital-icon"></i>
                  </div>
                </div>

                <div class="form-group">
                  <label>REASON FOR VISIT / CLINICAL NOTES</label>
                  <input type="text" name="reason" placeholder="e.g. Blood pressure follow-up, abdominal discomfort, general review" value="Clinical consultation & follow-up" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px;">
                </div>
              </div>

              <!-- Step 2: Select Doctor Card -->
              <div class="card step-card">
                <div class="step-card-header">
                  <div class="step-title">
                    <span class="step-number">2</span>
                    <h2>Select Doctor</h2>
                  </div>
                  <span class="text-muted" style="font-size: 0.85rem; font-weight: 600;"><?= count($doctors) ?> Available</span>
                </div>

                <div class="slot-filter-banner">
                  <div>
                    <strong>Available Doctors</strong>
                    <p>Choose your attending consultant for this booking.</p>
                  </div>
                  <span class="selected-doc-badge">Selected: <strong id="selectedDocName"><?= $firstDoctor ? 'Dr. ' . htmlspecialchars($firstDoctor['full_name']) : 'None' ?></strong></span>
                </div>

                <!-- Doctor List -->
                <div class="doctors-list" id="doctorsListContainer">
                  <?php if (empty($doctors)): ?>
                    <p style="padding: 20px; color: #64748b;">No doctors currently found in the registry.</p>
                  <?php else: ?>
                    <?php foreach ($doctors as $i => $doc): ?>
                      <?php $isSelected = ($i === 0); ?>
                      <div class="doctor-card <?= $isSelected ? 'selected' : '' ?>" data-doc-id="<?= $doc['doctor_id'] ?>" data-specialty-id="<?= $doc['primary_specialty_id'] ?>">
                        <div class="doc-main-info">
                          <img src="<?= !empty($doc['photo_url']) ? htmlspecialchars($doc['photo_url']) : 'https://i.pravatar.cc/100?img=' . (30 + $doc['doctor_id']) ?>" alt="<?= htmlspecialchars($doc['full_name']) ?>" class="doc-avatar-img">
                          <div class="doc-details">
                            <h3>Dr. <?= htmlspecialchars($doc['full_name']) ?></h3>
                            <p class="degrees"><?= htmlspecialchars($doc['qualifications'] ?? 'MBBS, Certified Specialist') ?></p>
                            <p class="designation"><?= htmlspecialchars($doc['designation'] ?? 'Consultant') ?> &bull; <?= htmlspecialchars($doc['specialty_name'] ?? 'General') ?></p>
                            <div class="location-rating">
                              <span><?= htmlspecialchars($doc['hospital_name'] ?? 'National Hospital') ?></span>
                              <span class="rating"><i class="fa-solid fa-star"></i> 4.9 (BMDC: <?= htmlspecialchars($doc['bmdc_registration_no'] ?? 'Verified') ?>)</span>
                            </div>
                          </div>
                        </div>
                        <div class="doc-actions">
                          <?php if ($isSelected): ?>
                            <span class="badge-selected-status"><i class="fa-solid fa-circle-check"></i> Selected</span>
                          <?php else: ?>
                            <button type="button" class="btn-select" onclick="chooseDoctor(<?= $doc['doctor_id'] ?>, 'Dr. <?= addslashes($doc['full_name']) ?>', this)">Select</button>
                          <?php endif; ?>
                          <span class="slot-badge"><?= htmlspecialchars($doc['shift_schedule'] ?? 'Available Today') ?></span>
                        </div>
                      </div>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </div>
              </div>

            </div>

            <!-- Right Column: Date, Time & Summary -->
            <div class="booking-right-col">
              
              <!-- Calendar & Slot Selector Card -->
              <div class="card calendar-card">
                <div class="calendar-header-top">
                  <div class="step-title">
                    <i class="fa-regular fa-calendar-days icon-step"></i>
                    <h2>Select Date & Time</h2>
                  </div>
                  <span class="timezone-label">Bangladesh Standard Time (UTC+6)</span>
                </div>

                <div style="margin: 15px 0;">
                  <label style="font-size: 0.8rem; font-weight: 700; color: #475569; display: block; margin-bottom: 6px;">APPOINTMENT DATE</label>
                  <input type="date" name="appointment_date" id="appointmentDateInput" value="<?= date('Y-m-d', strtotime('+1 day')) ?>" min="<?= date('Y-m-d') ?>" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-weight: 600;" onchange="updateSelectedDateDisplay(this.value)">
                </div>

                <!-- Available Time Slots Section -->
                <div class="slots-container">
                  <div class="slots-header">
                    <span>AVAILABLE TIME SLOTS</span>
                    <span class="slots-left text-blue">Daily Capacity Active</span>
                  </div>
                  <div class="time-slots">
                    <button type="button" class="slot-pill" onclick="pickSlot('09:30 AM', this)">09:30 AM</button>
                    <button type="button" class="slot-pill active" onclick="pickSlot('10:30 AM', this)">10:30 AM</button>
                    <button type="button" class="slot-pill" onclick="pickSlot('11:30 AM', this)">11:30 AM</button>
                    <button type="button" class="slot-pill" onclick="pickSlot('04:30 PM', this)">04:30 PM</button>
                    <button type="button" class="slot-pill" onclick="pickSlot('06:00 PM', this)">06:00 PM</button>
                  </div>
                </div>

                <!-- Fee Breakdown -->
                <div class="fee-breakdown">
                  <div class="fee-row">
                    <span>Consultation Standard Fee</span>
                    <span>&#2547; 1,000</span>
                  </div>
                  <div class="fee-row subsidy">
                    <span>NHMRD Universal Health Subsidy</span>
                    <span>- &#2547; 200</span>
                  </div>
                  <div class="fee-row total">
                    <span>Total Payable at Hospital OPD</span>
                    <span class="amount">&#2547; 800</span>
                  </div>
                </div>

                <!-- Confirm Button -->
                <button type="submit" class="btn-confirm-appointment">
                  <i class="fa-regular fa-calendar-check"></i> Confirm & Book Appointment
                </button>
                <p class="deposit-note">No advance deposit required. Direct OPD registration via Smart Health ID.</p>

              </div>

              <!-- Upcoming & Pending Requests Section -->
              <div class="card upcoming-card">
                <div class="upcoming-header">
                  <div>
                    <h2>Your Appointments History</h2>
                    <p>Track upcoming bookings and completed clinical reviews</p>
                  </div>
                  <span class="active-booking-count"><?= count($appointments) ?> Total</span>
                </div>

                <div class="upcoming-list">
                  <?php if (empty($appointments)): ?>
                    <p style="padding: 20px; color: #64748b; text-align: center;">No appointment records found.</p>
                  <?php else: ?>
                    <?php foreach ($appointments as $apt): ?>
                      <?php 
                        $aptDate = strtotime($apt['appointment_date']);
                        $month = date('M', $aptDate);
                        $day = date('d', $aptDate);
                        $isUpcoming = (strtolower($apt['status']) === 'booked' || strtolower($apt['status']) === 'rescheduled');
                        $badgeClass = $isUpcoming ? 'due' : (strtolower($apt['status']) === 'completed' ? 'visited' : 'cancelled');
                      ?>
                      <div class="upcoming-item">
                        <div class="date-badge <?= $isUpcoming ? '' : 'grey' ?>">
                          <span class="month"><?= strtoupper($month) ?></span>
                          <span class="day"><?= $day ?></span>
                        </div>
                        <div class="upcoming-details">
                          <div class="upcoming-top-row">
                            <span class="app-time">Date: <strong><?= date('d-m-Y', $aptDate) ?></strong> (<?= htmlspecialchars($apt['time_slot'] ?? '10:00 AM') ?>)</span>
                            <span class="badge-status <?= $badgeClass ?>"><?= ucfirst($apt['status']) ?></span>
                          </div>
                          <h3>Dr. <?= htmlspecialchars($apt['doctor_name'] ?? 'Consultant Physician') ?></h3>
                          <p class="hospital-room">Hospital: <?= htmlspecialchars($apt['hospital_name'] ?? 'National Hospital') ?> &bull; <?= htmlspecialchars($apt['reason'] ?? 'Consultation') ?></p>
                          <?php if ($isUpcoming): ?>
                            <div class="upcoming-actions">
                              <form method="POST" action="" onsubmit="return confirm('Cancel this appointment?');" style="display:inline;">
                                <input type="hidden" name="action" value="cancel_appointment">
                                <input type="hidden" name="appointment_id" value="<?= $apt['appointment_id'] ?>">
                                <button type="submit" class="btn-action-danger"><i class="fa-regular fa-circle-xmark"></i> Cancel</button>
                              </form>
                            </div>
                          <?php endif; ?>
                        </div>
                      </div>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </div>
              </div>

            </div>

          </div>
        </form>

      </main>
    </div>
  </div>

  <script src="/public_html/assets/js/paitent/req_appointment.js"></script>
  <script>
    function chooseDoctor(docId, docName, btnElem) {
      document.getElementById('selectedDoctorId').value = docId;
      document.getElementById('selectedDocName').textContent = docName;
      selectDoctor(btnElem, docName);
    }

    function pickSlot(slot, btnElem) {
      document.getElementById('selectedTimeSlot').value = slot;
      selectSlot(btnElem);
    }

    function filterDoctorsBySpecialty(specId) {
      const cards = document.querySelectorAll('.doctor-card');
      cards.forEach(card => {
        if (specId === 'all' || card.getAttribute('data-specialty-id') === specId) {
          card.style.display = 'flex';
        } else {
          card.style.display = 'none';
        }
      });
    }

    function updateSelectedDateDisplay(val) {
      // triggers when date input changes
    }
  </script>
</body>
</html>
