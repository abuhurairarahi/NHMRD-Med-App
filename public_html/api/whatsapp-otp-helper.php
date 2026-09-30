<?php
// public_html/api/whatsapp-otp-helper.php

// Configure your WhatsApp API endpoint and credentials here
define('WHATSAPP_API_URL', 'https://api.whatsapp-provider.com/v1/send-otp');
define('WHATSAPP_API_KEY', 'YOUR_WHATSAPP_API_KEY');

/**
 * Sends a WhatsApp OTP to a patient's mobile number.
 */
function sendWhatsAppOTP($phone, $otp) {
    // Format phone to E.164 (ensure country code)
    $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
    if (strlen($cleanPhone) == 10) {
        $cleanPhone = '880' . $cleanPhone; // Adjust default country code if needed
    }

    $payload = [
        'apiKey' => WHATSAPP_API_KEY,
        'to' => $cleanPhone,
        'message' => "Your NHMRD Access Verification OTP is: {$otp}. Valid for 5 minutes."
    ];

    $ch = curl_init(WHATSAPP_API_URL);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_TIMEOUT => 10
    ]);

    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        return ['success' => false, 'message' => 'WhatsApp API Error: ' . $error];
    }

    return ['success' => true, 'raw' => json_decode($response, true)];
}
?>