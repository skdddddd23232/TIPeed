<?php
include "db_connect.php";

function logAction($action, $details) {
    global $conn; // Use the database connection

    $stmt = $conn->prepare("INSERT INTO logs (action, details, created_at) VALUES (?, ?, NOW())");
    $stmt->bind_param("ss", $action, $details);
    $stmt->execute();
    $stmt->close();
}
?>
