<?php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$dbPath = __DIR__ . '/../../config/db.php';
$pdo = require_once $dbPath;

$patient_id      = $_POST['patient_id'] ?? null;
$hospital_id     = ($_POST['hospital_id'] === 'home') ? null : $_POST['hospital_id'];
$prescription_id = !empty($_POST['prescription_id']) ? $_POST['prescription_id'] : null;
$test_ids        = $_POST['test_ids'] ?? [];

if (!$patient_id || empty($test_ids)) {
    echo json_encode(['success' => false, 'message' => 'Please select at least one test to submit.']);
    exit;
}

$collection_fee = ($_POST['hospital_id'] === 'home') ? 200.00 : 0.00;

try {
    $pdo->beginTransaction();

    // Calculate dynamic test total
    $inClause = implode(',', array_fill(0, count($test_ids), '?'));
    $stmtCatalog = $pdo->prepare("SELECT test_id, price FROM lab_test_catalog WHERE test_id IN ($inClause)");
    $stmtCatalog->execute($test_ids);
    $testsFound = $stmtCatalog->fetchAll(PDO::FETCH_ASSOC);

    $testsTotal = 0;
    foreach ($testsFound as $t) {
        $testsTotal += $t['price'];
    }

    $grandTotal = $testsTotal + $collection_fee;

    // Handle File Upload if present
    $uploadedFilePath = null;
    if (isset($_FILES['prescription_file']) && $_FILES['prescription_file']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['prescription_file']['tmp_name'];
        $fileName = $_FILES['prescription_file']['name'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        $allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx'];
        if (in_array($fileExtension, $allowedExtensions)) {
            $uploadFileDir = __DIR__ . '/../../public_html/uploads/prescriptions/';
            if (!is_dir($uploadFileDir)) {
                mkdir($uploadFileDir, 0755, true);
            }
            $newFileName = md5(time() . $fileName) . '.' . $fileExtension;
            $dest_path = $uploadFileDir . $newFileName;

            if (move_uploaded_file($fileTmpPath, $dest_path)) {
                $uploadedFilePath = '/public_html/uploads/prescriptions/' . $newFileName;
            }
        }
    }

    // Insert Order Record
    $stmtOrder = $pdo->prepare("
        INSERT INTO lab_test_orders 
        (patient_id, doctor_id, prescription_id, hospital_id, collection_fee, total_amount, uploaded_prescription_file, status, ordered_at) 
        VALUES (:patient_id, NULL, :prescription_id, :hospital_id, :collection_fee, :total_amount, :uploaded_prescription_file, 'requested', NOW())
    ");

    $stmtOrder->execute([
        ':patient_id'                 => $patient_id,
        ':prescription_id'            => $prescription_id,
        ':hospital_id'                => $hospital_id,
        ':collection_fee'             => $collection_fee,
        ':total_amount'               => $grandTotal,
        ':uploaded_prescription_file' => $uploadedFilePath
    ]);

    $order_id = $pdo->lastInsertId();

    // Insert Order Items
    $stmtItem = $pdo->prepare("
        INSERT INTO lab_test_order_items (order_id, test_id, price, is_doctor_advised) 
        VALUES (:order_id, :test_id, :price, 0)
    ");

    foreach ($testsFound as $t) {
        $stmtItem->execute([
            ':order_id' => $order_id,
            ':test_id'  => $t['test_id'],
            ':price'    => $t['price']
        ]);
    }

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Test request submitted successfully!',
        'order_id' => $order_id,
        'total' => number_format($grandTotal, 2)
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'message' => 'Server Error: ' . $e->getMessage()]);
}