<?php
session_start();
require 'db_connect.php';

$community_id = $_POST['community_id'];
$access_code = $_POST['access_code'];

// Fetch the hashed access code
$stmt = $conn->prepare("SELECT access_code FROM communities WHERE communities_id = ?");
$stmt->bind_param("i", $community_id);
$stmt->execute();
$result = $stmt->get_result();
$community = $result->fetch_assoc();

if (!$community) {
    echo json_encode(['success' => false, 'message' => 'Community not found']);
    exit;
}

if (!password_verify($access_code, $community['access_code'])) {
    echo json_encode(['success' => false, 'message' => 'Incorrect access code']);
    exit;
}

// Add user to community_members
$stmt2 = $conn->prepare("INSERT INTO community_members (community_id, user_id, role) VALUES (?, ?, ?)");
$role = 'member';
$stmt2->bind_param("iis", $community_id, $_SESSION['userid'], $role);
$stmt2->execute();
$stmt2->close();

echo json_encode(['success' => true]);
?>
