<?php
// public_html/handler/paitent-panel/req-vaccine-handler.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json');

$pdo = require_once __DIR__ . '/../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit();
}

$patientId = filter_input(INPUT_POST, 'patient_id', FILTER_VALIDATE_INT) ?: ($_SESSION['patient_id'] ?? null);
if (!$patientId && isset($_SESSION['user_id'])) {
    $stmtUser = $pdo->prepare("SELECT patient_id FROM patients WHERE user_id = ? LIMIT 1");
    $stmtUser->execute([$_SESSION['user_id']]);
    $patientId = $stmtUser->fetchColumn() ?: null;
}
if (!$patientId) {
    $patientId = 1;
}

$vaccineId      = filter_input(INPUT_POST, 'vaccine_id', FILTER_VALIDATE_INT);
$hospitalId     = filter_input(INPUT_POST, 'hospital_id', FILTER_VALIDATE_INT);
$scheduledDate  = filter_input(INPUT_POST, 'scheduled_date', FILTER_DEFAULT);
$timeSlot       = filter_input(INPUT_POST, 'time_slot', FILTER_DEFAULT);
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
        'patient_id'     => $patientId,
        'vaccine_id'     => $vaccineId,
        'hospital_id'    => $hospitalId,
        'scheduled_date' => $scheduledDate,
        'time_slot'      => $timeSlot,
        'declaration'    => $declaration
    ]);

    echo json_encode([
        'success' => true, 
        'message' => 'Vaccine appointment requested successfully! Ref ID: #' . $pdo->lastInsertId()
    ]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}