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

        // 1. Book Appointment
        if ($action === 'book') {
            $doctorId = intval($input['doctor_id'] ?? 1);
            $hospitalId = intval($input['hospital_id'] ?? 1);
            $date = trim($input['date'] ?? '');
            $timeSlot = trim($input['time_slot'] ?? '19:00 PM');
            $reason = trim($input['reason'] ?? 'General Consultation & Routine Checkup');

            if (empty($date)) {
                $date = date('Y-m-d', strtotime('+3 days'));
            } else {
                $parsedDate = date('Y-m-d', strtotime($date));
                if ($parsedDate && $parsedDate !== '1970-01-01') {
                    $date = $parsedDate;
                }
            }

            // Verify doctor exists
            $stmtDoc = $pdo->prepare("SELECT doctor_id, full_name, hospital_id FROM doctors WHERE doctor_id = ?");
            $stmtDoc->execute([$doctorId]);
            $doc = $stmtDoc->fetch();
            if (!$doc) {
                // Fallback to first doctor
                $stmtDoc = $pdo->query("SELECT doctor_id, full_name, hospital_id FROM doctors LIMIT 1");
                $doc = $stmtDoc->fetch();
                $doctorId = $doc['doctor_id'];
            }
            if ($hospitalId <= 0 && !empty($doc['hospital_id'])) {
                $hospitalId = $doc['hospital_id'];
            }

            $stmtInsert = $pdo->prepare("
                INSERT INTO appointments (patient_id, doctor_id, hospital_id, appointment_date, time_slot, reason, status)
                VALUES (?, ?, ?, ?, ?, ?, 'booked')
            ");
            $stmtInsert->execute([$patient_id, $doctorId, $hospitalId, $date, $timeSlot, $reason]);
            $newId = $pdo->lastInsertId();

            // Fetch created appointment details
            $stmtGet = $pdo->prepare("
                SELECT a.appointment_id, a.appointment_date, a.time_slot, a.status, a.reason,
                       d.full_name AS doctor_name, d.designation,
                       COALESCE(h.legal_name, 'LABAID Specialized Hospital') AS hospital_name
                FROM appointments a
                JOIN doctors d ON a.doctor_id = d.doctor_id
                LEFT JOIN hospitals h ON a.hospital_id = h.hospital_id
                WHERE a.appointment_id = ?
            ");
            $stmtGet->execute([$newId]);
            $booking = $stmtGet->fetch();

            echo json_encode([
                'success' => true,
                'message' => 'Appointment booked successfully.',
                'appointment' => $booking
            ]);
            exit;
        }

        // 2. Cancel Appointment
        if ($action === 'cancel') {
            $appointmentId = intval($input['appointment_id'] ?? 0);
            if ($appointmentId <= 0) {
                echo json_encode(['success' => false, 'message' => 'Valid appointment ID required.']);
                exit;
            }

            $stmtCancel = $pdo->prepare("UPDATE appointments SET status = 'cancelled' WHERE appointment_id = ? AND patient_id = ?");
            $stmtCancel->execute([$appointmentId, $patient_id]);

            echo json_encode([
                'success' => true,
                'message' => 'Appointment cancelled successfully.',
                'appointment_id' => $appointmentId
            ]);
            exit;
        }

        // 3. Reschedule Appointment
        if ($action === 'reschedule') {
            $appointmentId = intval($input['appointment_id'] ?? 0);
            $newDate = trim($input['date'] ?? date('Y-m-d', strtotime('+7 days')));
            $newSlot = trim($input['time_slot'] ?? '18:00 PM');

            $parsedDate = date('Y-m-d', strtotime($newDate));
            if ($parsedDate && $parsedDate !== '1970-01-01') {
                $newDate = $parsedDate;
            }

            $stmtRes = $pdo->prepare("
                UPDATE appointments 
                SET appointment_date = ?, time_slot = ?, status = 'rescheduled' 
                WHERE appointment_id = ? AND patient_id = ?
            ");
            $stmtRes->execute([$newDate, $newSlot, $appointmentId, $patient_id]);

            echo json_encode([
                'success' => true,
                'message' => 'Appointment rescheduled to ' . $newDate . ' at ' . $newSlot . '.',
                'appointment_id' => $appointmentId,
                'new_date' => $newDate,
                'new_slot' => $newSlot
            ]);
            exit;
        }

        echo json_encode(['success' => false, 'message' => 'Unknown POST action.']);
        exit;
    }

    // GET requests
    $action = $_GET['action'] ?? 'list';

    if ($action === 'meta') {
        // Hospitals
        $hospitals = $pdo->query("SELECT hospital_id, legal_name, facility_type, er_hotline FROM hospitals WHERE license_status = 'licensed' ORDER BY legal_name ASC")->fetchAll();

        // Specialties
        $specialties = $pdo->query("SELECT specialty_id, name FROM specialties ORDER BY name ASC")->fetchAll();

        // Doctors
        $stmtDoc = $pdo->query("
            SELECT d.doctor_id, d.full_name, d.designation, d.qualifications, d.years_experience,
                   sp.name AS specialty, h.legal_name AS hospital_name, d.hospital_id,
                   '4.9' AS rating, '184 reviews' AS review_count, 'LABAID Wing B, 4th Fl' AS chamber_room,
                   'Wed, 19:00 PM' AS next_slot
            FROM doctors d
            LEFT JOIN specialties sp ON d.primary_specialty_id = sp.specialty_id
            LEFT JOIN hospitals h ON d.hospital_id = h.hospital_id
            WHERE d.status = 'active'
            ORDER BY d.doctor_id ASC
        ");
        $doctors = $stmtDoc->fetchAll();

        echo json_encode([
            'success' => true,
            'specialties' => $specialties,
            'hospitals' => $hospitals,
            'doctors' => $doctors
        ]);
        exit;
    }

    // Default: List patient appointments
    $stmtList = $pdo->prepare("
        SELECT a.appointment_id, a.appointment_date, a.time_slot, a.status, a.reason,
               d.doctor_id, d.full_name AS doctor_name, d.designation, d.qualifications,
               COALESCE(h.legal_name, 'LABAID Specialized Hospital') AS hospital_name,
               'Room 412 (OPD)' AS room_no
        FROM appointments a
        JOIN doctors d ON a.doctor_id = d.doctor_id
        LEFT JOIN hospitals h ON a.hospital_id = h.hospital_id
        WHERE a.patient_id = ?
        ORDER BY a.appointment_date DESC, a.appointment_id DESC
    ");
    $stmtList->execute([$patient_id]);
    $appointments = $stmtList->fetchAll();

    foreach ($appointments as &$app) {
        $appTime = strtotime($app['appointment_date']);
        $today = strtotime(date('Y-m-d'));
        $app['month_name'] = strtoupper(date('M', $appTime));
        $app['day_num'] = date('d', $appTime);
        $app['formatted_date'] = date('d-m-Y', $appTime);

        if ($app['status'] === 'booked') {
            $app['badge_status'] = ($appTime < $today) ? 'expired' : 'due';
            $app['badge_text'] = ($appTime < $today) ? 'Expired' : 'Due';
        } elseif ($app['status'] === 'completed') {
            $app['badge_status'] = 'visited';
            $app['badge_text'] = 'Visited';
        } else {
            $app['badge_status'] = strtolower($app['status']);
            $app['badge_text'] = ucfirst($app['status']);
        }
    }
    unset($app);

    echo json_encode([
        'success' => true,
        'appointments' => $appointments
    ]);

} catch (PDOException $e) {
    if ($e->getCode() == 23000 || strpos($e->getMessage(), 'Duplicate entry') !== false) {
        echo json_encode(['success' => false, 'message' => 'This doctor slot is already booked for the selected date and time. Please select another time slot.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Appointment API error: ' . $e->getMessage()]);
}
