<?php
// public_html/controllers/patient/DashboardController.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/db.php';

// Authenticated session check (Defaulting to user_id = 5 for fallback)
$logged_user_id = $_SESSION['user_id'] ?? 5;

try {
    // 1. Fetch Patient Header Information
    $stmtPatient = $pdo->prepare("
        SELECT p.*, u.uid AS user_uid 
        FROM patients p 
        JOIN users u ON p.user_id = u.user_id 
        WHERE p.user_id = ?
    ");
    $stmtPatient->execute([$logged_user_id]);
    $patient = $stmtPatient->fetch();

    if (!$patient) {
        die("Patient record not found.");
    }

    // Compute initials for the top bar avatar badge
    $nameParts = explode(' ', trim($patient['full_name']));
    $userInitials = strtoupper(
        substr($nameParts[0], 0, 1) . 
        (count($nameParts) > 1 ? substr(end($nameParts), 0, 1) : '')
    );

    $patientId = $patient['patient_id'];

    // 2. Query Dashboard Statistics
    $stmtRx = $pdo->prepare("SELECT COUNT(*) FROM prescriptions WHERE patient_id = ?");
    $stmtRx->execute([$patientId]);
    $totalPrescriptions = $stmtRx->fetchColumn();

    $stmtLab = $pdo->prepare("SELECT COUNT(*) FROM lab_test_orders WHERE patient_id = ?");
    $stmtLab->execute([$patientId]);
    $totalLabTests = $stmtLab->fetchColumn();

    $stmtVac = $pdo->prepare("SELECT COUNT(*) FROM vaccination_records WHERE patient_id = ?");
    $stmtVac->execute([$patientId]);
    $totalVaccines = $stmtVac->fetchColumn();

    $stmtSurg = $pdo->prepare("SELECT COUNT(*) FROM surgical_records WHERE patient_id = ? AND procedure_type = 'inpatient'");
    $stmtSurg->execute([$patientId]);
    $totalSurgeries = $stmtSurg->fetchColumn();

    // 3. Fetch Recent Appointments
    $stmtAppointments = $pdo->prepare("
        SELECT a.*, d.full_name AS doctor_name, h.legal_name AS hospital_name 
        FROM appointments a
        JOIN doctors d ON a.doctor_id = d.doctor_id
        LEFT JOIN hospitals h ON a.hospital_id = h.hospital_id
        WHERE a.patient_id = ?
        ORDER BY a.appointment_date DESC
        LIMIT 3
    ");
    $stmtAppointments->execute([$patientId]);
    $appointments = $stmtAppointments->fetchAll();

    // 4. Fetch Recent Medical Activities (Lab Tests)
    $stmtActivities = $pdo->prepare("
        SELECT lto.order_id, lto.ordered_at, ltc.test_name, d.full_name AS doctor_name
        FROM lab_test_orders lto
        JOIN lab_test_order_items ltoi ON lto.order_id = ltoi.order_id
        JOIN lab_test_catalog ltc ON ltoi.test_id = ltc.test_id
        LEFT JOIN doctors d ON lto.doctor_id = d.doctor_id
        WHERE lto.patient_id = ?
        ORDER BY lto.ordered_at DESC
        LIMIT 3
    ");
    $stmtActivities->execute([$patientId]);
    $activities = $stmtActivities->fetchAll();

    // Return view data payload
    return [
        'patient'            => $patient,
        'userInitials'       => $userInitials,
        'totalPrescriptions' => $totalPrescriptions,
        'totalLabTests'      => $totalLabTests,
        'totalVaccines'      => $totalVaccines,
        'totalSurgeries'     => $totalSurgeries,
        'appointments'       => $appointments,
        'activities'         => $activities
    ];

} catch (Exception $e) {
    error_log($e->getMessage());
    die("An error occurred while loading dashboard data.");
}