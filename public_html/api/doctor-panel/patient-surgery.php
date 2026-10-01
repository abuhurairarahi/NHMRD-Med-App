<?php
header('Content-Type: application/json');
require_once '../db.php';

$patient_id = isset($_GET['patient_id']) ? (int)$_GET['patient_id'] : 1;

try {
    $stmt = $pdo->prepare("SELECT s.*, h.name as hospital_name, d.full_name as surgeon_name 
                           FROM surgical_records s
                           LEFT JOIN hospitals h ON s.hospital_id = h.hospital_id
                           LEFT JOIN doctors d ON s.surgeon_id = d.doctor_id
                           WHERE s.patient_id = ?
                           ORDER BY s.operation_datetime DESC");
    $stmt->execute([$patient_id]);
    $surgeries = $stmt->fetchAll();

    echo json_encode(['success' => true, 'surgeries' => $surgeries]);
} catch (\PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
