<?php
// Ensure db.php is loaded for database connectivity
require_once __DIR__ . '/../../db.php'; 

session_start();

// Check patient authentication (adjust session key as needed)
if (!isset($_SESSION['user_id'])) {
    header("Location: /public_html/pages/login.php");
    exit();
}

$userId = $_SESSION['user_id'];

// 1. Fetch Patient Info & User Initials
$patientStmt = $pdo->prepare("
    SELECT p.*, u.uid as user_uid, u.email 
    FROM patients p 
    JOIN users u ON p.user_id = u.user_id 
    WHERE u.user_id = :user_id
");
$patientStmt->execute([':user_id' => $userId]);
$patient = $patientStmt->fetch(PDO::FETCH_ASSOC);

if (!$patient) {
    die("Patient profile not found.");
}

$names = explode(' ', trim($patient['full_name']));
$userInitials = strtoupper(substr($names[0], 0, 1) . (isset($names[1]) ? substr($names[1], 0, 1) : ''));

$patientId = $patient['patient_id'];

// 2. Fetch Stat Counters
$statRx = $pdo->prepare("SELECT COUNT(*) FROM prescriptions WHERE patient_id = :pid");
$statRx->execute([':pid' => $patientId]);
$totalPrescriptions = $statRx->fetchColumn();

$statLabs = $pdo->prepare("SELECT COUNT(*) FROM lab_test_orders WHERE patient_id = :pid");
$statLabs->execute([':pid' => $patientId]);
$totalLabTests = $statLabs->fetchColumn();

$statVaccines = $pdo->prepare("SELECT COUNT(*) FROM vaccine_records WHERE patient_id = :pid");
$statVaccines->execute([':pid' => $patientId]);
$totalVaccines = $statVaccines->fetchColumn();

$statSurgeries = $pdo->prepare("SELECT COUNT(*) FROM surgery_records WHERE patient_id = :pid");
$statSurgeries->execute([':pid' => $patientId]);
$totalSurgeries = $statSurgeries->fetchColumn();

// 3. Fetch Patient's Vaccine History Records
$vaccineStmt = $pdo->prepare("
    SELECT vr.*, h.legal_name as hospital_name, d.full_name as doctor_name
    FROM vaccine_records vr
    LEFT JOIN hospitals h ON vr.hospital_id = h.hospital_id
    LEFT JOIN doctors d ON vr.administered_by_doctor_id = d.doctor_id
    WHERE vr.patient_id = :pid
    ORDER BY vr.administered_date DESC
");
$vaccineStmt->execute([':pid' => $patientId]);
$vaccineRecords = $vaccineStmt->fetchAll(PDO::FETCH_ASSOC);

return [
    'patient'            => $patient,
    'userInitials'       => $userInitials,
    'totalPrescriptions' => $totalPrescriptions,
    'totalLabTests'      => $totalLabTests,
    'totalVaccines'      => $totalVaccines,
    'totalSurgeries'     => $totalSurgeries,
    'vaccineRecords'     => $vaccineRecords
];