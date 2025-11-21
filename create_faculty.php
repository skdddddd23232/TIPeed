<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');


include "db_connect.php";
include 'log_action.php';
require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $firstName = mysqli_real_escape_string($conn, $_POST['firstName'] ?? '');
    $lastName  = mysqli_real_escape_string($conn, $_POST['lastName'] ?? '');
    $email     = mysqli_real_escape_string($conn, $_POST['email'] ?? '');
    $isCoAdmin = isset($_POST['isCoAdmin']) && $_POST['isCoAdmin'] == 1 ? 'admin' : 'faculty';
    
    // Decode courses JSON
    $courses = json_decode($_POST['courses'] ?? '[]', true);
    if (!is_array($courses)) $courses = [];

    if (empty($firstName) || empty($lastName) || empty($email) || empty($courses)) {
        echo json_encode(['status' => 'error', 'message' => 'Missing required fields or no courses selected.']);
        exit;
    }

    // Generate temporary password
    $tempPassword = substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789'), 0, 8);
    $hashedPassword = password_hash($tempPassword, PASSWORD_DEFAULT);

    // Insert faculty account
    $stmt = $conn->prepare("INSERT INTO users (first_name, last_name, email, year_level, password, role, is_temp_password) VALUES (?, ?, ?, 0, ?, ?, 1)");
    $stmt->bind_param("sssss", $firstName, $lastName, $email, $hashedPassword, $isCoAdmin);


    if (!$stmt->execute()) {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $stmt->error]);
        exit;
    }

    $faculty_id = $stmt->insert_id;
    $stmt->close();

    $details = "New user:\nName: $firstName $lastName\nEmail: $email\nRole: $isCoAdmin";
    logAction("User Registration", $details);

    // Assign multiple courses to faculty
    $insertCourseStmt = $conn->prepare("INSERT INTO faculty_courses (faculty_id, course_id) VALUES (?, ?)");
    foreach ($courses as $course_id) {
        $course_id = intval($course_id); // cast to integer to prevent SQL injection
        if ($course_id <= 0) continue; // skip invalid IDs

        // Optional: check course exists
        $checkCourse = $conn->query("SELECT course_id FROM courses WHERE course_id = $course_id");
        if ($checkCourse->num_rows === 0) continue; // skip invalid courses

        $insertCourseStmt->bind_param("ii", $faculty_id, $course_id);
        $insertCourseStmt->execute();
    }
    $insertCourseStmt->close();

    // Send email
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'infotipeed@gmail.com';
        $mail->Password   = 'cwga otzv dklp ouzp'; // Gmail app password
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;

        $mail->setFrom('infotipeed@gmail.com', 'TiPeed Admin');
        $mail->addAddress($email, "$firstName $lastName");
        $mail->isHTML(true);
        $mail->Subject = 'Faculty Account Created';
        $mail->Body = "
            <h2>Welcome, $firstName!</h2>
            <p>Your faculty account has been created.</p>
            <p><b>Email:</b> $email<br>
               <b>Temporary Password:</b> $tempPassword<br>
               <b>Role:</b> $isCoAdmin</p>
            <p>Please change your password after logging in.</p>
        ";
        $mail->send();

        echo json_encode(['status' => 'success', 'message' => 'Faculty account created and email sent.']);
    } catch (Exception $e) {
        echo json_encode(['status' => 'warning', 'message' => 'Faculty created, but email failed: ' . $mail->ErrorInfo]);
    }
}
?>
