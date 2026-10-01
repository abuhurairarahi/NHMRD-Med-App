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
    $stmt = $pdo->prepare("SELECT user_id, uid, nid, password_hash, role, status, must_reset_password FROM users WHERE uid = :id OR nid = :id LIMIT 1");
    $stmt->execute(['id' => $identifier]);
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

    // Update last_login_at
    $pdo->prepare("UPDATE users SET last_login_at = NOW() WHERE user_id = ?")->execute([$user['user_id']]);

    // Map role to dashboard path
    $roleRedirects = [
        'admin'     => '/public_html/php/admin-panel/dashboard.php',
        'executive' => '/public_html/php/executive-panel/dashboard.php',
        'doctor'    => '/public_html/php/doctor-panel/dashboard.php',
        'surgeon'   => '/public_html/php/surgeon-panel/dashboard.php',
        'patient'   => '/public_html/php/Paitent-panel/dashboard.php',
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