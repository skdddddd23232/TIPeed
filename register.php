<?php
include "db_connect.php";
include "log_action.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $first_name  = trim($_POST['first_name']);
    $last_name   = trim($_POST['last_name']);
    $student_id  = !empty($_POST['student_id']) ? trim($_POST['student_id']) : NULL;
    $year_level  = isset($_POST['year_level']) ? $_POST['year_level'] : NULL;
    $email       = trim($_POST['email']);
    $password    = $_POST['password'];
    $confirm_pw  = $_POST['confirm_password'];

    // Check password match
    if ($password !== $confirm_pw) {
        die("❌ Passwords do not match.");
    }

    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Default role is student (faculty/admin created manually by admin)
    $role = "student";

    // Insert into DB including year_level
    $stmt = $conn->prepare("INSERT INTO users (first_name, last_name, student_id, email, password, role, year_level, is_temp_password) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, 0)"); // explicitly 0
    $stmt->bind_param("ssssssi", $first_name, $last_name, $student_id, $email, $hashed_password, $role, $year_level);

    if ($stmt->execute()) {

        logAction(
            "User Registration",
            "New user '{$first_name} {$last_name}' registered with email '{$email}'"
        );

        header("Location: auth.php");
        exit;
    } else {
        echo "❌ Error: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
}
?>
