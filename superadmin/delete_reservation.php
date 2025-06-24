<?php
require_once 'dbcon.php';
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || strtolower($_SESSION['role']) !== 'superadmin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$reservation_id = $input['id'] ?? 0;

if ($reservation_id > 0) {
    $stmt = $conn->prepare("DELETE FROM reservation WHERE Reservation_ID = ?");
    $stmt->bind_param("i", $reservation_id);
    
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            echo json_encode(['success' => true, 'message' => 'Reservation deleted successfully.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Reservation not found.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
    }
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid ID provided.']);
}
$conn->close();
?>