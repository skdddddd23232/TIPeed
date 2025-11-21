<?php
session_start();
require 'db_connect.php';

if ($_SESSION['role'] !== 'admin') {
    die("Unauthorized access");
}

if (isset($_POST['community_id'])) {
    $community_id = intval($_POST['community_id']);
    $stmt = $conn->prepare("DELETE FROM communities WHERE communities_id = ?");
    $stmt->bind_param("i", $community_id);
    if ($stmt->execute()) {
        header("Location: Community.php?deleted=1");
        exit;
    } else {
        echo "Error deleting community.";
    }
    $stmt->close();
}
$conn->close();
?>
