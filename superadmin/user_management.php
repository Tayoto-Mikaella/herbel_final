<?php
require_once '../PHP/dbcon.php';
session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || strtolower($_SESSION['role']) !== 'superadmin') {
    header("location: ../index.html");
    exit;
}

$message = '';
$message_type = '';
if (isset($_SESSION['flash_message'])) {
    $message = htmlspecialchars($_SESSION['flash_message']);
    $message_type = $_SESSION['flash_message_type'] ?? 'info';
    unset($_SESSION['flash_message']);
    unset($_SESSION['flash_message_type']);
}

$current_user_id = $_SESSION['user_id'];
$sql_admins = "SELECT User_ID, Name, Email FROM users WHERE Role = 'admin' AND User_ID != ? ORDER BY Name ASC";
$stmt = $conn->prepare($sql_admins);
$stmt->bind_param("i", $current_user_id);
$stmt->execute();
$result_admins = $stmt->get_result();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - Herbel Apartelle</title>
    <link rel="stylesheet" href="../CSS/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <header>
        <a href="superadmin_dashboard.php" class="logo-area">
            <img src="../logo.png" alt="Herbel Apartelle Logo">
            <h1 class="header-title">HERBEL APARTELLE RESERVATION</h1>
        </a>
         <a href="profile.php" class="admin-section">
            <span class="admin-icon" style="color:#D9534F;">&#9818;</span>
            <span><?php echo htmlspecialchars($_SESSION['name']); ?> (Super Admin)</span>
        </a>
    </header>

    <nav class="super-admin-nav">
        <a href="superadmin_dashboard.php">Dashboard</a>
        <a href="user_management.php" class="active">User Management</a>
        <a href="superadmin_history.php">History</a>
    </nav>

    <div class="page-wrapper-history">
        <div class="main-content-area">
            <h2 class="page-title">User Management</h2>
            
            <?php if ($message): ?>
                <div class="message <?php echo $message_type; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <div class="control-panel">
                <a href="user_management.php" class="action-btn refresh-btn"><i class="fas fa-sync-alt"></i> Refresh List</a>
                <button type="button" class="action-btn add-btn" id="addAdminBtn"><i class="fas fa-user-plus"></i> Add New Admin</button>
            </div>

            <table class="user-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result_admins && $result_admins->num_rows > 0): ?>
                        <?php while($admin = $result_admins->fetch_assoc()): ?>
                            <tr id="user-row-<?php echo $admin['User_ID']; ?>">
                                <td><?php echo htmlspecialchars($admin['Name']); ?></td>
                                <td><?php echo htmlspecialchars($admin['Email']); ?></td>
                                <td class="actions-cell">
                                    <button class="action-btn edit-btn" data-id="<?php echo $admin['User_ID']; ?>"><i class="fas fa-edit"></i> Edit</button>
                                    <button class="action-btn delete-btn" data-id="<?php echo $admin['User_ID']; ?>" data-name="<?php echo htmlspecialchars($admin['Name']); ?>"><i class="fas fa-trash-alt"></i> Delete</button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr class="no-results-row"><td colspan="3">No admin users found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div id="addAdminModal" class="modal-overlay">
        <div class="modal-content form-modal">
            <button class="modal-close-btn close-add-modal">&times;</button>
            <h2 class="form-title">Add New Admin</h2>
            <form action="add_user.php" method="POST" id="addAdminForm">
                <fieldset>
                    <label for="add-name">Full Name:</label>
                    <input type="text" id="add-name" name="name" required>
                    <label for="add-email">Email Address:</label>
                    <input type="email" id="add-email" name="email" required>
                    <label for="add-password">Password:</label>
                    <input type="password" id="add-password" name="password" required>
                </fieldset>
                <button type="submit" class="submit-btn">Create Admin Account</button>
            </form>
        </div>
    </div>

    <div id="editAdminModal" class="modal-overlay">
        <div class="modal-content form-modal">
            <button class="modal-close-btn close-edit-modal">&times;</button>
            <h2 class="form-title">Edit Admin Account</h2>
            <form action="update_user.php" method="POST" id="editAdminForm">
                <input type="hidden" id="edit-user-id" name="user_id">
                <fieldset>
                    <label for="edit-name">Full Name:</label>
                    <input type="text" id="edit-name" name="name" required>
                    <label for="edit-email">Email Address:</label>
                    <input type="email" id="edit-email" name="email" required>
                    <label for="edit-password">New Password:</label>
                    <input type="password" id="edit-password" name="password" placeholder="Leave blank to keep current password">
                </fieldset>
                <button type="submit" class="submit-btn">Update Account</button>
            </form>
        </div>
    </div>

    <div id="deleteConfirmModal" class="modal-overlay">
        <div class="modal-content confirm-modal">
            <i class="fas fa-exclamation-triangle confirm-icon"></i>
            <h2 class="form-title">Are you sure?</h2>
            <p id="delete-confirm-text">Do you really want to delete this record? This process cannot be undone.</p>
            <div class="confirm-actions">
                <button type="button" class="action-btn cancel-btn" id="cancelDelete">Cancel</button>
                <button type="button" class="action-btn delete-btn" id="confirmDelete">Confirm Delete</button>
            </div>
        </div>
    </div>

<script src="../JS/user_management.js"></script>
</body>
</html>