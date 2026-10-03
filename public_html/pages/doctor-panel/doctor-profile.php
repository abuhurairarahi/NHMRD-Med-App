<?php

declare(strict_types=1);

session_start();

// Database credentials for NHMRD
$dbHost = '127.0.0.1';
$dbName = 'nhmrd';
$dbUser = 'root';
$dbPass = '';

try {
  $pdo = new PDO(
    "mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4",
    $dbUser,
    $dbPass,
    [
      PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
      PDO::ATTR_EMULATE_PREPARES   => false,
    ]
  );
} catch (PDOException $e) {
  http_response_code(500);
  echo json_encode(['success' => false, 'error' => 'Database connection failure.']);
  exit;
}

// In production, extract user context from the authenticated session
$currentUserId  = (int)($_SESSION['user_id'] ?? 1);
$currentDocId   = (int)($_SESSION['doctor_id'] ?? 1);

$payload = json_decode(file_get_contents('php://input'), true) ?? [];
$action  = $payload['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  header('Content-Type: application/json; charset=utf-8');
  try {
  switch ($action) {
    // =================================================================
    // ACTION 1: Update Doctor Title and Badge (doctors table)
    // =================================================================
    case 'update_title_badge':
      $docTitle = trim($payload['doctor_title'] ?? '');
      $tagBadge = trim($payload['tag_badge'] ?? '');

      if ($docTitle === '') {
        throw new InvalidArgumentException('Doctor title cannot be empty.');
      }

      $pdo->beginTransaction();

      $stmt = $pdo->prepare("
                UPDATE doctors 
                SET full_name = :full_name,
                    designation = :designation
                WHERE doctor_id = :doctor_id
            ");
      $stmt->execute([
        ':full_name'   => $docTitle,
        ':designation' => $tagBadge,
        ':doctor_id'   => $currentDocId
      ]);

      // Audit Trail Log
      $auditStmt = $pdo->prepare("
                INSERT INTO audit_logs (user_id, action, entity_type, entity_id, details)
                VALUES (:user_id, 'UPDATE_DOCTOR_PROFILE', 'doctors', :entity_id, :details)
            ");
      $auditStmt->execute([
        ':user_id'   => $currentUserId,
        ':entity_id' => $currentDocId,
        ':details'   => json_encode(['title' => $docTitle, 'badge' => $tagBadge])
      ]);

      $pdo->commit();
      echo json_encode(['success' => true, 'message' => 'Profile updated successfully.']);
      break;

    // =================================================================
    // ACTION 2: Add Verified Education
    // =================================================================
    case 'add_education':
      $degreeTitle = trim($payload['degree_title'] ?? '');
      $degreeDesc  = trim($payload['degree_desc'] ?? '');
      $instName    = trim($payload['inst_name'] ?? '');
      $eduYear     = trim($payload['edu_year'] ?? '');

      if ($degreeTitle === '' || $instName === '') {
        throw new InvalidArgumentException('Degree title and Institution name are required.');
      }

      $pdo->beginTransaction();

      $stmt = $pdo->prepare("
                INSERT INTO doctor_qualifications 
                    (doctor_id, degree_title, degree_description, institution_name, edu_year, is_verified)
                VALUES 
                    (:doctor_id, :title, :descr, :inst, :yr, 1)
            ");
      $stmt->execute([
        ':doctor_id' => $currentDocId,
        ':title'     => $degreeTitle,
        ':descr'     => $degreeDesc,
        ':inst'      => $instName,
        ':yr'        => $eduYear
      ]);
      $newQualId = (int)$pdo->lastInsertId();

      // Audit Trail Log
      $auditStmt = $pdo->prepare("
                INSERT INTO audit_logs (user_id, action, entity_type, entity_id, details)
                VALUES (:user_id, 'ADD_DOCTOR_EDUCATION', 'doctor_qualifications', :entity_id, :details)
            ");
      $auditStmt->execute([
        ':user_id'   => $currentUserId,
        ':entity_id' => $newQualId,
        ':details'   => json_encode(['degree' => $degreeTitle, 'institution' => $instName])
      ]);

      $pdo->commit();
      echo json_encode(['success' => true, 'id' => $newQualId]);
      break;

    // =================================================================
    // ACTION 3: Add / Edit Routine Schedule with Conflict Check
    // =================================================================
    case 'save_schedule':
      $scheduleId = !empty($payload['schedule_id']) ? (int)$payload['schedule_id'] : null;
      $dayText    = trim($payload['day_text'] ?? '');
      $deptText   = trim($payload['dept_text'] ?? '');
      $timeRange  = trim($payload['time_range'] ?? '');
      $locText    = trim($payload['loc_text'] ?? '');

      // Standardize em-dash to regular hyphen for consistency
      $timeRange = str_replace('—', '-', $timeRange);

      if ($dayText === '' || $timeRange === '') {
        throw new InvalidArgumentException('Day and Time Range are required.');
      }

      $pdo->beginTransaction();

      // Check for conflict with existing schedule
      $conflictQuery = "
                SELECT schedule_id 
                FROM doctor_schedules 
                WHERE doctor_id = :doctor_id 
                  AND day_of_week = :day_text 
                  AND time_range = :time_range
            ";
      $params = [
        ':doctor_id'  => $currentDocId,
        ':day_text'   => $dayText,
        ':time_range' => $timeRange
      ];

      if ($scheduleId !== null) {
        $conflictQuery .= " AND schedule_id != :schedule_id";
        $params[':schedule_id'] = $scheduleId;
      }

      $conflictStmt = $pdo->prepare($conflictQuery);
      $conflictStmt->execute($params);

      if ($conflictStmt->fetch()) {
        $pdo->rollBack();
        http_response_code(409); // Conflict
        echo json_encode([
          'success' => false,
          'error'   => "Schedule conflict: Doctor already has an active shift on {$dayText} at {$timeRange}."
        ]);
        exit;
      }

      if ($scheduleId !== null) {
        // Update
        $updateStmt = $pdo->prepare("
                    UPDATE doctor_schedules 
                    SET day_of_week     = :day_text,
                        department_name = :dept_text,
                        time_range      = :time_range,
                        location        = :loc_text
                    WHERE schedule_id   = :schedule_id AND doctor_id = :doctor_id
                ");
        $updateStmt->execute([
          ':day_text'    => $dayText,
          ':dept_text'   => $deptText,
          ':time_range'  => $timeRange,
          ':loc_text'    => $locText,
          ':schedule_id' => $scheduleId,
          ':doctor_id'   => $currentDocId
        ]);
        $finalScheduleId = $scheduleId;
      } else {
        // Insert
        $insertStmt = $pdo->prepare("
                    INSERT INTO doctor_schedules 
                        (doctor_id, day_of_week, department_name, time_range, location)
                    VALUES 
                        (:doctor_id, :day_text, :dept_text, :time_range, :loc_text)
                ");
        $insertStmt->execute([
          ':doctor_id'  => $currentDocId,
          ':day_text'   => $dayText,
          ':dept_text'  => $deptText,
          ':time_range' => $timeRange,
          ':loc_text'   => $locText
        ]);
        $finalScheduleId = (int)$pdo->lastInsertId();
      }

      // Audit Trail Log
      $auditStmt = $pdo->prepare("
                INSERT INTO audit_logs (user_id, action, entity_type, entity_id, details)
                VALUES (:user_id, 'SAVE_DOCTOR_SCHEDULE', 'doctor_schedules', :entity_id, :details)
            ");
      $auditStmt->execute([
        ':user_id'   => $currentUserId,
        ':entity_id' => $finalScheduleId,
        ':details'   => json_encode(['day' => $dayText, 'time' => $timeRange, 'department' => $deptText])
      ]);

      $pdo->commit();
      echo json_encode(['success' => true, 'schedule_id' => $finalScheduleId]);
      break;

    default:
      http_response_code(400);
      echo json_encode(['success' => false, 'error' => 'Invalid action provided.']);
      break;
  }
} catch (Exception $e) {
  if ($pdo->inTransaction()) {
    $pdo->rollBack();
  }
  http_response_code(400);
  echo json_encode(['success' => false, 'error' => $e->getMessage()]);
  }
  exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>NHMRD - Doctor Profile</title>
  <link rel="stylesheet" href="../../assets/css/doctor-panel/doctor-profile.css">
  <link rel="stylesheet" href="../../assets/css/default-structure.css">
  <link rel="stylesheet" href="../../assets/css/doctor-panel/features/doctor-header.css">
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
        <a href="../../pages/doctor-panel/dashboard.php" class="nav-item">
          <i class="fa-solid fa-table-cells-large"></i>
          <span>Dashboard</span>
        </a>
        <a href="../../pages/doctor-panel/doctor-clinical-service-records.php" class="nav-item">
          <i class="fa-solid fa-notes-medical"></i>
          <span>Clinical Records</span>
        </a>
        <a href="../../pages/doctor-panel/patient-appointments.php" class="nav-item">
          <i class="fa-solid fa-user-clock"></i>
          <span>Patient Appointments</span>
        </a>
        <a href="../../pages/doctor-panel/doctor-profile.php" class="nav-item">
          <i class="fa-solid fa-user-doctor"></i>
          <span>Doctor Profile</span>
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
      <!-- Top Header Bar -->
      <header class="doc-header">
        <div class="search-bar">
          <i class="fa-solid fa-magnifying-glass"></i>
          <input type="text" placeholder="Search patient MRN, name, vitals...">
        </div>

        <div class="header-right">
          <div class="header-status-pill">
            <i class="fa-regular fa-clock"></i>
            <span>Shift: Morning Clinical Rounds (07:00 - 15:30)</span>
          </div>
          <div class="header-status-pill">
            <i class="fa-solid fa-rotate"></i>
            <span>EMR Synced</span>
          </div>
          <button class="icon-btn"><i class="fa-regular fa-bell"></i></button>

          <div class="doctor-profile-badge">
            <div class="doctor-info-text">
              <span class="doctor-name">Dr. Nusrat Jahan, MD</span>
              <span class="doctor-dept">Internal Medicine & Therapeutics</span>
            </div>
            <div class="doctor-avatar">
              <img src="https://i.pravatar.cc/100?img=47" alt="Dr. Nusrat Jahan">
            </div>
          </div>
        </div>
      </header>

      <!-- Main Content Area -->
      <main class="content-body">

        <!-- Doctor Profile Banner -->
        <div class="profile-banner-card">
          <div class="profile-main-info">
            <div class="profile-img-container">
              <img src="https://i.pravatar.cc/150?img=47" alt="Dr. Nusrat Jahan" class="profile-img">
              <span class="online-indicator"><i class="fa-solid fa-shield"></i></span>
            </div>

            <div class="profile-details">
              <div class="badge-row">
                <span class="tag-badge green-light">ATTENDING PHYSICIAN</span>
                <span class="tag-badge blue-light">FACULTY FELLOW (FCPS)</span>
                <span class="tag-badge sky-light">&bull; EPCS TIER-3 ACTIVE</span>
              </div>
              <h1 class="doctor-title">Dr. Nusrat Jahan, FCPS, MRCP</h1>
              <p class="doctor-subtitle">Board Certified Internal Medicine & Clinical Pharmacology</p>

              <div class="doctor-meta">
                <span><i class="fa-solid fa-id-card"></i> BMDC Reg: A-88204</span>
                <span class="meta-divider">&bull;</span>
                <span><i class="fa-solid fa-award"></i> NID/License: #BD-88204-DHK</span>
                <span class="meta-divider">&bull;</span>
                <span><i class="fa-solid fa-hospital"></i> NHMRD Outpatient Diagnostic Clinic &mdash; Suite 300</span>
              </div>
            </div>
          </div>

          <div class="profile-actions">
            <button class="btn-emerald" onclick="openEditProfileModal()"><i class="fa-solid fa-sliders"></i> Edit Profile</button>
          </div>
        </div>

        <!-- Tab Navigation Bar -->
        <div class="profile-tabs">
          <button class="tab-item active"><i class="fa-solid fa-user-doctor"></i> Professional Overview</button>
          <button class="tab-item" onclick="openClinicModal()"><i class="fa-regular fa-calendar-alt"></i> Clinic Hours & Availability</button> <button class="tab-item"><i class="fa-solid fa-file-prescription"></i> Prescription & Formulary
            Authority</button>
          <button class="tab-item"><i class="fa-solid fa-sliders"></i> Telehealth & Intake Preferences</button>
        </div>

        <!-- Middle Section (2 Columns: Statement/Subspecialties + Privileges) -->
        <div class="grid-2-cols">
          <!-- Left Column: Statement & Subspecialties -->
          <div class="card">
            <div class="card-header">
              <div class="header-title-flex">
                <i class="fa-solid fa-file-waveform text-emerald"></i>
                <h3>Physician Statement & Clinical Subspecialties</h3>
              </div>
              <span class="status-pill green-pill">Annual Review Validated 2025</span>
            </div>

            <p class="statement-paragraph">
              Dr. Nusrat Jahan has spent over 14 years serving inpatient tertiary care centers and outpatient ambulatory
              clinics. Her clinical practice centers on comprehensive metabolic risk stratification, secondary
              hypertension refractory interventions, and careful longitudinal stewardship of pharmacotherapy in
              multimorbid adult populations.
            </p>

            <div class="subspecialties-grid">
              <!-- Card 1 -->
              <div class="sub-card green-bg">
                <div class="sub-card-head">
                  <div class="sub-icon green-icon"><i class="fa-solid fa-heart-pulse"></i></div>
                  <h4>Preventative Cardiometabolic Health</h4>
                </div>
                <p>Lipidology sub-profiling, advanced CAC risk stratifications, endothelial biomarker monitoring.</p>
              </div>

              <!-- Card 2 -->
              <div class="sub-card blue-bg">
                <div class="sub-card-head">
                  <div class="sub-icon blue-icon"><i class="fa-solid fa-stethoscope"></i></div>
                  <h4>Hypertension Management</h4>
                </div>
                <p>Ambulatory 24h blood pressure monitoring (ABPM), refractory aldosterone screening, renal arterial
                  ultrasound review.</p>
              </div>

              <!-- Card 3 -->
              <div class="sub-card sky-bg">
                <div class="sub-card-head">
                  <div class="sub-icon sky-icon"><i class="fa-solid fa-wave-square"></i></div>
                  <h4>Adult Diabetes & Glycemic Care</h4>
                </div>
                <p>Continuous Glucose Monitoring (CGM) analytics, GLP-1/SGLT2 dual-regimen coordination, nephropathy
                  early triage.</p>
              </div>

              <!-- Card 4 -->
              <div class="sub-card emerald-bg">
                <div class="sub-card-head">
                  <div class="sub-icon emerald-icon"><i class="fa-solid fa-pills"></i></div>
                  <h4>Polypharmacy Optimization</h4>
                </div>
                <p>Beers Criteria auditing, cytochrome P450 drug-drug interaction mitigation, and rational deprescribing
                  protocols.</p>
              </div>
            </div>
          </div>

          <!-- Right Column: Institutional Privileges -->
          <div class="card">
            <div class="card-header">
              <div class="header-title-flex">
                <i class="fa-solid fa-building-user text-emerald"></i>
                <h3>Institutional Privileges</h3>
              </div>
            </div>

            <div class="privilege-list">
              <!-- Item 1 -->
              <div class="privilege-item">
                <div class="privilege-main">
                  <strong>Dhaka Medical College Hospital</strong>
                  <span class="privilege-sub">Admitting & Inpatient Consultation Rights</span>
                </div>
                <span class="privilege-badge">Full Active Attending</span>
              </div>

              <!-- Item 2 -->
              <div class="privilege-item">
                <div class="privilege-main">
                  <strong>Square Hospitals Ltd.</strong>
                  <span class="privilege-sub">Outpatient Cardio-Metabolic Consults</span>
                </div>
                <span class="privilege-badge">Courtesy Faculty</span>
              </div>

              <!-- Item 3 -->
              <div class="privilege-item">
                <div class="privilege-main">
                  <strong>BSMMU Research Centre</strong>
                  <span class="privilege-sub">Clinical Trials &mdash; Pharmacology Protocol #819</span>
                </div>
                <span class="privilege-badge">Principal Investigator</span>
              </div>

              <!-- Item 4 -->
              <div class="privilege-item border-none">
                <div class="privilege-main">
                  <strong>Medical Malpractice Policy:</strong>
                  <span class="privilege-sub">Coverage Amount: <strong>à§³3,00,00,000 / à§³5,00,00,000</strong></span>
                </div>
                <span class="policy-no">Green Delta Insurance #882-C</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Bottom Section (2 Columns: Weekly Routine + Degrees & Education) -->
        <div class="grid-2-cols">
          <!-- Weekly Routine -->
          <div class="card">
            <div class="card-header">
              <h3>Weekly Routine</h3>
              <span class="specialty-dept-tag">Specialty Department</span>
            </div>

            <div class="routine-list">
              <div class="routine-item">
                <div>
                  <strong class="day-text">Saturday</strong>
                  <span class="dept-text">Internal Medicine</span>
                </div>
                <div class="time-col">
                  <span class="time-range">08:00 AM &mdash; 05:00 PM</span>
                  <span class="loc-text">Outpatient Clinic</span>
                </div>
              </div>

              <div class="routine-item">
                <div>
                  <strong class="day-text">Sunday</strong>
                  <span class="dept-text">Clinical Pharmacology</span>
                </div>
                <div class="time-col">
                  <span class="time-range">09:00 AM &mdash; 06:00 PM</span>
                  <span class="loc-text">Consultation Rounds</span>
                </div>
              </div>

              <div class="routine-item">
                <div>
                  <strong class="day-text">Monday</strong>
                  <span class="dept-text">Telehealth</span>
                </div>
                <div class="time-col">
                  <span class="time-range">10:00 AM &mdash; 04:00 PM</span>
                  <span class="loc-text">Remote Follow-ups</span>
                </div>
              </div>

              <div class="routine-item">
                <div>
                  <strong class="day-text">Tuesday</strong>
                  <span class="dept-text">Hypertension Clinic</span>
                </div>
                <div class="time-col">
                  <span class="time-range">08:30 AM &mdash; 05:30 PM</span>
                  <span class="loc-text">Specialty Care</span>
                </div>
              </div>

              <div class="routine-item border-none">
                <div>
                  <strong class="day-text">Wednesday</strong>
                  <span class="dept-text">General Medicine</span>
                </div>
                <div class="time-col">
                  <span class="time-range">08:00 AM &mdash; 03:00 PM</span>
                  <span class="loc-text">Morning Rounds</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Degrees & Education -->
          <div class="card">
            <div class="card-header">
              <h3>Degrees & Education</h3>
              <div class="edu-actions">
                <button class="btn-outline-xs" onclick="openEduModal()"><i class="fa-solid fa-pen"></i> Edit</button> <button class="btn-emerald-xs"><i class="fa-solid fa-circle-check"></i> Verify Education</button>
              </div>
            </div>

            <div class="education-list">
              <!-- Degree 1 -->
              <div class="edu-item">
                <div>
                  <strong class="degree-title">MBBS</strong>
                  <p class="degree-desc">Bachelor of Medicine, Bachelor of Surgery</p>
                </div>
                <div class="edu-inst-col">
                  <strong class="inst-name">Dhaka Medical College</strong>
                  <span class="edu-year">2008 - 2014</span>
                </div>
              </div>

              <!-- Degree 2 -->
              <div class="edu-item">
                <div>
                  <strong class="degree-title">FCPS</strong>
                  <p class="degree-desc">Fellowship of the College of Physicians and Surgeons</p>
                </div>
                <div class="edu-inst-col">
                  <strong class="inst-name">BCPS, Bangladesh</strong>
                  <span class="edu-year">2016 - 2018</span>
                </div>
              </div>

              <!-- Degree 3 -->
              <div class="edu-item border-none">
                <div>
                  <strong class="degree-title">MRCP</strong>
                  <p class="degree-desc">Membership of the Royal College of Physicians</p>
                </div>
                <div class="edu-inst-col">
                  <strong class="inst-name">Royal College of Physicians, UK</strong>
                  <span class="edu-year">2020</span>
                </div>
              </div>
            </div>
          </div>
        </div>

      </main>
    </div>
  </div>

  <!-- 1. Edit Profile Modal -->
  <div id="modal-edit-profile" class="modal-overlay">
    <div class="modal-box">
      <h3>Edit Profile</h3>
      <input type="text" id="input-doc-title" placeholder="Doctor Title (e.g., Dr. Jane, MD)">
      <input type="text" id="input-tag-badge" placeholder="Primary Badge (e.g., ATTENDING PHYSICIAN)">
      <div class="modal-actions">
        <button class="btn-cancel" onclick="closeModals()">Cancel</button>
        <button class="btn-emerald" onclick="saveProfile()">Save</button>
      </div>
    </div>
  </div>

  <!-- 2. Add Education Modal -->
  <div id="modal-add-edu" class="modal-overlay">
    <div class="modal-box">
      <h3>Add Education</h3>
      <input type="text" id="input-deg-title" placeholder="Degree Title (e.g., MD)">
      <input type="text" id="input-deg-desc" placeholder="Degree Description">
      <input type="text" id="input-inst-name" placeholder="Institution Name">
      <input type="text" id="input-edu-year" placeholder="Year (e.g., 2018 - 2022)">
      <div class="modal-actions">
        <button class="btn-cancel" onclick="closeModals()">Cancel</button>
        <button class="btn-emerald" onclick="verifyAndAddEducation()">Verify & Add</button>
      </div>
    </div>
  </div>

  <!-- 3. Clinic Hours Modal -->
  <div id="modal-clinic-hours" class="modal-overlay">
    <div class="modal-box">
      <h3>Add/Edit Clinic Hours</h3>
      <select id="input-day">
        <option value="Saturday">Saturday</option>
        <option value="Sunday">Sunday</option>
        <option value="Monday">Monday</option>
        <option value="Tuesday">Tuesday</option>
        <option value="Wednesday">Wednesday</option>
        <option value="Thursday">Thursday</option>
        <option value="Friday">Friday</option>
      </select>
      <input type="text" id="input-dept" placeholder="Department (e.g., Internal Medicine)">
      <input type="text" id="input-time" placeholder="Time Range (e.g., 08:00 AM - 05:00 PM)">
      <input type="text" id="input-loc" placeholder="Location">
      <div class="modal-actions">
        <button class="btn-cancel" onclick="closeModals()">Cancel</button>
        <button class="btn-emerald" onclick="saveClinicHours()">Save Schedule</button>
      </div>
    </div>
  </div>

  <script src="../../assets/js/doctor-panel/profile.js"></script>
</body>

</html>

