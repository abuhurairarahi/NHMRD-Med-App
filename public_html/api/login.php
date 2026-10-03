<?php
require_once __DIR__ . '/db.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'POST method required']);
    exit;
}

// Support both JSON body and standard POST data
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
    // 1. Fetch user
    $stmt = $pdo->prepare("SELECT user_id, uid, nid, password_hash, role, status FROM users WHERE uid = :id1 OR nid = :id2 OR user_id = :id3 LIMIT 1");
    $stmt->execute([
        'id1' => $identifier,
        'id2' => $identifier,
        'id3' => $identifier
    ]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    $isValid = false;
    
    // 2. Verify password
    if ($user) {
        // Standard verify OR legacy plaintext check
        if (password_verify($password, $user['password_hash']) || $password === $user['password_hash']) {
            $isValid = true;
        }
        
        // Demo passwords fallback
        $demoPasswords = [
            '20421201' => 'admin123',
            '20421202' => 'executive123',
            '20421203' => 'doctor123',
            '20421204' => 'surgeon123',
            '2042122004' => 'patient123'
        ];
        
        if (!$isValid && isset($demoPasswords[$user['uid']]) && $password === $demoPasswords[$user['uid']]) {
            $isValid = true;
        }
    }

    if (!$user || !$isValid) {
        echo json_encode(['success' => false, 'message' => 'Invalid User ID/NID or password.']);
        exit;
    }

    if ($user['status'] !== 'active') {
        echo json_encode(['success' => false, 'message' => 'Account is ' . $user['status'] . '. Contact system administrator.']);
        exit;
    }

    // 3. Set basic Session data
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    $_SESSION['user_id']   = $user['user_id'];
    $_SESSION['uid']       = $user['uid'];
    $_SESSION['role']      = $user['role'];
    $_SESSION['role_type'] = $user['role']; // Maintained for backward compatibility

    // 4. Set role-specific Session data
    if ($user['role'] === 'patient') {
        $pStmt = $pdo->prepare("SELECT patient_id FROM patients WHERE user_id = ?");
        $pStmt->execute([$user['user_id']]);
        $patientId = $pStmt->fetchColumn();
        $_SESSION['patient_id'] = $patientId ?: 1;
        
    } elseif (in_array($user['role'], ['doctor', 'surgeon'])) {
        $dStmt = $pdo->prepare("SELECT doctor_id FROM doctors WHERE user_id = ?");
        $dStmt->execute([$user['user_id']]);
        $doctorId = $dStmt->fetchColumn();
        if ($doctorId) {
            $_SESSION['doctor_id'] = $doctorId;
        }
    }

    // 5. Determine Role-based redirects
    $roleRedirects = [
        'admin'     => '../pages/admin-panel/admin-dashboard.php',
        'executive' => '../pages/medical-executive-panel/executive-dashboard.php',
        'doctor'    => '../pages/doctor-panel/dashboard.php',
        'surgeon'   => '../pages/surgeon-panel/dashboard.php',
        'patient'   => '../pages/patient-panel/dashboard.php',
    ];

    $redirect = $roleRedirects[$user['role']] ?? '../pages/patient-panel/dashboard.php';

    // 6. Return success
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