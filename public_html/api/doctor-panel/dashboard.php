<?php
header('Content-Type: application/json');
require_once '../db.php';

$doctor_id = isset($_GET['doctor_id']) ? (int)$_GET['doctor_id'] : 1;
$today = date('Y-m-d');

try {
    // 1. Get Doctor Info
    $stmt = $pdo->prepare("SELECT d.full_name, d.designation, d.photo_url, s.name as specialty 
                           FROM doctors d 
                           LEFT JOIN specialties s ON d.primary_specialty_id = s.specialty_id
                           WHERE d.doctor_id = ?");
    $stmt->execute([$doctor_id]);
    $doctor = $stmt->fetch();

    if (!$doctor) {
        echo json_encode(['success' => false, 'error' => 'Doctor not found']);
        exit;
    }

    // 2. Metrics
    // Consultations today
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE doctor_id = ? AND DATE(appointment_date) = ?");
    $stmt->execute([$doctor_id, $today]);
    $consultations_today = $stmt->fetchColumn();

    // Rx Pending Reviews (for demo, using prescriptions created today)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM prescriptions WHERE doctor_id = ? AND DATE(created_at) = ?");
    $stmt->execute([$doctor_id, $today]);
    $rx_pending = $stmt->fetchColumn();

    // 3. Appointments Queue
    $stmt = $pdo->prepare("SELECT a.appointment_id, a.appointment_time, a.status, 
                                  p.full_name, p.dob, p.gender, p.blood_group, p.phone, p.photo_url 
                           FROM appointments a
                           JOIN patients p ON a.patient_id = p.patient_id
                           WHERE a.doctor_id = ? AND DATE(a.appointment_date) = ?
                           ORDER BY a.appointment_time ASC LIMIT 10");
    $stmt->execute([$doctor_id, $today]);
    $schedule = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'doctor' => [
            'name' => $doctor['full_name'],
            'department' => $doctor['specialty'] ?? $doctor['designation'],
            'avatar' => $doctor['photo_url'] ?: 'https://i.pravatar.cc/100?img=47'
        ],
        'metrics' => [
            'consultations_today' => $consultations_today,
            'rx_pending' => $rx_pending,
            'critical_alerts' => rand(0, 5),
            'care_index' => rand(90, 99) . '.' . rand(0, 9) . '%'
        ],
        'schedule' => $schedule
    ]);
} catch (\PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
