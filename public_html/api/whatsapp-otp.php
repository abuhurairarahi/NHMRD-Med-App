<?php
/**
 * Universal WhatsApp OTP Generator & Dispatcher
 * National Health & Medical Record Directory (NHMRD)
 */

header('Content-Type: application/json; charset=utf-8');

// Configuration
$host         = '127.0.0.1';
$db           = 'nhmrd_db';
$user         = 'nhmrd_user';
$pass         = 'your_secure_password';
$charset      = 'utf8mb4';

$instanceId   = 'YOUR_ULTRAMSG_INSTANCE_ID'; 
$token        = 'YOUR_ULTRAMSG_TOKEN';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed.']);
    exit;
}

// 1. Read Request Parameters
$identifier = trim($_POST['identifier'] ?? '');

if (empty($identifier)) {
    echo json_encode(['status' => 'error', 'message' => 'User identifier (NID/Username/Email/Phone) is required.']);
    exit;
}

// 2. Fetch User Across Roles
try {
    $sql = "
        SELECT 
            u.uid, 
            u.username, 
            u.email, 
            u.nid, 
            u.role, 
            u.status AS account_status,
            COALESCE(p.phone, d.phone, e.phone, a.phone) AS phone_number,
            COALESCE(p.full_name, d.full_name, e.full_name, a.full_name) AS full_name
        FROM users u
        LEFT JOIN patients p ON u.uid = p.uid
        LEFT JOIN doctors d ON u.uid = d.uid
        LEFT JOIN medical_executives e ON u.uid = e.uid
        LEFT JOIN admins a ON u.uid = a.uid
        WHERE (u.username = :id_1 OR u.email = :id_2 OR u.nid = :id_3 
               OR p.phone = :id_4 OR d.phone = :id_5 OR e.phone = :id_6 OR a.phone = :id_7)
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'id_1' => $identifier,
        'id_2' => $identifier,
        'id_3' => $identifier,
        'id_4' => $identifier,
        'id_5' => $identifier,
        'id_6' => $identifier,
        'id_7' => $identifier
    ]);

    $userData = $stmt->fetch();

    if (!$userData) {
        echo json_encode(['status' => 'error', 'message' => 'No account found matching the given identifier.']);
        exit;
    }

    if ($userData['account_status'] !== 'active') {
        echo json_encode(['status' => 'error', 'message' => 'Account is inactive, suspended, or deceased.']);
        exit;
    }

    $phoneNumber = $userData['phone_number'];
    if (empty($phoneNumber)) {
        echo json_encode(['status' => 'error', 'message' => 'No phone number associated with this account.']);
        exit;
    }

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database query error: ' . $e->getMessage()]);
    exit;
}

// 3. Phone Sanitize (PHP 8.0+ Compatible Fix)
$formattedPhone = preg_replace('/[^0-9]/', '', $phoneNumber);

// Corrected string function for starting character checks
if (strlen($formattedPhone) === 11 && str_starts_with($formattedPhone, '01')) {
    $formattedPhone = '88' . $formattedPhone;
}

// 4. Generate OTP & Save Session
$otpCode = sprintf('%06d', random_int(0, 999999));
$expiresAt = date('Y-m-d H:i:s', strtotime('+10 minutes'));

session_start();
$_SESSION['whatsapp_otp'] = [
    'uid'        => $userData['uid'],
    'role'       => $userData['role'],
    'code'       => $otpCode,
    'expires_at' => $expiresAt
];

// 5. Send via WhatsApp API
$message = "Hello " . $userData['full_name'] . ",\n\nYour NHMRD verification code is: *" . $otpCode . "*\n\nThis code will expire in 10 minutes. Do not share it with anyone.";

$params = [
    'token' => $token, // $token is now declared at top of file
    'to'    => '+' . $formattedPhone,
    'body'  => $message
];

$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_URL            => "https://api.ultramsg.com/" . $instanceId . "/messages/chat",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING       => "",
    CURLOPT_MAXREDIRS      => 10,
    CURLOPT_TIMEOUT        => 30,
    CURLOPT_SSL_VERIFYHOST => 0,
    CURLOPT_SSL_VERIFYPEER => 0,
    CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST  => "POST",
    CURLOPT_POSTFIELDS     => http_build_query($params),
    CURLOPT_HTTPHEADER     => ["content-type: application/x-www-form-urlencoded"]
]);

$response = curl_exec($curl);
$err = curl_error($curl);
curl_close($curl);

if ($err) {
    echo json_encode(['status' => 'error', 'message' => 'cURL Error: ' . $err]);
} else {
    echo json_encode([
        'status'  => 'success',
        'message' => 'OTP sent successfully via WhatsApp to ' . substr($formattedPhone, 0, 5) . '****' . substr($formattedPhone, -2),
        'role'    => $userData['role']
    ]);
}
?>