<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['userid'])) {
    echo json_encode(["status" => "error", "message" => "Not logged in"]);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id     = $_SESSION['userid'];
    $community_id = intval($_POST['community_id']);
    $message     = trim($_POST['message']);

    if ($message !== "") {
        $stmt = $conn->prepare("INSERT INTO community_messages (community_id, user_id, message) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $community_id, $user_id, $message);

        if ($stmt->execute()) {
            echo json_encode(["status" => "success"]);
        } else {
            echo json_encode(["status" => "error", "message" => "DB insert failed"]);
        }

        $stmt->close();
    } else {
        echo json_encode(["status" => "error", "message" => "Message empty"]);
    }
}
