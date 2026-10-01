<?php
session_start();

// Load DB connection using database file
require_once __DIR__ . '/../../db.php'; 

// Check authentication
$userId = $_SESSION['user_id'] ?? null;
if (!$userId) {
    // Demo fallback for testing if session is unassigned
    $userId = 1;
}

try {
    // 1. Fetch Patient Info
    $stmt = $pdo->prepare("SELECT * FROM patients WHERE user_id = :user_id LIMIT 1");
    $stmt->execute([':user_id' => $userId]);
    $patient = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$patient) {
        $patient = [
            'patient_id' => 1,
            'full_name'  => 'Shishir Rahaman',
            'user_uid'   => '48291'
        ];
    }

    // Initials calculation
    $nameParts = explode(' ', trim($patient['full_name']));
    $userInitials = strtoupper(substr($nameParts[0], 0, 1) . (isset($nameParts[1]) ? substr($nameParts[1], 0, 1) : ''));

    // 2. Fetch Available Doctors
    $docStmt = $pdo->query("
        SELECT d.*, h.legal_name as hospital_name, s.name as specialty_name
        FROM doctors d
        LEFT JOIN hospitals h ON d.hospital_id = h.hospital_id
        LEFT JOIN specialties s ON d.primary_specialty_id = s.specialty_id
        WHERE d.status = 'active'
        LIMIT 10
    ");
    $doctors = $docStmt->fetchAll(PDO::FETCH_ASSOC);

    // 3. Fetch Hospitals and Specialties for Filters
    $hospitals = $pdo->query("SELECT hospital_id, legal_name FROM hospitals WHERE license_status = 'licensed'")->fetchAll(PDO::FETCH_ASSOC);
    $specialties = $pdo->query("SELECT specialty_id, name FROM specialties")->fetchAll(PDO::FETCH_ASSOC);

    // 4. Fetch Patient's Active Appointments
    $apptStmt = $pdo->prepare("
        SELECT a.*, d.full_name as doctor_name, h.legal_name as hospital_name
        FROM appointments a
        JOIN doctors d ON a.doctor_id = d.doctor_id
        LEFT JOIN hospitals h ON a.hospital_id = h.hospital_id
        WHERE a.patient_id = :patient_id
        ORDER BY a.appointment_date DESC
    ");
    $apptStmt->execute([':patient_id' => $patient['patient_id']]);
    $upcomingBookings = $apptStmt->fetchAll(PDO::FETCH_ASSOC);

    return [
        'patient'          => $patient,
        'userInitials'     => $userInitials,
        'doctors'          => $doctors,
        'hospitals'        => $hospitals,
        'specialties'      => $specialties,
        'upcomingBookings' => $upcomingBookings
    ];

} catch (PDOException $e) {
    die("Database Connection Error: " . $e->getMessage());
}