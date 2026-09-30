<?php
// public_html/php/doctor-panel/doctor_bootstrap.php
// Central bootstrap & session context for NHMRD Doctor Panel

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pdo = require_once __DIR__ . '/../../config/db.php';

// Resolve current Doctor ID
$doctorId = isset($_GET['doctor_id']) ? (int)$_GET['doctor_id'] : ($_SESSION['doctor_id'] ?? null);

$currentDoctor = null;

if ($pdo) {
    if ($doctorId) {
        $stmtDoc = $pdo->prepare("
            SELECT d.*, h.legal_name AS hospital_name, s.name AS specialty_name, u.uid AS user_uid
            FROM doctors d
            LEFT JOIN hospitals h ON d.hospital_id = h.hospital_id
            LEFT JOIN specialties s ON d.primary_specialty_id = s.specialty_id
            LEFT JOIN users u ON d.user_id = u.user_id
            WHERE d.doctor_id = ?
            LIMIT 1
        ");
        $stmtDoc->execute([$doctorId]);
        $currentDoctor = $stmtDoc->fetch();
    }

    if (!$currentDoctor) {
        $stmtDoc = $pdo->query("
            SELECT d.*, h.legal_name AS hospital_name, s.name AS specialty_name, u.uid AS user_uid
            FROM doctors d
            LEFT JOIN hospitals h ON d.hospital_id = h.hospital_id
            LEFT JOIN specialties s ON d.primary_specialty_id = s.specialty_id
            LEFT JOIN users u ON d.user_id = u.user_id
            WHERE d.status = 'active'
            ORDER BY d.doctor_id ASC
            LIMIT 1
        ");
        $currentDoctor = $stmtDoc->fetch();
        if ($currentDoctor) {
            $doctorId = (int)$currentDoctor['doctor_id'];
            $_SESSION['doctor_id'] = $doctorId;
        }
    }
}

// Fallback doctor profile if database record is missing
if (!$currentDoctor) {
    $currentDoctor = [
        'doctor_id'            => 1,
        'full_name'            => 'Dr. Nusrat Jahan, MD',
        'specialty_name'       => 'Internal Medicine & Therapeutics',
        'qualifications'       => 'MBBS, FCPS (Internal Medicine), MD',
        'designation'          => 'Associate Professor & Clinical Consultant',
        'hospital_name'        => 'Dhaka Central Medical University Hospital',
        'shift_schedule'       => 'Morning Clinical Rounds (07:00 - 15:30)',
        'duty_status'          => 'on_duty',
        'bmdc_registration_no' => 'A-58921',
        'phone'                => '+880 1711-234567',
        'email'                => 'nusrat.jahan@nhmrd.gov.bd',
        'photo_url'            => 'https://i.pravatar.cc/100?img=47',
        'years_experience'     => 14,
    ];
    $doctorId = 1;
    $_SESSION['doctor_id'] = $doctorId;
}

$doctorName      = $currentDoctor['full_name'];
$doctorSpecialty = $currentDoctor['specialty_name'] ?? 'Internal Medicine & Therapeutics';
$doctorHospital  = $currentDoctor['hospital_name'] ?? 'Dhaka Central Medical University Hospital';
$doctorShift     = $currentDoctor['shift_schedule'] ?? 'Morning Clinical Rounds (07:00 - 15:30)';
$doctorAvatar    = !empty($currentDoctor['photo_url']) ? $currentDoctor['photo_url'] : 'https://i.pravatar.cc/100?img=47';

// Resolve current Patient context for patient-specific views
$patientId = isset($_GET['patient_id']) ? (int)$_GET['patient_id'] : ($_SESSION['current_patient_id'] ?? null);

// If MRN search was passed
if (!$patientId && !empty($_GET['mrn'])) {
    $mrnInput = trim($_GET['mrn']);
    $stmtMrn = $pdo->prepare("SELECT patient_id FROM patients WHERE health_card_no = ? OR crvs_token = ? OR patient_id = ? LIMIT 1");
    $stmtMrn->execute([$mrnInput, $mrnInput, (int)$mrnInput]);
    $patientId = $stmtMrn->fetchColumn() ?: null;
}

if (!$patientId && $pdo) {
    // Pick first active patient as default demo context
    $stmtPat = $pdo->query("SELECT patient_id FROM patients WHERE status = 'active' ORDER BY patient_id ASC LIMIT 1");
    $patientId = $stmtPat->fetchColumn() ?: 1;
}
if (!$patientId) {
    $patientId = 1;
}
$_SESSION['current_patient_id'] = $patientId;

// Helper: Fetch Full Patient Dossier
function getPatientDossier(PDO $pdo, int $pId): array {
    $stmt = $pdo->prepare("
        SELECT p.*, u.uid AS user_uid, h.legal_name AS registered_hospital_name
        FROM patients p
        LEFT JOIN users u ON p.user_id = u.user_id
        LEFT JOIN hospitals h ON p.registering_hospital_id = h.hospital_id
        WHERE p.patient_id = ?
        LIMIT 1
    ");
    $stmt->execute([$pId]);
    $pat = $stmt->fetch();

    if (!$pat) {
        $pat = [
            'patient_id'     => $pId,
            'full_name'      => 'Farhana Islam',
            'gender'         => 'female',
            'dob'            => '1988-03-14',
            'blood_group'    => 'A+',
            'health_card_no' => 'SHID-88019-449102',
            'phone'          => '+8801812345678',
            'email'          => 'farhana.islam@gmail.com',
            'address'        => 'House 42, Road 9A, Dhanmondi R/A, Dhaka-1209',
            'height_cm'      => 165.0,
            'weight_kg'      => 62.0,
            'blood_pressure' => '120/80',
            'bmi'            => 22.8,
            'status'         => 'active',
        ];
    }

    // Age
    $birthDate = new DateTime($pat['dob']);
    $todayDate = new DateTime();
    $pat['age'] = $todayDate->diff($birthDate)->y;

    // Allergies
    $stmtAllergies = $pdo->prepare("SELECT allergy_name, severity FROM patient_allergies WHERE patient_id = ?");
    $stmtAllergies->execute([$pId]);
    $pat['allergies'] = $stmtAllergies->fetchAll();

    // Chronic conditions
    $stmtChronic = $pdo->prepare("SELECT condition_name, diagnosed_date FROM patient_chronic_conditions WHERE patient_id = ?");
    $stmtChronic->execute([$pId]);
    $pat['chronic_conditions'] = $stmtChronic->fetchAll();

    // Contacts
    $stmtContacts = $pdo->prepare("SELECT label, value FROM patient_contacts WHERE patient_id = ?");
    $stmtContacts->execute([$pId]);
    $pat['contacts'] = $stmtContacts->fetchAll();

    // Blood Donors
    $stmtDonors = $pdo->prepare("SELECT donor_name, donor_blood_group, donor_phone, is_available FROM patient_blood_donors WHERE patient_id = ?");
    $stmtDonors->execute([$pId]);
    $pat['donors'] = $stmtDonors->fetchAll();

    return $pat;
}
