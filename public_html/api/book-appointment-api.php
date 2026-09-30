<?php
header('Content-Type: application/json');
session_start();

require_once __DIR__ . '/../../db.php';

$input = json_decode(file_get_contents('php_input'), true) ?? $_POST;
$action = $input['action'] ?? '';

// Fallback patient context if session is not configured
$userId = $_SESSION['user_id'] ?? 1;

$stmt = $pdo->prepare("SELECT patient_id FROM patients WHERE user_id = :uid LIMIT 1");
$stmt->execute([':uid' => $userId]);
$patient = $stmt->fetch(PDO::FETCH_ASSOC);
$patientId = $patient['patient_id'] ?? 1;

if ($action === 'book') {
    $doctorId        = $input['doctor_id'] ?? null;
    $hospitalId      = $input['hospital_id'] ?? null;
    $appointmentDate = $input['appointment_date'] ?? date('Y-m-d');
    $timeSlot        = $input['time_slot'] ?? '19:00 PM';

    if (!$doctorId) {
        echo json_encode(['success' => false, 'message' => 'Doctor selection is required.']);
        exit;
    }

    try {
        $insertStmt = $pdo->prepare("
            INSERT INTO appointments (patient_id, doctor_id, hospital_id, appointment_date, time_slot, status)
            VALUES (:pid, :did, :hid, :adate, :tslot, 'booked')
        ");
        $insertStmt->execute([
            ':pid'   => $patientId,
            ':did'   => $doctorId,
            ':hid'   => $hospitalId,
            ':adate' => $appointmentDate,
            ':tslot' => $timeSlot
        ]);

        echo json_encode(['success' => true, 'appointment_id' => $pdo->lastInsertId()]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} elseif ($action === 'cancel') {
    $appointmentId = $input['appointment_id'] ?? null;

    if (!$appointmentId) {
        echo json_encode(['success' => false, 'message' => 'Missing Appointment ID.']);
        exit;
    }

    $cancelStmt = $pdo->prepare("UPDATE appointments SET status = 'cancelled' WHERE appointment_id = :aid AND patient_id = :pid");
    $cancelStmt->execute([':aid' => $appointmentId, ':pid' => $patientId]);

    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid action.']);
}