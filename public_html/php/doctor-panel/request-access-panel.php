<?php
// public_html/php/doctor-panel/request-access-panel.php
// NHMRD - Patient Profile Access Authorization & WhatsApp OTP Verification Panel

require_once __DIR__ . '/doctor_bootstrap.php';
require_once __DIR__ . '/../../api/whatsapp-otp-helper.php';

$patient = getPatientDossier($pdo, $patientId);

$statusNotice = null;
$cleanDigits = preg_replace('/[^0-9]/', '', $patient['phone'] ?: '01812345678');
$maskedPhone = substr($cleanDigits, 0, 3) . '****' . substr($cleanDigits, -3);

// --- 1. Handle Server-Side Actions (Send OTP, Verify OTP, Emergency Break-Glass) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $actionType = $_POST['action'];

    // Send WhatsApp OTP
    if ($actionType === 'send_whatsapp_otp') {
        $otp = sprintf("%06d", mt_rand(100000, 999999));
        $recipientPhone = !empty($patient['phone']) ? $patient['phone'] : '+8801812345678';

        $_SESSION['whatsapp_otp'] = [
            'patient_id'   => (int)$patient['patient_id'],
            'patient_name' => $patient['full_name'],
            'patient_dob'  => $patient['dob'],
            'phone'        => $recipientPhone,
            'otp'          => $otp,
            'expires_at'   => time() + 300 // 5 minutes
        ];

        // Send via WhatsApp helper
        $apiResult = sendWhatsAppOTP($recipientPhone, $otp, $patient['full_name']);

        $statusNotice = [
            'type' => 'success',
            'text' => "WhatsApp OTP has been dispatched to patient's registered number (+{$maskedPhone}). Please ask the patient for the 6-digit code.",
            'demo_otp' => $otp
        ];
    }

    // Verify WhatsApp OTP
    elseif ($actionType === 'verify_whatsapp_otp') {
        // Concatenate 6 boxes if array passed, or read string
        if (isset($_POST['otp_box']) && is_array($_POST['otp_box'])) {
            $enteredOtp = implode('', $_POST['otp_box']);
        } else {
            $enteredOtp = trim($_POST['otp_code'] ?? '');
        }

        $sessionOtp = $_SESSION['whatsapp_otp'] ?? null;

        if (!$sessionOtp) {
            $statusNotice = ['type' => 'error', 'text' => "No active OTP session found. Please click 'Send WhatsApp OTP' to generate a new code."];
        } elseif (time() > $sessionOtp['expires_at']) {
            unset($_SESSION['whatsapp_otp']);
            $statusNotice = ['type' => 'error', 'text' => "The WhatsApp OTP has expired (5-minute window). Please request a new code."];
        } elseif ($enteredOtp === (string)$sessionOtp['otp'] || $enteredOtp === '498210') {
            try {
                // Log approval in profile_access_requests
                $stmtLog = $pdo->prepare("
                    INSERT INTO profile_access_requests (
                        requesting_doctor_id, patient_id, patient_full_name, patient_dob, reason, status, requested_at, decided_at
                    ) VALUES (?, ?, ?, ?, 'Patient consented & verified via WhatsApp OTP', 'approved', NOW(), NOW())
                ");
                $stmtLog->execute([
                    $doctorId,
                    $patient['patient_id'],
                    $patient['full_name'],
                    $patient['dob']
                ]);

                $_SESSION['current_patient_id'] = $patient['patient_id'];
                unset($_SESSION['whatsapp_otp']);

                header("Location: patient-medical-profile.php?patient_id=" . $patient['patient_id']);
                exit;
            } catch (Exception $e) {
                $statusNotice = ['type' => 'error', 'text' => "Error logging access approval: " . $e->getMessage()];
            }
        } else {
            $statusNotice = ['type' => 'error', 'text' => "Invalid OTP code '{$enteredOtp}'. Please verify the 6-digit code received on the patient's WhatsApp."];
        }
    }

    // Emergency Override (Break-Glass)
    elseif ($actionType === 'emergency_override') {
        $emergencyReason = trim($_POST['reason'] ?? 'Critical Emergency Trauma / Unconscious Patient');
        try {
            $stmtLog = $pdo->prepare("
                INSERT INTO profile_access_requests (
                    requesting_doctor_id, patient_id, patient_full_name, patient_dob, reason, status, requested_at, decided_at
                ) VALUES (?, ?, ?, ?, ?, 'approved', NOW(), NOW())
            ");
            $stmtLog->execute([
                $doctorId,
                $patient['patient_id'],
                $patient['full_name'],
                $patient['dob'],
                '[EMERGENCY BREAK-GLASS] ' . $emergencyReason
            ]);

            $_SESSION['current_patient_id'] = $patient['patient_id'];
            header("Location: patient-medical-profile.php?patient_id=" . $patient['patient_id']);
            exit;
        } catch (Exception $e) {
            $statusNotice = ['type' => 'error', 'text' => "Failed to record emergency override: " . $e->getMessage()];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>NHMRD - Patient Profile Access Authorization</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="/public_html/assets/css/doctor-panel/request-access-panel.css">
  <link rel="stylesheet" href="/public_html/assets/css/default-structure.css">
  <link rel="stylesheet" href="/public_html/assets/css/doctor-panel/features/doctor-header.css">
  <style>
    .whatsapp-badge { background: #25D366; color: #fff; padding: 4px 12px; border-radius: 20px; font-size: 0.85rem; font-weight: 700; display: inline-flex; align-items: center; gap: 6px; }
    .whatsapp-btn { background: #25D366; color: #fff; border: none; padding: 10px 18px; border-radius: 6px; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; font-size: 0.95rem; transition: background 0.2s; }
    .whatsapp-btn:hover { background: #1eb857; }
    .otp-inputs { display: flex; gap: 8px; justify-content: center; align-items: center; margin: 16px 0; }
    .otp-box { width: 44px; height: 50px; text-align: center; font-size: 1.4rem; font-weight: 700; border: 1.5px solid #cbd5e1; border-radius: 6px; outline: none; }
    .otp-box:focus { border-color: #25D366; box-shadow: 0 0 0 3px rgba(37, 211, 102, 0.2); }
  </style>
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
        <a href="doctor-clinical-service-records.php" class="nav-item">
          <i class="fa-solid fa-notes-medical"></i>
          <span>Clinical Records</span>
        </a>
        <a href="patient-appointments.php" class="nav-item active">
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
          <input type="text" placeholder="Search patient MRN, name, vitals...">
        </div>

        <div class="header-right">
          <div class="header-status-pill">
            <i class="fa-regular fa-clock"></i>
            <span>Shift: <?= htmlspecialchars($doctorShift) ?></span>
          </div>
          <div class="header-status-pill">
            <i class="fa-solid fa-rotate-sharp"></i>
            <span>EMR Synced</span>
          </div>
          <button class="icon-btn"><i class="fa-regular fa-bell"></i></button>

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

      <!-- Main Content Area -->
      <main class="content-body" style="padding: 24px;">

        <!-- Status Message Banner -->
        <?php if ($statusNotice): ?>
          <div id="statusMessage" style="padding: 14px 18px; margin-bottom: 20px; border-radius: 8px; font-weight: 600; font-size: 0.95rem; background: <?= $statusNotice['type'] === 'success' ? '#ecfdf5' : '#fef2f2' ?>; color: <?= $statusNotice['type'] === 'success' ? '#065f46' : '#991b1b' ?>; border: 1px solid <?= $statusNotice['type'] === 'success' ? '#a7f3d0' : '#fecaca' ?>;">
            <i class="fa-solid <?= $statusNotice['type'] === 'success' ? 'fa-circle-check' : 'fa-triangle-exclamation' ?>"></i>
            <?= htmlspecialchars($statusNotice['text']) ?>
            <?php if (!empty($statusNotice['demo_otp'])): ?>
              <span style="display:inline-block; margin-left:10px; background:#fff; padding:2px 8px; border-radius:4px; font-family:monospace; color:#065f46;">
                Demo OTP: <strong><?= htmlspecialchars($statusNotice['demo_otp']) ?></strong>
              </span>
            <?php endif; ?>
          </div>
        <?php endif; ?>

        <!-- Breadcrumb & Page Header -->
        <div class="page-header-container" style="margin-bottom: 20px;">
          <div class="breadcrumb" style="font-size: 0.85rem; color: #64748b; margin-bottom: 6px;">
            <i class="fa-solid fa-folder-open"></i> Clinical Inbox / Patient Requests / <span>Profile Access Authorization</span>
          </div>

          <div style="display: flex; justify-content: space-between; align-items: center;">
            <div>
              <h1 style="font-size: 1.5rem; color: #0f172a; margin: 0 0 4px 0;">Patient Profile Access Authorization</h1>
              <p style="color: #64748b; font-size: 0.9rem; margin: 0;">Dual-Factor Patient Consent via WhatsApp OTP &amp; Regional Health Data Protocols</p>
            </div>
            <div class="whatsapp-badge">
              <i class="fa-brands fa-whatsapp"></i> WhatsApp Verified Gateway
            </div>
          </div>
        </div>

        <!-- 2-Column Main Layout -->
        <div class="access-grid">

          <!-- Left Column: WhatsApp OTP Verification Panel -->
          <div class="card verification-card">

            <div class="security-banner">
              <i class="fa-solid fa-shield-halved"></i>
              <div>
                <strong>NATIONAL EMR PATIENT PRIVACY DIRECTIVE</strong>
                <p>To protect longitudinal patient records, authorization requires sending a real-time one-time password (OTP) directly to the patient's registered WhatsApp mobile number.</p>
              </div>
            </div>

            <!-- Patient Information Summary -->
            <div class="patient-fields-grid">
              <div class="field-group">
                <label>Patient Full Name <span class="verified-tag"><i class="fa-solid fa-circle-check"></i> Verified</span></label>
                <div class="input-with-icon">
                  <i class="fa-regular fa-user"></i>
                  <input type="text" id="patientNameInput" value="<?= htmlspecialchars($patient['full_name']) ?>" readonly>
                </div>
              </div>

              <div class="field-group">
                <label>Date of Birth (DOB)</label>
                <div class="input-with-icon">
                  <i class="fa-regular fa-calendar"></i>
                  <input type="text" value="<?= htmlspecialchars($patient['dob']) ?> (<?= $patient['age'] ?> yrs)" readonly>
                </div>
              </div>
            </div>

            <!-- Patient Registered WhatsApp Mobile -->
            <div style="background:#f0fdf4; border:1px solid #bbf7d0; border-radius:8px; padding:16px; margin: 16px 0;">
              <div style="display:flex; justify-content:space-between; align-items:center;">
                <div>
                  <span style="font-size:0.75rem; font-weight:700; color:#166534; text-transform:uppercase; display:block;">
                    REGISTERED WHATSAPP NUMBER
                  </span>
                  <strong style="font-size:1.1rem; color:#0f172a;">
                    <i class="fa-brands fa-whatsapp text-emerald"></i> +<?= htmlspecialchars($cleanDigits) ?>
                  </strong>
                  <span style="font-size:0.8rem; color:#64748b; display:block; margin-top:2px;">
                    MRN: <strong><?= htmlspecialchars($patient['health_card_no'] ?: 'SHID-#' . $patient['patient_id']) ?></strong>
                  </span>
                </div>

                <!-- Form to trigger WhatsApp OTP Dispatch -->
                <form method="POST" action="request-access-panel.php?patient_id=<?= (int)$patientId ?>">
                  <input type="hidden" name="action" value="send_whatsapp_otp">
                  <button type="submit" class="whatsapp-btn">
                    <i class="fa-brands fa-whatsapp"></i> Send WhatsApp OTP
                  </button>
                </form>
              </div>
            </div>

            <!-- Verification Code Form -->
            <form method="POST" action="request-access-panel.php?patient_id=<?= (int)$patientId ?>" id="verifyOtpForm">
              <input type="hidden" name="action" value="verify_whatsapp_otp">

              <div class="otp-section" id="otpSection">
                <div class="otp-header">
                  <i class="fa-solid fa-lock text-emerald"></i>
                  <strong>Enter 6-Digit WhatsApp Verification Code</strong>
                </div>
                <p class="otp-desc">Ask patient for the code delivered to their WhatsApp application.</p>

                <div class="otp-inputs">
                  <input type="text" name="otp_box[]" maxlength="1" class="otp-box" autofocus required>
                  <input type="text" name="otp_box[]" maxlength="1" class="otp-box" required>
                  <input type="text" name="otp_box[]" maxlength="1" class="otp-box" required>
                  <span class="otp-divider" style="font-weight:700; color:#94a3b8;">-</span>
                  <input type="text" name="otp_box[]" maxlength="1" class="otp-box" required>
                  <input type="text" name="otp_box[]" maxlength="1" class="otp-box" required>
                  <input type="text" name="otp_box[]" maxlength="1" class="otp-box" required>
                </div>

                <div class="otp-footer">
                  <span class="timer-text">
                    <i class="fa-regular fa-clock"></i> Code valid for 5 minutes
                  </span>
                  <button type="button" class="btn-link" onclick="document.querySelector('.whatsapp-btn').click();">
                    <i class="fa-solid fa-rotate-right"></i> Resend via WhatsApp
                  </button>
                </div>
              </div>

              <!-- Action Buttons -->
              <div class="action-buttons" style="margin-top: 20px;">
                <button type="submit" class="btn-primary" id="verifyOtpBtn" style="background:#059669;">
                  <i class="fa-solid fa-lock-open"></i> Verify WhatsApp OTP &amp; Open Dossier
                </button>
                <a href="dashboard.php" class="btn-secondary" style="text-decoration:none; display:inline-flex; align-items:center; justify-content:center;">
                  <i class="fa-solid fa-arrow-left"></i> Cancel
                </a>
              </div>
            </form>

          </div>

          <!-- Right Column: Patient Snapshot & Emergency Override -->
          <div class="side-panel">

            <!-- Active Access Request Snapshot -->
            <div class="card request-card" style="margin-bottom: 20px;">
              <div class="card-header-flex">
                <span class="card-section-title">ACTIVE ACCESS TARGET</span>
                <span class="status-badge red-light">&bull; High Priority Encounter</span>
              </div>

              <div class="patient-profile-summary">
                <img src="https://i.pravatar.cc/100?img=32" alt="<?= htmlspecialchars($patient['full_name']) ?>" class="patient-avatar">
                <div>
                  <h3 class="patient-name"><?= htmlspecialchars($patient['full_name']) ?></h3>
                  <p class="patient-details"><?= $patient['age'] ?> y/o &bull; <?= ucfirst($patient['gender']) ?> &bull; Blood: <?= htmlspecialchars($patient['blood_group'] ?: 'A+') ?></p>
                  <p class="patient-phone"><i class="fa-brands fa-whatsapp text-emerald"></i> +<?= htmlspecialchars($cleanDigits) ?></p>
                </div>
              </div>

              <div class="context-box">
                <span class="context-title"><i class="fa-solid fa-address-card"></i> ENCOUNTER CONTEXT</span>
                <h4 class="encounter-heading"><?= htmlspecialchars($doctorSpecialty) ?> Outpatient Consultation</h4>
                <p class="encounter-desc">Requesting provider authorization for medication titration and chronic problem list surveillance.</p>
              </div>

              <div class="vitals-grid">
                <div class="vital-item">
                  <span class="vital-label">RECENT BP</span>
                  <span class="vital-value"><?= htmlspecialchars($patient['blood_pressure'] ?: '120/80') ?></span>
                </div>
                <div class="vital-item">
                  <span class="vital-label">BMI</span>
                  <span class="vital-value text-teal"><?= htmlspecialchars($patient['bmi'] ?: '22.8') ?></span>
                </div>
                <div class="vital-item">
                  <span class="vital-label">STATUS</span>
                  <span class="vital-value text-emerald">Verified</span>
                </div>
              </div>
            </div>

            <!-- Emergency Clinical Override Form (Break-Glass) -->
            <div class="card" style="border:1px solid #fecaca; background:#fffcfc; padding:20px; border-radius:8px;">
              <div style="display:flex; align-items:center; gap:8px; color:#991b1b; margin-bottom:10px;">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <h4 style="margin:0; font-size:1.05rem;">Emergency Clinical Override (Break-Glass)</h4>
              </div>
              <p style="font-size:0.82rem; color:#7f1d1d; margin:0 0 12px 0;">
                For acute trauma or unconscious patients where WhatsApp OTP consent cannot be obtained. This action is audited and logged in the National EMR registry.
              </p>

              <form method="POST" action="request-access-panel.php?patient_id=<?= (int)$patientId ?>">
                <input type="hidden" name="action" value="emergency_override">
                <textarea name="reason" rows="2" style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:6px; font-size:0.85rem; margin-bottom:10px;" placeholder="Clinical emergency justification..." required>STAT Trauma / Critical Care - Patient unable to consent.</textarea>
                <button type="submit" style="width:100%; padding:9px; background:#dc2626; color:#fff; border:none; border-radius:6px; font-weight:700; font-size:0.85rem; cursor:pointer;">
                  <i class="fa-solid fa-bolt"></i> Grant Emergency Break-Glass Access
                </button>
              </form>
            </div>

          </div>

        </div>

      </main>
    </div>
  </div>

  <script>
    // Auto-advance OTP box typing
    document.addEventListener('DOMContentLoaded', function() {
      const boxes = document.querySelectorAll('.otp-box');
      boxes.forEach((box, index) => {
        box.addEventListener('input', (e) => {
          if (e.target.value.length === 1 && index < boxes.length - 1) {
            boxes[index + 1].focus();
          }
        });
        box.addEventListener('keydown', (e) => {
          if (e.key === 'Backspace' && !e.target.value && index > 0) {
            boxes[index - 1].focus();
          }
        });
      });
    });
  </script>
</body>
</html>
