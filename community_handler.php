<?php
session_start();
header('Content-Type: application/json');
require 'db_connect.php';
require 'log_action.php';

// Check if user is logged in
if (!isset($_SESSION['userid'])) {
    echo json_encode(['success' => false, 'message' => 'You must be logged in to create a community.']);
    exit;
}

$user_id = $_SESSION['userid'];
$name = trim($_POST['name'] ?? '');
$description = trim($_POST['description'] ?? '');
$category = trim($_POST['category'] ?? '');
$privacy = $_POST['privacy'] ?? 'Public'; // Default Public
$access_code = trim($_POST['access_code'] ?? '');


// Validate required fields
if ($name === '' || $description === '' || $category === '') {
    echo json_encode(['success' => false, 'message' => 'All fields are required.']);
    exit;
}

// If private, access code is required
if ($privacy === 'Private' && $access_code === '') {
    echo json_encode(['success' => false, 'message' => 'Access code is required for private communities.']);
    exit;
}

// Hash access code if private
$access_code_hashed = null;
if ($privacy === 'Private' && $access_code !== '') {
    $access_code_hashed = password_hash($access_code, PASSWORD_DEFAULT);
}

try {
    // Insert into communities table
    $stmt = $conn->prepare("INSERT INTO communities (name, description, category, privacy, access_code, created_by, status) VALUES (?, ?, ?, ?, ?, ?, 'pending')");
    $stmt->bind_param("sssssi", $name, $description, $category, $privacy, $access_code_hashed, $user_id);
    $stmt->execute();
    $community_id = $stmt->insert_id;
    $stmt->close();

    // Add creator as owner in community_members
    $stmt2 = $conn->prepare("INSERT INTO community_members (community_id, user_id, role) VALUES (?, ?, ?)");
    $role = 'owner';
    $stmt2->bind_param("iis", $community_id, $user_id, $role);

    $stmt2->execute();
    $stmt2->close();

    $first_name = $_SESSION['first_name'] ?? '';
    $last_name  = $_SESSION['last_name'] ?? '';
    $authorName = trim("$first_name $last_name");


    $details = "Community Created:\nName: $name\nCategory: $category\nPrivacy: $privacy\nCreator: $authorName";
    logAction("Community Creation", $details);

    echo json_encode(['success' => true, 'message' => 'Community created successfully!']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
