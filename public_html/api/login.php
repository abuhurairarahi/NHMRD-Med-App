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
    exit;
}

try {
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
}
?>
