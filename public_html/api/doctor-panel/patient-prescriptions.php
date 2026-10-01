<?php
header('Content-Type: application/json');
require_once '../db.php';

$doctor_id = isset($_GET['doctor_id']) ? (int)$_GET['doctor_id'] : 1;
// In reality, patient_id would come from the session or URL parameter. Defaulting to 1 for demo.
$patient_id = isset($_GET['patient_id']) ? (int)$_GET['patient_id'] : 1;

try {
    // 1. Get patient info
    $stmt = $pdo->prepare("SELECT full_name, dob, gender, blood_group, photo_url FROM patients WHERE patient_id = ?");
    $stmt->execute([$patient_id]);
    $patient = $stmt->fetch();

    if (!$patient) {
        echo json_encode(['success' => false, 'error' => 'Patient not found']);
        exit;
    }

    // 2. Get allergies (mocking a critical allergy for demo based on schema if exists, else static)
    $stmt = $pdo->prepare("SELECT allergy_name, reaction_severity, reaction_description FROM patient_allergies WHERE patient_id = ? AND status = 'active'");
    $stmt->execute([$patient_id]);
    $allergies = $stmt->fetchAll();

    // 3. Get prescriptions and medications
    $stmt = $pdo->prepare("SELECT p.prescription_id, p.title, p.created_at, p.status, 
                                  pm.medication_name, pm.dose_strength, pm.route_frequency, pm.refills, pm.sig_instructions,
                                  d.full_name as prescriber_name
                           FROM prescriptions p
                           JOIN prescription_medications pm ON p.prescription_id = pm.prescription_id
                           JOIN doctors d ON p.doctor_id = d.doctor_id
                           WHERE p.patient_id = ?
                           ORDER BY p.created_at DESC LIMIT 20");
    $stmt->execute([$patient_id]);
    $prescriptions = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'patient' => $patient,
        'allergies' => $allergies,
        'prescriptions' => $prescriptions
    ]);
} catch (\PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
