<?php
header('Content-Type: application/json');
require 'db_connect.php';

// Fetch all community creation requests (pending, approved, rejected)
$sql = "
    SELECT 
        c.communities_id AS id,
        c.name,
        c.description,
        c.category,
        c.privacy,
        c.status,
        c.created_at AS timestamp,
        u.first_name,
        u.last_name,
        u.email
    FROM communities c
    INNER JOIN users u ON u.userid = c.created_by
    ORDER BY c.created_at DESC
";

$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    $communities = [];
    while ($row = $result->fetch_assoc()) {
        $communities[] = [
            'id' => $row['id'],
            'name' => $row['name'],
            'description' => $row['description'],
            'category' => $row['category'],
            'privacy' => ucfirst($row['privacy']),
            'status' => $row['status'],
            'timestamp' => date('M d, Y h:i A', strtotime($row['timestamp'])),
            'username' => $row['first_name'] . ' ' . $row['last_name'],
            'email' => $row['email']
        ];
    }

    echo json_encode([
        'success' => true,
        'communities' => $communities
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'No communities found.'
    ]);
}

$conn->close();
?>
