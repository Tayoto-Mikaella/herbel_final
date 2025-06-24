<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("location: ../index.html");
    exit;
}
require_once 'dbcon.php';
$userId = $_SESSION['user_id'];
$name = $_SESSION['name'];
$role = $_SESSION['role'];
$email = '';
$stmt = $conn->prepare("SELECT Email FROM users WHERE User_ID = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
if ($user = $result->fetch_assoc()) {
    $email = $user['Email'];
}
$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile - Herbel Apartelle</title>
    <link rel="stylesheet" href="../CSS/style.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style> 
        body { 
            font-family: 'Inter', sans-serif; 
            background-color: #F8F9FA;
        } 
    </style>
</head>
<body>

    <header>
        <a href="<?php echo strtolower($_SESSION['role']) === 'superadmin' ? 'superadmin_dashboard.php' : 'homepage.php'; ?>" class="logo-area">
            <img src="../logo.png" alt="Herbel Apartelle Logo">
            <h1 class="header-title">HERBEL APARTELLE RESERVATION</h1>
        </a>
        <a href="profile.php" class="admin-section">
            <?php if (strtolower($role) === 'superadmin'): ?>
                <span class="admin-icon" style="color:#D9534F;">&#9818;</span>
                <span><?php echo htmlspecialchars($name); ?> (Super Admin)</span>
            <?php else: ?>
                <span class="admin-icon">&#128100;</span>
                <span><?php echo htmlspecialchars($name); ?></span>
            <?php endif; ?>
        </a>
    </header>

    <?php if (strtolower($role) === 'superadmin'): ?>
        <nav class="super-admin-nav">
            <a href="superadmin_dashboard.php">Dashboard</a>
            <a href="user_management.php">User Management</a>
            <a href="superadmin_history.php">History</a>
        </nav>
    <?php else: ?>
        <nav class="main-nav">
            <a href="homepage.php">Guest List</a>
            <a href="history.php">History</a>
        </nav>
    <?php endif; ?>

    <main class="w-full flex justify-center py-12">
        <div class="w-full max-w-lg bg-white rounded-xl shadow-lg p-8 m-4">
            <div class="flex flex-col items-center">
                <h1 class="text-3xl font-bold text-gray-800">User Profile</h1>
                <div class="w-24 h-24 mt-6 rounded-full bg-red-100 flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-red-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                </div>
                <div class="mt-8 text-left w-full">
                    <div class="py-3 px-4 border-b">
                        <p class="text-sm text-gray-500">Full Name</p>
                        <p class="text-lg font-semibold text-gray-900"><?php echo htmlspecialchars($name); ?></p>
                    </div>
                     <div class="py-3 px-4 border-b">
                        <p class="text-sm text-gray-500">Email Address</p>
                        <p class="text-lg font-semibold text-gray-900"><?php echo htmlspecialchars($email); ?></p>
                    </div>
                     <div class="py-3 px-4">
                        <p class="text-sm text-gray-500">Role</p>
                        <p class="text-lg font-semibold text-gray-900 capitalize"><?php echo htmlspecialchars($role); ?></p>
                    </div>
                </div>
                <div class="mt-8 flex space-x-4 w-full">
                    <a href="<?php echo strtolower($_SESSION['role']) === 'superadmin' ? 'superadmin_dashboard.php' : 'homepage.php'; ?>" class="w-full text-center py-3 px-4 border border-gray-300 rounded-lg shadow-sm font-bold text-gray-700 bg-white hover:bg-gray-50">Back to Dashboard</a>
                    <a href="logout.php" class="w-full text-center py-3 px-4 border border-transparent rounded-lg shadow-sm font-bold text-white bg-red-700 hover:bg-red-800">Log Out</a>
                </div>
            </div>
        </div>
    </main>

</body>
</html>