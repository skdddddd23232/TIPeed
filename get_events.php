<?php
session_start();
include "db_connect.php";

header('Content-Type: application/json');

try {
    $stmt = $conn->prepare("SELECT e.event_id, e.title, e.description, e.event_date, u.first_name, u.last_name 
                            FROM calendar_events e
                            JOIN users u ON e.created_by = u.userid
                            ORDER BY e.event_date ASC");
    $stmt->execute();
    $result = $stmt->get_result();

    $events = [];
    while($row = $result->fetch_assoc()) {
        $row['author'] = $row['first_name'] . ' ' . $row['last_name'];
        $row['date'] = $row['event_date']; // <-- important
        unset($row['first_name'], $row['last_name'], $row['event_date']);
        $events[] = $row;
    }

    echo json_encode($events);
} catch (Exception $e) {
    echo json_encode([]);
}
?>
