<?php
include "db_connect.php";

// Only fetch CCS department courses
$query = "SELECT course_id, course_code, course_name 
          FROM courses 
          WHERE department = 'CCS' 
          ORDER BY course_code ASC";

$result = mysqli_query($conn, $query);

$courses = [];
while ($row = mysqli_fetch_assoc($result)) {
    $courses[] = $row;
}

header('Content-Type: application/json');
echo json_encode($courses);
?>
