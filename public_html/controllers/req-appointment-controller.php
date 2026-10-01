<?php
// controllers/paitent-panel/req-appointment-controller.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pdo = require_once __DIR__ . '/../../config/db.php';

// Auth check — fallback to demo patient (user_id=5)
$logged_user_id = $_SESSION['user_id'] ?? 5;

try {
    // 1. Fetch Patient
    $stmt = $pdo->prepare("
        SELECT p.*, u.uid AS user_uid
        FROM patients p
        JOIN users u ON p.user_id = u.user_id
        WHERE p.user_id = ?
    ");
    $stmt->execute([$logged_user_id]);
    $patient = $stmt->fetch();

    if (!$patient) {
        die('Patient record not found.');
    }

    $nameParts    = explode(' ', trim($patient['full_name']));
    $userInitials = strtoupper(
        substr($nameParts[0], 0, 1) .
        (count($nameParts) > 1 ? substr(end($nameParts), 0, 1) : '')
    );

    $pid = $patient['patient_id'];

    // 2. Stats
    $totalPrescriptions = $pdo->prepare("SELECT COUNT(*) FROM prescriptions WHERE patient_id = ?");
    $totalPrescriptions->execute([$pid]);
    $totalPrescriptions = $totalPrescriptions->fetchColumn();

    $totalLabTests = $pdo->prepare("SELECT COUNT(*) FROM lab_test_orders WHERE patient_id = ?");
    $totalLabTests->execute([$pid]);
    $totalLabTests = $totalLabTests->fetchColumn();

    $totalVaccines = $pdo->prepare("SELECT COUNT(*) FROM vaccination_records WHERE patient_id = ?");
    $totalVaccines->execute([$pid]);
    $totalVaccines = $totalVaccines->fetchColumn();

    $totalSurgeries = $pdo->prepare("SELECT COUNT(*) FROM surgical_records WHERE patient_id = ?");
    $totalSurgeries->execute([$pid]);
    $totalSurgeries = $totalSurgeries->fetchColumn();

    // 3. Fetch available doctors with hospital info
    $stmtDoctors = $pdo->query("
        SELECT d.doctor_id, d.full_name, d.designation, d.qualifications, d.years_experience,
               d.duty_status, d.shift_schedule, s.name AS specialty, h.legal_name AS hospital_name
        FROM doctors d
        LEFT JOIN specialties s ON d.primary_specialty_id = s.specialty_id
        LEFT JOIN hospitals h ON d.hospital_id = h.hospital_id
        WHERE d.status = 'active' AND d.verification_status = 'verified'
        ORDER BY d.full_name ASC
    ");
    $doctors = $stmtDoctors->fetchAll();

    // 4. Fetch existing appointments
    $stmtAppts = $pdo->prepare("
        SELECT a.*, d.full_name AS doctor_name, h.legal_name AS hospital_name
        FROM appointments a
        JOIN doctors d ON a.doctor_id = d.doctor_id
        LEFT JOIN hospitals h ON a.hospital_id = h.hospital_id
        WHERE a.patient_id = ?
        ORDER BY a.appointment_date DESC
        LIMIT 10
    ");
    $stmtAppts->execute([$pid]);
    $appointments = $stmtAppts->fetchAll();

    return [
        'patient'            => $patient,
        'userInitials'       => $userInitials,
        'totalPrescriptions' => $totalPrescriptions,
        'totalLabTests'      => $totalLabTests,
        'totalVaccines'      => $totalVaccines,
        'totalSurgeries'     => $totalSurgeries,
        'doctors'            => $doctors,
        'appointments'       => $appointments,
    ];

} catch (Exception $e) {
    error_log($e->getMessage());
    die('Error loading appointment data.');
}
