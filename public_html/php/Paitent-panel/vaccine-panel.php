<?php
// Load Patient Info Controller
$data = require_once __DIR__ . '/../../controllers/patient/PatientInfoController.php';

$patient            = $data['patient'];
$userInitials       = $data['userInitials'];
$totalPrescriptions = $data['totalPrescriptions'];
$totalLabTests      = $data['totalLabTests'];
$totalVaccines      = $data['totalVaccines'];
$totalSurgeries     = $data['totalSurgeries'];
$allergies          = $data['allergies'];
$conditions         = $data['conditions'];
$contacts           = $data['contacts'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>NHMRD - Patient Profile & Information</title>
  <link rel="stylesheet" href="/public_html/assets/css/patient-panel/dashboard.css">
  <link rel="stylesheet" href="/public_html/assets/css/default-structure.css">
  <link rel="stylesheet" href="/public_html/assets/css/patient-panel/features/patient-header.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
        <button class="logout-btn" id="logoutBtn">
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
          <input type="text" id="dashboardSearch" placeholder="Search Profile Information...">
        </div>

        <div class="header-right">
          <button class="icon-btn" id="notificationBtn"><i class="fa-regular fa-bell"></i></button>
          
          <div class="user-badge-avatar"><?= htmlspecialchars($userInitials) ?></div>
          
          <div class="user-info-text">
            <span class="user-name"><?= htmlspecialchars($patient['full_name']) ?></span>
            <span class="patient-id">Patient ID #<?= htmlspecialchars($patient['user_uid']) ?></span>
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
              <p class="subtitle-text">National Health Identification &bull; Health Profile</p>
            </div>

            <div class="header-actions">
              <button class="btn-primary" id="editProfileBtn">
                <i class="fa-solid fa-pen-to-square"></i> Edit Profile
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

        <!-- Stat Counter Metrics -->
        <div class="stats-grid">
          <div class="card stat-card">
            <div class="stat-icon-wrapper icon-blue"><i class="fa-solid fa-file-prescription"></i></div>
            <div>
              <div class="stat-number"><?= $totalPrescriptions ?></div>
              <div class="stat-label">PRESCRIPTIONS</div>
            </div>
          </div>

          <div class="card stat-card">
            <div class="stat-icon-wrapper icon-cyan"><i class="fa-solid fa-flask"></i></div>
            <div>
              <div class="stat-number"><?= $totalLabTests ?></div>
              <div class="stat-label">LAB TESTS</div>
            </div>
          </div>

          <div class="card stat-card">
            <div class="stat-icon-wrapper icon-pink"><i class="fa-solid fa-syringe"></i></div>
            <div>
              <div class="stat-number"><?= $totalVaccines ?></div>
              <div class="stat-label">VACCINES</div>
            </div>
          </div>

          <div class="card stat-card">
            <div class="stat-icon-wrapper icon-indigo"><i class="fa-solid fa-heart-pulse"></i></div>
            <div>
              <div class="stat-number"><?= $totalSurgeries ?></div>
              <div class="stat-label">MAJOR SURGERY</div>
            </div>
          </div>
        </div>

        <!-- Profile Details Update Grid -->
        <div class="panels-grid" style="margin-top:20px;">
          <div class="card panel-card">
            <div class="panel-header">
              <div class="panel-title">
                <i class="fa-solid fa-user-gear"></i> Personal Contact & Emergency Info
              </div>
            </div>

            <form action="" method="POST" style="padding: 15px;">
              <input type="hidden" name="action" value="update_profile">
              
              <div style="margin-bottom: 12px;">
                <label style="font-weight: 600; font-size: 13px;">Primary Phone</label>
                <input type="text" name="phone" value="<?= htmlspecialchars($patient['phone'] ?? '') ?>" style="width:100%; padding: 8px; margin-top:4px; border: 1px solid #cbd5e1; border-radius: 6px;">
              </div>

              <div style="margin-bottom: 12px;">
                <label style="font-weight: 600; font-size: 13px;">Alt / Emergency Phone</label>
                <input type="text" name="alt_phone" value="<?= htmlspecialchars($patient['alt_phone'] ?? '') ?>" style="width:100%; padding: 8px; margin-top:4px; border: 1px solid #cbd5e1; border-radius: 6px;">
              </div>

              <div style="margin-bottom: 12px;">
                <label style="font-weight: 600; font-size: 13px;">Address</label>
                <textarea name="address" style="width:100%; padding: 8px; margin-top:4px; border: 1px solid #cbd5e1; border-radius: 6px;" rows="2"><?= htmlspecialchars($patient['address'] ?? '') ?></textarea>
              </div>

              <hr style="border: 0; border-top: 1px solid #e2e8f0; margin: 15px 0;">

              <div style="margin-bottom: 12px;">
                <label style="font-weight: 600; font-size: 13px;">Emergency Contact Name</label>
                <input type="text" name="emergency_contact_name" value="<?= htmlspecialchars($patient['emergency_contact_name'] ?? '') ?>" style="width:100%; padding: 8px; margin-top:4px; border: 1px solid #cbd5e1; border-radius: 6px;">
              </div>

              <div style="margin-bottom: 12px;">
                <label style="font-weight: 600; font-size: 13px;">Emergency Contact Relation</label>
                <input type="text" name="emergency_contact_relation" value="<?= htmlspecialchars($patient['emergency_contact_relation'] ?? '') ?>" style="width:100%; padding: 8px; margin-top:4px; border: 1px solid #cbd5e1; border-radius: 6px;">
              </div>

              <div style="margin-bottom: 12px;">
                <label style="font-weight: 600; font-size: 13px;">Emergency Contact Phone</label>
                <input type="text" name="emergency_contact_phone" value="<?= htmlspecialchars($patient['emergency_contact_phone'] ?? '') ?>" style="width:100%; padding: 8px; margin-top:4px; border: 1px solid #cbd5e1; border-radius: 6px;">
              </div>

              <button type="submit" class="btn-primary" style="margin-top: 10px; width:100%;">Save Changes</button>
            </form>
          </div>

          <!-- Medical Alerts & Summary Panel -->
          <div class="card panel-card">
            <div class="panel-header">
              <div class="panel-title">
                <i class="fa-solid fa-notes-medical" style="color:#e11d48;"></i> Known Allergies & Conditions
              </div>
            </div>
            
            <div style="padding: 15px;">
              <h4 style="margin-bottom: 8px; color: #334155;">Allergies</h4>
              <?php if (empty($allergies)): ?>
                <p style="color: #64748b; font-size: 13px;">No recorded allergies.</p>
              <?php else: ?>
                <ul style="padding-left: 20px; font-size: 14px;">
                  <?php foreach ($allergies as $item): ?>
                    <li><strong><?= htmlspecialchars($item['allergy_name']) ?></strong> (Severity: <?= htmlspecialchars($item['severity']) ?>)</li>
                  <?php endforeach; ?>
                </ul>
              <?php endif; ?>

              <h4 style="margin-top: 20px; margin-bottom: 8px; color: #334155;">Chronic Conditions</h4>
              <?php if (empty($conditions)): ?>
                <p style="color: #64748b; font-size: 13px;">No recorded chronic conditions.</p>
              <?php else: ?>
                <ul style="padding-left: 20px; font-size: 14px;">
                  <?php foreach ($conditions as $cond): ?>
                    <li><strong><?= htmlspecialchars($cond['condition_name']) ?></strong></li>
                  <?php endforeach; ?>
                </ul>
              <?php endif; ?>
            </div>
          </div>
        </div>

      </main>
    </div>
  </div>

  <script src="/public_html/assets/js/patient/patient-info.js"></script>
</body>
</html>