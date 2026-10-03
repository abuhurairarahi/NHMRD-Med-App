<?php

header('Content-Type: application/json');
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
    exit;
}

$userid = $_POST['userid'] ?? '';
$password = $_POST['password'] ?? '';

if (empty($userid) || empty($password)) {
    echo json_encode(['success' => false, 'error' => 'Please provide both UserID and Password.']);

require_once __DIR__ . '/db.php';
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'POST method required']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

$identifier = trim($input['identifier'] ?? $input['userid'] ?? '');
$password   = trim($input['password'] ?? '');

if (empty($identifier) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Please provide both User ID/NID and password.']);
>>>>>>> 71b16b1707a81a34d27c1bac9d704c80e3d2e86e
    exit;
}

try {
<<<<<<< HEAD
    // 1. Fetch user by UID
    $stmt = $pdo->prepare("SELECT user_id, password_hash, role_type FROM users WHERE uid = ? AND status = 'active'");
    $stmt->execute([$userid]);
    $user = $stmt->fetch();

    if (!$user) {
        // Mock success for doctor demo based on previous requests
        if ($userid === '20421203' && $password === 'doctor123') {
            echo json_encode([
                'success' => true,
                'role' => 'doctor',
                'redirect' => '/public_html/pages/doctor-panel/dashboard.html'
            ]);
            exit;
        }

        echo json_encode(['success' => false, 'error' => 'Invalid UserID or account is inactive.']);
        exit;
    }

    // 2. Verify password (for demo purposes, if it's plaintext, check directly. In real world, use password_verify)
    // Assuming password_hash contains plaintext for this demo or we use password_verify
    $is_valid = password_verify($password, $user['password_hash']);
    
    // For demo purposes, we will also allow plaintext check if password_verify fails
    if (!$is_valid && $password === $user['password_hash']) {
        $is_valid = true;
    }

    if (!$is_valid) {
        echo json_encode(['success' => false, 'error' => 'Incorrect password.']);
        exit;
    }

    // 3. Set Session and return redirect path based on role
    session_start();
    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['role_type'] = $user['role_type'];

    $redirect = '/';
    switch ($user['role_type']) {
        case 'doctor':
        case 'surgeon':
            // we'll need to fetch their specific doctor_id for the session
            $stmt = $pdo->prepare("SELECT doctor_id FROM doctors WHERE user_id = ?");
            $stmt->execute([$user['user_id']]);
            $doc = $stmt->fetch();
            if($doc) $_SESSION['doctor_id'] = $doc['doctor_id'];
            
            $redirect = '/public_html/pages/doctor-panel/dashboard.html';
            break;
        case 'patient':
            $redirect = '/public_html/pages/patient-panel/dashboard.html';
            break;
        case 'admin':
            $redirect = '/public_html/pages/admin-panel/dashboard.html';
            break;
        case 'executive':
            $redirect = '/public_html/pages/executive-panel/dashboard.html';
            break;
    }

    echo json_encode(['success' => true, 'role' => $user['role_type'], 'redirect' => $redirect]);

} catch (\PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
=======
    $stmt = $pdo->prepare("SELECT user_id, uid, nid, password_hash, role, status FROM users WHERE uid = :id OR nid = :id LIMIT 1");
    $stmt->execute(['id' => $identifier]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid User ID/NID or password.']);
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

    // If patient, also store patient_id
    if ($user['role'] === 'patient') {
        $pStmt = $pdo->prepare("SELECT patient_id FROM patients WHERE user_id = ?");
        $pStmt->execute([$user['user_id']]);
        $_SESSION['patient_id'] = $pStmt->fetchColumn() ?: 1;
    }

    // Role-based redirects
    $roleRedirects = [
        'admin'     => '../pages/admin-panel/admin-dashboard.html',
        'executive' => '../pages/medical-executive-panel/executive-dashboard.html',
        'doctor'    => '../pages/doctor-panel/dashboard.html',
        'surgeon'   => '../pages/surgeon-panel/dashboard.html',
        'patient'   => '../pages/patient-panel/dashboard.html',
    ];

    $redirect = $roleRedirects[$user['role']] ?? '../pages/patient-panel/dashboard.html';

    echo json_encode([
        'success'  => true,
        'role'     => $user['role'],
        'uid'      => $user['uid'],
        'redirect' => $redirect
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
>>>>>>> 71b16b1707a81a34d27c1bac9d704c80e3d2e86e
}
?>
