<?php
require_once 'dbcon.php';
session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || strtolower($_SESSION['role']) !== 'superadmin') {
    header("location: superadmin_dashboard.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $reservation_id = $_POST['reservation_id'];
    $guest_id = $_POST['guest_id'];
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $check_in_date = $_POST['check_in_date'];
    $check_out_date = $_POST['check_out_date'];
    $status = $_POST['status'];

    $conn->begin_transaction();
    try {
        $stmt_guest = $conn->prepare("UPDATE guest SET First_Name = ?, Last_Name = ? WHERE Guest_ID = ?");
        $stmt_guest->bind_param("ssi", $first_name, $last_name, $guest_id);
        $stmt_guest->execute();
        $stmt_guest->close();
        
        $stmt_res = $conn->prepare("UPDATE reservation SET Check_in_Date = ?, Check_out_Date = ?, Status = ? WHERE Reservation_ID = ?");
        $stmt_res->bind_param("sssi", $check_in_date, $check_out_date, $status, $reservation_id);
        $stmt_res->execute();
        $stmt_res->close();

        $conn->commit();
        $_SESSION['flash_message'] = "Reservation updated successfully.";
        $_SESSION['flash_message_type'] = 'success';

    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['flash_message'] = "Error updating reservation: " . $e->getMessage();
        $_SESSION['flash_message_type'] = 'error';
    }
}
header("location: superadmin_dashboard.php");
exit;
?>