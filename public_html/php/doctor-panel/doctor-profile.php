<?php
// public_html/php/doctor-panel/doctor-profile.php
// NHMRD - Doctor Comprehensive Professional Profile

require_once __DIR__ . '/doctor_bootstrap.php';

$updateMsg = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    $newPhone = trim($_POST['phone'] ?? '');
    $newEmail = trim($_POST['email'] ?? '');
    $newDuty  = trim($_POST['duty_status'] ?? 'on_duty');

    if (!empty($newPhone) && !empty($newEmail)) {
        try {
            $stmtUp = $pdo->prepare("UPDATE doctors SET phone = ?, email = ?, duty_status = ? WHERE doctor_id = ?");
            $stmtUp->execute([$newPhone, $newEmail, $newDuty, $doctorId]);
            $updateMsg = ['type' => 'success', 'text' => 'Profile contact information and duty status updated successfully.'];
            
            // Refresh doctor data
            $currentDoctor['phone'] = $newPhone;
            $currentDoctor['email'] = $newEmail;
            $currentDoctor['duty_status'] = $newDuty;
        } catch (Exception $e) {
            $updateMsg = ['type' => 'error', 'text' => 'Failed to update profile: ' . $e->getMessage()];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>NHMRD - Doctor Profile</title>
  <link rel="stylesheet" href="/public_html/assets/css/doctor-panel/doctor-profile.css">
  <link rel="stylesheet" href="/public_html/assets/css/default-structure.css">
  <link rel="stylesheet" href="/public_html/assets/css/doctor-panel/features/doctor-header.css">
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
        <a href="dashboard.php" class="nav-item">
          <i class="fa-solid fa-table-cells-large"></i>
          <span>Dashboard</span>
        </a>
        <a href="doctor-clinical-service-records.php" class="nav-item">
          <i class="fa-solid fa-notes-medical"></i>
          <span>Clinical Records</span>
        </a>
        <a href="patient-appointments.php" class="nav-item">
          <i class="fa-solid fa-user-clock"></i>
          <span>Patient Appointments</span>
        </a>
        <a href="doctor-profile.php" class="nav-item active">
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
            <i class="fa-solid fa-rotate"></i>
            <span>EMR Synced</span>
          </div>
          <button class="icon-btn"><i class="fa-regular fa-bell"></i></button>

          <div class="doctor-profile-badge">
            <div class="doctor-info-text">
              <span class="doctor-name"><?= htmlspecialchars($doctorName) ?></span>
              <span class="doctor-dept"><?= htmlspecialchars($doctorSpecialty) ?></span>
            </div>
            <div class="doctor-avatar">
              <img src="<?= htmlspecialchars($doctorAvatar) ?>" alt="<?= htmlspecialchars($doctorName) ?>">
            </div>
          </div>
        </div>
      </header>

      <!-- Main Content Area -->
      <main class="content-body">

        <?php if ($updateMsg): ?>
          <div style="padding: 12px 16px; margin-bottom: 20px; border-radius: 8px; font-weight: 600; background: <?= $updateMsg['type'] === 'success' ? '#ecfdf5' : '#fef2f2' ?>; color: <?= $updateMsg['type'] === 'success' ? '#065f46' : '#991b1b' ?>; border: 1px solid <?= $updateMsg['type'] === 'success' ? '#a7f3d0' : '#fecaca' ?>;">
            <i class="fa-solid <?= $updateMsg['type'] === 'success' ? 'fa-circle-check' : 'fa-triangle-exclamation' ?>"></i>
            <?= htmlspecialchars($updateMsg['text']) ?>
          </div>
        <?php endif; ?>

        <!-- Doctor Profile Banner -->
        <div class="profile-banner-card">
          <div class="profile-main-info">
            <div class="profile-img-container">
              <img src="<?= htmlspecialchars($doctorAvatar) ?>" alt="<?= htmlspecialchars($doctorName) ?>" class="profile-img">
              <span class="online-indicator"><i class="fa-solid fa-shield"></i></span>
            </div>

            <div class="profile-details">
              <div class="badge-row">
                <span class="tag-badge green-light">ATTENDING PHYSICIAN</span>
                <span class="tag-badge blue-light"><?= htmlspecialchars($currentDoctor['designation'] ?? 'SENIOR CONSULTANT') ?></span>
                <span class="tag-badge sky-light">&bull; BMDC VERIFIED ACTIVE</span>
              </div>
              <h1 class="doctor-full-name"><?= htmlspecialchars($doctorName) ?></h1>
              <p class="doctor-credentials-text"><?= htmlspecialchars($currentDoctor['qualifications'] ?? 'MBBS, FCPS (Internal Medicine), MD') ?></p>
              <div class="meta-inline-info">
                <span><i class="fa-solid fa-id-badge text-emerald"></i> BMDC Reg: <strong><?= htmlspecialchars($currentDoctor['bmdc_registration_no'] ?? 'A-49821') ?></strong></span>
                <span><i class="fa-solid fa-hospital text-blue"></i> <?= htmlspecialchars($doctorHospital) ?></span>
                <span><i class="fa-solid fa-briefcase text-teal"></i> <?= (int)($currentDoctor['years_experience'] ?? 12) ?> Years Experience</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Details Grid -->
        <div class="profile-content-grid" style="display:grid; grid-template-columns: 2fr 1fr; gap: 24px; margin-top: 24px;">

          <!-- Left Info Cards -->
          <div class="info-column">
            <!-- Academic & Credentials -->
            <div class="profile-card" style="background:#fff; border-radius:8px; padding:24px; border:1px solid #e2e8f0; margin-bottom:20px;">
              <h3 style="font-size:1.1rem; color:#1e293b; margin-bottom:16px; display:flex; align-items:center; gap:8px;">
                <i class="fa-solid fa-graduation-cap text-emerald"></i> Academic Credentials &amp; Medical Registration
              </h3>
              <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                <div>
                  <span style="font-size:0.8rem; color:#64748b; font-weight:600;">PRIMARY SPECIALTY</span>
                  <p style="font-size:0.95rem; font-weight:600; color:#1e293b; margin-top:4px;"><?= htmlspecialchars($doctorSpecialty) ?></p>
                </div>
                <div>
                  <span style="font-size:0.8rem; color:#64748b; font-weight:600;">GRADUATING INSTITUTION</span>
                  <p style="font-size:0.95rem; font-weight:600; color:#1e293b; margin-top:4px;"><?= htmlspecialchars($currentDoctor['graduating_institution'] ?? 'Dhaka Medical College & Hospital') ?></p>
                </div>
                <div>
                  <span style="font-size:0.8rem; color:#64748b; font-weight:600;">BMDC REGISTRATION NO</span>
                  <p style="font-size:0.95rem; font-weight:600; color:#1e293b; margin-top:4px;"><?= htmlspecialchars($currentDoctor['bmdc_registration_no'] ?? 'A-49821') ?></p>
                </div>
                <div>
                  <span style="font-size:0.8rem; color:#64748b; font-weight:600;">NATIONAL NID NO</span>
                  <p style="font-size:0.95rem; font-weight:600; color:#1e293b; margin-top:4px;"><?= htmlspecialchars($currentDoctor['nid'] ?? '19892691234560003') ?></p>
                </div>
              </div>
            </div>

            <!-- Current Clinical Posting -->
            <div class="profile-card" style="background:#fff; border-radius:8px; padding:24px; border:1px solid #e2e8f0;">
              <h3 style="font-size:1.1rem; color:#1e293b; margin-bottom:16px; display:flex; align-items:center; gap:8px;">
                <i class="fa-solid fa-hospital-user text-emerald"></i> Current Clinical Posting &amp; Hospital Assignment
              </h3>
              <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                <div>
                  <span style="font-size:0.8rem; color:#64748b; font-weight:600;">HOSPITAL NAME</span>
                  <p style="font-size:0.95rem; font-weight:600; color:#1e293b; margin-top:4px;"><?= htmlspecialchars($doctorHospital) ?></p>
                </div>
                <div>
                  <span style="font-size:0.8rem; color:#64748b; font-weight:600;">OFFICIAL DESIGNATION</span>
                  <p style="font-size:0.95rem; font-weight:600; color:#1e293b; margin-top:4px;"><?= htmlspecialchars($currentDoctor['designation'] ?? 'Senior Consultant') ?></p>
                </div>
                <div>
                  <span style="font-size:0.8rem; color:#64748b; font-weight:600;">SHIFT SCHEDULE</span>
                  <p style="font-size:0.95rem; font-weight:600; color:#1e293b; margin-top:4px;"><?= htmlspecialchars($doctorShift) ?></p>
                </div>
                <div>
                  <span style="font-size:0.8rem; color:#64748b; font-weight:600;">DUTY STATUS</span>
                  <p style="font-size:0.95rem; font-weight:600; color:#059669; margin-top:4px;">
                    <i class="fa-solid fa-circle" style="font-size:8px;"></i> <?= strtoupper(htmlspecialchars($currentDoctor['duty_status'] ?? 'ON_DUTY')) ?>
                  </p>
                </div>
              </div>
            </div>
          </div>

          <!-- Right Column: Edit Profile & Duty Form -->
          <div class="action-column">
            <div class="profile-card" style="background:#fff; border-radius:8px; padding:24px; border:1px solid #e2e8f0;">
              <h3 style="font-size:1.05rem; color:#1e293b; margin-bottom:16px; display:flex; align-items:center; gap:8px;">
                <i class="fa-solid fa-sliders text-emerald"></i> Update Contact &amp; Duty
              </h3>
              <form method="POST" action="doctor-profile.php">
                <input type="hidden" name="action" value="update_profile">
                
                <div style="margin-bottom:14px;">
                  <label style="display:block; font-size:0.8rem; font-weight:600; color:#475569; margin-bottom:6px;">Official Phone</label>
                  <input type="text" name="phone" value="<?= htmlspecialchars($currentDoctor['phone'] ?? '+880 1711-234567') ?>" style="width:100%; padding:8px 12px; border:1px solid #cbd5e1; border-radius:6px;" required>
                </div>

                <div style="margin-bottom:14px;">
                  <label style="display:block; font-size:0.8rem; font-weight:600; color:#475569; margin-bottom:6px;">Email Address</label>
                  <input type="email" name="email" value="<?= htmlspecialchars($currentDoctor['email'] ?? 'doctor@nhmrd.gov.bd') ?>" style="width:100%; padding:8px 12px; border:1px solid #cbd5e1; border-radius:6px;" required>
                </div>

                <div style="margin-bottom:18px;">
                  <label style="display:block; font-size:0.8rem; font-weight:600; color:#475569; margin-bottom:6px;">Current Duty Status</label>
                  <select name="duty_status" style="width:100%; padding:8px 12px; border:1px solid #cbd5e1; border-radius:6px;">
                    <option value="on_duty" <?= ($currentDoctor['duty_status'] ?? '') === 'on_duty' ? 'selected' : '' ?>>On Duty (Active Rounds)</option>
                    <option value="off_duty" <?= ($currentDoctor['duty_status'] ?? '') === 'off_duty' ? 'selected' : '' ?>>Off Duty</option>
                    <option value="leave" <?= ($currentDoctor['duty_status'] ?? '') === 'leave' ? 'selected' : '' ?>>On Leave</option>
                  </select>
                </div>

                <button type="submit" style="width:100%; background:#059669; color:#fff; border:none; padding:10px; font-weight:600; border-radius:6px; cursor:pointer;">
                  <i class="fa-solid fa-floppy-disk"></i> Save Profile Settings
                </button>
              </form>
            </div>
          </div>

        </div>

      </main>
    </div>
  </div>

</body>
</html>
