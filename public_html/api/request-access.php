<?php
// public_html/api/request-access.php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/whatsapp-otp-helper.php';

$conn = getDBConnection();
$action = $_POST['action'] ?? '';

// Step 1: Send OTP to Patient via WhatsApp
if ($action === 'send_otp') {
    $patientIdentifier = trim($_POST['patient_id'] ?? '');

    if (empty($patientIdentifier)) {
        echo json_encode(['success' => false, 'message' => 'Please enter Patient ID or Phone Number.']);
        exit;
    }

    // Query universal patient database (by patient_id, mobile, or national_id)
    $stmt = $conn->prepare("SELECT id, name, phone FROM patients WHERE id = ? OR phone = ? OR national_id = ? LIMIT 1");
    $stmt->bind_param("sss", $patientIdentifier, $patientIdentifier, $patientIdentifier);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($patient = $result->fetch_assoc()) {
        $otp = sprintf("%06d", mt_rand(100000, 999999));
        
        // Save OTP session with 5-min expiry
        $_SESSION['whatsapp_otp'] = [
            'patient_id' => $patient['id'],
            'phone' => $patient['phone'],
            'otp' => $otp,
            'expires_at' => time() + 300
        ];

        // Send OTP via WhatsApp API
        $apiResult = sendWhatsAppOTP($patient['phone'], $otp);

        if ($apiResult['success']) {
            // Mask phone number for display
            $maskedPhone = substr($patient['phone'], 0, 3) . '****' . substr($patient['phone'], -3);
            echo json_encode([
                'success' => true, 
                'message' => "OTP sent via WhatsApp to patient {$patient['name']} ({$maskedPhone}).",
                'patient_name' => $patient['name']
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to send WhatsApp message.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Patient not found in NHMRD database.']);
    }
    exit;
}

// Step 2: Verify OTP
if ($action === 'verify_otp') {
    $userOTP = trim($_POST['otp'] ?? '');
    $sessionOTP = $_SESSION['whatsapp_otp'] ?? null;

    if (!$sessionOTP) {
        echo json_encode(['success' => false, 'message' => 'OTP expired or session invalid. Please request a new code.']);
        exit;
    }

    if (time() > $sessionOTP['expires_at']) {
        unset($_SESSION['whatsapp_otp']);
        echo json_encode(['success' => false, 'message' => 'OTP has expired.']);
        exit;
    }

    if ($userOTP === (string)$sessionOTP['otp']) {
        $patientId = $sessionOTP['patient_id'];
        
        // Log access request in database
        $doctorId = $_SESSION['doctor_id'] ?? 1; // Default doctor session
        $stmt = $conn->prepare("INSERT INTO doctor_patient_access (doctor_id, patient_id, granted_at, status) VALUES (?, ?, NOW(), 'ACTIVE')");
        $stmt->bind_param("ii", $doctorId, $patientId);
        $stmt->execute();

        unset($_SESSION['whatsapp_otp']);
        echo json_encode(['success' => true, 'message' => 'Access granted successfully!', 'redirect' => 'doctor-dashboard.html']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid OTP code. Please try again.']);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid action.']);
?>