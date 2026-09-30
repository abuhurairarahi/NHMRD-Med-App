<?php
// login.php
require_once 'db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = trim($_POST['identifier'] ?? '');
    $password   = trim($_POST['password'] ?? '');

    if (empty($identifier) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Please provide both User ID/NID and password.']);
        exit;
    }

    // Query user by UID or NID
    $stmt = $pdo->prepare("SELECT user_id, uid, nid, password_hash, role, status, must_reset_password FROM users WHERE uid = :id OR nid = :id LIMIT 1");
    $stmt->execute(['id' => $identifier]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        if ($user['status'] !== 'active') {
            echo json_encode(['success' => false, 'message' => 'Account is ' . $user['status'] . '. Contact system administrator.']);
            exit;
        }

        // Set Session Variables
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['uid']     = $user['uid'];
        $_SESSION['role']    = $user['role'];

        // Update last login timestamp
        $updateStmt = $pdo->prepare("UPDATE users SET last_login_at = NOW() WHERE user_id = ?");
        $updateStmt->execute([$user['user_id']]);

        echo json_encode([
            'success' => true,
            'role' => $user['role'],
            'must_reset' => (bool)$user['must_reset_password'],
            'redirect' => $user['role'] . '-dashboard.php'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid credentials.']);
    }
    exit;
}
?>