<?php
require_once '../PHP/dbcon.php';
session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || strtolower($_SESSION['role']) !== 'superadmin') {
    header("location: ../index.html");
    exit;
}

$sql_history = "
    SELECT 
        r.Reservation_ID,
        g.First_Name, 
        g.Last_Name, 
        rm.Room_No,
        rt.Type_Name AS Room_Type, 
        r.Check_in_Date, 
        r.Check_out_Date, 
        r.Status AS Reservation_Status, 
        r.Amount_Paid_Initial,
        pm.Method_Name AS Payment_Method
    FROM reservation r
    JOIN guest g ON r.Guest_ID = g.Guest_ID
    JOIN room rm ON r.Room_ID = rm.Room_ID
    JOIN room_type rt ON rm.Room_Type_ID = rt.Room_Type_ID
    LEFT JOIN payment p ON r.Reservation_ID = p.Reservation_ID
    LEFT JOIN payment_method pm ON p.Payment_Method_ID = pm.Payment_Method_ID
    WHERE r.Status IN ('Checked-Out', 'Cancelled')
    ORDER BY r.Check_out_Date DESC
";
$result_history = $conn->query($sql_history);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reservation History - Herbel Apartelle</title>
    <link rel="stylesheet" href="../CSS/style.css">
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
        <a href="user_management.php">User Management</a>
        <a href="superadmin_history.php" class="active">History</a>
    </nav>

    <div class="page-wrapper-history">
        <div class="main-content-area">
            <h2 class="page-title">Reservation History</h2>
            <p class="page-subtitle">This page shows all completed or cancelled reservations.</p>

            <table class="guest-table">
                <thead>
                    <tr>
                        <th>Guest Name</th>
                        <th>Room</th>
                        <th>Check-in</th>
                        <th>Check-out</th>
                        <th>Amount Paid</th>
                        <th>Final Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result_history && $result_history->num_rows > 0): ?>
                        <?php while($row = $result_history->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['First_Name'] . ' ' . $row['Last_Name']); ?></td>
                                <td><?php echo htmlspecialchars($row['Room_No'] . ' (' . $row['Room_Type'] . ')'); ?></td>
                                <td><?php echo htmlspecialchars(date('m/d/y h:i A', strtotime($row['Check_in_Date']))); ?></td>
                                <td><?php echo htmlspecialchars(date('m/d/y h:i A', strtotime($row['Check_out_Date']))); ?></td>
                                <td>₱<?php echo htmlspecialchars(number_format($row['Amount_Paid_Initial'], 2)); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo strtolower(str_replace([' ', '-'], ['', ''], $row['Reservation_Status'])); ?>">
                                        <?php echo htmlspecialchars($row['Reservation_Status']); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr class="no-results-row"><td colspan="6">No historical records found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>