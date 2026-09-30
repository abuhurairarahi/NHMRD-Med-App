<?php
// create_prescription.php
require_once 'db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $patient_id        = $_POST['patient_id'];
    $doctor_id         = $_POST['doctor_id'];
    $appointment_id    = $_POST['appointment_id'] ?? null;
    $title             = $_POST['title'];
    $chief_complaint   = $_POST['chief_complaint'] ?? '';
    $symptoms          = $_POST['symptoms'] ?? '';
    $current_condition = $_POST['current_condition'] ?? '';
    $doctors_statement = $_POST['doctors_statement'] ?? '';
    $referred_surgeon  = !empty($_POST['referred_surgeon_id']) ? $_POST['referred_surgeon_id'] : null;
    $next_visit_date   = !empty($_POST['next_visit_date']) ? $_POST['next_visit_date'] : null;
    $visit_type        = $_POST['visit_type'] ?? 'in_person';
    $is_long_term      = isset($_POST['is_long_term']) ? 1 : 0;

    // Decode JSON input sent from front-end dynamic tables
    $medications = isset($_POST['medications']) ? json_encode($_POST['medications']) : '[]';
    $lab_tests   = isset($_POST['lab_tests'])   ? json_encode($_POST['lab_tests'])   : '[]';

    try {
        $stmt = $pdo->prepare("CALL sp_create_prescription(
            :patient_id, :doctor_id, :appointment_id, :title, :chief_complaint, 
            :symptoms, :current_condition, :doctors_statement, :referred_surgeon, 
            :next_visit_date, :visit_type, :is_long_term, :medications, :lab_tests, @prescription_id
        )");

        $stmt->execute([
            'patient_id'        => $patient_id,
            'doctor_id'         => $doctor_id,
            'appointment_id'    => $appointment_id,
            'title'             => $title,
            'chief_complaint'   => $chief_complaint,
            'symptoms'          => $symptoms,
            'current_condition' => $current_condition,
            'doctors_statement' => $doctors_statement,
            'referred_surgeon'  => $referred_surgeon,
            'next_visit_date'   => $next_visit_date,
            'visit_type'        => $visit_type,
            'is_long_term'      => $is_long_term,
            'medications'       => $medications,
            'lab_tests'         => $lab_tests
        ]);

        $res = $pdo->query("SELECT @prescription_id AS prescription_id")->fetch();

        echo json_encode([
            'success' => true,
            'message' => 'Prescription created successfully.',
            'prescription_id' => $res['prescription_id']
        ]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}
?>