<?php
header('Content-Type: application/json');
require_once '../db.php';

$action = $_POST['action'] ?? '';

if ($action === 'send_otp') {
    $patient_mrn = $_POST['patient_mrn'] ?? '';
    
    // 1. Find patient by MRN or UID
    $stmt = $pdo->prepare("SELECT patient_id, full_name, phone FROM patients WHERE uid = ? OR patient_id = ?");
    $stmt->execute([$patient_mrn, str_replace('MRN-', '', $patient_mrn)]);
    $patient = $stmt->fetch();

    if (!$patient) {
        echo json_encode(['success' => false, 'error' => 'Patient not found.']);
        exit;
    }

    $phone = $patient['phone'];
    if (!$phone) {
        echo json_encode(['success' => false, 'error' => 'Patient does not have a registered phone number.']);
        exit;
    }

    // 2. Generate 6-digit OTP
    $otp = rand(100000, 999999);
    
    // Store in session (for demo) or DB
    session_start();
    $_SESSION['wa_otp_' . $patient['patient_id']] = $otp;

    // 3. Send WhatsApp OTP via WhatsApp Cloud API (Simulated)
    /*
    $wa_token = 'YOUR_WHATSAPP_CLOUD_API_TOKEN';
    $wa_phone_id = 'YOUR_PHONE_NUMBER_ID';
    $url = "https://graph.facebook.com/v17.0/{$wa_phone_id}/messages";

    $data = [
        'messaging_product' => 'whatsapp',
        'to' => $phone,
        'type' => 'template',
        'template' => [
            'name' => 'auth_otp', // your WhatsApp approved template name
            'language' => ['code' => 'en_US'],
            'components' => [
                [
                    'type' => 'body',
                    'parameters' => [['type' => 'text', 'text' => $otp]]
                ],
                [
                    'type' => 'button',
                    'sub_type' => 'url',
                    'index' => '0',
                    'parameters' => [['type' => 'text', 'text' => $otp]]
                ]
            ]
        ]
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer {$wa_token}", "Content-Type: application/json"]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);
    */

    // For Demo: Return OTP in response so we can test the frontend easily
    echo json_encode([
        'success' => true, 
        'message' => "OTP sent to WhatsApp ($phone).", 
        'patient_id' => $patient['patient_id'],
        'demo_otp' => $otp // REMOVE IN PRODUCTION
    ]);

} elseif ($action === 'verify_otp') {
    session_start();
    $patient_id = $_POST['patient_id'] ?? '';
    $entered_otp = $_POST['otp'] ?? '';
    
    $saved_otp = $_SESSION['wa_otp_' . $patient_id] ?? null;

    if ($saved_otp && $saved_otp == $entered_otp) {
        // Clear OTP
        unset($_SESSION['wa_otp_' . $patient_id]);
        
        // Grant Profile Access (Insert into profile_access_requests or similar)
        // For demo, just return success
        echo json_encode(['success' => true, 'message' => 'OTP Verified. Profile unlocked.']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid or expired OTP.']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid action.']);
}
?>
