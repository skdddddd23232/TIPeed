<?php
session_start();
include "db_connect.php"; // mysqli connection
include 'log_action.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!isset($_SESSION['userid'])) {
        header("Location: auth.php");
        exit;
    }

    $user_id = $_SESSION['userid'];
    $title   = mysqli_real_escape_string($conn, $_POST['title']);
    $content = mysqli_real_escape_string($conn, $_POST['content']);

    $first_name = $_SESSION['first_name'] ?? '';
    $last_name  = $_SESSION['last_name'] ?? '';
    $authorName = trim("$first_name $last_name");

    // Handle image upload
    $imagePath = null;
    if (!empty($_FILES['image']['name'])) {
        $targetDir = "uploads/";
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }
        $imagePath = $targetDir . time() . "_" . basename($_FILES["image"]["name"]);
        move_uploaded_file($_FILES["image"]["tmp_name"], $imagePath);
    }

    // ✅ use image_path (your actual DB column name)
    $sql = "INSERT INTO threads (user_id, title, content, image_path, created_at) 
            VALUES (?, ?, ?, ?, NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isss", $user_id, $title, $content, $imagePath);

    if ($stmt->execute()) {

        $details = "New thread created:\nAuthor: $authorName\nTitle: $title";
        logAction("Thread Creation", $details);

        header("Location: student_home.php");
        exit;
    } else {
        echo "❌ Error: " . $stmt->error;
    }
}
?>
