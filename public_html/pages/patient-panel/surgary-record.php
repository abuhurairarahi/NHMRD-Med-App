<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <title>Surgery & Procedure Records - NHMRD</title>
  <link rel="stylesheet" href="../../assets/css/patient-panel/surgary-record.css">
  <link rel="stylesheet" href="../../assets/css/default-structure.css">
  <link rel="stylesheet" href="../../assets/css/patient-panel/features/patient-header.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

  <div class="app-container">
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
        <a href="prescription-record.php" class="nav-item">
          <i class="fa-solid fa-file-prescription"></i>
          <span>Prescription Records</span>
        </a>
        <a href="surgary-record.php" class="nav-item active">
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

    <div class="main-wrapper">
      <header class="patient-header">
        <div class="search-bar">
          <i class="fa-solid fa-magnifying-glass"></i>
          <input type="text" placeholder="Search Queries" oninput="handleGlobalSearch(event)">
        </div>

        <div class="header-right">
          <button class="icon-btn"><i class="fa-regular fa-bell"></i></button>
          <div class="user-badge-avatar">SR</div>
          <span class="user-name">Shishir Rahaman</span>
        </div>
      </header>

      <main class="content-body">

        <div class="user-banner">
          <div class="user-info-left">
            <div class="user-photo">
              <i class="fa-solid fa-user"></i>
            </div>
            <div class="user-details">
              <div class="name-badge-row">
                <h2>Shishir Rahaman</h2>
                <span class="verified-pill">Verified Record</span>
              </div>
              <p class="user-ids">
                Patient ID: #48291 &bull; NID: 0123456789 &bull; <span class="blood-group">Blood Group: AB+</span>
              </p>
            </div>
          </div>
          <div class="banner-actions">
            <button class="btn-secondary" onclick="handleExportLog()"><i class="fa-solid fa-arrow-up-from-bracket"></i> Export Log</button>
            <button class="btn-primary" onclick="handleUploadReport()"><i class="fa-solid fa-file-medical"></i> Upload External Report</button>
          </div>
        </div>

        <div class="section-top-bar">
          <div>
            <h1>Surgery & Procedure Records</h1>
            <p class="subtitle">Comprehensive operative history, clinical anesthesia notes, and surgical clearances.</p>
          </div>
          <div class="filter-tabs">
  <button class="tab-btn active" data-filter="all" onclick="handleTabFilter(this)">All Surgeries (2)</button>
  <button class="tab-btn" data-filter="inpatient" onclick="handleTabFilter(this)">Inpatient (2)</button>
  <button class="tab-btn" data-filter="outpatient" onclick="handleTabFilter(this)">Outpatient (0)</button>
  <button class="tab-btn" data-filter="pre-op" onclick="handleTabFilter(this)">Pre-op Assessments</button>
</div>
        </div>

        <div class="vitals-grid">
          <div class="vital-card">
            <div class="vital-icon blood"><i class="fa-solid fa-droplet"></i></div>
            <div class="vital-info">
              <span class="vital-label">BLOOD GROUP</span>
              <strong class="vital-val">AB Rh+</strong>
              <span class="vital-sub">Universal recipient</span>
            </div>
          </div>

          <div class="vital-card">
            <div class="vital-icon risk"><i class="fa-solid fa-shield-heart"></i></div>
            <div class="vital-info">
              <span class="vital-label">ANESTHESIA RISK</span>
              <strong class="vital-val">ASA Class I</strong>
              <span class="vital-sub">Normal healthy status</span>
            </div>
          </div>

          <div class="vital-card">
            <div class="vital-icon allergy"><i class="fa-solid fa-triangle-exclamation"></i></div>
            <div class="vital-info">
              <span class="vital-label">ALLERGIES / ADVERSE</span>
              <strong class="vital-val">NKDA</strong>
              <span class="vital-sub">No known drug allergies</span>
            </div>
          </div>

          <div class="vital-card">
            <div class="vital-icon airway"><i class="fa-solid fa-lungs"></i></div>
            <div class="vital-info">
              <span class="vital-label">INTUBATION HISTORY</span>
              <strong class="vital-val">Mallampati I</strong>
              <span class="vital-sub">Easy airway access verified</span>
            </div>
          </div>
        </div>

        <div class="surgical-records-list">
<div class="card procedure-card">
          <div class="procedure-header">
            <div class="proc-title-left">
              <div class="proc-icon blue-bg"><i class="fa-solid fa-briefcase-medical"></i></div>
              <div>
                <div class="proc-title-line">
                  <h3>Laparoscopic Cholecystectomy</h3>
                  <span class="sub-name">(Gallbladder Removal)</span>
                </div>
                <p class="proc-meta-id">Procedure ID: SUR-2024-8841 &bull; General Surgery Department</p>
              </div>
            </div>
            <div class="proc-tags">
              <span class="status-badge success"><i class="fa-solid fa-circle-check"></i> Successful / Healed</span>
              <span class="type-badge">Inpatient</span>
            </div>
          </div>

          <div class="meta-columns-grid">
            <div class="meta-col">
              <span class="col-label">DATE OF OPERATION</span>
              <strong class="col-val">14-02-2024</strong>
              <span class="col-sub">09:30 AM &bull; 75 mins</span>
            </div>
            <div class="meta-col">
              <span class="col-label">HOSPITAL & OT ROOM</span>
              <strong class="col-val">LABAID Specialized Hospital</strong>
              <span class="col-sub">OT Suite 04 (Main Wing)</span>
            </div>
            <div class="meta-col">
              <span class="col-label">LEAD SURGEON</span>
              <strong class="col-val">Prof. Dr. Farhana Ahmed</strong>
              <span class="col-sub">FRCS, Laparoscopic Specialist</span>
            </div>
            <div class="meta-col">
              <span class="col-label">DISCHARGE & STAY</span>
              <strong class="col-val">16-02-2024</strong>
              <span class="col-sub">48 hrs total inpatient care</span>
            </div>
          </div>

          <div class="proc-body-grid">
            <div class="narrative-box">
              <h4><i class="fa-solid fa-file-lines"></i> Surgical Narrative & Post-Op Recovery</h4>
              <p>
                Elective laparoscopic cholecystectomy conducted under general endotracheal anesthesia. Standard four-port entry achieved with pneumoperitoneum at 12 mmHg. Gallbladder displayed chronic mild wall thickening secondary to recurrent cholelithiasis. Calot's triangle successfully dissected; cystic duct and cystic artery doubly clipped and severed without bile spillage. Complete hemostasis verified in the gallbladder bed. Uneventful laparoscopic extraction via epigastric port. Port sites closed in anatomical layers. Patient ambulated within 6 hours post-op, tolerated solid nutrition on Day 1, and made an uneventful complete clinical recovery.
              </p>
              <div class="narrative-pills">
                <span class="info-pill"><i class="fa-regular fa-user"></i> Anesthesiologist: Dr. K. M. Rahman</span>
                <span class="info-pill"><i class="fa-solid fa-scissors"></i> Incisions: 4 Minimal Portals</span>
                <span class="info-pill"><i class="fa-solid fa-microscope"></i> Histopathology: Benign Chronic Cholecystitis</span>
              </div>
            </div>

            <div class="documents-box">
              <h4><i class="fa-solid fa-shield-check"></i> Verified Documents (3)</h4>
              <div class="doc-list">
                <div class="doc-item">
                  <i class="fa-regular fa-file-pdf doc-icon"></i>
                  <div class="doc-info">
                    <strong>Operative_Note_SUR8841.pdf</strong>
                    <p>1.4 MB &bull; Signed by Dr. Ahmed</p>
                  </div>
                  <button class="doc-dl-btn" onclick="handleDownloadDocument('Operative_Note_SUR8841.pdf')"><i class="fa-solid fa-download"></i></button>
                </div>

                <div class="doc-item">
                  <i class="fa-regular fa-file-pdf doc-icon"></i>
                  <div class="doc-info">
                    <strong>Discharge_Summary_Signed.pdf</strong>
                    <p>890 KB &bull; Hospital Record</p>
                  </div>
                  <button class="doc-dl-btn" onclick="handleDownloadDocument('Discharge_Summary_Signed.pdf')"><i class="fa-solid fa-download"></i></button>
                </div>

                <div class="doc-item">
                  <i class="fa-regular fa-file-code doc-icon"></i>
                  <div class="doc-info">
                    <strong>PostOp_Ultrasound_DICOM.pd</strong>
                    <p>4.2 MB &bull; High-res Scans</p>
                  </div>
                  <button class="doc-dl-btn" onclick="handleViewDocument('PostOp_Ultrasound_DICOM.pdf')"><i class="fa-solid fa-eye"></i></button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="card procedure-card">
          <div class="procedure-header">
            <div class="proc-title-left">
              <div class="proc-icon blue-bg"><i class="fa-solid fa-notes-medical"></i></div>
              <div>
                <div class="proc-title-line">
                  <h3>Emergency Open Appendectomy</h3>
                  <span class="sub-name">(Appendix Removal)</span>
                </div>
                <p class="proc-meta-id">Procedure ID: SUR-2018-0219 &bull; Trauma & Emergency Surgery</p>
              </div>
            </div>
            <div class="proc-tags">
              <span class="status-badge dark-blue">Historical / Resolved</span>
              <span class="type-badge emergency">Emergency</span>
            </div>
          </div>

          <div class="meta-columns-grid">
            <div class="meta-col">
              <span class="col-label">DATE OF OPERATION</span>
              <strong class="col-val">10-06-2018</strong>
              <span class="col-sub">02:15 AM &bull; 50 mins</span>
            </div>
            <div class="meta-col">
              <span class="col-label">HOSPITAL & FACILITY</span>
              <strong class="col-val">Dhaka Medical College Hospital</strong>
              <span class="col-sub">Emergency Operating Theatre 2</span>
            </div>
            <div class="meta-col">
              <span class="col-label">LEAD SURGEON</span>
              <strong class="col-val">Dr. M. A. Karim</strong>
              <span class="col-sub">MS (General Surgery), Associate Prof.</span>
            </div>
            <div class="meta-col">
              <span class="col-label">DISCHARGE & STAY</span>
              <strong class="col-val">13-06-2018</strong>
              <span class="col-sub">72 hrs inpatient post-op care</span>
            </div>
          </div>

          <div class="proc-body-grid">
            <div class="narrative-box">
              <h4><i class="fa-solid fa-file-lines"></i> Surgical Narrative & Pathological Outcome</h4>
              <p>
                Emergency McBurney incision performed for acute suppurative appendicitis with localized peritoneal reaction. Appendiceal artery ligated, base transfixed and inverted with purse-string suture. Peritoneal cavity washed with warm saline. No evidence of perforation or abscess. Wound closed with subcuticular sutures. Smooth postoperative period with complete resolution of symptoms and uneventful stitch removal at day 8.
              </p>
              <div class="narrative-pills">
                <span class="info-pill"><i class="fa-solid fa-scissors"></i> Approach: Open McBurney Incision (4.5 cm)</span>
                <span class="info-pill"><i class="fa-regular fa-circle-check"></i> Current Status: Fully Resolved (6+ Years)</span>
              </div>
            </div>

            <div class="documents-box">
              <h4><i class="fa-solid fa-shield-check"></i> Verified Documents (2)</h4>
              <div class="doc-list">
                <div class="doc-item">
                  <i class="fa-regular fa-file-pdf doc-icon"></i>
                  <div class="doc-info">
                    <strong>DMCH_Emergency_Surgery_Re</strong>
                    <p>2.1 MB &bull; Archival Copy</p>
                  </div>
                  <button class="doc-dl-btn" onclick="handleDownloadDocument('DMCH_Emergency_Surgery_Re')"><i class="fa-solid fa-download"></i></button>
                </div>

                <div class="doc-item">
                  <i class="fa-regular fa-file-pdf doc-icon"></i>
                  <div class="doc-info">
                    <strong>Discharge_Certificate_2018.pdf</strong>
                    <p>540 KB &bull; Certified Archival</p>
                  </div>
                  <button class="doc-dl-btn" onclick="handleDownloadDocument('Discharge_Certificate_2018.pdf')"><i class="fa-solid fa-download"></i></button>
                </div>
              </div>
            </div>
          </div>
        </div>

      </div>

      </main>
    </div>
  </div>
  <script src="../../assets/js/paitent/common.js"></script>
  <script src="../../assets/js/paitent/surgary.js"></script>

  <!-- Bootstrap Bundle JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
