<?php
// public_html/handlers/patient/reqVaccineHandler.php

session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../../../db.php'; // Adjust path to db.php as needed

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit();
}

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit();
}

$patientId      = filter_input(INPUT_POST, 'patient_id', FILTER_VALIDATE_INT);
$vaccineId      = filter_input(INPUT_POST, 'vaccine_id', FILTER_VALIDATE_INT);
$hospitalId     = filter_input(INPUT_POST, 'hospital_id', FILTER_VALIDATE_INT);
$scheduledDate  = filter_input(INPUT_POST, 'scheduled_date', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
$timeSlot       = filter_input(INPUT_POST, 'time_slot', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
$declaration    = isset($_POST['health_declaration_confirmed']) ? 1 : 0;

if (!$patientId || !$vaccineId || !$hospitalId || !$scheduledDate || !$timeSlot || !$declaration) {
    echo json_encode(['success' => false, 'message' => 'Please fill all required fields properly.']);
    exit();
}

try {
    $stmt = $pdo->prepare("
        INSERT INTO vaccine_appointments 
        (patient_id, vaccine_id, hospital_id, scheduled_date, time_slot, health_declaration_confirmed, status) 
        VALUES (:patient_id, :vaccine_id, :hospital_id, :scheduled_date, :time_slot, :declaration, 'scheduled')
    ");

    $stmt->execute([
        'patient_id'  => $patientId,
        'vaccine_id'  => $vaccineId,
        'hospital_id' => $hospitalId,
        'scheduled_date' => $scheduledDate,
        'time_slot'   => $timeSlot,
        'declaration' => $declaration
    ]);

    echo json_encode(['success' => true, 'message' => 'Vaccine appointment requested successfully!']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}