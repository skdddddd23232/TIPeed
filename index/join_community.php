<?php
session_start();
require 'db_connect.php';

if (!isset($_SESSION['userid'])) {
    die("You must be logged in to join.");
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $community_id = intval($_POST['community_id']);
    $user_id = $_SESSION['userid'];

    // prevent duplicate membership
    $check = $conn->prepare("SELECT 1 FROM community_members WHERE community_id=? AND user_id=?");
    $check->bind_param("ii", $community_id, $user_id);
    $check->execute();
    $check->store_result();

    if ($check->num_rows === 0) {
        $stmt = $conn->prepare("INSERT INTO community_members (community_id, user_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $community_id, $user_id);
        $stmt->execute();
        $stmt->close();
    }
    $check->close();

    header("Location: community.php");
    exit;
}
?>
