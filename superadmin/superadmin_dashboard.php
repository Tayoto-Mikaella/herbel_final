<?php
require_once '../PHP/dbcon.php';
session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || strtolower($_SESSION['role']) !== 'superadmin') {
    header("location: ../../index.html");
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

$sql_reservations = "
    SELECT 
        r.Reservation_ID,
        g.Guest_ID,
        g.First_Name, 
        g.Last_Name, 
        g.Contact_No, 
        rm.Room_No,
        rt.Type_Name AS Room_Type, 
        rt.Capacity, 
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

$whereClauses = ["r.Status NOT IN ('Checked-Out', 'Cancelled')"];
$params = []; 
$types = "";

$search_name = isset($_GET['search_name']) ? trim($_GET['search_name']) : '';
if (!empty($search_name)) {
    $searchTerm = "%" . $search_name . "%";
    $whereClauses[] = "(g.First_Name LIKE ? OR g.Last_Name LIKE ?)";
    $params[] = $searchTerm; $params[] = $searchTerm; $types .= "ss";
}
$filter_date_val = isset($_GET['filter_date']) ? $_GET['filter_date'] : '';
if (!empty($filter_date_val)) {
    $whereClauses[] = "DATE(r.Check_in_Date) = ?";
    $params[] = $filter_date_val; $types .= "s";
}
$filter_status_val = isset($_GET['filter_status']) ? $_GET['filter_status'] : '';
if (!empty($filter_status_val) && $filter_status_val != 'all') {
    $whereClauses[] = "r.Status = ?";
    $params[] = $filter_status_val; $types .= "s";
}

if (!empty($whereClauses)) { $sql_reservations .= " WHERE " . implode(" AND ", $whereClauses); }
$sql_reservations .= " ORDER BY r.Check_in_Date DESC";

$stmt_reservations = $conn->prepare($sql_reservations);
if ($stmt_reservations === false) { die("Error preparing statement for reservations: " . $conn->error); }
if (!empty($params)) { $stmt_reservations->bind_param($types, ...$params); }
$stmt_reservations->execute();
$result_reservations = $stmt_reservations->get_result();

$available_rooms_sql = "SELECT r.Room_ID, r.Room_No, rt.Type_Name, rt.Capacity FROM room r JOIN room_type rt ON r.Room_Type_ID = rt.Room_Type_ID WHERE r.Status = 'Available' ORDER BY rt.Type_Name ASC, r.Room_No ASC";
$available_rooms_result = $conn->query($available_rooms_sql);
$available_count = $available_rooms_result ? $available_rooms_result->num_rows : 0;

$occupied_rooms_sql = "SELECT r.Room_No, r.Room_ID, rt.Type_Name, rt.Capacity, r.Status AS Room_Status FROM room r JOIN room_type rt ON r.Room_Type_ID = rt.Room_Type_ID WHERE r.Status != 'Available' ORDER BY r.Status ASC, rt.Type_Name ASC, r.Room_No ASC";
$occupied_rooms_result = $conn->query($occupied_rooms_sql);
$occupied_count = $occupied_rooms_result ? $occupied_rooms_result->num_rows : 0;


$room_types_data = [];
$room_types_sql = "SELECT Room_Type_ID, Type_Name, Capacity, Rate_3hr, Rate_6hr, Rate_12hr, Rate_24hr FROM room_type";
$room_types_result_for_js = $conn->query($room_types_sql);
if ($room_types_result_for_js) {
    while($row = $room_types_result_for_js->fetch_assoc()) {
        $room_types_data[] = $row;
    }
}

$all_rooms_data = [];
$all_rooms_sql = "SELECT Room_ID, Room_No, Room_Type_ID, Status FROM room";
$all_rooms_result_for_js = $conn->query($all_rooms_sql);
if ($all_rooms_result_for_js) {
    while($row = $all_rooms_result_for_js->fetch_assoc()) {
        $all_rooms_data[] = $row;
    }
}

$payment_methods_data = [];
$payment_methods_sql = "SELECT Payment_Method_ID, Method_Name FROM payment_method WHERE Is_Active = TRUE ORDER BY Method_Name ASC";
$payment_methods_result_for_form = $conn->query($payment_methods_sql);
if($payment_methods_result_for_form){
    while($pm_row = $payment_methods_result_for_form->fetch_assoc()){
        $payment_methods_data[] = $pm_row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Admin Dashboard - Herbel Apartelle</title>
    <link rel="stylesheet" href="../../CSS/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <header>
        <a href="superadmin_dashboard.php" class="logo-area">
            <img src="../../logo.png" alt="Herbel Apartelle Logo">
            <h1 class="header-title">HERBEL APARTELLE RESERVATION</h1>
        </a>
        <a href="../profile.php" class="admin-section">
            <span class="admin-icon" style="color:#D9534F;">&#9818;</span>
            <span><?php echo htmlspecialchars($_SESSION['name']); ?> (Super Admin)</span>
        </a>
    </header>

    <nav class="super-admin-nav">
        <a href="superadmin_dashboard.php" class="active">Dashboard</a>
        <a href="./user_management.php">User Management</a>
        <a href="./superadmin_history.php">History</a>
    </nav>

    <div class="page-wrapper">
        <div class="main-content-area">
            <div id="notification-area"></div>
            <h2 class="page-title">Active Reservations Dashboard</h2>
             <p class="page-subtitle">Manage all ongoing and upcoming reservations.</p>

            <?php if ($message): ?>
                <div class="message <?php echo $message_type; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <form method="GET" action="superadmin_dashboard.php" id="filterForm">
                <div class="controls">
                    <div class="left-controls">
                        <input type="date" id="filter_date_input" name="filter_date" placeholder="mm/dd/yyyy" value="<?php echo htmlspecialchars($filter_date_val); ?>" onchange="this.form.submit();" title="Filter by Check-in Date">
                        <select name="filter_status" onchange="this.form.submit();" title="Filter by Status">
                            <option value="all" <?php echo ($filter_status_val == 'all' || empty($filter_status_val)) ? 'selected' : ''; ?>>All Active</option>
                            <option value="Reserved" <?php echo ($filter_status_val == 'Reserved') ? 'selected' : ''; ?>>Reserved</option>
                            <option value="Checked-In" <?php echo ($filter_status_val == 'Checked-In') ? 'selected' : ''; ?>>Checked-In</option>
                        </select>
                        <input type="text" name="search_name" class="search-input" placeholder="Search by Guest Name" value="<?php echo htmlspecialchars($search_name); ?>">
                        <button type="submit" class="search-action-btn">Search</button>
                    </div>
                    <div class="right-controls">
                        <button type="button" class="add-guest-btn-trigger" id="addGuestBtn"><i class="fas fa-plus"></i> Add Guest</button>
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
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="reservations-table-body">
                    <?php if ($result_reservations->num_rows > 0): ?>
                        <?php while($row = $result_reservations->fetch_assoc()): ?>
                            <tr id="res-row-<?php echo $row['Reservation_ID']; ?>" data-guest-name="<?php echo htmlspecialchars($row['First_Name'] . ' ' . $row['Last_Name']); ?>" data-checkout="<?php echo htmlspecialchars($row['Check_out_Date']); ?>" data-status="<?php echo htmlspecialchars($row['Reservation_Status']); ?>">
                                <td><?php echo htmlspecialchars($row['First_Name'] . ' ' . $row['Last_Name']); ?></td>
                                <td><?php echo htmlspecialchars($row['Room_No'] . ' (' . $row['Room_Type'] . ')'); ?></td>
                                <td><?php echo htmlspecialchars(date('m/d/y h:i A', strtotime($row['Check_in_Date']))); ?></td>
                                <td><?php echo htmlspecialchars(date('m/d/y h:i A', strtotime($row['Check_out_Date']))); ?></td>
                                <td>₱<?php echo htmlspecialchars(number_format($row['Amount_Paid_Initial'], 2)); ?></td>
                                <td><?php echo htmlspecialchars($row['Payment_Method'] ?? 'N/A'); ?></td>
                                <td class="status-cell">
                                    <span class="status-badge status-<?php echo strtolower(str_replace([' ', '-'], ['', ''], $row['Reservation_Status'])); ?>">
                                        <?php echo htmlspecialchars($row['Reservation_Status']); ?>
                                    </span>
                                </td>
                                <td class="actions-cell">
                                    <?php if ($row['Reservation_Status'] == 'Reserved'): ?>
                                        <button class="action-btn check-in-btn" data-id="<?php echo $row['Reservation_ID']; ?>"><i class="fas fa-sign-in-alt"></i> Check-in</button>
                                    <?php elseif ($row['Reservation_Status'] == 'Checked-In'): ?>
                                        <button class="action-btn check-out-btn" data-id="<?php echo $row['Reservation_ID']; ?>"><i class="fas fa-sign-out-alt"></i> Check-out</button>
                                    <?php endif; ?>
                                    <button class="action-btn-icon edit-reservation-btn" data-id="<?php echo $row['Reservation_ID']; ?>" title="Edit"><i class="fas fa-edit"></i></button>
                                    <button class="action-btn-icon cancel-reservation-btn" data-id="<?php echo $row['Reservation_ID']; ?>" title="Cancel Reservation"><i class="fas fa-times-circle"></i></button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr class="no-results-row"><td colspan="8">No active reservations found matching your criteria.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <aside class="sidebar available-rooms-sidebar">
            <h3 class="sidebar-title">Room Status</h3>
            <div class="sidebar-content-wrapper">
                <div class="room-panel">
                    <h4 class="sidebar-subtitle">Available (<?php echo $available_count; ?>)</h4>
                    <ul class="rooms-status-list">
                        <?php if ($available_rooms_result && $available_rooms_result->num_rows > 0): mysqli_data_seek($available_rooms_result, 0); ?>
                            <?php while($room = $available_rooms_result->fetch_assoc()): ?>
                                <li class="room-item-sidebar room-available">
                                    <span class="status-indicator"></span>
                                    <div class="room-details">
                                        <span class="room-number"><?php echo htmlspecialchars($room['Room_No']); ?></span>
                                        <span class="room-type-capacity"><?php echo htmlspecialchars($room['Type_Name']); ?> (<?php echo htmlspecialchars($room['Capacity']); ?>pax)</span>
                                    </div>
                                </li>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <li class="no-rooms">No rooms currently available.</li>
                        <?php endif; ?>
                    </ul>
                </div>
                <div class="room-panel">
                    <h4 class="sidebar-subtitle">Occupied / Other (<?php echo $occupied_count; ?>)</h4>
                    <ul class="rooms-status-list">
                        <?php if ($occupied_rooms_result && $occupied_rooms_result->num_rows > 0): mysqli_data_seek($occupied_rooms_result, 0); ?>
                            <?php while($room = $occupied_rooms_result->fetch_assoc()): ?>
                                <li class="room-item-sidebar room-<?php echo strtolower(htmlspecialchars(str_replace(' ', '-', $room['Room_Status']))); ?>">
                                    <span class="status-indicator"></span>
                                    <div class="room-details">
                                        <span class="room-number"><?php echo htmlspecialchars($room['Room_No']); ?></span>
                                        <div class="room-sub-details">
                                            <span class="room-type-capacity"><?php echo htmlspecialchars($room['Type_Name']); ?> (<?php echo htmlspecialchars($room['Capacity']); ?>pax)</span>
                                            <span class="room-actual-status">(<?php echo htmlspecialchars($room['Room_Status']); ?>)</span>
                                        </div>
                                    </div>
                                </li>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <li class="no-rooms">No rooms are currently occupied or in other states.</li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </aside>
    </div>

    <div id="addGuestModal" class="modal-overlay">
        <div class="modal-content">
            <button class="modal-close-btn">&times;</button>
            <h2 class="form-title">Add New Guest and Reservation</h2>
            <form action="../PHP/submit_reservation.php" method="POST" id="reservationFormModal">
                <div class="form-columns">
                    <div class="form-column">
                        <fieldset>
                            <legend>Guest Information</legend>
                            <label for="first_name_modal">First Name:</label>
                            <input type="text" id="first_name_modal" name="first_name" required>
                            <label for="last_name_modal">Last Name:</label>
                            <input type="text" id="last_name_modal" name="last_name" required>
                            <label for="contact_no_modal">Contact Number:</label>
                            <input type="tel" id="contact_no_modal" name="contact_no" placeholder="e.g., 09123456789">
                            <label for="email_modal">Email:</label>
                            <input type="email" id="email_modal" name="email">
                        </fieldset>
                        <fieldset>
                            <legend>Reservation Details</legend>
                            <label for="room_type_modal">Room Type:</label>
                            <select id="room_type_modal" name="room_type_id" required>
                                <option value="">-- Select Room Type --</option>
                                <?php foreach($room_types_data as $type): ?>
                                    <option value="<?php echo htmlspecialchars($type['Room_Type_ID']); ?>" 
                                            data-capacity="<?php echo htmlspecialchars($type['Capacity']); ?>"
                                            data-rate3hr="<?php echo htmlspecialchars($type['Rate_3hr'] ?? ''); ?>"
                                            data-rate6hr="<?php echo htmlspecialchars($type['Rate_6hr'] ?? ''); ?>"
                                            data-rate12hr="<?php echo htmlspecialchars($type['Rate_12hr'] ?? ''); ?>"
                                            data-rate24hr="<?php echo htmlspecialchars($type['Rate_24hr'] ?? ''); ?>">
                                        <?php echo htmlspecialchars($type['Type_Name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <label for="room_id_modal">Room:</label>
                            <select id="room_id_modal" name="room_id" required>
                                <option value="">-- Select Room Type First --</option>
                            </select>
                            <label for="check_in_date_modal">Check-in Date & Time:</label>
                            <input type="text" class="datetimepicker" id="check_in_date_modal" name="check_in_date" required placeholder="Select Check-in Date & Time">
                            <label>Quick Durations:</label>
                            <div class="duration-buttons">
                                <button type="button" data-duration="3">3 hrs</button>
                                <button type="button" data-duration="6">6 hrs</button>
                                <button type="button" data-duration="12">12 hrs</button>
                                <button type="button" data-duration="24">1 Day</button>
                            </div>
                            <label for="check_out_date_modal">Check-out Date & Time:</label>
                            <input type="text" class="datetimepicker" id="check_out_date_modal" name="check_out_date" required placeholder="Select Check-out Date & Time">
                        </fieldset>
                    </div>
                    <div class="form-column">
                        <fieldset>
                            <legend>Payment Details</legend>
                            <div class="room-capacity-display">Room Capacity: <span id="room_capacity_display_value">--</span></div>
                            <label for="additional_persons_modal">Additional Persons (₱200/person):</label>
                            <input type="number" id="additional_persons_modal" name="additional_persons" min="0" value="0" step="1">
                            <div class="calculated-value-group">
                                <label for="room_rate_modal">Room Rate (PHP):</label>
                                <input type="text" id="room_rate_modal" name="room_rate_display" class="display-field" readonly placeholder="0.00">
                            </div>
                            <div class="calculated-value-group">
                                <label for="additional_fee_display_modal">Additional Fee (PHP):</label>
                                <input type="text" id="additional_fee_display_modal" name="additional_fee_display" class="display-field" readonly placeholder="0.00">
                                <input type="hidden" id="additional_fee_actual_modal" name="additional_fee" value="0.00">
                            </div>
                            <div class="calculated-value-group grand-total-group">
                                <label for="grand_total_modal">Grand Total (PHP):</label>
                                <input type="text" id="grand_total_modal" name="grand_total_display" class="display-field" readonly placeholder="0.00">
                            </div>
                            <div class="calculated-value-group">
                                   <label for="required_payment_modal">Payment Due Now (PHP):</label>
                                   <input type="text" id="required_payment_modal" name="amount_to_pay" class="display-field" readonly placeholder="0.00">
                            </div>
                            <label for="payment_method_id_modal">Payment Method:</label>
                            <select id="payment_method_id_modal" name="payment_method_id" required>
                                <option value="">-- Select Payment Method --</option>
                                <?php foreach($payment_methods_data as $pm): ?>
                                    <option value="<?php echo htmlspecialchars($pm['Payment_Method_ID']); ?>"><?php echo htmlspecialchars($pm['Method_Name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <label for="notes_modal">Notes (Special Requests):</label>
                            <textarea id="notes_modal" name="notes" placeholder="e.g., Extra pillows"></textarea>
                        </fieldset>
                    </div>
                </div>
                <button type="submit" class="submit-btn">Submit Reservation & Payment</button>
            </form>
        </div>
    </div>

    <div id="checkOutConfirmModal" class="modal-overlay">
        <div class="modal-content confirm-modal">
            <i class="fas fa-sign-out-alt confirm-icon"></i>
            <h2 class="form-title">Confirm Check-Out</h2>
            <p id="check-out-confirm-text">Are you sure you want to check out this guest?</p>
            <div class="confirm-actions">
                <button type="button" class="action-btn cancel-btn" data-close>Cancel</button>
                <button type="button" class="action-btn delete-btn" id="confirmCheckOut">Confirm</button>
            </div>
        </div>
    </div>
    
    <div id="editReservationModal" class="modal-overlay">
        <div class="modal-content form-modal">
            <button class="modal-close-btn">&times;</button>
            <h2 class="form-title">Edit Reservation</h2>
            <form id="editReservationForm" action="update_reservation.php" method="POST">
                 <input type="hidden" name="reservation_id" id="edit-reservation-id">
                 <input type="hidden" name="guest_id" id="edit-guest-id">
                 <fieldset>
                     <legend>Guest Details</legend>
                     <label for="edit-first-name">First Name:</label>
                     <input type="text" id="edit-first-name" name="first_name" required>
                     <label for="edit-last-name">Last Name:</label>
                     <input type="text" id="edit-last-name" name="last_name" required>
                 </fieldset>
                 <fieldset>
                     <legend>Reservation Details</legend>
                     <label for="edit-checkin-date">Check-in Date & Time:</label>
                     <input type="text" class="datetimepicker" id="edit-checkin-date" name="check_in_date" required>
                     <label for="edit-checkout-date">Check-out Date & Time:</label>
                     <input type="text" class="datetimepicker" id="edit-checkout-date" name="check_out_date" required>
                     <label for="edit-status">Status:</label>
                     <select id="edit-status" name="status">
                         <option value="Reserved">Reserved</option>
                         <option value="Checked-In">Checked-In</option>
                         <option value="Cancelled">Cancelled</option>
                     </select>
                 </fieldset>
                 <button type="submit" class="submit-btn">Update Reservation</button>
            </form>
        </div>
    </div>

    <div id="cancelReservationModal" class="modal-overlay">
        <div class="modal-content confirm-modal">
             <i class="fas fa-exclamation-triangle confirm-icon" style="color: #f0ad4e;"></i>
            <h2 class="form-title">Cancel Reservation</h2>
            <p id="cancel-res-confirm-text"></p>
            <div class="confirm-actions">
                <button type="button" class="action-btn cancel-btn" data-close>No, Keep It</button>
                <button type="button" class="action-btn delete-btn" id="confirmResCancel">Yes, Cancel It</button>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        const SCRIPT_ROOM_TYPES_DATA = <?php echo json_encode($room_types_data); ?>;
        const SCRIPT_ALL_ROOMS_DATA = <?php echo json_encode($all_rooms_data); ?>;
    </script>
    <script src="../../JS/superadmin_dashboard.js"></script>
</body>
</html>