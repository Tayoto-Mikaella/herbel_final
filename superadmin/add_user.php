<?php
require_once 'dbcon.php';
session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || strtolower($_SESSION['role']) !== 'superadmin') {
    $_SESSION['flash_message'] = "You do not have permission to perform this action.";
    $_SESSION['flash_message_type'] = 'error';
    header("location: user_management.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($name) || empty($email) || empty($password)) {
        $_SESSION['flash_message'] = "Please fill in all required fields.";
        $_SESSION['flash_message_type'] = 'error';
        header("location: user_management.php");
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['flash_message'] = "Invalid email format.";
        $_SESSION['flash_message_type'] = 'error';
        header("location: user_management.php");
        exit;
    }

    $stmt = $conn->prepare("SELECT User_ID FROM users WHERE Email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    
    if ($stmt->num_rows > 0) {
        $_SESSION['flash_message'] = "An account with this email already exists.";
        $_SESSION['flash_message_type'] = 'error';
        header("location: user_management.php");
        exit;
    }
    $stmt->close();

    $hashed_password = password_hash($password, PASSWORD_BCRYPT);
    $role = 'admin';

    $stmt_insert = $conn->prepare("INSERT INTO users (Name, Email, Password, Role) VALUES (?, ?, ?, ?)");
    $stmt_insert->bind_param("ssss", $name, $email, $hashed_password, $role);

    if ($stmt_insert->execute()) {
        $_SESSION['flash_message'] = "Admin account for '" . htmlspecialchars($name) . "' was created successfully.";
        $_SESSION['flash_message_type'] = 'success';
    } else {
        $_SESSION['flash_message'] = "Error: Could not create account. " . $conn->error;
        $_SESSION['flash_message_type'] = 'error';
    }
    
    $stmt_insert->close();
    $conn->close();

    header("location: user_management.php");
    exit;
}
?>