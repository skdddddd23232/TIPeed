<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include "db_connect.php"; // database connection

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email    = trim($_POST['email']);
    $password = $_POST['password'];

    // Find user by email
    $stmt = $conn->prepare("SELECT userid, first_name, last_name, email, password, role, student_id, year_level, is_temp_password
                        FROM users WHERE email = ? AND is_active = 1 LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        // Verify password
        if (password_verify($password, $user['password'])) {
            // Store user info in session
            $_SESSION['userid']     = $user['userid'];
            $_SESSION['first_name'] = $user['first_name'];
            $_SESSION['last_name']  = $user['last_name'];
            $_SESSION['role']       = $user['role'];
            $_SESSION['year_level'] = $user['year_level'];

            // Check if the user has a temporary password
            if (isset($user['is_temp_password']) && $user['is_temp_password'] == 1) {
                header("Location: changepassword.php");
                exit;
            }

            // Redirect based on role
            if ($user['role'] === "student") {
                header("Location: student_home.php");
            } elseif ($user['role'] === "faculty") {
                header("Location: faculty_home.php");
            } elseif ($user['role'] === "admin") {
                header("Location: admin_home.php");
            }
            exit;
        } else {
            // Redirect back to auth.php with error parameter
            header("Location: auth.php?error=invalid_password");
            exit;
        }

    } else {
        // Redirect back to auth.php with error parameter
        header("Location: auth.php?error=no_account");
        exit;
    }

    $stmt->close();
    $conn->close();
}
?>