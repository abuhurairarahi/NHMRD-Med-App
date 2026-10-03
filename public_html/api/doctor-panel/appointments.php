<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../db.php';

$doctor_id = isset($_GET['doctor_id']) ? (int)$_GET['doctor_id'] : 1;

try {
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $action = $input['action'] ?? '';
        $appointment_id = $input['appointment_id'] ?? null;
        
        if (!$appointment_id) {
            echo json_encode(['success' => false, 'error' => 'Appointment ID missing']);
            exit;
        }

        if ($action === 'approve') {
            $update = $pdo->prepare("UPDATE appointments SET status = 'approved' WHERE appointment_id = ?");
            $update->execute([$appointment_id]);
            echo json_encode(['success' => true]);
            exit;
        } 
        elseif ($action === 'propose') {
            $day = $input['day'] ?? '';
            $time = $input['time'] ?? '';
            $reason = $input['reason'] ?? '';
            $update = $pdo->prepare("UPDATE appointments SET status = 'rescheduled', reason = ? WHERE appointment_id = ?");
            // Assuming reason column can hold the new proposed details or there is another table/column for this. 
            // For now, updating status to rescheduled and appending reason.
            $update->execute(["Proposed $day at $time: $reason", $appointment_id]);
            echo json_encode(['success' => true]);
            exit;
        } else {
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
            exit;
        }
    }

    // GET Request: Fetch Appointments
    $stmt = $pdo->prepare("SELECT a.appointment_id, a.appointment_date, a.time_slot, a.status, a.reason, 
                                  p.full_name, p.dob, p.gender, p.phone, p.patient_id 
                           FROM appointments a
                           JOIN patients p ON a.patient_id = p.patient_id
                           WHERE a.doctor_id = ? 
                           ORDER BY a.appointment_date ASC, a.time_slot ASC LIMIT 50");
    $stmt->execute([$doctor_id]);
    $appointments = $stmt->fetchAll();

    echo json_encode(['success' => true, 'appointments' => $appointments]);
} catch (\PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
