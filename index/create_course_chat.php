<?php
session_start();
include "db_connect.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $groupName = mysqli_real_escape_string($conn, $input['groupName'] ?? '');
    $courseId = intval($input['courseId'] ?? 0);
    $classSection = mysqli_real_escape_string($conn, $input['classSection'] ?? '');
    $description = mysqli_real_escape_string($conn, $input['description'] ?? '');
    $facultyId = $_SESSION['userid'];
    
    $isCoAdminAllowed = isset($input['coAdmin']) ? 1 : 0;
    $isApprovalAllowed = isset($input['allowApproval']) ? 1 : 0;
    $isStudentAdditionAllowed = isset($input['addStudent']) ? 1 : 0;
    
    $coAdmins = $input['coAdmins'] ?? [];
    $students = $input['students'] ?? [];
    
    if (empty($groupName) || $courseId <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Group Name and Course selection are required']);
        exit;
    }
    
    $inviteCode = generateInviteCode();
    $inviteLink = "https://tipeed.com/join/" . $inviteCode;
    
    try {
        $conn->begin_transaction();
        
        // Insert into course_chat table (singular)
        $sql = "INSERT INTO course_chats (course_id, faculty_id, group_name, class_section, description, 
                invite_link, is_coadmin_allowed, is_approval_allowed, is_student_addition_allowed) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iissssiii", $courseId, $facultyId, $groupName, $classSection, $description, 
                         $inviteLink, $isCoAdminAllowed, $isApprovalAllowed, $isStudentAdditionAllowed);
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to create course chat: " . $stmt->error);
        }
        
        $chatId = $stmt->insert_id;
        
        // Add faculty as member
        $memberSql = "INSERT INTO course_chat_members (chat_id, user_id, role) VALUES (?, ?, 'faculty')";
        $memberStmt = $conn->prepare($memberSql);
        $memberStmt->bind_param("ii", $chatId, $facultyId);
        
        if (!$memberStmt->execute()) {
            throw new Exception("Failed to add faculty as member: " . $memberStmt->error);
        }
        
        // Process co-admins
        foreach ($coAdmins as $coAdmin) {
            $coAdminEmail = mysqli_real_escape_string($conn, $coAdmin['email']);
            
            $findUserSql = "SELECT userid FROM users WHERE email = ?";
            $findStmt = $conn->prepare($findUserSql);
            $findStmt->bind_param("s", $coAdminEmail);
            $findStmt->execute();
            $userResult = $findStmt->get_result();
            
            if ($userResult->num_rows > 0) {
                $userRow = $userResult->fetch_assoc();
                $userId = $userRow['userid'];
                
                $coAdminSql = "INSERT INTO course_chat_members (chat_id, user_id, role) VALUES (?, ?, 'co-admin')";
                $coAdminStmt = $conn->prepare($coAdminSql);
                $coAdminStmt->bind_param("ii", $chatId, $userId);
                $coAdminStmt->execute();
            }
        }
        
        // Process students
        foreach ($students as $student) {
            $studentEmail = mysqli_real_escape_string($conn, $student['email']);
            
            $findUserSql = "SELECT userid FROM users WHERE email = ?";
            $findStmt = $conn->prepare($findUserSql);
            $findStmt->bind_param("s", $studentEmail);
            $findStmt->execute();
            $userResult = $findStmt->get_result();
            
            if ($userResult->num_rows > 0) {
                $userRow = $userResult->fetch_assoc();
                $userId = $userRow['userid'];
                
                $studentSql = "INSERT INTO course_chat_members (chat_id, user_id, role) VALUES (?, ?, 'student')";
                $studentStmt = $conn->prepare($studentSql);
                $studentStmt->bind_param("ii", $chatId, $userId);
                $studentStmt->execute();
            }
        }
        
        $conn->commit();
        
        echo json_encode([
            'status' => 'success', 
            'message' => 'Course chat created successfully!',
            'chatId' => $chatId,
            'inviteLink' => $inviteLink
        ]);
        
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

function generateInviteCode() {
    return substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789'), 0, 10);
}
?>