<?php
// controllers/paitent-panel/vaccine-controller.php
// Loads data for the patient Vaccine Records view (not the request form)

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pdo = require_once __DIR__ . '/../../config/db.php';

$logged_user_id = $_SESSION['user_id'] ?? 5;

try {
    // 1. Patient
    $stmt = $pdo->prepare("
        SELECT p.*, u.uid AS user_uid
        FROM patients p
        JOIN users u ON p.user_id = u.user_id
        WHERE p.user_id = ?
    ");
    $stmt->execute([$logged_user_id]);
    $patient = $stmt->fetch();
    if (!$patient) die('Patient record not found.');

    $nameParts    = explode(' ', trim($patient['full_name']));
    $userInitials = strtoupper(
        substr($nameParts[0], 0, 1) .
        (count($nameParts) > 1 ? substr(end($nameParts), 0, 1) : '')
    );

    $pid = $patient['patient_id'];

    // 2. Stats
    $s = $pdo->prepare("SELECT COUNT(*) FROM prescriptions WHERE patient_id = ?");
    $s->execute([$pid]);
    $totalPrescriptions = $s->fetchColumn();

    $s = $pdo->prepare("SELECT COUNT(*) FROM lab_test_orders WHERE patient_id = ?");
    $s->execute([$pid]);
    $totalLabTests = $s->fetchColumn();

    $s = $pdo->prepare("SELECT COUNT(*) FROM vaccination_records WHERE patient_id = ?");
    $s->execute([$pid]);
    $totalVaccines = $s->fetchColumn();

    $s = $pdo->prepare("SELECT COUNT(*) FROM surgical_records WHERE patient_id = ?");
    $s->execute([$pid]);
    $totalSurgeries = $s->fetchColumn();

    // 3. Vaccine Records
    $stmtVacs = $pdo->prepare("
        SELECT vr.*, vc.vaccine_name, vc.batch_number, vc.dose_ml, vc.route,
               d.full_name AS administered_by, h.legal_name AS hospital_name
        FROM vaccination_records vr
        JOIN vaccine_catalog vc ON vr.vaccine_id = vc.vaccine_id
        LEFT JOIN doctors d ON vr.administered_by_doctor_id = d.doctor_id
        LEFT JOIN hospitals h ON vr.hospital_id = h.hospital_id
        WHERE vr.patient_id = ?
        ORDER BY vr.administered_date DESC
    ");
    $stmtVacs->execute([$pid]);
    $vaccinationRecords = $stmtVacs->fetchAll();

    return [
        'patient'            => $patient,
        'userInitials'       => $userInitials,
        'totalPrescriptions' => $totalPrescriptions,
        'totalLabTests'      => $totalLabTests,
        'totalVaccines'      => $totalVaccines,
        'totalSurgeries'     => $totalSurgeries,
        'vaccinationRecords' => $vaccinationRecords,
    ];

} catch (Exception $e) {
    error_log($e->getMessage());
    die('Error loading vaccine records.');
}
