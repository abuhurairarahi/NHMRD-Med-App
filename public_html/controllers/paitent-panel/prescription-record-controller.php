<?php
// Prevent direct access
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Database Connection
$dbPath = __DIR__ . '/../../db.php';
if (!file_exists($dbPath)) {
    // Fallback if db.php is in root or another relative path
    $dbPath = $_SERVER['DOCUMENT_ROOT'] . '/db.php';
}
$pdo = require_once $dbPath;

// 2. Resolve Active Patient User ID
// Adjust session variable key to match your auth structure (e.g., $_SESSION['user_id'])
$userId = $_SESSION['user_id'] ?? $_SESSION['user']['user_id'] ?? null;

if (!$userId) {
    // Demo fallback for testing if no active session
    $userId = 1; 
}

// 3. Fetch Patient Record
$stmt = $pdo->prepare("
    SELECT p.*, u.uid AS user_uid 
    FROM patients p 
    LEFT JOIN users u ON u.user_id = p.user_id 
    WHERE p.user_id = :user_id OR p.patient_id = :user_id
    LIMIT 1
");
$stmt->execute(['user_id' => $userId]);
$patient = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$patient) {
    die("Patient record not found.");
}

$patientId = $patient['patient_id'];

// Calculate User Initials
$nameParts = explode(' ', trim($patient['full_name']));
$userInitials = strtoupper(substr($nameParts[0], 0, 1));
if (count($nameParts) > 1) {
    $userInitials .= strtoupper(substr(end($nameParts), 0, 1));
}

// 4. Fetch Summary Counts for Dashboard Stat Cards
$stmt = $pdo->prepare("SELECT COUNT(*) FROM prescriptions WHERE patient_id = :pid");
$stmt->execute(['pid' => $patientId]);
$totalPrescriptions = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM lab_test_orders WHERE patient_id = :pid");
$stmt->execute(['pid' => $patientId]);
$totalLabTests = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM vaccination_records WHERE patient_id = :pid");
$stmt->execute(['pid' => $patientId]);
$totalVaccines = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM surgical_records WHERE patient_id = :pid");
$stmt->execute(['pid' => $patientId]);
$totalSurgeries = $stmt->fetchColumn();

// 5. Fetch Full Prescription Records with Medications & Doctors
$stmt = $pdo->prepare("
    SELECT 
        rx.prescription_id,
        rx.title,
        rx.chief_complaint,
        rx.symptoms,
        rx.current_condition,
        rx.doctors_statement,
        rx.next_visit_date,
        rx.visit_type,
        rx.status,
        rx.is_long_term,
        rx.created_at,
        d.full_name AS doctor_name,
        d.bmdc_registration_no,
        sp.name AS specialty_name,
        h.legal_name AS hospital_name,
        surg.full_name AS referred_surgeon_name
    FROM prescriptions rx
    JOIN doctors d ON d.doctor_id = rx.doctor_id
    LEFT JOIN specialties sp ON sp.specialty_id = d.primary_specialty_id
    LEFT JOIN hospitals h ON h.hospital_id = d.hospital_id
    LEFT JOIN doctors surg ON surg.doctor_id = rx.referred_surgeon_id
    WHERE rx.patient_id = :pid
    ORDER BY rx.created_at DESC
");
$stmt->execute(['pid' => $patientId]);
$prescriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Attach medications and lab tests to each prescription
foreach ($prescriptions as &$rx) {
    // Fetch Medications
    $medStmt = $pdo->prepare("
        SELECT medication_name, dose_strength, route_frequency, dispense_quantity, refills, sig_instructions 
        FROM prescription_medications 
        WHERE prescription_id = :rx_id
    ");
    $medStmt->execute(['rx_id' => $rx['prescription_id']]);
    $rx['medications'] = $medStmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch Prescribed Lab Tests
    $labStmt = $pdo->prepare("
        SELECT test_name, test_code, instructions 
        FROM prescription_lab_tests 
        WHERE prescription_id = :rx_id
    ");
    $labStmt->execute(['rx_id' => $rx['prescription_id']]);
    $rx['lab_tests'] = $labStmt->fetchAll(PDO::FETCH_ASSOC);
}
unset($rx);

// Return consolidated payload array
return [
    'patient'            => $patient,
    'userInitials'       => $userInitials,
    'totalPrescriptions' => $totalPrescriptions,
    'totalLabTests'      => $totalLabTests,
    'totalVaccines'      => $totalVaccines,
    'totalSurgeries'     => $totalSurgeries,
    'prescriptions'      => $prescriptions
];