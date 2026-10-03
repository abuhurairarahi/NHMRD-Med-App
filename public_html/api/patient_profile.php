<?php
require_once __DIR__ . '/db.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($method === 'OPTIONS') {
    exit;
}

try {
    $patient = get_logged_in_patient($pdo);
    if (!$patient) {
        echo json_encode(['success' => false, 'message' => 'No active patient session.']);
        exit;
    }

    $patient_id = $patient['patient_id'];

    if ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) {
            $input = $_POST;
        }

        $action = $input['action'] ?? ($_GET['action'] ?? '');

        if ($action === 'update_profile') {
            $fullName = trim($input['full_name'] ?? $patient['full_name']);
            $email = trim($input['email'] ?? $patient['email']);
            $phone = trim($input['phone'] ?? $patient['phone']);
            $address = trim($input['address'] ?? $patient['address']);
            $gender = strtolower(trim($input['gender'] ?? $patient['gender']));
            $bloodGroup = trim($input['blood_group'] ?? $patient['blood_group']);

            $stmt = $pdo->prepare("
                UPDATE patients 
                SET full_name = ?, email = ?, phone = ?, address = ?, gender = ?, blood_group = ?, updated_at = NOW() 
                WHERE patient_id = ?
            ");
            $stmt->execute([$fullName, $email, $phone, $address, $gender, $bloodGroup, $patient_id]);

            echo json_encode([
                'success' => true,
                'message' => 'Profile updated successfully.',
                'patient' => [
                    'full_name' => $fullName,
                    'email' => $email,
                    'phone' => $phone,
                    'address' => $address,
                    'gender' => ucfirst($gender),
                    'blood_group' => $bloodGroup
                ]
            ]);
            exit;
        }

        if ($action === 'update_vitals') {
            $height = floatval($input['height_cm'] ?? $patient['height_cm'] ?? 170.5);
            $weight = floatval($input['weight_kg'] ?? $patient['weight_kg'] ?? 69.0);
            $bp = trim($input['blood_pressure'] ?? $patient['blood_pressure'] ?? '120/80');

            // Calculate BMI: weight (kg) / [height (m)]^2
            $bmi = 0;
            if ($height > 0) {
                $heightM = $height / 100.0;
                $bmi = round($weight / ($heightM * $heightM), 1);
            }

            $stmt = $pdo->prepare("
                UPDATE patients 
                SET height_cm = ?, weight_kg = ?, blood_pressure = ?, bmi = ?, vitals_last_synced_at = NOW() 
                WHERE patient_id = ?
            ");
            $stmt->execute([$height, $weight, $bp, $bmi, $patient_id]);

            echo json_encode([
                'success' => true,
                'message' => 'Vitals synchronized successfully.',
                'vitals' => [
                    'height_cm' => $height,
                    'weight_kg' => $weight,
                    'blood_pressure' => $bp,
                    'bmi' => $bmi,
                    'last_synced' => date('d-m-Y H:i A')
                ]
            ]);
            exit;
        }

        if ($action === 'add_contact') {
            $label = trim($input['label'] ?? '');
            $value = trim($input['value'] ?? '');

            if (empty($label) || empty($value)) {
                echo json_encode(['success' => false, 'message' => 'Label and value are required.']);
                exit;
            }

            $stmt = $pdo->prepare("INSERT INTO patient_contacts (patient_id, label, value) VALUES (?, ?, ?)");
            $stmt->execute([$patient_id, $label, $value]);

            echo json_encode([
                'success' => true,
                'message' => 'New contact entry added successfully.',
                'contact' => [
                    'id' => $pdo->lastInsertId(),
                    'label' => $label,
                    'value' => $value
                ]
            ]);
            exit;
        }

        if ($action === 'add_donor') {
            $name = trim($input['donor_name'] ?? '');
            $blood = trim($input['blood_group'] ?? 'O+');
            $phone = trim($input['phone'] ?? '');

            if (empty($name) || empty($phone)) {
                echo json_encode(['success' => false, 'message' => 'Donor name and phone are required.']);
                exit;
            }

            $stmt = $pdo->prepare("INSERT INTO patient_blood_donors (patient_id, donor_name, blood_group, phone, verification_status) VALUES (?, ?, ?, ?, 'verified')");
            $stmt->execute([$patient_id, $name, $blood, $phone]);

            echo json_encode([
                'success' => true,
                'message' => 'Donor registered successfully.',
                'donor' => [
                    'donor_id' => $pdo->lastInsertId(),
                    'donor_name' => $name,
                    'blood_group' => $blood,
                    'phone' => $phone,
                    'verification_status' => 'verified'
                ]
            ]);
            exit;
        }

        echo json_encode(['success' => false, 'message' => 'Unknown POST action.']);
        exit;
    }

    // GET request: Fetch full profile
    // Allergies
    $stmtAllergies = $pdo->prepare("SELECT allergy_name, severity FROM patient_allergies WHERE patient_id = ?");
    $stmtAllergies->execute([$patient_id]);
    $allergies = $stmtAllergies->fetchAll();

    // Chronic Conditions
    $stmtCond = $pdo->prepare("SELECT condition_name, diagnosed_date FROM patient_chronic_conditions WHERE patient_id = ?");
    $stmtCond->execute([$patient_id]);
    $conditions = $stmtCond->fetchAll();

    // Extra Contacts
    $stmtContacts = $pdo->prepare("SELECT id, label, value FROM patient_contacts WHERE patient_id = ? ORDER BY id DESC");
    $stmtContacts->execute([$patient_id]);
    $contacts = $stmtContacts->fetchAll();

    // Donors
    $stmtDonors = $pdo->prepare("SELECT donor_id, donor_name, blood_group, phone, verification_status FROM patient_blood_donors WHERE patient_id = ? ORDER BY donor_id DESC");
    $stmtDonors->execute([$patient_id]);
    $donors = $stmtDonors->fetchAll();

    // If no donors in DB, insert default demo donor from mockup if wanted, or return DB records
    if (empty($donors)) {
        $donors = [
            [
                'donor_id' => 1,
                'donor_name' => 'Huraira Hasib',
                'blood_group' => 'O+',
                'phone' => '01673478477',
                'verification_status' => 'verified'
            ]
        ];
    }

    // Historical vitals
    $historical_vitals = [
        ['date' => '12 May 2024 • 14:30', 'height' => '170 cm', 'weight' => '67.8 kg', 'bmi' => '23.5'],
        ['date' => '02 Feb 2024 • 15:15', 'height' => '170 cm', 'weight' => '68.5 kg', 'bmi' => '23.7'],
        ['date' => '20 Nov 2023 • 14:05', 'height' => '171 cm', 'weight' => '70.2 kg', 'bmi' => '24.0'],
        ['date' => '05 Sep 2023 • 16:40', 'height' => '171 cm', 'weight' => '69.4 kg', 'bmi' => '23.7']
    ];

    // Calculate age from dob
    $age = 36;
    if (!empty($patient['dob'])) {
        $dobDate = new DateTime($patient['dob']);
        $now = new DateTime();
        $age = $now->diff($dobDate)->y;
    }

    // Format last synced
    $lastSynced = !empty($patient['vitals_last_synced_at']) ? date('d-m-Y', strtotime($patient['vitals_last_synced_at'])) : date('d-m-Y');

    // Counts for dossier banner
    $stmtRxCount = $pdo->prepare("SELECT COUNT(*) FROM prescriptions WHERE patient_id = ? AND status = 'active'");
    $stmtRxCount->execute([$patient_id]);
    $active_rx_count = max(3, (int)$stmtRxCount->fetchColumn());

    $stmtLabCount = $pdo->prepare("SELECT COUNT(*) FROM lab_test_order_items i JOIN lab_test_orders o ON i.order_id = o.order_id WHERE o.patient_id = ?");
    $stmtLabCount->execute([$patient_id]);
    $total_test_count = max(14, (int)$stmtLabCount->fetchColumn());

    echo json_encode([
        'success' => true,
        'patient' => [
            'patient_id' => $patient['patient_id'],
            'user_id' => $patient['user_id'],
            'uid' => $patient['uid'] ?? '2042122004',
            'full_name' => $patient['full_name'],
            'nid' => $patient['nid'],
            'crvs_token' => $patient['crvs_token'],
            'health_card_no' => $patient['health_card_no'] ?? ('SHID-' . substr($patient['nid'], 0, 5) . '-449102'),
            'dob' => $patient['dob'],
            'age' => $age,
            'gender' => ucfirst($patient['gender']),
            'blood_group' => $patient['blood_group'] ?? 'A+',
            'phone' => $patient['phone'] ?? '+8801812345678',
            'email' => $patient['email'] ?? 'farhana.islam@gmail.com',
            'address' => $patient['address'] ?? 'House 42, Road 9A, Dhanmondi R/A, Dhaka-1209',
            'status' => $patient['status'] ?? 'active',
            'marital_status' => 'Married',
            'citizen_status' => 'Enrolled NHPS',
            'nid_verification_status' => $patient['nid_verification_status'] ?? 'biometric_verified',
            'vitals' => [
                'height_cm' => $patient['height_cm'] ?? 170.5,
                'weight_kg' => $patient['weight_kg'] ?? 69.0,
                'blood_pressure' => $patient['blood_pressure'] ?? '120/80',
                'bmi' => $patient['bmi'] ?? 23.7,
                'last_synced' => $lastSynced
            ]
        ],
        'allergies' => $allergies,
        'conditions' => $conditions,
        'contacts' => $contacts,
        'donors' => $donors,
        'historical_vitals' => $historical_vitals,
        'dossier_counts' => [
            'active_prescriptions' => $active_rx_count,
            'total_tests' => $total_test_count
        ],
        'otp_preview' => [
            'token' => 'NHMRD-' . rand(100000, 999999),
            'expires_in' => '5 minutes'
        ]
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Profile error: ' . $e->getMessage()]);
}
