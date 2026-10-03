<?php
require_once __DIR__ . 'public_html/api/db.php';

$doctor_id = 1; // Mock logged-in doctor

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'batch_renew') {
    $stmt_renew = $pdo->prepare("
        UPDATE prescriptions 
        SET next_visit_date = DATE_ADD(CURDATE(), INTERVAL 90 DAY), updated_at = NOW()
        WHERE doctor_id = :doctor_id AND is_long_term = 1 AND status = 'active'
    ");
    $stmt_renew->execute(['doctor_id' => $doctor_id]);
    $batch_message = $stmt_renew->rowCount() . " prescriptions successfully renewed.";
}

// Metric: MONITORED COHORT (Unique patients with long-term prescriptions by this doctor)
$stmt_cohort = $pdo->prepare("
    SELECT COUNT(DISTINCT patient_id) as total_monitored 
    FROM prescriptions 
    WHERE doctor_id = :doctor_id AND is_long_term = 1
");
$stmt_cohort->execute(['doctor_id' => $doctor_id]);
$cohort = $stmt_cohort->fetch();

// Metric: HBA1C <7.0% CONTROL (Calculated from lab test results containing HbA1c)
$stmt_hba1c = $pdo->prepare("
    SELECT ROUND((SUM(CASE WHEN CAST(r.remarks AS DECIMAL(4,1)) < 7.0 THEN 1 ELSE 0 END) / NULLIF(COUNT(*), 0)) * 100, 1) as hba1c_control 
    FROM lab_test_results r 
    JOIN lab_test_order_items i ON r.order_item_id = i.id 
    JOIN lab_test_catalog c ON i.test_id = c.test_id 
    JOIN lab_test_orders o ON i.order_id = o.order_id
    WHERE o.doctor_id = :doctor_id AND c.test_name LIKE '%HbA1c%'
");
$stmt_hba1c->execute(['doctor_id' => $doctor_id]);
$hba1c = $stmt_hba1c->fetch();

// Metric: BP PROTOCOL TARGET (Systolic < 130 derived from patients table)
$stmt_bp = $pdo->prepare("
    SELECT ROUND((SUM(CASE WHEN CAST(SUBSTRING_INDEX(p.blood_pressure, '/', 1) AS UNSIGNED) < 130 THEN 1 ELSE 0 END) / NULLIF(COUNT(*), 0)) * 100, 1) as bp_control 
    FROM patients p 
    JOIN prescriptions rx ON p.patient_id = rx.patient_id 
    WHERE rx.doctor_id = :doctor_id AND rx.is_long_term = 1
");
$stmt_bp->execute(['doctor_id' => $doctor_id]);
$bp = $stmt_bp->fetch();

// Metric: POLYPHARMACY REVIEWS (Prescriptions with fewer than 5 medications)
$stmt_poly = $pdo->prepare("
    SELECT ROUND((SUM(CASE WHEN med_counts.total_meds < 5 THEN 1 ELSE 0 END) / NULLIF(COUNT(*),0)) * 100, 1) as polypharmacy_safe 
    FROM (SELECT prescription_id, COUNT(*) as total_meds FROM prescription_medications GROUP BY prescription_id) med_counts 
    JOIN prescriptions rx ON med_counts.prescription_id = rx.prescription_id 
    WHERE rx.doctor_id = :doctor_id AND rx.status = 'active'
");
$stmt_poly->execute(['doctor_id' => $doctor_id]);
$polypharmacy = $stmt_poly->fetch();

$search = $_GET['search'] ?? '';
$filter = $_GET['filter'] ?? ''; // Values: 'cardiovascular', 'endocrine', 'respiratory'
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 6;
$offset = ($page - 1) * $limit;

// Mapping tabs to diagnostic keywords (since exact ICD-10 columns aren't in the schema natively)
$filter_keyword = '';
if ($filter === 'cardiovascular') $filter_keyword = '%Hypertension%';
if ($filter === 'endocrine') $filter_keyword = '%Diabetes%';
if ($filter === 'respiratory') $filter_keyword = '%Asthma%';

// Count total records for pagination
$count_sql = "
    SELECT COUNT(DISTINCT p.patient_id) as total_records
    FROM patients p
    JOIN prescriptions rx ON p.patient_id = rx.patient_id
    WHERE rx.doctor_id = :doctor_id AND rx.is_long_term = 1
      AND (p.full_name LIKE :search OR p.health_card_no LIKE :search)
";
if ($filter_keyword) $count_sql .= " AND rx.title LIKE :filter_keyword";

$stmt_count = $pdo->prepare($count_sql);
$stmt_count->bindValue(':doctor_id', $doctor_id);
$stmt_count->bindValue(':search', '%' . $search . '%');
if ($filter_keyword) $stmt_count->bindValue(':filter_keyword', $filter_keyword);
$stmt_count->execute();
$total_records = $stmt_count->fetch()['total_records'];
$total_pages = ceil($total_records / $limit);

// Fetch Paginated Records
$records_sql = "
    SELECT p.patient_id, p.full_name, p.health_card_no, p.dob, p.gender, p.blood_pressure, p.bmi, 
           rx.prescription_id, rx.title as diagnosis,
           (SELECT GROUP_CONCAT(CONCAT(medication_name, ' ', dose_strength) SEPARATOR '<br>') 
            FROM prescription_medications WHERE prescription_id = rx.prescription_id) as regimen
    FROM patients p
    JOIN prescriptions rx ON p.patient_id = rx.patient_id
    WHERE rx.doctor_id = :doctor_id AND rx.is_long_term = 1
      AND (p.full_name LIKE :search OR p.health_card_no LIKE :search)
";
if ($filter_keyword) $records_sql .= " AND rx.title LIKE :filter_keyword";
$records_sql .= " ORDER BY p.patient_id DESC LIMIT :offset, :limit";

$stmt_records = $pdo->prepare($records_sql);
$stmt_records->bindValue(':doctor_id', $doctor_id, PDO::PARAM_INT);
$stmt_records->bindValue(':search', '%' . $search . '%', PDO::PARAM_STR);
if ($filter_keyword) $stmt_records->bindValue(':filter_keyword', $filter_keyword, PDO::PARAM_STR);
$stmt_records->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt_records->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt_records->execute();
$patients = $stmt_records->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>NHMRD - Electronic Clinical Records</title>
  <link rel="stylesheet" href="/public_html/assets/css/doctor-panel/doctor-clinical-service-records.css">
  <link rel="stylesheet" href="/public_html/assets/css/doctor-panel/features/doctor-header.css">
  <link rel="stylesheet" href="/public_html/assets/css/default-structure.css">
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
        <a href="/public_html/pages/Doctor-panel/Dashboard.html" class="nav-item">
          <i class="fa-solid fa-table-cells-large"></i>
          <span>Dashboard</span>
        </a>
        <a href="/public_html/pages/Doctor-panel/doctor-clinical-service-records.html" class="nav-item active">
          <i class="fa-solid fa-notes-medical"></i>
          <span>Clinical Records</span>
        </a>
        <a href="/public_html/pages/Doctor-panel/patient-appointments.html" class="nav-item">
          <i class="fa-solid fa-user-clock"></i>
          <span>Patient Appointments</span>
        </a>
        <a href="/public_html/pages/doctor-panel/doctor-profile.html" class="nav-item">
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
          <input type="text" placeholder="Search patient MRN, name, vitals..." onkeyup="filterTable(this.value)">
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
          <button class="icon-btn" onclick="openNotifications()"><i class="fa-regular fa-bell"></i></button>

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

      <!-- Main Content Body -->
      <main class="content-body">
        <div class="clinical-records-grid">
          <div class="primary-column">

            <div class="page-title-section">
              <div class="title-text">
                <span class="section-subtitle">DIVISION OF INTERNAL MEDICINE & OUTPATIENT PHARMACOTHERAPY</span>
                <h1>Electronic Clinical Records</h1>
              </div>
              <div class="title-actions">
                <!-- Batch Rx Renewal Action -->
                <form method="POST" style="display:inline;">
                    <input type="hidden" name="action" value="batch_renew">
                    <button type="submit" class="btn-secondary"><i class="fa-solid fa-rotate"></i> Batch Rx Renewal</button>
                </form>
              </div>
            </div>
            
            <?php if(isset($batch_message)) echo "<div class='alert alert-success'>$batch_message</div>"; ?>

            <!-- Metrics Grid -->
            <div class="vitals-grid">
              <div class="vital-card">
                <div class="vital-header">
                  <span class="vital-title">MONITORED COHORT</span>
                </div>
                <div class="vital-body">
                  <span class="vital-value"><?php echo number_format($cohort['total_monitored'] ?? 0); ?></span>
                </div>
              </div>

              <div class="vital-card">
                <div class="vital-header">
                  <span class="vital-title">HBA1C &lt;7.0% CONTROL</span>
                </div>
                <div class="vital-body">
                  <span class="vital-value"><?php echo $hba1c['hba1c_control'] ?? '0.0'; ?>%</span>
                </div>
              </div>

              <div class="vital-card">
                <div class="vital-header">
                  <span class="vital-title">BP PROTOCOL TARGET</span>
                </div>
                <div class="vital-body">
                  <span class="vital-value"><?php echo $bp['bp_control'] ?? '0.0'; ?>%</span>
                </div>
              </div>

              <div class="vital-card">
                <div class="vital-header">
                  <span class="vital-title">POLYPHARMACY REVIEWS</span>
                </div>
                <div class="vital-body">
                  <span class="vital-value"><?php echo $polypharmacy['polypharmacy_safe'] ?? '0.0'; ?>%</span>
                </div>
              </div>
            </div>

            <!-- Table Container Section -->
            <div class="records-table-container">
              
              <form method="GET" action="">
                <div class="table-controls">
                  <div class="search-mini">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <!-- Search Input -->
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search by patient MRN, full name...">
                  </div>
                  <button type="submit" class="btn-primary">Search</button>
                </div>

                <!-- Module Filter Tabs based on image_da4fc5.png -->
                <div class="module-tabs filter-tabs">
                  <button type="submit" name="filter" value="" class="tab-btn <?php echo $filter == '' ? 'active' : ''; ?>">All Internal Medicine</button>
                  <button type="submit" name="filter" value="cardiovascular" class="tab-btn <?php echo $filter == 'cardiovascular' ? 'active' : ''; ?>">Cardiovascular (I10-I15)</button>
                  <button type="submit" name="filter" value="endocrine" class="tab-btn <?php echo $filter == 'endocrine' ? 'active' : ''; ?>">Endocrine & Diabetes (E00-E35)</button>
                  <button type="submit" name="filter" value="respiratory" class="tab-btn <?php echo $filter == 'respiratory' ? 'active' : ''; ?>">Respiratory (J00-J99)</button>
                </div>
              </form>

              <!-- Patient Table -->
              <table class="roster-table">
                <thead>
                  <tr>
                    <th>PATIENT IDENTIFIER</th>
                    <th>DEMOGRAPHICS</th>
                    <th>PRIMARY DIAGNOSIS</th>
                    <th>PHARMACOTHERAPY REGIMEN</th>
                    <th>LATEST VITALS</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach($patients as $patient): 
                    $age = date_diff(date_create($patient['dob']), date_create('today'))->y;
                  ?>
                  <tr>
                    <td>
                      <div class="patient-cell flex-align">
                        <div>
                          <strong><?php echo htmlspecialchars($patient['full_name']); ?></strong>
                          <span class="mrn-code"><?php echo htmlspecialchars($patient['health_card_no']); ?></span>
                        </div>
                      </div>
                    </td>
                    <td><?php echo $age; ?> yrs<br><span class="text-muted"><?php echo htmlspecialchars(ucfirst($patient['gender'])); ?></span></td>
                    <td><strong><?php echo htmlspecialchars($patient['diagnosis']); ?></strong></td>
                    <td><?php echo $patient['regimen'] ?: 'No active meds'; ?></td>
                    <td class="vitals-cell">
                      <strong><?php echo htmlspecialchars($patient['blood_pressure']); ?></strong> <small>mmHg</small><br>
                      <span class="text-muted">BMI: <?php echo htmlspecialchars($patient['bmi']); ?></span>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>

              <!-- Table Pagination Slider (< 1 2 3 >) -->
              <div class="pagination-footer">
                <span>Showing <?php echo $offset + 1; ?> to <?php echo min($offset + $limit, $total_records); ?> of <?php echo $total_records; ?> records</span>
                <div class="pagination-buttons">
                  <a href="?search=<?php echo urlencode($search); ?>&filter=<?php echo urlencode($filter); ?>&page=<?php echo max(1, $page - 1); ?>" class="btn-page"><i class="fa-solid fa-chevron-left"></i></a>
                  
                  <?php for($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="?search=<?php echo urlencode($search); ?>&filter=<?php echo urlencode($filter); ?>&page=<?php echo $i; ?>" class="<?php echo $i == $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
                  <?php endfor; ?>

                  <a href="?search=<?php echo urlencode($search); ?>&filter=<?php echo urlencode($filter); ?>&page=<?php echo min($total_pages, $page + 1); ?>" class="btn-page"><i class="fa-solid fa-chevron-right"></i></a>
                </div>
              </div>
            </div>

          </div>
        </div>
      </main>
    </div>
  </div>

  <script src="/public_html/assets/js/doctor-panel/clinical-records.js"></script>
</body>

</html>


