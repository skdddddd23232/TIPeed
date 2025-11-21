<?php
session_start();
include "db_connect.php"; // database connection

if (!isset($_SESSION['userid'])) {
    header("Location: auth.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $newPassword     = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if ($newPassword !== $confirmPassword) {
        echo "❌ Passwords do not match.";
        exit;
    }

    // Hash the new password
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

    // Update password in DB and reset is_temp_password
    $stmt = $conn->prepare("UPDATE users SET password = ?, is_temp_password = 0 WHERE userid = ?");
    $stmt->bind_param("si", $hashedPassword, $_SESSION['userid']);

    if ($stmt->execute()) {
        $stmt->close();
        $conn->close();

        // Redirect based on role
        if ($_SESSION['role'] === "faculty") {
            header("Location: faculty_home.php");
        } elseif ($_SESSION['role'] === "admin") {
            header("Location: admin_home.php");
        }
        exit;
    } else {
        echo "❌ Failed to update password: " . $stmt->error;
    }
}
?>
