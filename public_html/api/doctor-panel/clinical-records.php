<?php
header('Content-Type: application/json');
require_once '../db.php';

$doctor_id = isset($_GET['doctor_id']) ? (int)$_GET['doctor_id'] : 1;

try {
    $stmt = $pdo->prepare("SELECT a.appointment_id, a.appointment_date, a.appointment_time, a.status, a.visit_type, a.chief_complaint, 
                                  p.full_name, p.dob, p.gender, p.blood_group, p.phone, p.photo_url 
                           FROM appointments a
                           JOIN patients p ON a.patient_id = p.patient_id
                           WHERE a.doctor_id = ? AND a.status IN ('completed', 'ongoing')
                           ORDER BY a.appointment_date DESC, a.appointment_time DESC LIMIT 50");
    $stmt->execute([$doctor_id]);
    $records = $stmt->fetchAll();

    echo json_encode(['success' => true, 'records' => $records]);
} catch (\PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
