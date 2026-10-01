<?php
// Load Data Controller
$data = require_once __DIR__ . '/../../controllers/patient/AppointmentController.php';

$patient          = $data['patient'];
$userInitials     = $data['userInitials'];
$doctors          = $data['doctors'];
$hospitals        = $data['hospitals'];
$specialties      = $data['specialties'];
$upcomingBookings = $data['upcomingBookings'];
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
  <!-- FontAwesome Icons -->
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
        <a href="/public_html/pages/Patient-panel/req-appointment.php" class="nav-item active">
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
        <button class="logout-btn" onclick="handleLogout()">
          <i class="fa-solid fa-arrow-right-from-bracket"></i> 
          <span>Logout</span>
        </button>
      </div>
    </aside>

    <!-- Main Content Wrapper -->
    <div class="main-wrapper">
      <!-- Top Navigation Header -->
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
            <span>Patient: <strong><?= htmlspecialchars($patient['full_name']) ?> (#<?= htmlspecialchars($patient['patient_id']) ?>)</strong></span>
          </div>
        </div>

        <!-- 2-Column Booking Workspace -->
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
                <label>SPECIALTY DEPARTMENT</label>
                <div class="select-wrapper">
                  <select id="specialtySelect" onchange="filterDoctors()">
                    <option value="">All Specialties</option>
                    <?php foreach ($specialties as $spec): ?>
                      <option value="<?= htmlspecialchars($spec['name']) ?>"><?= htmlspecialchars($spec['name']) ?></option>
                    <?php endforeach; ?>
                  </select>
                  <i class="fa-solid fa-chevron-down select-icon"></i>
                </div>
              </div>

              <div class="form-group">
                <label>ACCREDITED HOSPITAL</label>
                <div class="select-wrapper">
                  <select id="hospitalSelect" onchange="filterDoctors()">
                    <option value="">All Hospitals</option>
                    <?php foreach ($hospitals as $hosp): ?>
                      <option value="<?= htmlspecialchars($hosp['hospital_id']) ?>"><?= htmlspecialchars($hosp['legal_name']) ?></option>
                    <?php endforeach; ?>
                  </select>
                  <i class="fa-solid fa-hospital hospital-icon"></i>
                </div>
              </div>
            </div>

            <!-- Step 2: Select Doctor Card -->
            <div class="card step-card">
              <div class="step-card-header">
                <div class="step-title">
                  <span class="step-number">2</span>
                  <h2>Select Doctor</h2>
                </div>
                <a href="#" class="link-btn">View All Doctors &rarr;</a>
              </div>

              <div class="slot-filter-banner">
                <div>
                  <strong>Available in this slot</strong>
                  <p>Choose a doctor for your appointment.</p>
                </div>
                <span class="selected-doc-badge">Selected: <strong id="selectedDoctorLabel">None</strong></span>
              </div>

              <!-- Dynamic Doctor List -->
              <div class="doctors-list" id="doctorsList">
                <?php if (empty($doctors)): ?>
                  <p class="p-3 text-muted">No doctors currently available.</p>
                <?php else: ?>
                  <?php foreach ($doctors as $index => $doc): ?>
                    <div class="doctor-card <?= $index === 0 ? 'selected' : '' ?>" 
                         data-doctor-id="<?= $doc['doctor_id'] ?>" 
                         data-hospital-id="<?= $doc['hospital_id'] ?>" 
                         data-specialty="<?= htmlspecialchars($doc['specialty_name'] ?? '') ?>">
                      <div class="doc-main-info">
                        <?php if (!empty($doc['photo_url'])): ?>
                          <img src="<?= htmlspecialchars($doc['photo_url']) ?>" alt="<?= htmlspecialchars($doc['full_name']) ?>" class="doc-avatar-img">
                        <?php else: ?>
                          <div class="doc-avatar-placeholder"></div>
                        <?php endif; ?>
                        <div class="doc-details">
                          <h3><?= htmlspecialchars($doc['full_name']) ?></h3>
                          <p class="degrees"><?= htmlspecialchars($doc['qualifications'] ?? 'Medical Specialist') ?></p>
                          <p class="designation"><?= htmlspecialchars($doc['designation'] ?? 'Consultant') ?></p>
                          <div class="location-rating">
                            <span><?= htmlspecialchars($doc['hospital_name'] ?? 'NHMRD Facility') ?></span>
                            <span class="rating"><i class="fa-solid fa-star"></i> 4.9 (120+ reviews)</span>
                          </div>
                        </div>
                      </div>
                      <div class="doc-actions">
                        <?php if ($index === 0): ?>
                          <span class="badge-selected-status"><i class="fa-solid fa-circle-check"></i> Selected</span>
                        <?php else: ?>
                          <button class="btn-select" onclick="selectDoctor(this, '<?= htmlspecialchars($doc['full_name']) ?>', <?= $doc['doctor_id'] ?>, <?= $doc['hospital_id'] ?? 'null' ?>)">Select</button>
                        <?php endif; ?>
                        <span class="slot-badge">Next Slot: Today</span>
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
                <span class="timezone-label">Timezone: UTC+6</span>
              </div>

              <!-- Month Navigation Header -->
              <div class="calendar-month-nav">
                <h3>September 2026</h3>
                <div class="month-arrows">
                  <button><i class="fa-solid fa-chevron-left"></i></button>
                  <button><i class="fa-solid fa-chevron-right"></i></button>
                </div>
              </div>

              <!-- Calendar Grid -->
              <div class="calendar-grid">
                <div class="day-head">Su</div><div class="day-head">Mo</div><div class="day-head">Tu</div>
                <div class="day-head">We</div><div class="day-head">Th</div><div class="day-head">Fr</div><div class="day-head">Sa</div>

                <div class="date-cell disabled">30</div>
                <div class="date-cell disabled">31</div>
                <div class="date-cell" data-date="2026-09-01" onclick="selectDate(this)">1</div>
                <div class="date-cell" data-date="2026-09-02" onclick="selectDate(this)">2</div>
                <div class="date-cell" data-date="2026-09-03" onclick="selectDate(this)">3</div>
                <div class="date-cell" data-date="2026-09-04" onclick="selectDate(this)">4</div>
                <div class="date-cell disabled">5</div>

                <div class="date-cell disabled">6</div>
                <div class="date-cell" data-date="2026-09-07" onclick="selectDate(this)">7</div>
                <div class="date-cell" data-date="2026-09-08" onclick="selectDate(this)">8</div>
                <div class="date-cell" data-date="2026-09-09" onclick="selectDate(this)">9</div>
                <div class="date-cell" data-date="2026-09-10" onclick="selectDate(this)">10</div>
                <div class="date-cell" data-date="2026-09-11" onclick="selectDate(this)">11</div>
                <div class="date-cell disabled">12</div>

                <div class="date-cell disabled">13</div>
                <div class="date-cell" data-date="2026-09-14" onclick="selectDate(this)">14</div>
                <div class="date-cell" data-date="2026-09-15" onclick="selectDate(this)">15</div>
                <div class="date-cell selected" data-date="2026-09-16" onclick="selectDate(this)">16</div>
                <div class="date-cell" data-date="2026-09-17" onclick="selectDate(this)">17</div>
                <div class="date-cell" data-date="2026-09-18" onclick="selectDate(this)">18</div>
                <div class="date-cell disabled">19</div>

                <div class="date-cell disabled">20</div>
                <div class="date-cell" data-date="2026-09-21" onclick="selectDate(this)">21</div>
                <div class="date-cell" data-date="2026-09-22" onclick="selectDate(this)">22</div>
                <div class="date-cell" data-date="2026-09-23" onclick="selectDate(this)">23</div>
                <div class="date-cell" data-date="2026-09-24" onclick="selectDate(this)">24</div>
                <div class="date-cell" data-date="2026-09-25" onclick="selectDate(this)">25</div>
                <div class="date-cell disabled">26</div>
              </div>

              <div class="calendar-legend">
                <span><i class="fa-solid fa-circle text-blue"></i> Selected</span>
                <span><i class="fa-solid fa-circle text-dot"></i> Dr. Available</span>
              </div>

              <!-- Available Time Slots Section -->
              <div class="slots-container">
                <div class="slots-header">
                  <span>AVAILABLE SLOTS</span>
                  <span class="slots-left text-blue">3 Slots Left</span>
                </div>
                <div class="time-slots">
                  <button class="slot-pill" onclick="selectSlot(this)">16:30 PM</button>
                  <button class="slot-pill" onclick="selectSlot(this)">18:00 PM</button>
                  <button class="slot-pill active" onclick="selectSlot(this)">19:00 PM</button>
                </div>
              </div>

              <!-- Fee Breakdown -->
              <div class="fee-breakdown">
                <div class="fee-row">
                  <span>Specialist Consultation Fee</span>
                  <span>&#2547; 1,500</span>
                </div>
                <div class="fee-row subsidy">
                  <span>NHMRD National Health Subsidy</span>
                  <span>- &#2547; 300</span>
                </div>
                <div class="fee-row total">
                  <span>Total Payable at Clinic</span>
                  <span class="amount">&#2547; 1,200</span>
                </div>
              </div>

              <!-- Confirm Button -->
              <button class="btn-confirm-appointment" onclick="confirmAppointment()">
                <i class="fa-regular fa-calendar-check"></i> Confirm & Book Appointment
              </button>
              <p class="deposit-note">No advance deposit required. Cancellations allowed up to 2 hours prior.</p>

            </div>

            <!-- Upcoming & Pending Requests Section -->
            <div class="card upcoming-card">
              <div class="upcoming-header">
                <div>
                  <h2>Your Upcoming & Pending Requests</h2>
                  <p>Review status or modify your active NHMRD consultations</p>
                </div>
                <span class="active-booking-count"><?= count($upcomingBookings) ?> Active Bookings</span>
              </div>

              <div class="upcoming-list">
                <?php if (empty($upcomingBookings)): ?>
                  <p style="padding: 15px; color: #64748b;">No active bookings found.</p>
                <?php else: ?>
                  <?php foreach ($upcomingBookings as $booking): ?>
                    <?php
                      $dt = strtotime($booking['appointment_date']);
                      $month = strtoupper(date('M', $dt));
                      $day = date('d', $dt);
                      $statusClass = strtolower($booking['status']) === 'completed' ? 'visited' : 'due';
                    ?>
                    <div class="upcoming-item" id="appointment-row-<?= $booking['appointment_id'] ?>">
                      <div class="date-badge <?= $statusClass === 'visited' ? 'grey' : '' ?>">
                        <span class="month"><?= $month ?></span>
                        <span class="day"><?= $day ?></span>
                      </div>
                      <div class="upcoming-details">
                        <div class="upcoming-top-row">
                          <span class="app-time">Appointment Date: <strong><?= date('d-m-Y', $dt) ?></strong> <?= htmlspecialchars($booking['time_slot'] ?? '') ?></span>
                          <span class="badge-status <?= $statusClass ?>"><?= ucfirst($booking['status']) ?></span>
                        </div>
                        <h3><?= htmlspecialchars($booking['doctor_name']) ?></h3>
                        <p class="hospital-room">Hospital: <?= htmlspecialchars($booking['hospital_name'] ?? 'NHMRD Facility') ?></p>
                        <div class="upcoming-actions">
                          <?php if ($statusClass !== 'visited'): ?>
                            <button class="btn-action-outline" onclick="rescheduleAppointment(<?= $booking['appointment_id'] ?>)"><i class="fa-solid fa-calendar-days"></i> Reschedule</button>
                            <button class="btn-action-danger" onclick="cancelAppointment(<?= $booking['appointment_id'] ?>)"><i class="fa-regular fa-circle-xmark"></i> Cancel</button>
                          <?php else: ?>
                            <a href="#" class="link-summary"><i class="fa-regular fa-file-lines"></i> View Summary</a>
                          <?php endif; ?>
                        </div>
                      </div>
                    </div>
                  <?php endforeach; ?>
                <?php endif; ?>
              </div>
            </div>

          </div>

        </div>

      </main>
    </div>
  </div>

  <script src="/public_html/assets/js/paitent/req_appointment.js"></script>
</body>
</html>