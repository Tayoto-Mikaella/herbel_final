<?php
session_start();
header('Content-Type: application/json');

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'dbcon.php';

try {
    $input = json_decode(file_get_contents('php://input'), true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON received.');
    }

    $email = $input['username'] ?? '';
    $password = $input['password'] ?? '';
    $role = $input['role'] ?? '';

    if (empty($email) || empty($password) || empty($role)) {
        throw new Exception('Please fill in all fields.');
    }

    $stmt = $conn->prepare("SELECT User_ID, Name, Password, Role FROM users WHERE Email = ?");
    if ($stmt === false) {
        throw new Exception('Database prepare statement failed: ' . $conn->error);
    }

    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($user = $result->fetch_assoc()) {
        if (password_verify($password, $user['Password']) && strtolower($role) === strtolower($user['Role'])) {
            $_SESSION['loggedin'] = true;
            $_SESSION['user_id'] = $user['User_ID'];
            $_SESSION['name'] = $user['Name'];
            $_SESSION['role'] = $user['Role'];

            $redirect_url = '';
            if (strtolower($user['Role']) === 'superadmin') {
                $redirect_url = '../superadmin/superadmin_dashboard.php';
            } else {
                $redirect_url = '../admin/admin_dashboard.php';
            }

            echo json_encode(['status' => 'success', 'message' => 'Login successful!', 'redirect' => $redirect_url]);
        } else {
            throw new Exception('Invalid email, password, or role.');
        }
    } else {
        throw new Exception('Invalid email, password, or role.');
    }

    $stmt->close();
    $conn->close();

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>