<?php
// controllers/auth/login.php — handles POST login requests
header('Content-Type: application/json');

$pdo = require_once __DIR__ . '/../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

$identifier = trim($_POST['identifier'] ?? '');
$password   = trim($_POST['password'] ?? '');

if (empty($identifier) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Please provide both User ID/NID and password.']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT user_id, uid, nid, password_hash, role, status, must_reset_password FROM users WHERE uid = :uid OR nid = :nid LIMIT 1");
    $stmt->execute(['uid' => $identifier, 'nid' => $identifier]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid credentials. Please check your User ID/NID and password.']);
        exit;
    }

    if ($user['status'] !== 'active') {
        echo json_encode(['success' => false, 'message' => 'Account is ' . $user['status'] . '. Contact system administrator.']);
        exit;
    }

    // Set session
    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['uid']     = $user['uid'];
    $_SESSION['role']    = $user['role'];

    // Also link entity id into session
    if ($user['role'] === 'patient') {
        $pStmt = $pdo->prepare("SELECT patient_id, full_name, health_card_no FROM patients WHERE user_id = ?");
        $pStmt->execute([$user['user_id']]);
        if ($p = $pStmt->fetch()) {
            $_SESSION['patient_id'] = $p['patient_id'];
            $_SESSION['full_name']  = $p['full_name'];
            $_SESSION['health_card_no'] = $p['health_card_no'];
        }
    } elseif ($user['role'] === 'doctor' || $user['role'] === 'surgeon') {
        $dStmt = $pdo->prepare("SELECT doctor_id, full_name, doctor_type, hospital_id FROM doctors WHERE user_id = ?");
        $dStmt->execute([$user['user_id']]);
        if ($d = $dStmt->fetch()) {
            $_SESSION['doctor_id'] = $d['doctor_id'];
            $_SESSION['full_name'] = $d['full_name'];
            $_SESSION['hospital_id'] = $d['hospital_id'];
        }
    } elseif ($user['role'] === 'executive') {
        $eStmt = $pdo->prepare("SELECT executive_id, full_name, hospital_id FROM medical_executives WHERE user_id = ?");
        $eStmt->execute([$user['user_id']]);
        if ($e = $eStmt->fetch()) {
            $_SESSION['executive_id'] = $e['executive_id'];
            $_SESSION['full_name']    = $e['full_name'];
            $_SESSION['hospital_id']  = $e['hospital_id'];
        }
    } elseif ($user['role'] === 'admin') {
        $aStmt = $pdo->prepare("SELECT admin_id, full_name FROM admins WHERE user_id = ?");
        $aStmt->execute([$user['user_id']]);
        if ($a = $aStmt->fetch()) {
            $_SESSION['admin_id']  = $a['admin_id'];
            $_SESSION['full_name'] = $a['full_name'];
        }
    }

    // Update last_login_at
    $pdo->prepare("UPDATE users SET last_login_at = NOW() WHERE user_id = ?")->execute([$user['user_id']]);

    // Map role to dashboard path
    $roleRedirects = [
        'admin'     => '/public_html/pages/admin-panel/admin-dashboard.html',
        'executive' => '/public_html/pages/medical-executive-panel/executive-dashboard.html',
        'doctor'    => '/public_html/php/doctor-panel/dashboard.php',
        'surgeon'   => '/public_html/pages/surgeon-panel/dashboard.html',
        'patient'   => '/public_html/pages/patient-panel/dashboard.html',
    ];

    $redirect = $roleRedirects[$user['role']] ?? '/public_html/pages/login.html';

    echo json_encode([
        'success'    => true,
        'role'       => $user['role'],
        'must_reset' => (bool)$user['must_reset_password'],
        'redirect'   => $redirect
    ]);

} catch (Exception $e) {
    error_log('Login error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error. Please try again.']);
}
exit;