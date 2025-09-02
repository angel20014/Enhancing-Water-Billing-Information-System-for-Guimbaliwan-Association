<?php
session_start();
if (!isset($_SESSION['staff_id'])) {

    header("Location: login.php");
    exit;
}
include_once 'config.php';


// Fetch staff's full name
$userId = $_SESSION['staff_id'];
$stmt = $conn->prepare("SELECT full_name FROM staff WHERE staff_id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$staff = $result->fetch_assoc();
$fullName = $staff ? $staff['full_name'] : 'User'; // Default to 'User' if not found


// Fetch client data from the database
$sql = "SELECT COUNT(*) as total_clients FROM clients";
$result = $conn->query($sql);
$clients_count = $result->fetch_assoc()['total_clients'];


// Fetch today's transactions with client details from the database
// Remove date checks for testing
$sql = "
    SELECT 
        c.client_name, 
        b.consumption,
        b.billing_id, 
        b.bill_amount, 
        b.billing_date, 
        b.status
    FROM 
        billing b
    JOIN 
        clients c ON b.client_id = c.client_id 
    WHERE 
        b.status = 'unpaid'
";

$transactions = $conn->query($sql);

// Initialize an array to store transactions
$transaction_list = [];
if ($transactions->num_rows > 0) {
    while ($row = $transactions->fetch_assoc()) {
        $transaction_list[] = $row;
    }
}

// Calculate unpaid bills
$unpaid_bills = count(array_filter($transaction_list, function($t) {
    return isset($t['status']) && $t['status'] === 'unpaid';
}));

// Fetch monthly transactions to calculate total income
$sql = "SELECT bill_amount FROM billing WHERE MONTH(billing_date) = MONTH(CURDATE())";
$monthly_transactions = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);
// Fetch the count of unpaid bills directly from the database
$sql = "SELECT COUNT(*) as unpaid_count FROM billing WHERE status = 'unpaid'";
$result = $conn->query($sql);
$unpaid_bills = $result->fetch_assoc()['unpaid_count'];

// Fetch the count of overdue bills directly from the database
// Fetch the count of overdue bills (unpaid and past 7 days from the billing date)
$sql = "SELECT COUNT(*) as overdue_count 
        FROM billing 
        WHERE status = 'unpaid' 
        AND billing_date < DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
$result = $conn->query($sql);
$overdue_bills = $result->fetch_assoc()['overdue_count'];

// Fetch clients with upcoming payment due dates (within the next 7 days)
$sql = "
    SELECT c.client_name, b.billing_date
    FROM billing b
    JOIN clients c ON b.client_id = c.client_id
    WHERE b.status = 'unpaid' 
    AND b.billing_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
";
$upcoming_payments = $conn->query($sql);

// Prepare notifications
$notifications = [];
if ($upcoming_payments->num_rows > 0) {
    while ($row = $upcoming_payments->fetch_assoc()) {
        $notifications[] = "Reminder: Payment for {$row['client_name']} is due on {$row['billing_date']}.";
    }
}
if ($overdue_bills > 0) {
    $notifications[] = "Notice: There are {$overdue_bills} overdue bills that are unpaid for more than 7 days.";
}

// Calculate total income for the current month
$total_income_month = array_sum(array_column($monthly_transactions, 'bill_amount'));

// Fetch the current month and year
$currentMonth = date('n');
$currentYear = date('Y');

// Get the current month name
$currentMonthName = date('F'); // 'F' gives the full month name (e.g., 'October')

// Generate the calendar for the current month and year
$calendar = generate_calendar($currentMonth, $currentYear);

function generate_calendar($month, $year) {
    // Array containing days of the week
    $daysOfWeek = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

    // First day of the month
    $firstDayOfMonth = mktime(0, 0, 0, $month, 1, $year);

    // Number of days in the month
    $daysInMonth = date('t', $firstDayOfMonth);

    // Day of the week the month starts on
    $dayOfWeek = date('w', $firstDayOfMonth);

    // Get the current day
    $currentDay = date('j');

    // Create the table for the calendar
    $calendar = '<table>';
    $calendar .= '<tr>';

    // Create the table headers for the days of the week
    foreach ($daysOfWeek as $day) {
        $calendar .= '<th>' . $day . '</th>';
    }

    $calendar .= '</tr><tr>';

    // Fill the first row with empty days until the first day of the month
    for ($i = 0; $i < $dayOfWeek; $i++) {
        $calendar .= '<td></td>';
    }

    // Initialize day counter
    $dayCounter = 1;

    // Fill the calendar with days
    while ($dayCounter <= $daysInMonth) {
        // Start a new row every Sunday
        if ($dayOfWeek == 7) {
            $dayOfWeek = 0;
            $calendar .= '</tr><tr>';
        }

        // Highlight the current day
        if ($dayCounter == $currentDay) {
            $calendar .= '<td><strong>' . $dayCounter . '</strong></td>';
        } else {
            $calendar .= '<td>' . $dayCounter . '</td>';
        }

        $dayCounter++;
        $dayOfWeek++;
    }

    // Fill the last row with empty cells if needed
    if ($dayOfWeek != 7) {
        $remainingDays = 7 - $dayOfWeek;
        for ($i = 0; $i < $remainingDays; $i++) {
            $calendar .= '<td></td>';
        }
    }

    $calendar .= '</tr>';
    $calendar .= '</table>';

    return $calendar;
}

// Fetch the current month and year
$currentMonth = date('n');
$currentYear = date('Y');

// Generate the calendar for the current month and year
$calendar = generate_calendar($currentMonth, $currentYear);

     
// Fetch count of paid bills directly from the database
$sql = "SELECT COUNT(DISTINCT client_id) as paid_count FROM billing WHERE status = 'paid'";
$result = $conn->query($sql);
$paid_bills = $result->fetch_assoc()['paid_count'];

// Fetch count of unpaid bills directly from the database
$sql = "SELECT COUNT(DISTINCT client_id) as unpaid_count FROM billing WHERE status = 'unpaid'";
$result = $conn->query($sql);
$unpaid_bills = $result->fetch_assoc()['unpaid_count'];

// Add this query after the other queries in your code
$sql = "SELECT COUNT(*) as disconnected_count FROM clients WHERE status = 'disconnected'";
$result = $conn->query($sql);
$disconnected_clients = $result->fetch_assoc()['disconnected_count'];

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" integrity="sha384-k6RqeWeci5ZR/Lv4MR0sA0FfDOMt23cez/3paNdF+qGL6jxt9fopzBWtS3sX6eB7" crossorigin="anonymous">


    <style>
        /* Header adjust */
        .header {
            background-color: blue;
            color: white;
            padding: 5px 20px;
            height: 60px;
            width: 100%;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            display: flex;
            justify-content: center;
            align-items: center;
            
        }
        /* Logout adjust */
        .logout-btn {
            background-color: red;
            color: white;
            border: none;
            border-radius: 5px;
            padding: 5px 15px;
            cursor: pointer;
            font-size: 16px;
            text-decoration: none;
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            right: 60px;
        }

        .logout-btn:hover {
            background-color: darkred;
        }

        .admin-settings-icon {
    color: white;
    font-size: 15px;
    cursor: pointer;
    text-decoration: none;
    display: flex;
    align-items: center;
    transition: color 0.3s;
}

 .admin-settings-icon:hover {
    color: #4CAF50; /* Change to a color of your choice for the hover effect */
}

.header .admin-settings-icon i {
    margin-right: 0;
}
 

        /* Modal styles */
        /* Modal CSS */
.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    overflow: auto;
    background-color: rgba(0,0,0,0.4);
}

.modal-content {
    background-color: #fefefe;
    margin: 15% auto;
    padding: 10px;
    border: 1px solid #888;
    width: 80%;
    max-width: 600px;
    position: relative;
    border-radius: 8px;
}

/* Close button */
.close-btn {
    color: #aaa;
    float: right;
    font-size: 28px;
    font-weight: bold;
}

.close-btn:hover,
.close-btn:focus {
    color: black;
    text-decoration: none;
    cursor: pointer;
}

/* Section styles */
.section {
    margin-bottom: 20px;
}

.section h3 {
    margin-top: 0;
}

/* Divider between sections */
.section-divider {
    border-top: 4px solid #ddd;
    margin: 20px 0;
}

/* Form styling */
.form-group {
    margin-bottom: 15px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: bold;
}

.form-group input {
    width: 95%;
    padding: 10px;
    border: 1px solid #ccc;
    border-radius: 4px;
}

.btn-submit,
.btn-cancel {
    padding: 10px 20px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 16px;
    margin-right: 10px;
}

.btn-submit {
    background-color: #4CAF50;
    color: white;
}

.btn-submit:hover {
    background-color: #45a049;
}

.btn-cancel {
    background-color: #f44336;
    color: white;
}

.btn-cancel:hover {
    background-color: #e53935;
}

    /* Password visibility toggle styles */
.password-container {
    position: relative;
    display: flex;
    align-items: center;
}

.password-container input {
    width: 100%;
    padding: 10px;
    padding-right: 40px; /* Adjust space for the icon */
}

.password-container i {
    position: absolute;
    right: 10px;
    cursor: pointer;
    font-size: 18px;
    color: #aaa;
}

.password-container i:hover {
    color: #333;
}

        .header h1 {
            margin: 0;
        }

        .sidebar {
            width: 250px;
            background-color: black;
            color: white;
            height: 100%;
            position: fixed;
            top: 70px;
            left: 0;
            overflow-x: hidden;
            padding-top: 20px;
            text-align: center;
        }


        .sidebar a {
            padding: 15px 30px;
            text-decoration: none;
            font-size: 20px;
            color: white;
            display: flex;
            align-items: center;
            margin-bottom: 10px;
            border-radius: 5px;
            transition: background-color 0.3s;
            font-size: 24px;
        }

        .sidebar a i {
            margin-right: 10px;
        }

        .sidebar a:hover {
            background-color: #0099cc;
        }

        .content {
    margin-left: 250px;
    margin-top: 50px;
    padding: 20px;
    display: flex;
    flex-wrap: wrap;
    justify-content: space-around; /* Changed to space-around for better spacing */
}

.container {
    display: flex; /* Use flex to create columns */
    align-items: center; /* Center items vertically */
    margin-bottom: 20px; /* Space between containers */
    border: 1px solid #ddd; /* Optional: Add border */
    border-radius: 8px; /* Optional: Rounded corners */
    padding: 50px; /* Optional: Padding inside containers */
    background-color: #f9f9f9; /* Optional: Background color */
}

.row {
    display: flex; /* Flex for icon and text */
    width: 100%; /* Full width */
}

.icon {
    background-color: #e0f7fa; /* Background color for icon */
    padding: 15px; /* Padding for icon area */
    border-radius: 8px; /* Rounded corners */
    display: flex; /* Center icon */
    justify-content: center; /* Center icon horizontally */
    align-items: center; /* Center icon vertically */
    width: 90px; /* Fixed width */
    height: 90px; /* Fixed height */
    margin-right: 70px; /* Space between icon and text */
}

.icon i {
    font-size: 50px; /* Icon size */
}

.text {
    flex-grow: 1; /* Allow text area to take remaining space */
}

.text h3 {
    font-size: 30px; /* Header font size */
    margin: 0; /* Remove default margin */
}

.text p {
    font-size: 28px; /* Paragraph font size */
    margin: 5px 0 0; /* Adjust margin */
    text-align: center;
    margin-top: 30px;
}
        .container:nth-child(1) {
            background-color: gray;
        }

        .container:nth-child(2) {
            background-color: gray;
        }

        .container:nth-child(3) {
            background-color: gray;
        }

      
        .container h3 {
            margin-top: 0;
        }
    
        .transaction-table {
    margin-top: 20px; /* Space above this section */
    margin-left: 2px; /* Space for sidebar */
    margin-left: 260px;
    flex: 1; /* Allows the table to take available space */
}

.transaction-table h3{
    font-size: 25px;
}
.transaction-table table {
    width: 100%; /* Full width */
    border-collapse: collapse; /* Merge borders */
}

.transaction-table th, .transaction-table td {
    border: 1px solid #ddd; /* Border for cells */
    padding: 8px; /* Padding for cells */
    text-align: CENTER; /* Left align text */
}

.transaction-table th {
    background-color: #f2f2f2; /* Light grey background for header */
    font-weight: bold; /* Bold header text */
    font-size: 18px;
}

.transaction-table tr:nth-child(even) {
    background-color: #f9f9f9; /* Alternate row colors */
}

.transaction-table tr:hover {
    background-color: #f1f1f1; /* Hover effect for rows */
}

.transaction-table h3 {
    font-size: 25px;
}


/* Adjust box total clients, total income, unpaid bills*/
.summary-box {
    margin-top: 10px;
    display: flex; /* Use flex to arrange items in a row */
    justify-content: space-between;  /* Space items evenly */
    align-items: stretch;  /* Stretch to fill container */
    gap: 10px;  /* Adds space between the boxes */
    flex-wrap: nowrap; /* Prevent wrapping to keep all items in one line */
}

/* Summary item (individual box) styling */
.summary-item {
    text-align: center;  /* Centers the text content */
    border: 1px solid #ddd;
    padding: 15px;
    flex: 1;  /* Allows boxes to grow/shrink to fill the available space */
    min-width: 200px;  /* Set a minimum width for smaller screens */
    max-width: 220px;  /* Set a maximum width to fit four items in a row */
    background-color: #f9f9f9;
    border-radius: 10px;
    box-shadow: 0px 3px 12px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease-in-out;
}

/* Responsive design for small screens */
@media (max-width: 768px) {
    .summary-item {
        flex: 1 1 100%;  /* Full width for each box on small screens */
        margin-bottom: 20px;
    }
}


/* Adjust text total clients, total income, unpaid bills*/
.summary-item h4 {
    font-size: 30px;
    margin-bottom: 15px;
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 10px;
    color: #333;
}

.summary-item i {
    font-size: 24px;
    color: #007BFF; /* Highlight the icon with a specific color */
}

/* Hover effect */
.summary-item:hover {
    background-color: #e8e8e8;
    box-shadow: 0px 5px 18px rgba(0, 0, 0, 0.15);
    transform: translateY(-5px); /* Adds a lift effect on hover */
}

/* Styling for the paragraph (numbers inside the box) */
.summary-item p {
    font-size: 28px;
    font-weight: bold;
    margin: 0;
    color: #333;
}

/* Calendar Box*/
.calendar-box {
    margin-top: 20px;
    text-align: center;
}

.calendar-box table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 10px;
}

.calendar-box th, .calendar-box td {
    padding: 10px;
    border: 1px solid #ccc;
    text-align: center;
    font-size: 16px;
    background-color: #f2f2f2;
}

.calendar-box th {
    background-color: #f2f2f2;
    font-weight: bold;
}

.calendar-box td {
    height: 40px;
}

.calendar-box td strong {
    color: #ff5722;
    font-weight: bold;
}


.billing-calendar-row {
    display: flex; /* Aligns items in a row */
    justify-content: space-between; /* Adds space between the table and calendar */
    align-items: flex-start; /* Aligns items at the start */
    gap: 20px; /* Adds some space between the two sections */
    margin-right: 50px;
}


/* Adjust Calendar box */
.calendar-box {
    flex: 1; /* Allows the calendar to take available space */
    max-width: 400px; /* Optional: Set a max width for the calendar */
    background-color: lemonchiffon;
}

.summary-item a {
    display: block; /* Makes the link a block-level element */
    color: #007BFF; /* Blue color for the link */
    text-decoration: none; /* Remove underline */
    margin-top: 10px; /* Space above the link */
}

.summary-item a:hover {
    text-decoration: underline; /* Underline on hover */
    color: #0056b3; /* Darker blue on hover */
}

.modal {
    display: none; /* Hidden by default */
    position: fixed; /* Stay in place */
    z-index: 1; /* Sit on top */
    left: 0;
    top: 0;
    width: 100%; /* Full width */
    height: 100%; /* Full height */
    overflow: auto; /* Enable scroll if needed */
    background-color: rgb(0,0,0); /* Fallback color */
    background-color: rgba(0,0,0,0.4); /* Black w/ opacity */
}

.modal-content {
    background-color: #fefefe;
    margin: 15% auto; /* 15% from the top and centered */
    padding: 20px;
    border: 1px solid #888;
    width: 80%; /* Could be more or less, depending on screen size */
}

.close {
    color: #aaa;
    float: right;
    font-size: 28px;
    font-weight: bold;
}

.close:hover,
.close:focus {
    color: black;
    text-decoration: none;
    cursor: pointer;
}
/* TODAY TIME */
.current-time {
    font-size: 20px;
    font-weight: bold;
    margin-bottom: 1px;
    margin-right: 100px;
}

 /* CSS for new user info section inside the sidebar */
 .user-info-sidebar {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 20px;
    background-color: black; /* Match the sidebar color or choose a different color */
    margin-bottom: 20px; /* Space between user info and the sidebar links */
    border-radius: 8px; /* Rounded corners for visual appeal */
    color: white; /* Text color */
    text-align: center; /* Center align text */
}

.user-icon {
    font-size: 90px; /* Size of the user icon */
    margin-bottom: 10px; /* Space between icon and text */
}

.user-text {
    font-size: 25px; /* Size of the text */
    font-weight: bold; /* Bold text */
}

.notification {
        position: relative;
        border: 1px solid #ddd;
        padding: 10px;
        margin: 5px;
        background-color: #f9f9f9;
        border-radius: 5px;
        transition: transform 0.3s ease-in-out;
    }

    /* Optional disappear animation */
    .disappeared {
        opacity: 0;
        transition: opacity 0.3s ease-in-out;
    }
    .no-notifications {
        color: green;
        font-weight: bold;
    }
    .swiped {
        transform: translateX(-100%);
    }
    .close-btn {
        position: absolute;
        top: 5px;
        right: 10px;
        font-size: 18px;
        color: #ffffff;
        background-color: #f00;
        padding: 5px;
        border-radius: 50%;
        cursor: pointer;
        z-index: 10; /* Ensure button is above other elements */
    }
    .close-btn:hover {
        background-color: #c00;
    }

    .view-details {
    cursor: pointer; /* Ensures a pointer cursor on hover */
    pointer-events: auto; /* Allows clicking */
    z-index: 1; /* Ensures it's above other elements */
}


</style>
</head>
<body>

<div class="header">
    <h1>Water Billing Information System</h1>
   
    <a href="#" class="logout-btn" onclick="confirmLogout()"><i class="fas fa-sign-out-alt"></i> Logout</a>

    </div>
    
    <div class="sidebar">
   
    <p style="margin: 0; padding: 0; font-size: 16px; color:green;">Welcome, <?= htmlspecialchars($fullName); ?>!</p>
    <div class="sidebar-logo">
        <img src="logo.png" alt="User Logo" style="width: 250px; height: auto; margin-bottom: 10px;">
    </div>
    <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
    <a href="clients.php"><i class="fas fa-users"></i> Clients</a>
    <a href="billing.php"><i class="fas fa-dollar-sign"></i> Billing</a>
    <a href="bill_report.php"><i class="fas fa-file-invoice"></i> Bill Report</a>
</div>

    <!-- Modal Structure -->
<div id="settingsModal" class="modal">
    <div class="modal-content">
        <span class="close-btn" onclick="closeModal()">&times;</span>
        <h2>Change Username and Password</h2>
        <form id="settingsForm" action="settings_process.php" method="post">
            <!-- Current Information Section -->
            <div class="section">
                <h3>Current Information</h3>
                <div class="form-group">
                    <label for="currentUsername">Current Username</label>
                    <input type="text" id="currentUsername" name="currentUsername" required>
                </div>
                <div class="form-group">
                    <label for="currentPassword">Current Password</label>
                    <div class="password-container">
                        <input type="password" id="currentPassword" name="currentPassword" required>
                        <i class="fas fa-eye" id="toggleCurrentPassword" onclick="togglePasswordVisibility('currentPassword', 'toggleCurrentPassword')"></i>
                    </div>
                </div>
            </div>
            
            <!-- Divider -->
            <div class="section-divider"></div>
            
            <!-- New Information Section -->
            <div class="section">
                <h3>New Information</h3>
                <div class="form-group">
                    <label for="newUsername">New Username</label>
                    <input type="text" id="newUsername" name="newUsername" required>
                </div>
                <div class="form-group">
                    <label for="newPassword">New Password</label>
                    <div class="password-container">
                        <input type="password" id="newPassword" name="newPassword" required>
                        <i class="fas fa-eye" id="toggleNewPassword" onclick="togglePasswordVisibility('newPassword', 'toggleNewPassword')"></i>
                    </div>
                </div>
                <div class="form-group">
                    <label for="confirmPassword">Confirm New Password</label>
                    <div class="password-container">
                        <input type="password" id="confirmPassword" name="confirmPassword" required>
                        <i class="fas fa-eye" id="toggleConfirmPassword" onclick="togglePasswordVisibility('confirmPassword', 'toggleConfirmPassword')"></i>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn-submit">Save Changes</button>
            <button type="button" class="btn-cancel" onclick="closeModal()">Cancel</button>
        </form>
        <div id="responseMessage" style="display: none;"></div> <!-- For displaying messages -->
    </div>
</div>

<div class="content">
<?php
  // Set the time zone
  date_default_timezone_set('Asia/Manila');
?>
<div class="current-time">
  <p>Today is <?php echo date('l, h:i A'); ?></p>
</div>

<div id="notification-container">
    <?php if (empty($notifications)): ?>
        <div class="notification no-notifications">No overdue bills or upcoming notifications.</div>
    <?php else: ?>
        <?php foreach ($notifications as $notification): ?>
            <div class="notification">
                <span class="close-btn" onclick="closeNotification(this)">
                    <i class="fas fa-times"></i> <!-- Font Awesome icon for close button -->
                </span>
                <?php echo $notification; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>


    <!-- Summary Box Container -->
    <div class="summary-box">

    <div class="summary-item">
        <h4><i class="fas fa-users"></i>Total Clients</h4>
        <p><?php echo $clients_count; ?></p>
        <p><a href="#" class="view-details" data-modal="clientsModal"></a></p>
    </div>
    <div class="summary-item">
        <h4><i class="fas fa-money-bill-wave"></i>Total Income</h4>
        <p>Php <?php echo number_format($total_income_month, 2); ?></p>
        <p><a href="#" class="view-details" data-modal="incomeModal"></a></p>
    </div>

    <div class="summary-item">
        <h4><i class="fas fa-check-circle"></i> Paid Bills</h4>
        <p><?php echo $paid_bills; ?></p>
    </div>

    <div class="summary-item">
    <h4><i class="fas fa-times-circle"></i> Unpaid Bills</h4>
    <p><?php echo $unpaid_bills; ?></p>
    <p><a href="#" class="view-details" data-type="unpaid">View</a></p>
</div>

<div class="summary-item">
    <h4><i class="fas fa-exclamation-triangle"></i> Overdue Bills</h4>
    <p><?php echo $overdue_bills; ?></p>
    <p><a href="#" class="view-details" data-type="overdue">View</a></p>
</div>

<div class="summary-item">
    <h4><i class="fas fa-user-slash"></i> Disconnected Clients</h4>
    <p><?php echo $disconnected_clients; ?></p>
    <p><a href="#" class="view-details" data-type="disconnected">View </a></p>
</div>



</div>

</div>
    </div>
    

    <div class="billing-calendar-row">
<div class="transaction-table">
    <h3>Today's Clients Billing</h3>
    <table>
        <thead>
            <tr>
                <th>Client Name</th>
                <th>Consumption (Cubic Meters)</th>
                <th>Bill Amount ($)</th>
                <th>Billing Date</th>
            </tr>
        </thead>
        <tbody>

        
            <?php if (!empty($transaction_list)): ?>
                <?php foreach ($transaction_list as $transaction): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($transaction['client_name']); ?></td>
                        <td><?php echo htmlspecialchars($transaction['consumption']); ?></td>
                        <td><?php echo number_format($transaction['bill_amount'], 2); ?></td>
                        <td><?php echo date('Y-m-d', strtotime($transaction['billing_date'])); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="4">No transactions today.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<div class="calendar-box">
<h3><?php echo $currentMonthName; ?></h3>
    <?php echo $calendar; ?>
</div>


<!-- Unpaid Modal -->
<div id="unpaidModal" class="modal">
    <div class="modal-content">
        <span class="modal-close">&times;</span>
        <h4>Unpaid Bills</h4>
        <p></p>
    </div>
</div>

<!-- Overdue Modal -->
<div id="overdueModal" class="modal">
    <div class="modal-content">
        <span class="modal-close">&times;</span>
        <h4>Overdue Bills</h4>
        <p></p>
    </div>
</div>

<!-- Disconnected Clients Modal -->
<div id="disconnectedModal" class="modal">
    <div class="modal-content">
        <span class="modal-close">&times;</span>
        <h4>Disconnected Clients</h4>
        <p></p>
    </div>
</div>

<script>
    function confirmLogout() {
            if (confirm('Are you sure you want to logout?')) {
                window.location.href = 'logout.php'; // Replace with your actual logout URL
            }
        }
        document.getElementById('settingsForm').addEventListener('submit', function(e) {
    e.preventDefault();

    // Simple client-side validation
    var newPassword = document.getElementById('newPassword').value;
    var confirmPassword = document.getElementById('confirmPassword').value;

    if (newPassword !== confirmPassword) {
        alert("Passwords do not match!");
        return;
    }

    var formData = new FormData(this);
    var xhr = new XMLHttpRequest();
    xhr.open('POST', 'settings_process.php', true);
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

    xhr.onload = function() {
        var responseMessage = document.getElementById('responseMessage');
        if (xhr.status === 200) {
            var response = JSON.parse(xhr.responseText);
            responseMessage.textContent = response.message;
            responseMessage.style.color = response.success ? 'green' : 'red';
            responseMessage.style.display = 'block';
            if (response.success) {
                setTimeout(closeModal, 2000);
            }
        } else {
            responseMessage.textContent = 'An error occurred.';
            responseMessage.style.color = 'red';
            responseMessage.style.display = 'block';
        }
    };

    xhr.send(formData);
});


    function closeModal() {
        document.getElementById('settingsModal').style.display = 'none';
    }

    function confirmSettings() {
        document.getElementById('settingsModal').style.display = 'block';
    }

    window.onclick = function(event) {
        if (event.target === document.getElementById('settingsModal')) {
            closeModal();
        }
    }

    function togglePasswordVisibility(inputId, iconId) {
        const passwordInput = document.getElementById(inputId);
        const eyeIcon = document.getElementById(iconId);

        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            eyeIcon.classList.remove('fa-eye');
            eyeIcon.classList.add('fa-eye-slash');
        } else {
            passwordInput.type = 'password';
            eyeIcon.classList.remove('fa-eye-slash');
            eyeIcon.classList.add('fa-eye');
        }
    }
    document.addEventListener('DOMContentLoaded', () => {
    const notificationContainer = document.getElementById('notification-container');
    const notifications = notificationContainer.querySelectorAll('.notification:not(.no-notifications)');
    
    // Function to close notification on click
    window.closeNotification = function(element) {
        const notification = element.parentElement;
        notification.classList.add('disappeared');
        setTimeout(() => {
            notification.remove();
            checkNotifications();
        }, 500); // Optional short delay for fade-out
    };
    
    // Function to display "no notifications" message if all notifications are removed
    function checkNotifications() {
        if (!notificationContainer.querySelector('.notification:not(.no-notifications)')) {
            notificationContainer.innerHTML = '<div class="notification no-notifications">No overdue bills or upcoming notifications.</div>';
        }
    }
});
document.addEventListener('DOMContentLoaded', function() {
    const viewDetailsLinks = document.querySelectorAll('.view-details');
    
    viewDetailsLinks.forEach(link => {
        link.addEventListener('click', function(event) {
            event.preventDefault();
            const dataType = link.getAttribute('data-type');
            console.log('Link clicked:', dataType);
            
            fetch('fetch_client.php?type=unpaid')
                .then(response => response.json())
                .then(data => {
                    const modal = document.getElementById(`${dataType}Modal`);
                    const modalContent = modal.querySelector('.modal-content p');
                    
                    if (data.length > 0) {
                        // Update this to include more details
                        modalContent.innerHTML = data.map(client => 
                            `<p>${client.client_name} - ${client.address} - ${client.contact} - 
                             Billing ID: ${client.billing_id} - 
                             Consumption: ${client.consumption} - 
                             Bill Amount: ${client.bill_amount}</p>`
                        ).join('');
                    } else {
                        modalContent.innerHTML = "<p>No records found.</p>";
                    }
                    modal.style.display = 'block';
                })
                .catch(error => console.error('Error fetching data:', error));
        });
    });

    const closeButtons = document.querySelectorAll('.modal-close');
    closeButtons.forEach(button => {
        button.addEventListener('click', function() {
            const modal = button.closest('.modal');
            modal.style.display = 'none';
        });
    });

    window.addEventListener('click', function(event) {
        const modals = document.querySelectorAll('.modal');
        modals.forEach(modal => {
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        });
    });
});


link.addEventListener('click', function(event) {
    event.preventDefault();
    console.log('Link clicked:', dataType); // Add this line to verify the click
    // Continue with the rest of the code...
});



</script>
</body>
</html>