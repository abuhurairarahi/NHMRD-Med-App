<?php
session_start();

$host = 'localhost';
$dbname = 'nhmrd';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die(json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]));
}

// Temporary for testing based on hello.md
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = '2042122004'; // Patient UID from hello.md
}

function get_logged_in_patient($pdo) {
    $identifier = $_SESSION['user_id'] ?? $_SESSION['uid'] ?? $_SESSION['patient_id'] ?? '2042122004';
    
    // First try matching user by uid, user_id, or patient_id
    $stmt = $pdo->prepare("SELECT p.*, u.uid, u.role, u.status AS user_status 
                           FROM patients p 
                           JOIN users u ON p.user_id = u.user_id 
                           WHERE u.uid = ? OR u.user_id = ? OR p.patient_id = ?");
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
?>
