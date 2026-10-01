<?php
// controllers/patient/reqVaccineController.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pdo = require_once __DIR__ . '/../../config/db.php';

// Verify user authentication
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
    header("Location: /public_html/pages/login.html");
    exit();
}

$userId = $_SESSION['user_id'];

try {
    // 1. Fetch Patient Info
    $stmt = $pdo->prepare("
        SELECT p.*, u.uid as user_uid 
        FROM patients p 
        JOIN users u ON u.user_id = p.user_id 
        WHERE p.user_id = :user_id
    ");
    $stmt->execute(['user_id' => $userId]);
    $patient = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$patient) {
        throw new Exception("Patient record not found.");
    }

    // Patient Initials
    $nameParts = explode(' ', trim($patient['full_name']));
    $userInitials = '';
    foreach ($nameParts as $part) {
        if (!empty($part)) {
            $userInitials .= strtoupper($part[0]);
        }
    }
    $userInitials = substr($userInitials, 0, 2);

    // 2. Fetch Stat Counts
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM prescriptions WHERE patient_id = :pid");
    $stmt->execute(['pid' => $patient['patient_id']]);
    $totalPrescriptions = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM lab_test_orders WHERE patient_id = :pid");
    $stmt->execute(['pid' => $patient['patient_id']]);
    $totalLabTests = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM vaccination_records WHERE patient_id = :pid");
    $stmt->execute(['pid' => $patient['patient_id']]);
    $totalVaccines = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM surgical_records WHERE patient_id = :pid");
    $stmt->execute(['pid' => $patient['patient_id']]);
    $totalSurgeries = $stmt->fetchColumn();

    // 3. Fetch Hospitals for Selection Dropdown
    $stmt = $pdo->query("SELECT hospital_id, legal_name, official_email FROM hospitals WHERE license_status = 'licensed' ORDER BY legal_name ASC");
    $hospitals = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 4. Fetch Vaccines Catalog
    $stmt = $pdo->query("SELECT vaccine_id, vaccine_name, dose_ml FROM vaccine_catalog ORDER BY vaccine_name ASC");
    $vaccines = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 5. Fetch Existing Vaccine Appointments History
    $stmt = $pdo->prepare("
        SELECT va.*, v.vaccine_name, h.legal_name as hospital_name 
        FROM vaccine_appointments va
        JOIN vaccine_catalog v ON v.vaccine_id = va.vaccine_id
        JOIN hospitals h ON h.hospital_id = va.hospital_id
        WHERE va.patient_id = :pid 
        ORDER BY va.scheduled_date DESC
    ");
    $stmt->execute(['pid' => $patient['patient_id']]);
    $vaccineAppointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    return [
        'patient'            => $patient,
        'userInitials'       => $userInitials,
        'totalPrescriptions' => $totalPrescriptions,
        'totalLabTests'      => $totalLabTests,
        'totalVaccines'      => $totalVaccines,
        'totalSurgeries'     => $totalSurgeries,
        'hospitals'          => $hospitals,
        'vaccines'           => $vaccines,
        'vaccineAppointments'=> $vaccineAppointments
    ];

} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}