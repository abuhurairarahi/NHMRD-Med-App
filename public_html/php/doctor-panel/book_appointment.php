<?php
// book_appointment.php
require_once 'db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $patient_id  = $_POST['patient_id']  ?? null;
    $doctor_id   = $_POST['doctor_id']   ?? null;
    $hospital_id = $_POST['hospital_id'] ?? null;
    $date        = $_POST['appointment_date'] ?? null;
    $time_slot   = $_POST['time_slot']   ?? null;
    $reason      = $_POST['reason']      ?? '';

    try {
        // Call stored procedure sp_book_appointment
        $stmt = $pdo->prepare("CALL sp_book_appointment(:patient_id, :doctor_id, :hospital_id, :date, :time_slot, :reason, @appointment_id)");
        $stmt->execute([
            'patient_id'  => $patient_id,
            'doctor_id'   => $doctor_id,
            'hospital_id' => $hospital_id,
            'date'        => $date,
            'time_slot'   => $time_slot,
            'reason'      => $reason
        ]);

        // Fetch OUT parameter
        $res = $pdo->query("SELECT @appointment_id AS appointment_id")->fetch();

        echo json_encode([
            'success' => true,
            'message' => 'Appointment booked successfully!',
            'appointment_id' => $res['appointment_id']
        ]);
    } catch (PDOException $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
    exit;
}
?>