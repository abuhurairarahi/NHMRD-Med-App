<?php
// controllers/paitent-panel/medical-test-request-controller.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pdo = require_once __DIR__ . '/../../config/db.php';

$logged_user_id = $_SESSION['user_id'] ?? 5;

try {
    // 1. Patient
    $stmt = $pdo->prepare("
        SELECT p.*, u.uid AS user_uid
        FROM patients p
        JOIN users u ON p.user_id = u.user_id
        WHERE p.user_id = ?
    ");
    $stmt->execute([$logged_user_id]);
    $patient = $stmt->fetch();
    if (!$patient) die('Patient record not found.');

    $nameParts    = explode(' ', trim($patient['full_name']));
    $userInitials = strtoupper(
        substr($nameParts[0], 0, 1) .
        (count($nameParts) > 1 ? substr(end($nameParts), 0, 1) : '')
    );

    $pid = $patient['patient_id'];

    // 2. Stats
    $s = $pdo->prepare("SELECT COUNT(*) FROM prescriptions WHERE patient_id = ?");
    $s->execute([$pid]);
    $totalPrescriptions = $s->fetchColumn();

    $s = $pdo->prepare("SELECT COUNT(*) FROM lab_test_orders WHERE patient_id = ?");
    $s->execute([$pid]);
    $totalLabTests = $s->fetchColumn();

    $s = $pdo->prepare("SELECT COUNT(*) FROM vaccination_records WHERE patient_id = ?");
    $s->execute([$pid]);
    $totalVaccines = $s->fetchColumn();

    $s = $pdo->prepare("SELECT COUNT(*) FROM surgical_records WHERE patient_id = ?");
    $s->execute([$pid]);
    $totalSurgeries = $s->fetchColumn();

    // 3. Lab test catalog — categorized
    $stmtCatalog = $pdo->query("
        SELECT test_id, test_name, test_code, category, price
        FROM lab_test_catalog
        ORDER BY category ASC, test_name ASC
    ");
    $allTests = $stmtCatalog->fetchAll();

    // Group by category
    $testsByCategory = [];
    foreach ($allTests as $test) {
        $testsByCategory[$test['category']][] = $test;
    }

    // 4. Doctor-advised tests from the patient's latest active prescription
    $stmtAdvised = $pdo->prepare("
        SELECT plt.test_name, plt.test_code, plt.instructions
        FROM prescription_lab_tests plt
        JOIN prescriptions p ON plt.prescription_id = p.prescription_id
        WHERE p.patient_id = ? AND p.status = 'active'
        ORDER BY p.prescription_id DESC
        LIMIT 10
    ");
    $stmtAdvised->execute([$pid]);
    $advisedTests = $stmtAdvised->fetchAll();

    // 5. Existing test orders
    $stmtOrders = $pdo->prepare("
        SELECT lto.order_id, lto.ordered_at, lto.status, lto.total_amount,
               GROUP_CONCAT(ltc.test_name SEPARATOR ', ') AS tests
        FROM lab_test_orders lto
        JOIN lab_test_order_items ltoi ON lto.order_id = ltoi.order_id
        JOIN lab_test_catalog ltc ON ltoi.test_id = ltc.test_id
        WHERE lto.patient_id = ?
        GROUP BY lto.order_id
        ORDER BY lto.ordered_at DESC
        LIMIT 10
    ");
    $stmtOrders->execute([$pid]);
    $testOrders = $stmtOrders->fetchAll();

    return [
        'patient'            => $patient,
        'userInitials'       => $userInitials,
        'totalPrescriptions' => $totalPrescriptions,
        'totalLabTests'      => $totalLabTests,
        'totalVaccines'      => $totalVaccines,
        'totalSurgeries'     => $totalSurgeries,
        'allTests'           => $allTests,
        'testsByCategory'    => $testsByCategory,
        'advisedTests'       => $advisedTests,
        'testOrders'         => $testOrders,
    ];

} catch (Exception $e) {
    error_log($e->getMessage());
    die('Error loading medical test request data.');
}
