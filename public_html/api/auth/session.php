<?php
// public_html/api/auth/session.php
header('Content-Type: application/json');
$pdo = require_once __DIR__ . '/../../config/db.php';

$userId = $_SESSION['user_id'] ?? null;

// Optional: Fallback for direct development access if passed ?as=doctor / ?as=patient / ?as=admin / etc.
if (!$userId && isset($_GET['as'])) {
    $roleMap = [
        'admin'     => 1,
        'executive' => 2,
        'doctor'    => 3,
        'surgeon'   => 4,
        'patient'   => 5
    ];
    $targetUid = $roleMap[$_GET['as']] ?? null;
    if ($targetUid) {
        $stmt = $pdo->prepare("SELECT user_id, uid, role, status FROM users WHERE user_id = ?");
        $stmt->execute([$targetUid]);
        if ($u = $stmt->fetch()) {
            $_SESSION['user_id'] = $u['user_id'];
            $_SESSION['uid']     = $u['uid'];
            $_SESSION['role']    = $u['role'];
            $userId              = $u['user_id'];
        }
    }
}

if (!$userId) {
    echo json_encode([
        'logged_in' => false,
        'message'   => 'No active session.'
    ]);
    exit;
}

$stmt = $pdo->prepare("SELECT user_id, uid, nid, role, status FROM users WHERE user_id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    session_destroy();
    echo json_encode(['logged_in' => false, 'message' => 'User not found.']);
    exit;
}

$profile = [
    'user_id' => $user['user_id'],
    'uid'     => $user['uid'],
    'nid'     => $user['nid'],
    'role'    => $user['role'],
    'status'  => $user['status'],
];

if ($user['role'] === 'patient') {
    $pStmt = $pdo->prepare("SELECT * FROM patients WHERE user_id = ?");
    $pStmt->execute([$userId]);
    if ($patient = $pStmt->fetch()) {
        $profile['patient_id']     = $patient['patient_id'];
        $profile['full_name']      = $patient['full_name'];
        $profile['health_card_no'] = $patient['health_card_no'];
        $profile['dob']            = $patient['dob'];
        $profile['gender']         = $patient['gender'];
        $profile['blood_group']    = $patient['blood_group'];
        $profile['blood_pressure'] = $patient['blood_pressure'];
        $profile['bmi']            = $patient['bmi'];
        $profile['phone']          = $patient['phone'];
    }
} elseif ($user['role'] === 'doctor' || $user['role'] === 'surgeon') {
    $dStmt = $pdo->prepare("
        SELECT d.*, h.legal_name AS hospital_name, s.name AS specialty_name
        FROM doctors d
        LEFT JOIN hospitals h ON d.hospital_id = h.hospital_id
        LEFT JOIN specialties s ON d.primary_specialty_id = s.specialty_id
        WHERE d.user_id = ?
    ");
    $dStmt->execute([$userId]);
    if ($doc = $dStmt->fetch()) {
        $profile['doctor_id']            = $doc['doctor_id'];
        $profile['full_name']            = $doc['full_name'];
        $profile['bmdc_registration_no'] = $doc['bmdc_registration_no'];
        $profile['doctor_type']          = $doc['doctor_type'];
        $profile['hospital_id']          = $doc['hospital_id'];
        $profile['hospital_name']        = $doc['hospital_name'];
        $profile['specialty_name']       = $doc['specialty_name'];
        $profile['duty_status']          = $doc['duty_status'];
        $profile['shift_schedule']       = $doc['shift_schedule'];
        $profile['phone']                = $doc['phone'];
        $profile['email']                = $doc['email'];
    }
} elseif ($user['role'] === 'executive') {
    $eStmt = $pdo->prepare("
        SELECT e.*, h.legal_name AS hospital_name
        FROM medical_executives e
        LEFT JOIN hospitals h ON e.hospital_id = h.hospital_id
        WHERE e.user_id = ?
    ");
    $eStmt->execute([$userId]);
    if ($exec = $eStmt->fetch()) {
        $profile['executive_id']  = $exec['executive_id'];
        $profile['full_name']     = $exec['full_name'];
        $profile['designation']   = $exec['designation'];
        $profile['hospital_name'] = $exec['hospital_name'];
        $profile['phone']         = $exec['phone'];
        $profile['email']         = $exec['email'];
    }
} elseif ($user['role'] === 'admin') {
    $aStmt = $pdo->prepare("SELECT * FROM admins WHERE user_id = ?");
    $aStmt->execute([$userId]);
    if ($adm = $aStmt->fetch()) {
        $profile['admin_id']    = $adm['admin_id'];
        $profile['full_name']   = $adm['full_name'];
        $profile['designation'] = $adm['designation'];
        $profile['department']  = $adm['department'];
        $profile['phone']       = $adm['phone'];
        $profile['email']       = $adm['email'];
    }
}

echo json_encode([
    'logged_in' => true,
    'user'      => $profile
]);
