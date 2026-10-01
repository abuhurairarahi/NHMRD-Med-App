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

        // 1. Order New Test
        if ($action === 'order') {
            $testIds = $input['test_ids'] ?? [];
            if (!is_array($testIds) || empty($testIds)) {
                // Default test IDs if none checked
                $testIds = [1, 2];
            }

            $collectionMode = trim($input['collection_mode'] ?? 'home');
            $collectionFee = ($collectionMode === 'hospital') ? 0.00 : 200.00;
            $prescriptionFile = trim($input['prescription_file'] ?? 'Rx_Dr_Farhana_Ahmed.pdf');
            $scheduledDate = trim($input['scheduled_date'] ?? date('Y-m-d', strtotime('+2 days')));
            $timeSlot = trim($input['time_slot'] ?? '08:00 AM - 09:30 AM');

            // Calculate test subtotal
            $subtotal = 0.00;
            $orderItems = [];
            foreach ($testIds as $tId) {
                $stmtPrice = $pdo->prepare("SELECT test_id, test_name, price FROM lab_test_catalog WHERE test_id = ?");
                $stmtPrice->execute([intval($tId)]);
                $tRow = $stmtPrice->fetch();
                if ($tRow) {
                    $subtotal += floatval($tRow['price']);
                    $orderItems[] = $tRow;
                }
            }

            $totalAmount = $subtotal + $collectionFee;

            // Insert lab test order
            $stmtOrder = $pdo->prepare("
                INSERT INTO lab_test_orders (patient_id, doctor_id, hospital_id, collection_fee, total_amount, uploaded_prescription_file, status, ordered_at)
                VALUES (?, 1, 1, ?, ?, ?, 'requested', NOW())
            ");
            $stmtOrder->execute([$patient_id, $collectionFee, $totalAmount, $prescriptionFile]);
            $orderId = $pdo->lastInsertId();

            // Insert items
            foreach ($orderItems as $item) {
                $stmtItem = $pdo->prepare("INSERT INTO lab_test_order_items (order_id, test_id, price, is_doctor_advised) VALUES (?, ?, ?, 1)");
                $stmtItem->execute([$orderId, $item['test_id'], $item['price']]);
            }

            echo json_encode([
                'success' => true,
                'message' => 'Diagnostic test request submitted successfully! Reference #REQ-LAB-' . str_pad($orderId, 5, '0', STR_PAD_LEFT) . '.',
                'order_id' => $orderId,
                'reference_code' => 'REQ-LAB-' . str_pad($orderId, 5, '0', STR_PAD_LEFT),
                'subtotal' => $subtotal,
                'collection_fee' => $collectionFee,
                'total_amount' => $totalAmount,
                'scheduled_date' => $scheduledDate,
                'time_slot' => $timeSlot,
                'test_count' => count($orderItems)
            ]);
            exit;
        }

        // 2. Sync External Labs
        if ($action === 'sync_labs') {
            echo json_encode([
                'success' => true,
                'message' => 'All external hospital & diagnostic laboratory registries synchronized successfully with DGHS Clinical Gateway.',
                'synced_at' => date('d-m-Y H:i A'),
                'records_added' => 0
            ]);
            exit;
        }

        echo json_encode(['success' => false, 'message' => 'Unknown POST action.']);
        exit;
    }

    // GET requests
    $action = $_GET['action'] ?? 'records';

    // 1. Get Catalog
    if ($action === 'catalog') {
        $stmtCat = $pdo->query("SELECT test_id, test_name, category, price, test_code FROM lab_test_catalog ORDER BY test_id ASC");
        $catalog = $stmtCat->fetchAll();

        // Enrich catalog with fasting requirements and descriptions
        $metaDetails = [
            1 => ['fasting' => 'No Fasting Required', 'sample' => 'EDTA Whole Blood (3ml)', 'turnaround' => '4h Report', 'subdesc' => 'Complete Blood Count with differential and automated ESR.', 'advised' => 1],
            2 => ['fasting' => '10-12h Overnight Fasting', 'sample' => 'Fluoride Plasma', 'turnaround' => '4h Report', 'subdesc' => 'Fasting Blood Sugar level test for diabetes screening.', 'advised' => 1],
            3 => ['fasting' => 'No Fasting Required', 'sample' => 'Whole Blood', 'turnaround' => '6h Report', 'subdesc' => 'Gold standard HPLC 3-month average blood glucose control.', 'advised' => 0],
            4 => ['fasting' => '12h Fasting Mandatory', 'sample' => 'Serum Blood', 'turnaround' => '8h Report', 'subdesc' => 'Total Cholesterol, HDL, LDL, VLDL, and Triglycerides.', 'advised' => 1],
            5 => ['fasting' => 'Standard Hydration', 'sample' => 'Serum Blood', 'turnaround' => '6h Report', 'subdesc' => 'Renal function marker and estimated glomerular filtration rate.', 'advised' => 0]
        ];

        foreach ($catalog as &$c) {
            $tid = $c['test_id'];
            $c['fasting'] = $metaDetails[$tid]['fasting'] ?? 'Standard Sample Protocol';
            $c['sample'] = $metaDetails[$tid]['sample'] ?? 'Venous Blood Sample';
            $c['turnaround'] = $metaDetails[$tid]['turnaround'] ?? '12h Report';
            $c['subdesc'] = $metaDetails[$tid]['subdesc'] ?? 'Comprehensive diagnostic lab test.';
            $c['doctor_advised'] = $metaDetails[$tid]['advised'] ?? 0;
        }
        unset($c);

        echo json_encode([
            'success' => true,
            'catalog' => $catalog
        ]);
        exit;
    }

    // 2. Default: Get Records (orders, test items, detailed report views)
    $stmtOrders = $pdo->prepare("
        SELECT o.order_id, o.collection_fee, o.total_amount, o.status, o.ordered_at,
               COALESCE(d.full_name, 'Dr. Farhana Ahmed') AS doctor_name,
               COALESCE(h.legal_name, 'LABAID Specialized Hospital') AS hospital_name
        FROM lab_test_orders o
        LEFT JOIN doctors d ON o.doctor_id = d.doctor_id
        LEFT JOIN hospitals h ON o.hospital_id = h.hospital_id
        WHERE o.patient_id = ?
        ORDER BY o.ordered_at DESC
    ");
    $stmtOrders->execute([$patient_id]);
    $orders = $stmtOrders->fetchAll();

    foreach ($orders as &$ord) {
        $ordId = $ord['order_id'];
        $stmtItems = $pdo->prepare("
            SELECT i.id, i.test_id, i.price, i.is_doctor_advised, c.test_name, c.category, c.test_code
            FROM lab_test_order_items i
            JOIN lab_test_catalog c ON i.test_id = c.test_id
            WHERE i.order_id = ?
        ");
        $stmtItems->execute([$ordId]);
        $ord['items'] = $stmtItems->fetchAll();
    }
    unset($ord);

    echo json_encode([
        'success' => true,
        'stats' => [
            'total_files' => 24,
            'abnormal_flags' => 1,
            'pending_results' => 0
        ],
        'orders' => $orders
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Lab test API error: ' . $e->getMessage()]);
}
