<?php
header('Content-Type: application/json');
require_once '../db.php';

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
    exit;
}

// In real application, this comes from session
$doctor_id = isset($_POST['doctor_id']) ? (int)$_POST['doctor_id'] : 1;
$patient_id = isset($_POST['patient_id']) ? (int)$_POST['patient_id'] : 1;

$title = $_POST['title'] ?? 'New Prescription';
$chief_complaint = $_POST['chief_complaint'] ?? '';
$symptoms = $_POST['symptoms'] ?? '';
$medications = isset($_POST['medications']) ? json_decode($_POST['medications'], true) : [];

try {
    $pdo->beginTransaction();

    // 1. Insert Prescription Record
    $stmt = $pdo->prepare("INSERT INTO prescriptions (patient_id, doctor_id, title, chief_complaint, symptoms, status) 
                           VALUES (?, ?, ?, ?, ?, 'active')");
    $stmt->execute([$patient_id, $doctor_id, $title, $chief_complaint, $symptoms]);
    $prescription_id = $pdo->lastInsertId();

    // 2. Insert Medications
    if (!empty($medications)) {
        $stmtMed = $pdo->prepare("INSERT INTO prescription_medications (prescription_id, medication_name, dose_strength, route_frequency, dispense_quantity, refills, sig_instructions) 
                                  VALUES (?, ?, ?, ?, ?, ?, ?)");
        
        foreach ($medications as $med) {
            $stmtMed->execute([
                $prescription_id,
                $med['name'],
                $med['strength'] ?? '',
                $med['route'] ?? '',
                $med['quantity'] ?? '1 Strip',
                $med['refills'] ?? 0,
                $med['instructions'] ?? ''
            ]);
        }
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Prescription saved successfully.', 'prescription_id' => $prescription_id]);

} catch (\PDOException $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
