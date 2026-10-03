<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

  <title>NHMRD - Dashboard</title>
  <link rel="stylesheet" href="../../assets/css/patient-panel/dashboard.css">
  <link rel="stylesheet" href="../../assets/css/default-structure.css">
  <link rel="stylesheet" href="../../assets/css/patient-panel/features/patient-header.css">
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
        <a href="dashboard.php" class="nav-item active">
          <i class="fa-solid fa-table-cells-large"></i>
          <span>Dashboard</span>
        </a>
        <a href="prescription-record.php" class="nav-item">
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
        <a href="medical-test-req.php" class="nav-item">
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
        <button class="logout-btn" onclick="handleLogout()">
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
          <input type="text" placeholder="Search Queries" oninput="filterDashboardContent(event)">
        </div>

        <div class="header-right">
          <button class="icon-btn" onclick="handleNotificationClick()"><i class="fa-regular fa-bell"></i></button>
          <div class="user-badge-avatar">FI</div>
          <div class="user-info-text">
            <span class="user-name">Farhana Islam</span>
            <span class="patient-id">Patient ID #2042122004</span>
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
                      <path
                        d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
                    </svg>
                  </div>
                </div>
              </div>
            </div>

            <div class="patient-main-details">
              <div class="patient-title-row">
                <h2>Farhana Islam</h2>
                <span class="badge-status">Citizen Patient</span>
              </div>
              <p class="subtitle-text">National Health Identification &bull; Regular Outpatient Profile</p>
            </div>

            <div class="header-actions">
              <button class="btn-secondary" onclick="navigateToUpdateProfile()">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8" />
                  <polyline points="16 6 12 2 8 6" />
                  <line x1="12" y1="2" x2="12" y2="15" />
                </svg>
                Update Data
              </button>
              <button class="btn-primary" onclick="printPatientCard()">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <polyline points="6 9 6 2 18 2 18 9" />
                  <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2" />
                  <rect x="6" y="14" width="12" height="8" />
                </svg>
                Print Card
              </button>
            </div>
          </div>

          <div class="patient-info-grid">
            <div class="info-item"><span class="label">NID:</span> <span class="value">1992042122004</span></div>
            <div class="info-item"><span class="label">Email:</span> <span class="value">farhana.islam@nhmrd.gov.bd</span>
            </div>
            <div class="info-item"><span class="label">Phone:</span> <span class="value">+880 1712-345678</span></div>
            <div class="info-item"><span class="label">Gender:</span> <span class="value">Female</span></div>
            <div class="info-item"><span class="label">Date of Birth:</span> <span class="value">1992-05-14</span></div>
            <div class="info-item"><span class="label">Blood Group:</span> <span class="value text-danger">B+</span>
            </div>
          </div>
        </div>

        <!-- 4 Key Stat Metrics -->
        <div class="stats-grid">
          <div class="card stat-card">
            <div class="stat-icon-wrapper icon-blue">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                <polyline points="14 2 14 8 20 8" />
                <line x1="16" y1="13" x2="8" y2="13" />
                <line x1="16" y1="17" x2="8" y2="17" />
              </svg>
            </div>
            <div>
              <div class="stat-number">12</div>
              <div class="stat-label">PRESCRIPTIONS</div>
            </div>
          </div>

          <div class="card stat-card">
            <div class="stat-icon-wrapper icon-cyan">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M10 2v7.31M14 2v7.31M8.5 2h7M14 9.3a6.5 6.5 0 1 1-4 0" />
              </svg>
            </div>
            <div>
              <div class="stat-number">24</div>
              <div class="stat-label">LAB TESTS</div>
            </div>
          </div>

          <div class="card stat-card">
            <div class="stat-icon-wrapper icon-pink">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M18 2t2 2v16t-2 2H6t-2-2V4t2-2h12z" />
                <line x1="12" y1="8" x2="12" y2="16" />
                <line x1="8" y1="12" x2="16" y2="12" />
              </svg>
            </div>
            <div>
              <div class="stat-number">4</div>
              <div class="stat-label">VACCINES</div>
            </div>
          </div>

          <div class="card stat-card">
            <div class="stat-icon-wrapper icon-indigo">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path
                  d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z" />
                <path d="M12 5v14" />
                <path d="M5 12h14" />
              </svg>
            </div>
            <div>
              <div class="stat-number">1</div>
              <div class="stat-label">MAJOR SURGERY</div>
            </div>
          </div>
        </div>

        <!-- 2 Main Data Panels -->
        <div class="panels-grid">

          <!-- Left Panel: Recent Appointments -->
          <div class="card panel-card">
            <div class="panel-header">
              <div class="panel-title">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <rect x="3" y="4" width="18" height="18" rx="2" ry="2" />
                  <line x1="16" y1="2" x2="16" y2="6" />
                  <line x1="8" y1="2" x2="8" y2="6" />
                  <line x1="3" y1="10" x2="21" y2="10" />
                </svg>
                Recent Appointments
              </div>
              <a href="#" class="link-btn">View All &rarr;</a>
            </div>

            <div class="appointment-list">
              <!-- Item 1: Due -->
              <div class="appointment-item">
                <div class="appointment-info">
                  <div class="meta-date">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <circle cx="12" cy="12" r="10" />
                      <polyline points="12 6 12 12 16 14" />
                    </svg>
                    Appointment Date: 13-09-2026 . 19:00 PM
                  </div>
                  <h3>Dr. Farhana Ahmed</h3>
                  <div class="hospital-name">Hospital: <strong>LABAID Specialized Hospital</strong></div>
                </div>
                <div class="item-status">
                  <span class="badge-status-pill badge-due">Due</span>
                  <span class="chevron-icon">&rsaquo;</span>
                </div>
              </div>

              <!-- Item 2: Expired -->
              <div class="appointment-item">
                <div class="appointment-info">
                  <div class="meta-date">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <rect x="3" y="4" width="18" height="18" rx="2" ry="2" />
                      <line x1="16" y1="2" x2="16" y2="6" />
                      <line x1="8" y1="2" x2="8" y2="6" />
                      <line x1="3" y1="10" x2="21" y2="10" />
                    </svg>
                    Appointment Date: 10-09-2026 . 19:00 PM
                  </div>
                  <h3>Dr. Farhana Ahmed</h3>
                  <div class="hospital-name">Hospital: <strong>LABAID Specialized Hospital</strong></div>
                </div>
                <div class="item-status">
                  <span class="badge-status-pill badge-expired">Expired</span>
                  <span class="chevron-icon">&rsaquo;</span>
                </div>
              </div>

              <!-- Item 3: Visited -->
              <div class="appointment-item">
                <div class="appointment-info">
                  <div class="meta-date">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <circle cx="12" cy="12" r="10" />
                      <polyline points="12 6 12 12 16 14" />
                    </svg>
                    Appointment Date: 06-09-2026 . 19:00 PM
                  </div>
                  <h3>Dr. Farhana Ahmed</h3>
                  <div class="hospital-name">Hospital: <strong>LABAID Specialized Hospital</strong></div>
                </div>
                <div class="item-status">
                  <span class="badge-status-pill badge-visited">Visited</span>
                  <span class="chevron-icon">&rsaquo;</span>
                </div>
              </div>
            </div>

            <div class="panel-footer-suggested">
              <span>Next suggested consult: 18-09-2026</span>
              <a href="#" class="link-btn-bold">+ Book Consultation</a>
            </div>
          </div>

          <!-- Right Panel: Recent Medical Activities -->
          <div class="card panel-card">
            <div class="panel-header">
              <div class="panel-title">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                  <polyline points="14 2 14 8 20 8" />
                </svg>
                Recent Medical Activities
              </div>
              <a href="#" class="link-btn">All Records &rarr;</a>
            </div>

            <div class="activity-list">
              <!-- Item 1 -->
              <div class="activity-item">
                <div class="activity-info">
                  <div class="meta-date">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                      <polyline points="14 2 14 8 20 8" />
                    </svg>
                    Publish Date: 11-09-2026
                  </div>
                  <h3>Liver Function Test (LFT) Report</h3>
                  <div class="doctor-name">Prescribed by: <strong>Dr. Farhana Ahmed</strong></div>
                </div>
                <div class="action-buttons">
                  <button class="icon-action-btn" title="Download">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                      <polyline points="7 10 12 15 17 10" />
                      <line x1="12" y1="15" x2="12" y2="3" />
                    </svg>
                  </button>
                  <button class="icon-action-btn primary" title="View">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                      <circle cx="12" cy="12" r="3" />
                    </svg>
                  </button>
                </div>
              </div>

              <!-- Item 2 -->
              <div class="activity-item">
                <div class="activity-info">
                  <div class="meta-date">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" />
                    </svg>
                    Publish Date: 07-09-2026
                  </div>
                  <h3>Hepatitis Report</h3>
                  <div class="doctor-name">Prescribed by: <strong>Dr. Farhana Ahmed</strong></div>
                </div>
                <div class="action-buttons">
                  <button class="icon-action-btn" title="Download">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                      <polyline points="7 10 12 15 17 10" />
                      <line x1="12" y1="15" x2="12" y2="3" />
                    </svg>
                  </button>
                  <button class="icon-action-btn primary" title="View">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                      <circle cx="12" cy="12" r="3" />
                    </svg>
                  </button>
                </div>
              </div>

              <!-- Item 3 -->
              <div class="activity-item">
                <div class="activity-info">
                  <div class="meta-date">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                      <polyline points="14 2 14 8 20 8" />
                    </svg>
                    Publish Date: 07-09-2026
                  </div>
                  <h3>Chronic Liver Disease Report</h3>
                  <div class="doctor-name">Prescribed by: <strong>Dr. Farhana Ahmed</strong></div>
                </div>
                <div class="action-buttons">
                  <button class="icon-action-btn" title="Download">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                      <polyline points="7 10 12 15 17 10" />
                      <line x1="12" y1="15" x2="12" y2="3" />
                    </svg>
                  </button>
                  <button class="icon-action-btn primary" title="View">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                      <circle cx="12" cy="12" r="3" />
                    </svg>
                  </button>
                </div>
              </div>
            </div>

            <div class="panel-footer-sync">
              <span class="text-muted">Certified by DGHS Clinical Registry</span>
              <a href="#" class="link-btn-bold" onclick="syncExternalLabs()">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67" />
                </svg>
                Sync External Labs
              </a>
            </div>
          </div>

        </div>

      </main>
    </div>

  </div>

  <!-- Add this at the bottom of Dashboard.php -->
  <script src="../../assets/js/paitent/common.js"></script>
  <script src="../../assets/js/paitent/dashboard.js"></script>


  <!-- Bootstrap Bundle JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
