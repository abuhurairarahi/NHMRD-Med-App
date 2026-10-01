<?php
// Ensure session is started and user authenticated
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$dbPath = __DIR__ . '/../../config/db.php';
$pdo = require_once $dbPath;

// Fallback session user check (Replace with your login session key if needed)
$user_id = $_SESSION['user_id'] ?? 1;

try {
    // 1. Fetch Patient Info & User UID
    $stmtPatient = $pdo->prepare("
        SELECT p.*, u.uid AS user_uid 
        FROM patients p 
        JOIN users u ON p.user_id = u.user_id 
        WHERE p.user_id = :user_id 
        LIMIT 1
    ");
    $stmtPatient->execute([':user_id' => $user_id]);
    $patient = $stmtPatient->fetch(PDO::FETCH_ASSOC);

    if (!$patient) {
        die("Patient record not found.");
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

    // 2. Fetch Sample Collection Centers (Hospitals)
    $stmtHospitals = $pdo->query("
        SELECT hospital_id, legal_name 
        FROM hospitals 
        WHERE license_status = 'licensed' 
        ORDER BY legal_name ASC
    ");
    $hospitals = $stmtHospitals->fetchAll(PDO::FETCH_ASSOC);

    // 3. Fetch Lab Test Catalog
    $stmtTests = $pdo->query("
        SELECT test_id, test_name, category, price, test_code 
        FROM lab_test_catalog 
        ORDER BY test_name ASC
    ");
    $labTests = $stmtTests->fetchAll(PDO::FETCH_ASSOC);

    // 4. Fetch Latest Prescription (for doctor advice linking)
    $stmtPresc = $pdo->prepare("
        SELECT prescription_id, title, created_at 
        FROM prescriptions 
        WHERE patient_id = :patient_id 
        ORDER BY created_at DESC 
        LIMIT 1
    ");
    $stmtPresc->execute([':patient_id' => $patient['patient_id']]);
    $latestPrescription = $stmtPresc->fetch(PDO::FETCH_ASSOC);

    // Fetch Advised Test IDs if prescription exists
    $advisedTestIds = [];
    if ($latestPrescription) {
        $stmtAdvised = $pdo->prepare("
            SELECT test_name 
            FROM prescription_lab_tests 
            WHERE prescription_id = :rx_id
        ");
        $stmtAdvised->execute([':rx_id' => $latestPrescription['prescription_id']]);
        $advisedTestNames = $stmtAdvised->fetchAll(PDO::FETCH_COLUMN);

        // Match with lab_test_catalog IDs
        if (!empty($advisedTestNames)) {
            $inClause = implode(',', array_fill(0, count($advisedTestNames), '?'));
            $stmtMatch = $pdo->prepare("SELECT test_id FROM lab_test_catalog WHERE test_name IN ($inClause)");
            $stmtMatch->execute($advisedTestNames);
            $advisedTestIds = $stmtMatch->fetchAll(PDO::FETCH_COLUMN);
        }
    }

    return [
        'patient'            => $patient,
        'userInitials'       => $userInitials,
        'hospitals'          => $hospitals,
        'labTests'           => $labTests,
        'latestPrescription' => $latestPrescription,
        'advisedTestIds'     => $advisedTestIds
    ];

} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}