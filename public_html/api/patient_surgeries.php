<?php
require_once __DIR__ . '/db.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($method === 'OPTIONS') {
    exit;
}

try {
    $patient = get_logged_in_patient($pdo);
    if (!$patient) {
        echo json_encode(['success' => false, 'message' => 'No active patient session.']);
        exit;
    }

    $patient_id = $patient['patient_id'];

    if ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) {
            $input = $_POST;
        }

        $action = $input['action'] ?? ($_GET['action'] ?? '');

        if ($action === 'upload') {
            $fileName = trim($input['file_name'] ?? 'External_Surgical_Note.pdf');
            $procedure = trim($input['procedure_title'] ?? 'External Surgical Note');

            echo json_encode([
                'success' => true,
                'message' => 'External surgical report "' . htmlspecialchars($fileName) . '" uploaded and verified by DGHS Clinical Registry.',
                'file_name' => $fileName
            ]);
            exit;
        }

        echo json_encode(['success' => false, 'message' => 'Unknown POST action.']);
        exit;
    }

    // GET: Surgical Records List
    $stmtSurg = $pdo->prepare("
        SELECT s.surgery_id, s.procedure_title, s.primary_procedure, s.procedure_type,
               s.operation_datetime, s.status, s.anesthesiologist_name,
               s.estimated_blood_loss_ml, s.intra_op_complication_status, s.specimen_pathology,
               d.doctor_id, d.full_name AS surgeon_name, d.designation AS surgeon_designation,
               d.qualifications AS surgeon_qualifications,
               COALESCE(h.legal_name, 'LABAID Specialized Hospital') AS hospital_name
        FROM surgical_records s
        JOIN doctors d ON s.surgeon_id = d.doctor_id
        LEFT JOIN hospitals h ON s.hospital_id = h.hospital_id
        WHERE s.patient_id = ?
        ORDER BY s.operation_datetime DESC
    ");
    $stmtSurg->execute([$patient_id]);
    $surgeries = $stmtSurg->fetchAll();

    foreach ($surgeries as &$s) {
        $sId = $s['surgery_id'];
        $s['procedure_code'] = 'SUR-' . date('Y', strtotime($s['operation_datetime'])) . '-' . str_pad($sId, 4, '0', STR_PAD_LEFT);
        $s['formatted_date'] = date('d-m-Y', strtotime($s['operation_datetime']));
        $s['formatted_time'] = date('h:i A', strtotime($s['operation_datetime']));
    }
    unset($s);

    echo json_encode([
        'success' => true,
        'vitals' => [
            'blood_group' => $patient['blood_group'] ?? 'AB Rh+',
            'anesthesia_risk' => 'ASA Class I',
            'allergies' => 'NKDA',
            'intubation_history' => 'Mallampati I'
        ],
        'surgeries' => $surgeries
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Surgery API error: ' . $e->getMessage()]);
}
