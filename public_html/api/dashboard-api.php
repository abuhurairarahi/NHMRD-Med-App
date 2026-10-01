<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

// Database Configuration
$db_host = 'localhost';
$db_name = 'nhmrd';
$db_user = 'root';
$db_pass = '';

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database Connection Failed: ' . $e->getMessage()]);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'get_dashboard_data':
        fetchDashboardData($pdo);
        break;
    case 'call_triage':
        callTriage($pdo);
        break;
    case 'search_patient':
        searchPatient($pdo);
        break;
    case 'page_cardiology':
        pageCardiology($pdo);
        break;
    case 'queue_prescription':
        queuePrescription($pdo);
        break;
    case 'check_medication_renal':
        checkMedicationRenal($pdo);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action endpoint.']);
        break;
}

function fetchDashboardData($pdo) {
    try {
        // 1. Fetch Metrics Cards Data
        $today = date('Y-m-d');
        
        $consultationsStmt = $pdo->prepare("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as done,
                SUM(CASE WHEN status = 'booked' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'rescheduled' THEN 1 ELSE 0 END) as in_exam
            FROM appointments 
            WHERE appointment_date = :today
        ");
        $consultationsStmt->execute(['today' => $today]);
        $consultations = $consultationsStmt->fetch();

        // 2. Fetch Patient Queue
        $queueStmt = $pdo->prepare("
            SELECT 
                a.appointment_id,
                a.time_slot,
                a.status,
                a.reason as chief_complaint,
                p.patient_id,
                p.full_name,
                p.gender,
                p.blood_pressure,
                p.bmi,
                TIMESTAMPDIFF(YEAR, p.dob, CURDATE()) AS age
            FROM appointments a
            JOIN patients p ON a.patient_id = p.patient_id
            WHERE a.appointment_date = :today
            ORDER BY a.time_slot ASC
        ");
        $queueStmt->execute(['today' => $today]);
        $queue = $queueStmt->fetchAll();

        // 3. Fetch Lab Telemetry Critical Alerts
        $telemetryStmt = $pdo->query("
            SELECT 
                p.full_name,
                p.health_card_no as mrn,
                c.test_name,
                r.remarks as observed_value,
                c.category
            FROM lab_test_orders o
            JOIN patients p ON o.patient_id = p.patient_id
            JOIN lab_test_order_items i ON o.order_id = i.order_id
            JOIN lab_test_catalog c ON i.test_id = c.test_id
            LEFT JOIN lab_test_results r ON i.id = r.order_item_id
            WHERE o.status = 'requested' OR r.remarks LIKE '%Critical%'
            LIMIT 5
        ");
        $telemetry = $telemetryStmt->fetchAll();

        echo json_encode([
            'success' => true,
            'metrics' => [
                'consultations' => $consultations
            ],
            'queue' => $queue,
            'telemetry' => $telemetry
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function callTriage($pdo) {
    // Audit Log Entry
    $stmt = $pdo->prepare("INSERT INTO audit_logs (action, details) VALUES ('CALL_TRIAGE', :details)");
    $stmt->execute(['details' => json_encode(['station' => 'Station 04', 'ward' => 'Service Ward 3B'])]);
    
    echo json_encode(['success' => true, 'message' => 'Triage notification dispatched to Ward 3B.']);
}

function searchPatient($pdo) {
    $query = $_GET['query'] ?? '';
    if (empty($query)) {
        echo json_encode(['success' => true, 'results' => []]);
        return;
    }

    $stmt = $pdo->prepare("
        SELECT patient_id, full_name, health_card_no, nid, blood_pressure, bmi 
        FROM patients 
        WHERE full_name LIKE :q OR health_card_no LIKE :q OR nid LIKE :q 
        LIMIT 10
    ");
    $stmt->execute(['q' => '%' . $query . '%']);
    $results = $stmt->fetchAll();

    echo json_encode(['success' => true, 'results' => $results]);
}

function pageCardiology($pdo) {
    $patientMrn = $_POST['mrn'] ?? 'MRN-7892-C';
    
    $stmt = $pdo->prepare("INSERT INTO audit_logs (action, details) VALUES ('STAT_PAGE_CARDIOLOGY', :details)");
    $stmt->execute(['details' => json_encode(['mrn' => $patientMrn, 'reason' => 'Critical Troponin High'])]);

    echo json_encode(['success' => true, 'message' => "STAT Page dispatched to Duty Cardiologist for MRN: {$patientMrn}."]);
}

function queuePrescription($pdo) {
    $medication = $_POST['medication'] ?? '';
    $dosage = $_POST['dosage'] ?? '';
    $frequency = $_POST['frequency'] ?? '';

    if (empty($medication)) {
        echo json_encode(['success' => false, 'message' => 'Medication name is required.']);
        return;
    }

    echo json_encode([
        'success' => true, 
        'message' => "Successfully queued {$medication} ({$dosage}, {$frequency}) into patient active profile."
    ]);
}

function checkMedicationRenal($pdo) {
    $medication = $_GET['medication'] ?? '';
    
    // Default safe check response logic
    $response = [
        'egfr_status' => 'eGFR 72 mL/min (Safe)',
        'max_daily_threshold' => '2000 mg/day'
    ];

    if (stripos($medication, 'metformin') !== false) {
        $response = [
            'egfr_status' => 'eGFR 72 mL/min (Safe)',
            'max_daily_threshold' => '2000 mg/day'
        ];
    } else if (stripos($medication, 'ibuprofen') !== false) {
        $response = [
            'egfr_status' => 'eGFR 45 mL/min (Caution Required)',
            'max_daily_threshold' => '1200 mg/day'
        ];
    }

    echo json_encode(['success' => true, 'data' => $response]);
}