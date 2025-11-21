<?php
include 'db_connect.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['userid']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'error' => 'Access denied']);
    exit;
}

$reportId = $_POST['report_id'] ?? null;
if (!$reportId) {
    echo json_encode(['success' => false, 'error' => 'Missing report_id']);
    exit;
}

// Instead of deleting, mark report as resolved and tag as deleted
$stmt = $conn->prepare("UPDATE reports SET status = 'resolved', reported_content = CONCAT('[DELETED REPORT] ', IFNULL(reported_content, '')), description = CONCAT(IFNULL(description,''), '\n\n[Report removed by admin]') WHERE report_id = ?");
if (!$stmt) {
    echo json_encode(['success' => false, 'error' => $conn->error]);
    exit;
}
$stmt->bind_param('s', $reportId);
$ok = $stmt->execute();
$stmt->close();

if ($ok) echo json_encode(['success' => true]);
else echo json_encode(['success' => false, 'error' => $conn->error]);

?>