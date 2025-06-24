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

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['reservation_id']) || !isset($input['status'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid input.']);
    exit;
}

$reservation_id = intval($input['reservation_id']);
$status = $conn->real_escape_string($input['status']);
$valid_statuses = ['Checked-In', 'Checked-Out', 'Cancelled'];

if (!in_array($status, $valid_statuses)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid status value.']);
    exit;
}

$conn->begin_transaction();

try {
    $stmt_update_res = $conn->prepare("UPDATE reservation SET Status = ? WHERE Reservation_ID = ?");
    if ($stmt_update_res === false) {
        throw new Exception("Prepare failed (reservation update): " . $conn->error);
    }
    $stmt_update_res->bind_param("si", $status, $reservation_id);
    $stmt_update_res->execute();
    $stmt_update_res->close();

    if ($status === 'Checked-Out' || $status === 'Cancelled') {
        $stmt_get_room = $conn->prepare("SELECT Room_ID FROM reservation WHERE Reservation_ID = ?");
        if ($stmt_get_room === false) {
            throw new Exception("Prepare failed (get room id): " . $conn->error);
        }
        $stmt_get_room->bind_param("i", $reservation_id);
        $stmt_get_room->execute();
        $result = $stmt_get_room->get_result();
        $res_data = $result->fetch_assoc();
        $stmt_get_room->close();
        
        if ($res_data && !empty($res_data['Room_ID'])) {
            $room_id = $res_data['Room_ID'];
            $stmt_update_room = $conn->prepare("UPDATE room SET Status = 'Available' WHERE Room_ID = ?");
            if ($stmt_update_room === false) {
                throw new Exception("Prepare failed (room update): " . $conn->error);
            }
            $stmt_update_room->bind_param("i", $room_id);
            $stmt_update_room->execute();
            $stmt_update_room->close();
        }
    }

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Status updated successfully.']);

} catch (Exception $e) {
    $conn->rollback();
    http_response_code(500);
    error_log("Transaction Failed in update_sstatus.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'A database transaction error occurred.']);
}

$conn->close();