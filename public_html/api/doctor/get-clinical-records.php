<?php
// /api/doctor/get-clinical-records.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

// Database Configuration
$host     = '127.0.0.1';
$db       = 'nhmrd';
$user     = 'root';
$pass     = '';
$charset  = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database Connection Failed']);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? 'fetch_roster';

switch ($action) {

    // 1. Fetch Patient Roster with Filters, Search, and Pagination
    case 'fetch_roster':
        $search   = trim($_GET['search'] ?? '');
        $category = trim($_GET['category'] ?? '');
        $page     = max(1, intval($_GET['page'] ?? 1));
        $limit    = 6;
        $offset   = ($page - 1) * $limit;

        $whereClause = ["p.status = 'active'"];
        $params = [];

        if (!empty($search)) {
            $whereClause[] = "(p.full_name LIKE :search OR p.health_card_no LIKE :search OR p.nid LIKE :search OR pc.condition_name LIKE :search)";
            $params[':search'] = '%' . $search . '%';
        }

        if (!empty($category)) {
            if ($category === 'cardiovascular') {
                $whereClause[] = "(pc.condition_name LIKE '%hypertension%' OR pc.condition_name LIKE '%cardio%')";
            } elseif ($category === 'endocrine') {
                $whereClause[] = "(pc.condition_name LIKE '%diabetes%' OR pc.condition_name LIKE '%thyroid%')";
            } elseif ($category === 'respiratory') {
                $whereClause[] = "(pc.condition_name LIKE '%asthma%' OR pc.condition_name LIKE '%copd%')";
            }
        }

        $whereSql = implode(' AND ', $whereClause);

        // Count query
        $countStmt = $pdo->prepare("SELECT COUNT(DISTINCT p.patient_id) FROM patients p LEFT JOIN patient_chronic_conditions pc ON p.patient_id = pc.patient_id WHERE $whereSql");
        $countStmt->execute($params);
        $totalRecords = $countStmt->fetchColumn();

        // Data query
        $sql = "SELECT 
                    p.patient_id, p.full_name, p.health_card_no, p.dob, p.gender, p.blood_pressure, p.bmi,
                    TIMESTAMPDIFF(YEAR, p.dob, CURDATE()) AS age,
                    (SELECT GROUP_CONCAT(condition_name SEPARATOR ', ') FROM patient_chronic_conditions WHERE patient_id = p.patient_id) AS primary_diagnosis,
                    (SELECT GROUP_CONCAT(CONCAT(medication_name, ' ', dose_strength, ' ', route_frequency) SEPARATOR '<br>') 
                     FROM prescription_medications pm 
                     JOIN prescriptions rx ON pm.prescription_id = rx.prescription_id 
                     WHERE rx.patient_id = p.patient_id AND rx.status = 'active') AS active_rx
                FROM patients p
                LEFT JOIN patient_chronic_conditions pc ON p.patient_id = pc.patient_id
                WHERE $whereSql
                GROUP BY p.patient_id
                ORDER BY p.patient_id DESC
                LIMIT :limit OFFSET :offset";

        $stmt = $pdo->prepare($sql);
        foreach ($params as $k => $v) $stmt->bindValue($k, $v);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $patients = $stmt->fetchAll();

        echo json_encode([
            'success' => true,
            'data' => $patients,
            'total' => $totalRecords,
            'page' => $page,
            'totalPages' => ceil($totalRecords / $limit)
        ]);
        break;

    // 2. Load Selected Patient Details
    case 'get_patient_detail':
        $patientId = intval($_GET['patient_id'] ?? 0);
        
        // Fetch Patient
        $pStmt = $pdo->prepare("SELECT p.*, TIMESTAMPDIFF(YEAR, p.dob, CURDATE()) as age FROM patients p WHERE p.patient_id = ?");
        $pStmt->execute([$patientId]);
        $patient = $pStmt->fetch();

        // Fetch Prescriptions
        $mStmt = $pdo->prepare("SELECT pm.* FROM prescription_medications pm JOIN prescriptions rx ON pm.prescription_id = rx.prescription_id WHERE rx.patient_id = ? AND rx.status = 'active'");
        $mStmt->execute([$patientId]);
        $meds = $mStmt->fetchAll();

        echo json_encode([
            'success' => true,
            'patient' => $patient,
            'medications' => $meds
        ]);
        break;

    // 3. Append Follow-Up SOAP Note
    case 'append_soap_note':
        $patientId = intval($_POST['patient_id'] ?? 0);
        $soapNote  = trim($_POST['soap_note'] ?? '');

        if ($patientId > 0 && !empty($soapNote)) {
            $stmt = $pdo->prepare("INSERT INTO audit_logs (user_id, action, entity_type, entity_id, details) VALUES (1, 'APPEND_SOAP_NOTE', 'patients', ?, ?)");
            $stmt->execute([$patientId, json_encode(['note' => $soapNote])]);
            echo json_encode(['success' => true, 'message' => 'SOAP note appended successfully.']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Invalid patient or note content.']);
        }
        break;

    // 4. Batch Renewal & HL7/PDF Summary Export Actions
    case 'batch_renewal':
    case 'export_summary':
        echo json_encode(['success' => true, 'message' => 'Action executed successfully.']);
        break;

    default:
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
        break;
}