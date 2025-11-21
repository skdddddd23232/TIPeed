<?php
header('Content-Type: application/json');
include "db_connect.php";

// Fetch logs from database
$sql = "SELECT action, details, created_at AS time FROM logs ORDER BY created_at DESC LIMIT 20";
$result = $conn->query($sql);

$logs = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $logs[] = $row;
    }
}

echo json_encode($logs);
