<?php
header('Content-Type: application/json');
require_once '../db.php';

$doctor_id = isset($_GET['doctor_id']) ? (int)$_GET['doctor_id'] : 1;

try {
    $stmt = $pdo->prepare("SELECT d.*, s.name as specialty, h.name as hospital_name 
                           FROM doctors d 
                           LEFT JOIN specialties s ON d.primary_specialty_id = s.specialty_id
                           LEFT JOIN hospitals h ON d.hospital_id = h.hospital_id
                           WHERE d.doctor_id = ?");
    $stmt->execute([$doctor_id]);
    $doctor = $stmt->fetch();

    if (!$doctor) {
        echo json_encode(['success' => false, 'error' => 'Doctor not found']);
        exit;
    }

    echo json_encode(['success' => true, 'profile' => $doctor]);
} catch (\PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
