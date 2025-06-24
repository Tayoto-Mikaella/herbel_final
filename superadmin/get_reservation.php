<?php
error_reporting(0);
ini_set('display_errors', 0);

require_once '../dbcon.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['loggedin']) || !isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'superadmin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized Access']);
    exit;
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid Reservation ID provided.']);
    exit;
}

$reservation_id = intval($_GET['id']);
$data = null;

$sql = "
    SELECT 
        r.Reservation_ID,
        g.Guest_ID,
        g.First_Name,
        g.Last_Name,
        r.Check_in_Date,
        r.Check_out_Date,
        r.Status
    FROM reservation r
    JOIN guest g ON r.Guest_ID = g.Guest_ID
    WHERE r.Reservation_ID = ?
    LIMIT 1
";

$stmt = $conn->prepare($sql);

if ($stmt === false) {
    http_response_code(500);
    error_log("SQL Prepare Failed in get_reservation.php: " . $conn->error);
    echo json_encode(['success' => false, 'message' => 'Internal Server Error: Database statement could not be prepared.']);
    exit;
}

$stmt->bind_param("i", $reservation_id);
if ($stmt->execute()) {
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
}
$stmt->close();

if ($data) {
    echo json_encode(['success' => true, 'data' => $data]);
} else {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Reservation not found or database execution error.']);
}

$conn->close();