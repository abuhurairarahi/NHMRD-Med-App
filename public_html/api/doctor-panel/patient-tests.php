<?php
header('Content-Type: application/json');
require_once '../db.php';

$doctor_id = isset($_GET['doctor_id']) ? (int)$_GET['doctor_id'] : 1;
$patient_id = isset($_GET['patient_id']) ? (int)$_GET['patient_id'] : 1;

try {
    // 1. Get patient info
    $stmt = $pdo->prepare("SELECT p.full_name, p.dob, p.gender, p.blood_group, p.photo_url, 
                                  d.full_name as attending_doctor
                           FROM patients p
                           LEFT JOIN doctors d ON p.primary_care_doctor_id = d.doctor_id
                           WHERE p.patient_id = ?");
    $stmt->execute([$patient_id]);
    $patient = $stmt->fetch();

    if (!$patient) {
        echo json_encode(['success' => false, 'error' => 'Patient not found']);
        exit;
    }

    // 2. Get Test Orders and Catalog info
    $stmt = $pdo->prepare("SELECT o.order_id, o.ordered_at, o.status, 
                                  i.id as item_id, c.test_name, c.category
                           FROM lab_test_orders o
                           JOIN lab_test_order_items i ON o.order_id = i.order_id
                           JOIN lab_test_catalog c ON i.test_id = c.test_id
                           WHERE o.patient_id = ?
                           ORDER BY o.ordered_at DESC LIMIT 20");
    $stmt->execute([$patient_id]);
    $tests = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'patient' => $patient,
        'tests' => $tests
    ]);
} catch (\PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
