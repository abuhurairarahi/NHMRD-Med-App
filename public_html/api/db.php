<?php
// Removed duplicate variables and invisible non-breaking spaces
$host = '127.0.0.1';
$dbname = 'nhmrd';
$username = 'root';
$password = '';
$charset = 'utf8mb4';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

// Fixed Try-Catch logic for the fallback connection
try {
    $pdo = new PDO($dsn, $username, $password, $options);
} catch (PDOException $e) {
    // If 127.0.0.1 fails, fallback to localhost
    try {
        $dsn_fallback = "mysql:host=localhost;dbname=$dbname;charset=$charset";
        $pdo = new PDO($dsn_fallback, $username, $password, $options);
    } catch (PDOException $ex) {
        // Only die if BOTH connections fail
        die(json_encode(['success' => false, 'error' => 'Database connection failed: ' . $ex->getMessage()]));
    }
}

// Default session for demo testing if not set
if (!isset($_SESSION['user_id']) && !isset($_SESSION['uid'])) {
    $_SESSION['user_id'] = 5;
    $_SESSION['uid'] = '2042122004';
    $_SESSION['role'] = 'patient';
    $_SESSION['patient_id'] = 1;
}

if (!function_exists('get_logged_in_patient')) {
    function get_logged_in_patient($pdo) {
        $identifier = $_SESSION['uid'] ?? $_SESSION['user_id'] ?? $_SESSION['patient_id'] ?? '2042122004';
        
        // Match user by uid, user_id, or patient_id
        $stmt = $pdo->prepare("SELECT p.*, u.uid, u.role, u.status AS user_status 
                               FROM patients p 
                               JOIN users u ON p.user_id = u.user_id 
                               WHERE u.uid = ? OR u.user_id = ? OR p.patient_id = ? 
                               LIMIT 1");
        $stmt->execute([$identifier, $identifier, $identifier]);
        $patient = $stmt->fetch();
        
        if (!$patient) {
            // Fallback to first active patient
            $stmt = $pdo->query("SELECT p.*, u.uid, u.role, u.status AS user_status 
                                 FROM patients p 
                                 LEFT JOIN users u ON p.user_id = u.user_id 
                                 ORDER BY p.patient_id ASC LIMIT 1");
            $patient = $stmt->fetch();
        }
        return $patient ?: null;
    }
}
?>