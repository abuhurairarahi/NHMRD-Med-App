<?php
// public_html/php/doctor-panel/patient-medical-profile.php
// NHMRD - Patient Full Electronic Medical Profile & Clinical Dossier

require_once __DIR__ . '/doctor_bootstrap.php';

$patient = getPatientDossier($pdo, $patientId);
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>NHMRD - Patient Full Medical Profile</title>
  <link rel="stylesheet" href="/public_html/assets/css/doctor-panel/patient-medical-profile.css">
  <link rel="stylesheet" href="/public_html/assets/css/default-structure.css">
  <link rel="stylesheet" href="/public_html/assets/css/doctor-panel/features/doctor-header.css">
  <link rel="stylesheet" href="/public_html/assets/css/doctor-panel/features/doctor-subheader.css">
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

    <!-- Main Content Area -->
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

      <!-- Sub Header Navigation Bar -->
      <div class="subheader-bar">
        <div class="subheader-left">
          <span class="ehr-tag"><i class="fa-solid fa-folder-open"></i> Patient Electronic Health Record</span>
          <span class="status-pill-green">Active Clinical Encounter</span>
        </div>
        <div class="subheader-right">
          <div class="session-timer">
            <i class="fa-solid fa-user-clock"></i> Session: <span>Active</span>
            <span class="tag-interactive">INTERACTIVE</span>
          </div>
          <a href="new-prescription-form.php?patient_id=<?= (int)$patientId ?>" class="btn-write-rx" style="text-decoration:none;">
            <i class="fa-solid fa-pen-to-square"></i> Write Prescription
          </a>
        </div>
      </div>

      <!-- Module Navigation Tabs -->
      <nav class="module-tabs">
        <a href="patient-medical-profile.php?patient_id=<?= (int)$patientId ?>" class="tab-btn active"><i class="fa-solid fa-user"></i> Patient Profile</a>
        <a href="patient-prescription-records.php?patient_id=<?= (int)$patientId ?>" class="tab-btn"><i class="fa-solid fa-capsules"></i> Prescription Records</a>
        <a href="patient-surgary-records.php?patient_id=<?= (int)$patientId ?>" class="tab-btn"><i class="fa-solid fa-scalpel"></i> Surgery Records</a>
        <a href="patient-test-records.php?patient_id=<?= (int)$patientId ?>" class="tab-btn"><i class="fa-solid fa-vial"></i> Test Records</a>
        <a href="patient-vaccine-records.php?patient_id=<?= (int)$patientId ?>" class="tab-btn"><i class="fa-solid fa-syringe"></i> Vaccine Records</a>
      </nav>

      <!-- Main Content Body -->
      <main class="content-body" style="padding: 24px;">

        <!-- Patient Info Banner Card -->
        <div class="patient-card" style="background:#fff; border-radius:8px; padding:24px; border:1px solid #e2e8f0; margin-bottom:24px;">
          <div class="patient-main-info" style="display:flex; gap:20px; align-items:center;">
            <div class="patient-avatar-wrap" style="position:relative;">
              <img src="https://i.pravatar.cc/120?img=32" alt="<?= htmlspecialchars($patient['full_name']) ?>" style="width:90px; height:90px; border-radius:50%; object-fit:cover; border:3px solid #059669;">
              <span class="blood-badge" style="position:absolute; bottom:0; right:0; background:#dc2626; color:#fff; font-size:0.75rem; font-weight:800; padding:2px 8px; border-radius:12px;">
                <?= htmlspecialchars($patient['blood_group'] ?: 'A+') ?>
              </span>
            </div>

            <div class="patient-details">
              <div class="patient-name-row" style="display:flex; align-items:center; gap:12px; margin-bottom:6px;">
                <h2 style="font-size:1.4rem; color:#0f172a; margin:0;"><?= htmlspecialchars($patient['full_name']) ?></h2>
                <span style="background:#e0f2fe; color:#0369a1; padding:3px 10px; border-radius:12px; font-size:0.75rem; font-weight:700;">
                  <?= htmlspecialchars($patient['health_card_no'] ?: 'SHID-#' . $patient['patient_id']) ?>
                </span>
                <span style="background:#dcfce7; color:#166534; padding:3px 10px; border-radius:12px; font-size:0.75rem; font-weight:700;">
                  <i class="fa-solid fa-shield-check"></i> Biometric Verified
                </span>
              </div>
              <p style="color:#64748b; font-size:0.9rem; margin:0 0 8px 0;">
                <?= $patient['age'] ?> yrs &bull; <?= ucfirst($patient['gender']) ?> &bull; DOB: <?= htmlspecialchars($patient['dob']) ?> &bull; Phone: <?= htmlspecialchars($patient['phone'] ?: '+880 1800-000000') ?>
              </p>
              <p style="color:#475569; font-size:0.85rem; margin:0;">
                <i class="fa-solid fa-location-dot text-emerald"></i> <?= htmlspecialchars($patient['address'] ?: 'Dhaka, Bangladesh') ?>
              </p>
            </div>
          </div>
        </div>

        <!-- Medical Profile Details Grid -->
        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:24px;">

          <!-- Card 1: Key Vitals & Anthropometrics -->
          <div style="background:#fff; border-radius:8px; padding:24px; border:1px solid #e2e8f0;">
            <h3 style="font-size:1.1rem; color:#1e293b; margin-bottom:18px; display:flex; align-items:center; gap:8px;">
              <i class="fa-solid fa-heart-pulse text-emerald"></i> Vital Signs &amp; Physical Metrics
            </h3>
            <div style="display:grid; grid-template-columns: repeat(3, 1fr); gap:16px;">
              <div style="background:#f8fafc; padding:12px; border-radius:6px; border:1px solid #e2e8f0;">
                <span style="font-size:0.75rem; color:#64748b; font-weight:600; display:block;">BLOOD PRESSURE</span>
                <strong style="font-size:1.15rem; color:#0f172a;"><?= htmlspecialchars($patient['blood_pressure'] ?: '120/80') ?></strong>
                <span style="font-size:0.75rem; color:#059669; display:block;">Target &lt;130/80</span>
              </div>
              <div style="background:#f8fafc; padding:12px; border-radius:6px; border:1px solid #e2e8f0;">
                <span style="font-size:0.75rem; color:#64748b; font-weight:600; display:block;">BMI INDEX</span>
                <strong style="font-size:1.15rem; color:#0f172a;"><?= htmlspecialchars($patient['bmi'] ?: '22.8') ?></strong>
                <span style="font-size:0.75rem; color:#64748b; display:block;">Normal Range</span>
              </div>
              <div style="background:#f8fafc; padding:12px; border-radius:6px; border:1px solid #e2e8f0;">
                <span style="font-size:0.75rem; color:#64748b; font-weight:600; display:block;">WEIGHT / HEIGHT</span>
                <strong style="font-size:1.15rem; color:#0f172a;"><?= htmlspecialchars($patient['weight_kg'] ?: '62') ?> kg</strong>
                <span style="font-size:0.75rem; color:#64748b; display:block;"><?= htmlspecialchars($patient['height_cm'] ?: '165') ?> cm</span>
              </div>
            </div>
          </div>

          <!-- Card 2: Chronic Conditions -->
          <div style="background:#fff; border-radius:8px; padding:24px; border:1px solid #e2e8f0;">
            <h3 style="font-size:1.1rem; color:#1e293b; margin-bottom:18px; display:flex; align-items:center; gap:8px;">
              <i class="fa-solid fa-notes-medical text-blue"></i> Chronic Conditions &amp; Diagnoses
            </h3>
            <?php if (!empty($patient['chronic_conditions'])): ?>
              <div style="display:flex; flex-direction:column; gap:10px;">
                <?php foreach ($patient['chronic_conditions'] as $cc): ?>
                  <div style="display:flex; justify-content:space-between; align-items:center; background:#f8fafc; padding:10px 14px; border-radius:6px; border-left:4px solid #0284c7;">
                    <strong style="font-size:0.95rem; color:#0f172a;"><?= htmlspecialchars($cc['condition_name']) ?></strong>
                    <span style="font-size:0.8rem; color:#64748b;">Diagnosed: <?= htmlspecialchars($cc['diagnosed_date'] ?: 'Historical') ?></span>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php else: ?>
              <p style="color:#64748b; font-size:0.9rem;">No chronic conditions documented for this patient.</p>
            <?php endif; ?>
          </div>

          <!-- Card 3: Allergies & Sensitivities -->
          <div style="background:#fff; border-radius:8px; padding:24px; border:1px solid #e2e8f0;">
            <h3 style="font-size:1.1rem; color:#1e293b; margin-bottom:18px; display:flex; align-items:center; gap:8px;">
              <i class="fa-solid fa-triangle-exclamation text-red"></i> Documented Allergies
            </h3>
            <?php if (!empty($patient['allergies'])): ?>
              <div style="display:flex; flex-direction:column; gap:10px;">
                <?php foreach ($patient['allergies'] as $al): ?>
                  <div style="display:flex; justify-content:space-between; align-items:center; background:#fef2f2; padding:10px 14px; border-radius:6px; border-left:4px solid #dc2626;">
                    <strong style="font-size:0.95rem; color:#991b1b;"><?= htmlspecialchars($al['allergy_name']) ?></strong>
                    <span style="font-size:0.75rem; background:#fee2e2; color:#991b1b; font-weight:700; padding:2px 8px; border-radius:10px;">
                      <?= strtoupper(htmlspecialchars($al['severity'] ?? 'MODERATE')) ?>
                    </span>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php else: ?>
              <p style="color:#64748b; font-size:0.9rem;">No allergies recorded.</p>
            <?php endif; ?>
          </div>

          <!-- Card 4: Emergency Contacts & Registered Blood Donors -->
          <div style="background:#fff; border-radius:8px; padding:24px; border:1px solid #e2e8f0;">
            <h3 style="font-size:1.1rem; color:#1e293b; margin-bottom:18px; display:flex; align-items:center; gap:8px;">
              <i class="fa-solid fa-address-book text-emerald"></i> Emergency Contacts &amp; Donor Matches
            </h3>
            <?php if (!empty($patient['contacts'])): ?>
              <div style="margin-bottom: 14px;">
                <?php foreach ($patient['contacts'] as $c): ?>
                  <div style="font-size:0.9rem; color:#334155; margin-bottom:6px;">
                    <strong><?= htmlspecialchars($c['label']) ?>:</strong> <?= htmlspecialchars($c['value']) ?>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php else: ?>
              <p style="color:#64748b; font-size:0.9rem;">Emergency Contact: <?= htmlspecialchars($patient['emergency_contact_name'] ?? 'Family Contact') ?> (<?= htmlspecialchars($patient['emergency_contact_phone'] ?? $patient['phone'] ?? '+880 1800-000000') ?>)</p>
            <?php endif; ?>

            <?php if (!empty($patient['donors'])): ?>
              <div style="margin-top:10px; border-top:1px dashed #e2e8f0; padding-top:10px;">
                <span style="font-size:0.8rem; font-weight:700; color:#475569;">PLEDGED BLOOD DONORS:</span>
                <?php foreach ($patient['donors'] as $dn): ?>
                  <div style="font-size:0.85rem; color:#0f172a; margin-top:4px;">
                    &bull; <?= htmlspecialchars($dn['donor_name']) ?> (Group: <?= htmlspecialchars($dn['donor_blood_group']) ?>) - <?= htmlspecialchars($dn['donor_phone']) ?>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>

        </div>

      </main>
    </div>
  </div>

</body>
</html>
