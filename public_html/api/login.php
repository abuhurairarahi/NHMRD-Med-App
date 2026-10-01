<?php
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
    exit;
}

try {
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
}
?>
