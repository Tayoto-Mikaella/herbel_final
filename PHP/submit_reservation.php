<?php
require_once 'dbcon.php';
session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("location: ../index.html");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $conn->begin_transaction();

    try {
        $first_name = $_POST['first_name'];
        $last_name = $_POST['last_name'];
        $contact_no = $_POST['contact_no'] ?? null;
        $email = $_POST['email'] ?? null;
        
        $room_id = $_POST['room_id'];
        $check_in_date = $_POST['check_in_date'];
        $check_out_date = $_POST['check_out_date'];
        $additional_persons = $_POST['additional_persons'];
        $number_of_guests = $additional_persons + 2;
        $amount_to_pay = $_POST['amount_to_pay'];
        $payment_method_id = !empty($_POST['payment_method_id']) ? $_POST['payment_method_id'] : null;
        $notes = $_POST['notes'] ?? null;
        
        $guest_id = null;
        
        $stmt_find_guest = $conn->prepare("SELECT Guest_ID FROM guest WHERE First_Name = ? AND Last_Name = ? AND (Contact_No = ? OR Email = ?)");
        $stmt_find_guest->bind_param("ssss", $first_name, $last_name, $contact_no, $email);
        $stmt_find_guest->execute();
        $result = $stmt_find_guest->get_result();

        if ($result->num_rows > 0) {
            $guest_id = $result->fetch_assoc()['Guest_ID'];
        } else {
            $stmt_insert_guest = $conn->prepare("INSERT INTO guest (First_Name, Last_Name, Contact_No, Email) VALUES (?, ?, ?, ?)");
            $stmt_insert_guest->bind_param("ssss", $first_name, $last_name, $contact_no, $email);
            $stmt_insert_guest->execute();
            $guest_id = $stmt_insert_guest->insert_id;
            $stmt_insert_guest->close();
        }
        $stmt_find_guest->close();

        if (!$guest_id) {
            throw new Exception("Failed to create or find guest.");
        }
        
        $stmt_insert_reservation = $conn->prepare("INSERT INTO reservation (Guest_ID, Room_ID, Check_in_Date, Check_out_Date, Number_of_Guests, Status, Amount_Paid_Initial, Notes) VALUES (?, ?, ?, ?, ?, 'Reserved', ?, ?)");
        $stmt_insert_reservation->bind_param("iissids", $guest_id, $room_id, $check_in_date, $check_out_date, $number_of_guests, $amount_to_pay, $notes);
        $stmt_insert_reservation->execute();
        $reservation_id = $stmt_insert_reservation->insert_id;
        $stmt_insert_reservation->close();
        
        if (!$reservation_id) {
            throw new Exception("Failed to create reservation.");
        }

        if ($amount_to_pay > 0 && $payment_method_id) {
            $payment_date = date('Y-m-d H:i:s');
            $stmt_insert_payment = $conn->prepare("INSERT INTO payment (Reservation_ID, Amount_Paid, Payment_Date, Payment_Method_ID) VALUES (?, ?, ?, ?)");
            $stmt_insert_payment->bind_param("idsi", $reservation_id, $amount_to_pay, $payment_date, $payment_method_id);
            $stmt_insert_payment->execute();
            $stmt_insert_payment->close();
        }

        $stmt_update_room = $conn->prepare("UPDATE room SET Status = 'Reserved' WHERE Room_ID = ?");
        $stmt_update_room->bind_param("i", $room_id);
        $stmt_update_room->execute();
        $stmt_update_room->close();
        
        $conn->commit();
        
        $_SESSION['flash_message'] = "Reservation for " . htmlspecialchars($first_name) . " " . htmlspecialchars($last_name) . " created successfully!";
        $_SESSION['flash_message_type'] = 'success';

    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['flash_message'] = "Error creating reservation: " . $e->getMessage();
        $_SESSION['flash_message_type'] = 'error';
    }

    header("Location: homepage.php");
    exit();
} else {
    header("Location: homepage.php");
    exit();
}
?>