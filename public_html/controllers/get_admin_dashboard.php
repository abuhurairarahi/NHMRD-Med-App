<?php
// get_admin_dashboard.php
require_once 'db.php';

header('Content-Type: application/json');

try {
    // Read from v_admin_dashboard_kpis
    $kpiStmt = $pdo->query("SELECT * FROM v_admin_dashboard_kpis");
    $kpis = $kpiStmt->fetch();

    // Read pending credential queue
    $queueStmt = $pdo->query("SELECT * FROM v_pending_credential_queue LIMIT 10");
    $pendingQueue = $queueStmt->fetchAll();

    echo json_encode([
        'success' => true,
        'kpis' => $kpis,
        'pending_credential_requests' => $pendingQueue
    ]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>