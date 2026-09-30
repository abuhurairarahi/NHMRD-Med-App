<?php
// public_html/api/common/lookup.php
header('Content-Type: application/json');
$pdo = require_once __DIR__ . '/../../config/db.php';

$type = $_GET['type'] ?? '';

try {
    switch ($type) {
        case 'hospitals':
            $stmt = $pdo->query("SELECT hospital_id, legal_name, facility_type, classification, total_beds, icu_beds, er_hotline, license_status FROM hospitals WHERE license_status = 'licensed' ORDER BY legal_name ASC");
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
            break;

        case 'doctors':
            $specialtyId = isset($_GET['specialty_id']) ? (int)$_GET['specialty_id'] : null;
            $typeFilter  = $_GET['doctor_type'] ?? null;
            
            $sql = "
                SELECT d.doctor_id, d.full_name, d.doctor_type, d.bmdc_registration_no, d.qualifications,
                       d.designation, d.shift_schedule, d.duty_status,
                       s.name AS specialty_name, h.legal_name AS hospital_name, h.hospital_id
                FROM doctors d
                LEFT JOIN specialties s ON d.primary_specialty_id = s.specialty_id
                LEFT JOIN hospitals h ON d.hospital_id = h.hospital_id
                WHERE d.status = 'active'
            ";
            $params = [];
            if ($specialtyId) {
                $sql .= " AND d.primary_specialty_id = ?";
                $params[] = $specialtyId;
            }
            if ($typeFilter) {
                $sql .= " AND d.doctor_type = ?";
                $params[] = $typeFilter;
            }
            $sql .= " ORDER BY d.full_name ASC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
            break;

        case 'specialties':
            $stmt = $pdo->query("SELECT specialty_id, name FROM specialties ORDER BY name ASC");
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
            break;

        case 'test_catalog':
            $category = $_GET['category'] ?? null;
            $sql = "SELECT test_id, test_code, test_name, category, price, specimen_type, fasting_required FROM lab_test_catalog WHERE is_active = 1";
            $params = [];
            if ($category) {
                $sql .= " AND category = ?";
                $params[] = $category;
            }
            $sql .= " ORDER BY test_name ASC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
            break;

        case 'vaccine_catalog':
            $stmt = $pdo->query("SELECT vaccine_id, code, trade_name, manufacturer, target_disease, total_doses, min_age_months, cold_chain_temp FROM vaccine_catalog WHERE is_active = 1 ORDER BY trade_name ASC");
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
            break;

        case 'geo':
            $level = $_GET['level'] ?? 'divisions';
            if ($level === 'divisions') {
                $stmt = $pdo->query("SELECT division_id, name FROM divisions ORDER BY name ASC");
                echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
            } elseif ($level === 'districts') {
                $divId = (int)($_GET['division_id'] ?? 0);
                $stmt = $pdo->prepare("SELECT district_id, division_id, name FROM districts WHERE division_id = ? ORDER BY name ASC");
                $stmt->execute([$divId]);
                echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
            } elseif ($level === 'upazilas') {
                $distId = (int)($_GET['district_id'] ?? 0);
                $stmt = $pdo->prepare("SELECT upazila_id, district_id, name FROM upazilas WHERE district_id = ? ORDER BY name ASC");
                $stmt->execute([$distId]);
                echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Unknown geo level.']);
            }
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Invalid lookup type.']);
            break;
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
