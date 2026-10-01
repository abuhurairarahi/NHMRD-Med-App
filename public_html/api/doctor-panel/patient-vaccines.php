<?php
header('Content-Type: application/json');
require_once '../db.php';

$patient_id = isset($_GET['patient_id']) ? (int)$_GET['patient_id'] : 1;

try {
    $stmt = $pdo->prepare("SELECT v.*, c.vaccine_name, c.disease_target, d.full_name as administered_by 
                           FROM vaccination_records v
                           JOIN vaccine_catalog c ON v.vaccine_id = c.vaccine_id
                           LEFT JOIN doctors d ON v.administered_by = d.doctor_id
                           WHERE v.patient_id = ?
                           ORDER BY v.administered_date DESC");
    $stmt->execute([$patient_id]);
    $vaccines = $stmt->fetchAll();

    echo json_encode(['success' => true, 'vaccines' => $vaccines]);
} catch (\PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
