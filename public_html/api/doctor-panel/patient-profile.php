<?php
header('Content-Type: application/json');
require_once '../db.php';

$patient_id = isset($_GET['patient_id']) ? (int)$_GET['patient_id'] : 1;

try {
    // 1. Patient Base Info
    $stmt = $pdo->prepare("SELECT p.*, d.full_name as attending_doctor 
                           FROM patients p
                           LEFT JOIN doctors d ON p.primary_care_doctor_id = d.doctor_id
                           WHERE p.patient_id = ?");
    $stmt->execute([$patient_id]);
    $patient = $stmt->fetch();

    if (!$patient) {
        echo json_encode(['success' => false, 'error' => 'Patient not found']);
        exit;
    }

    // 2. Allergies
    $stmt = $pdo->prepare("SELECT allergy_name, reaction_severity, reaction_description 
                           FROM patient_allergies WHERE patient_id = ?");
    $stmt->execute([$patient_id]);
    $allergies = $stmt->fetchAll();

    // 3. Lifetime Medications
    $stmt = $pdo->prepare("SELECT p.created_at, p.title, p.status, 
                                  pm.medication_name, pm.dose_strength, pm.route_frequency, pm.sig_instructions,
                                  d.full_name as prescriber
                           FROM prescriptions p
                           JOIN prescription_medications pm ON p.prescription_id = pm.prescription_id
                           JOIN doctors d ON p.doctor_id = d.doctor_id
                           WHERE p.patient_id = ?
                           ORDER BY p.created_at DESC");
    $stmt->execute([$patient_id]);
    $medications = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'patient' => $patient,
        'allergies' => $allergies,
        'medications' => $medications
    ]);
} catch (\PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
