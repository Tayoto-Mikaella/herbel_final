<?php
require_once 'dbcon.php';
session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
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
";

$whereClauses = ["r.Status IN ('Checked-Out', 'Cancelled')"];
$params = [];
$types = "";

$search_name = isset($_GET['search_name']) ? trim($_GET['search_name']) : '';
if (!empty($search_name)) {
    $searchTerm = "%" . $search_name . "%";
    $whereClauses[] = "(g.First_Name LIKE ? OR g.Last_Name LIKE ?)";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $types .= "ss";
}

$filter_date_val = isset($_GET['filter_date']) ? $_GET['filter_date'] : '';
if (!empty($filter_date_val)) {
    $whereClauses[] = "DATE(r.Check_in_Date) = ?";
    $params[] = $filter_date_val;
    $types .= "s";
}

if (!empty($whereClauses)) {
    $sql_history .= " WHERE " . implode(" AND ", $whereClauses);
}

$sql_history .= " ORDER BY r.Check_out_Date DESC";

$stmt_history = $conn->prepare($sql_history);
if ($stmt_history === false) {
    die("Error preparing statement for history: " . $conn->error);
}
if (!empty($params)) {
    $stmt_history->bind_param($types, ...$params);
}
$stmt_history->execute();
$result_history = $stmt_history->get_result();

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
        <a href="homepage.php" class="logo-area">
            <img src="../logo.png" alt="Herbel Apartelle Logo">
            <h1 class="header-title">HERBEL APARTELLE RESERVATION</h1>
        </a>
        <a href="profile.php" class="admin-section">
            <span class="admin-icon">&#128100;</span>
            <span><?php echo htmlspecialchars($_SESSION['name']); ?></span>
        </a>
    </header>

    <nav class="main-nav">
        <a href="homepage.php">Guest List</a>
        <a href="history.php" class="active">History</a>
    </nav>

    <div class="page-wrapper-history">
        <div class="main-content-area">
            <h2 class="page-title">Reservation History</h2>

            <form method="GET" action="history.php" id="filterForm">
                <div class="controls">
                    <div class="left-controls">
                        <input type="date" id="filter_date_input" name="filter_date" value="<?php echo htmlspecialchars($filter_date_val); ?>" onchange="this.form.submit();" title="Filter by Check-in Date">
                        <input type="text" name="search_name" class="search-input" placeholder="Search by Guest Name" value="<?php echo htmlspecialchars($search_name); ?>">
                        <button type="submit" class="search-action-btn">Search</button>
                    </div>
                </div>
            </form>

            <table class="guest-table">
                <thead>
                    <tr>
                        <th>Guest Name</th>
                        <th>Room</th>
                        <th>Check-in</th>
                        <th>Check-out</th>
                        <th>Amount Paid</th>
                        <th>Payment Method</th>
                        <th>Final Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result_history->num_rows > 0): ?>
                        <?php while($row = $result_history->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['First_Name'] . ' ' . $row['Last_Name']); ?></td>
                                <td><?php echo htmlspecialchars($row['Room_No'] . ' (' . $row['Room_Type'] . ')'); ?></td>
                                <td><?php echo htmlspecialchars(date('m/d/y h:i A', strtotime($row['Check_in_Date']))); ?></td>
                                <td><?php echo htmlspecialchars(date('m/d/y h:i A', strtotime($row['Check_out_Date']))); ?></td>
                                <td>₱<?php echo htmlspecialchars(number_format($row['Amount_Paid_Initial'], 2)); ?></td>
                                <td><?php echo htmlspecialchars($row['Payment_Method'] ?? 'N/A'); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo strtolower(str_replace([' ', '-'], ['', ''], $row['Reservation_Status'])); ?>">
                                        <?php echo htmlspecialchars($row['Reservation_Status']); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr class="no-results-row"><td colspan="7">No history found matching your criteria.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>