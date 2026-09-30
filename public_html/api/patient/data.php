<?php
// public_html/api/patient/data.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');

require_once __DIR__ . '/../../config/db.php';
$pdo = getDB();

// Resolve active patient ID
$userId = $_SESSION['user_id'] ?? null;
$patientId = $_SESSION['patient_id'] ?? null;

if (!$patientId) {
    if ($userId) {
        $pStmt = $pdo->prepare("SELECT patient_id FROM patients WHERE user_id = ? LIMIT 1");
        $pStmt->execute([$userId]);
        $patientId = $pStmt->fetchColumn();
    }
    // Fallback to demo patient Farhana Islam (patient_id 1)
    if (!$patientId) {
        $patientId = 1;
    }
}

$action = $_GET['action'] ?? $_POST['action'] ?? 'get_dashboard';

try {
    switch ($action) {

        case 'get_dashboard':
            // 1. Patient basic header details
            $stmt = $pdo->prepare("SELECT patient_id, full_name, health_card_no, nid, dob, gender, blood_group, blood_pressure, bmi, phone, address FROM patients WHERE patient_id = ?");
            $stmt->execute([$patientId]);
            $patient = $stmt->fetch();

            // 2. Metrics
            $s1 = $pdo->prepare("SELECT COUNT(*) FROM prescriptions WHERE patient_id = ?");
            $s1->execute([$patientId]);
            $rxCount = (int)$s1->fetchColumn();

            $s2 = $pdo->prepare("SELECT COUNT(*) FROM lab_test_orders WHERE patient_id = ?");
            $s2->execute([$patientId]);
            $labCount = (int)$s2->fetchColumn();

            $s3 = $pdo->prepare("SELECT COUNT(*) FROM vaccination_records WHERE patient_id = ?");
            $s3->execute([$patientId]);
            $vacCount = (int)$s3->fetchColumn();

            $s4 = $pdo->prepare("SELECT COUNT(*) FROM surgical_records WHERE patient_id = ?");
            $s4->execute([$patientId]);
            $surgCount = (int)$s4->fetchColumn();

            // 3. Upcoming Appointments
            $appStmt = $pdo->prepare("
                SELECT a.appointment_id, a.appointment_date, a.time_slot, a.status, a.reason,
                       d.full_name AS doctor_name, s.name AS specialty_name,
                       h.legal_name AS hospital_name
                FROM appointments a
                JOIN doctors d ON a.doctor_id = d.doctor_id
                LEFT JOIN specialties s ON d.primary_specialty_id = s.specialty_id
                LEFT JOIN hospitals h ON a.hospital_id = h.hospital_id
                WHERE a.patient_id = ?
                ORDER BY a.appointment_date DESC, a.time_slot ASC
                LIMIT 5
            ");
            $appStmt->execute([$patientId]);
            $appointments = $appStmt->fetchAll();

            // 4. Recent Medical Activity stream
            $activities = [];
            // Prescriptions activity
            $rxAct = $pdo->prepare("
                SELECT p.prescription_id AS id, CONCAT('RX-', p.prescription_id) AS prescription_code, p.created_at AS act_date, p.title,
                       d.full_name AS doctor_name, 'Prescription Record' AS type
                FROM prescriptions p
                JOIN doctors d ON p.doctor_id = d.doctor_id
                WHERE p.patient_id = ?
                ORDER BY p.created_at DESC LIMIT 3
            ");
            $rxAct->execute([$patientId]);
            while ($r = $rxAct->fetch()) {
                $activities[] = $r;
            }

            // Lab Tests activity
            $labAct = $pdo->prepare("
                SELECT o.order_id AS id, CONCAT('ORD-', o.order_id) AS prescription_code, o.ordered_at AS act_date,
                       c.test_name AS title, COALESCE(h.legal_name, 'DGHS Certified Lab') AS doctor_name, 'Diagnostic Report' AS type
                FROM lab_test_orders o
                JOIN lab_test_order_items i ON o.order_id = i.order_id
                JOIN lab_test_catalog c ON i.test_id = c.test_id
                LEFT JOIN hospitals h ON o.hospital_id = h.hospital_id
                WHERE o.patient_id = ?
                ORDER BY o.ordered_at DESC LIMIT 3
            ");
            $labAct->execute([$patientId]);
            while ($l = $labAct->fetch()) {
                $activities[] = $l;
            }

            echo json_encode([
                'success' => true,
                'patient' => $patient,
                'counts'  => [
                    'prescriptions' => $rxCount,
                    'lab_tests'     => $labCount,
                    'vaccines'      => $vacCount,
                    'surgeries'     => $surgCount
                ],
                'appointments' => $appointments,
                'activities'   => $activities
            ]);
            break;

        case 'get_profile':
            // Patient core
            $stmt = $pdo->prepare("SELECT * FROM patients WHERE patient_id = ?");
            $stmt->execute([$patientId]);
            $patient = $stmt->fetch();

            // Chronic conditions
            $cStmt = $pdo->prepare("SELECT * FROM patient_chronic_conditions WHERE patient_id = ?");
            $cStmt->execute([$patientId]);
            $conditions = $cStmt->fetchAll();

            // Allergies
            $aStmt = $pdo->prepare("SELECT * FROM patient_allergies WHERE patient_id = ?");
            $aStmt->execute([$patientId]);
            $allergies = $aStmt->fetchAll();

            // Emergency Contacts
            $ctStmt = $pdo->prepare("SELECT * FROM patient_contacts WHERE patient_id = ?");
            $ctStmt->execute([$patientId]);
            $contacts = $ctStmt->fetchAll();

            // Blood Donors
            $bdStmt = $pdo->prepare("SELECT * FROM patient_blood_donors WHERE patient_id = ?");
            $bdStmt->execute([$patientId]);
            $donors = $bdStmt->fetchAll();

            echo json_encode([
                'success'    => true,
                'patient'    => $patient,
                'conditions' => $conditions,
                'allergies'  => $allergies,
                'contacts'   => $contacts,
                'donors'     => $donors
            ]);
            break;

        case 'update_vitals':
            $bp  = trim($_POST['blood_pressure'] ?? '');
            $bmi = trim($_POST['bmi'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $address = trim($_POST['address'] ?? '');

            $stmt = $pdo->prepare("UPDATE patients SET blood_pressure = COALESCE(NULLIF(?, ''), blood_pressure), bmi = COALESCE(NULLIF(?, ''), bmi), phone = COALESCE(NULLIF(?, ''), phone), address = COALESCE(NULLIF(?, ''), address), vitals_last_synced_at = NOW() WHERE patient_id = ?");
            $stmt->execute([$bp, $bmi, $phone, $address, $patientId]);

            echo json_encode(['success' => true, 'message' => 'Profile & vitals updated successfully.']);
            break;

        case 'add_contact':
            $label = trim($_POST['label'] ?? 'Contact');
            $val   = trim($_POST['value'] ?? '');
            if (!$val) {
                echo json_encode(['success' => false, 'message' => 'Contact details cannot be empty.']);
                exit;
            }
            $stmt = $pdo->prepare("INSERT INTO patient_contacts (patient_id, contact_name, relationship, phone) VALUES (?, ?, 'Alternate', ?)");
            $stmt->execute([$patientId, $label, $val]);
            echo json_encode(['success' => true, 'message' => 'Emergency contact added.']);
            break;

        case 'add_donor':
            $donorName  = trim($_POST['donor_name'] ?? '');
            $bloodGroup = trim($_POST['blood_group'] ?? '');
            $phone      = trim($_POST['phone'] ?? '');

            if (!$donorName || !$bloodGroup || !$phone) {
                echo json_encode(['success' => false, 'message' => 'Please provide donor name, blood group, and phone.']);
                exit;
            }

            $stmt = $pdo->prepare("INSERT INTO patient_blood_donors (patient_id, donor_name, blood_group, phone, is_available) VALUES (?, ?, ?, ?, 1)");
            $stmt->execute([$patientId, $donorName, $bloodGroup, $phone]);

            echo json_encode(['success' => true, 'message' => 'Blood donor record added successfully.']);
            break;

        case 'get_prescriptions':
            $sql = "
                SELECT p.*, d.full_name AS doctor_name, d.bmdc_registration_no,
                       s.name AS specialty_name, h.legal_name AS hospital_name
                FROM prescriptions p
                JOIN doctors d ON p.doctor_id = d.doctor_id
                LEFT JOIN specialties s ON d.primary_specialty_id = s.specialty_id
                LEFT JOIN hospitals h ON d.hospital_id = h.hospital_id
                WHERE p.patient_id = ?
                ORDER BY p.created_at DESC
            ";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$patientId]);
            $prescriptions = $stmt->fetchAll();

            // Load medications for each prescription
            foreach ($prescriptions as &$rx) {
                $mStmt = $pdo->prepare("SELECT * FROM prescription_medications WHERE prescription_id = ?");
                $mStmt->execute([$rx['prescription_id']]);
                $rx['medications'] = $mStmt->fetchAll();

                $lStmt = $pdo->prepare("SELECT l.*, c.test_name FROM prescription_lab_tests l LEFT JOIN lab_test_catalog c ON l.test_id = c.test_id WHERE l.prescription_id = ?");
                $lStmt->execute([$rx['prescription_id']]);
                $rx['lab_tests'] = $lStmt->fetchAll();
            }

            echo json_encode(['success' => true, 'data' => $prescriptions]);
            break;

        case 'get_surgeries':
            $sql = "
                SELECT s.*, d.full_name AS surgeon_name, d.bmdc_registration_no,
                       h.legal_name AS hospital_name
                FROM surgical_records s
                JOIN doctors d ON s.surgeon_id = d.doctor_id
                LEFT JOIN hospitals h ON s.hospital_id = h.hospital_id
                WHERE s.patient_id = ?
                ORDER BY s.operation_datetime DESC
            ";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$patientId]);
            $surgeries = $stmt->fetchAll();
            echo json_encode(['success' => true, 'data' => $surgeries]);
            break;

        case 'get_lab_tests':
            $sql = "
                SELECT o.order_id, o.order_no, o.ordered_at, o.status AS order_status, o.total_amount,
                       h.legal_name AS hospital_name,
                       c.test_name, c.category, c.price, c.specimen_type,
                       r.result_value, r.reference_range, r.remarks, r.verified_at
                FROM lab_test_orders o
                JOIN lab_test_order_items i ON o.order_id = i.order_id
                JOIN lab_test_catalog c ON i.test_id = c.test_id
                LEFT JOIN hospitals h ON o.hospital_id = h.hospital_id
                LEFT JOIN lab_test_results r ON i.id = r.order_item_id
                WHERE o.patient_id = ?
                ORDER BY o.ordered_at DESC
            ";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$patientId]);
            $tests = $stmt->fetchAll();
            echo json_encode(['success' => true, 'data' => $tests]);
            break;

        case 'get_vaccines':
            // Records
            $vStmt = $pdo->prepare("
                SELECT vr.*, vc.trade_name, vc.manufacturer, vc.target_disease,
                       h.legal_name AS hospital_name, d.full_name AS doctor_name
                FROM vaccination_records vr
                JOIN vaccine_catalog vc ON vr.vaccine_id = vc.vaccine_id
                LEFT JOIN hospitals h ON vr.hospital_id = h.hospital_id
                LEFT JOIN doctors d ON vr.administered_by_doctor_id = d.doctor_id
                WHERE vr.patient_id = ?
                ORDER BY vr.administered_date DESC
            ");
            $vStmt->execute([$patientId]);
            $records = $vStmt->fetchAll();

            // Appointments
            $vaStmt = $pdo->prepare("
                SELECT va.*, vc.trade_name, h.legal_name AS hospital_name
                FROM vaccine_appointments va
                JOIN vaccine_catalog vc ON va.vaccine_id = vc.vaccine_id
                LEFT JOIN hospitals h ON va.hospital_id = h.hospital_id
                WHERE va.patient_id = ?
                ORDER BY va.scheduled_date ASC
            ");
            $vaStmt->execute([$patientId]);
            $appointments = $vaStmt->fetchAll();

            echo json_encode([
                'success'      => true,
                'records'      => $records,
                'appointments' => $appointments
            ]);
            break;

        case 'book_appointment':
            $doctorId        = (int)($_POST['doctor_id'] ?? 0);
            $hospitalId      = !empty($_POST['hospital_id']) ? (int)$_POST['hospital_id'] : 1;
            $appointmentDate = trim($_POST['appointment_date'] ?? date('Y-m-d'));
            $timeSlot        = trim($_POST['time_slot'] ?? '10:00 AM');
            $reason          = trim($_POST['reason'] ?? 'Routine Clinical Consultation');

            if (!$doctorId) {
                echo json_encode(['success' => false, 'message' => 'Please select a doctor.']);
                exit;
            }

            $stmt = $pdo->prepare("
                INSERT INTO appointments (patient_id, doctor_id, hospital_id, appointment_date, time_slot, status, reason)
                VALUES (?, ?, ?, ?, ?, 'booked', ?)
            ");
            $stmt->execute([$patientId, $doctorId, $hospitalId, $appointmentDate, $timeSlot, $reason]);

            echo json_encode([
                'success'        => true,
                'message'        => 'Appointment booked successfully!',
                'appointment_id' => $pdo->lastInsertId()
            ]);
            break;

        case 'cancel_appointment':
            $appId = (int)($_POST['appointment_id'] ?? 0);
            $stmt = $pdo->prepare("UPDATE appointments SET status = 'cancelled' WHERE appointment_id = ? AND patient_id = ?");
            $stmt->execute([$appId, $patientId]);
            echo json_encode(['success' => true, 'message' => 'Appointment cancelled successfully.']);
            break;

        case 'request_test':
            $hospitalId = !empty($_POST['hospital_id']) ? (int)$_POST['hospital_id'] : 1;
            $testIds    = $_POST['test_ids'] ?? [];

            if (is_string($testIds)) {
                $testIds = json_decode($testIds, true) ?: [$testIds];
            }
            if (empty($testIds)) {
                echo json_encode(['success' => false, 'message' => 'Please select at least one test.']);
                exit;
            }

            $orderNo = 'ORD-' . date('Ymd') . '-' . mt_rand(1000, 9999);
            $pdo->beginTransaction();

            $totalAmount = 0.00;
            $inClause = implode(',', array_fill(0, count($testIds), '?'));
            $cStmt = $pdo->prepare("SELECT test_id, price FROM lab_test_catalog WHERE test_id IN ($inClause)");
            $cStmt->execute($testIds);
            $catalogItems = $cStmt->fetchAll();
            foreach ($catalogItems as $ci) {
                $totalAmount += (float)$ci['price'];
            }

            $oStmt = $pdo->prepare("
                INSERT INTO lab_test_orders (patient_id, hospital_id, status, total_amount, ordered_at)
                VALUES (?, ?, 'requested', ?, NOW())
            ");
            $oStmt->execute([$patientId, $hospitalId, $totalAmount]);
            $orderId = $pdo->lastInsertId();

            $iStmt = $pdo->prepare("INSERT INTO lab_test_order_items (order_id, test_id) VALUES (?, ?)");
            foreach ($testIds as $tid) {
                $iStmt->execute([$orderId, (int)$tid]);
            }

            $pdo->commit();
            echo json_encode(['success' => true, 'message' => "Lab test request #{$orderNo} submitted successfully!", 'order_id' => $orderId]);
            break;

        case 'request_vaccine':
            $vaccineId     = (int)($_POST['vaccine_id'] ?? 0);
            $hospitalId    = (int)($_POST['hospital_id'] ?? 1);
            $scheduledDate = trim($_POST['scheduled_date'] ?? date('Y-m-d', strtotime('+3 days')));
            $timeSlot      = trim($_POST['time_slot'] ?? '10:00 AM');

            if (!$vaccineId) {
                echo json_encode(['success' => false, 'message' => 'Please select a vaccine.']);
                exit;
            }

            $stmt = $pdo->prepare("
                INSERT INTO vaccine_appointments (patient_id, vaccine_id, hospital_id, scheduled_date, time_slot, health_declaration_confirmed, status)
                VALUES (?, ?, ?, ?, ?, 1, 'scheduled')
            ");
            $stmt->execute([$patientId, $vaccineId, $hospitalId, $scheduledDate, $timeSlot]);

            echo json_encode(['success' => true, 'message' => 'Vaccination appointment confirmed successfully!']);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Unknown patient action.']);
            break;
    }
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
