<?php
session_start();
include "db_connect.php";

if (!isset($_SESSION['userid']) || $_SESSION['role'] !== 'faculty') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
    exit;
}

$faculty_id = $_SESSION['userid'];

try {
    $sql = "SELECT c.course_id, c.course_code, c.course_name 
            FROM courses c 
            INNER JOIN faculty_courses fc ON c.course_id = fc.course_id 
            WHERE fc.faculty_id = ? 
            ORDER BY c.course_code";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $faculty_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $courses = [];
    while ($row = $result->fetch_assoc()) {
        $courses[] = $row;
    }
    
    echo json_encode([
        'status' => 'success', 
        'data' => $courses
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error', 
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>