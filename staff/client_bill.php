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




// Check if client_id is provided
if (isset($_GET['id'])) {
    $clientId = $_GET['id'];

    // Fetch client details
    $clientSql = "SELECT * FROM clients WHERE client_id = $clientId";
    $clientResult = $conn->query($clientSql);
    
    // Check if client exists
    if ($clientResult && $clientResult->num_rows > 0) {
        $client = $clientResult->fetch_assoc();
        $meter = $client['meter'] ?? null; // Get meter or null

        // Fetch the last billing record to get the present reading
        $billingSql = "SELECT present_reading, billing_date FROM billing WHERE client_id = $clientId ORDER BY billing_date DESC LIMIT 1";
        $billingResult = $conn->query($billingSql);

        // Use the present reading from the last billing or the meter if no previous billing
        if ($billingResult && $billingResult->num_rows > 0) {
            $lastBillingRow = $billingResult->fetch_assoc();
            $previousReading = $lastBillingRow['present_reading'];
            $lastBillingDate = new DateTime($lastBillingRow['billing_date']);
        } else {
            $previousReading = $meter; // Default to meter if no previous readings
            $lastBillingDate = new DateTime(); // Today's date if no previous billing
        }
        
        // Calculate next payment date (one month after last billing)
        $nextPaymentDate = (clone $lastBillingDate)->modify('+1 month');
        $nextPaymentFormatted = $nextPaymentDate->format('F j, Y');
        
        // Calculate penalty if payment is late
        $currentDate = new DateTime();
        $penalty = 0;
        if ($currentDate > $nextPaymentDate && $currentDate <= $nextPaymentDate->modify('+7 days')) {
            $penalty = 112; // Penalty if payment is 1 to 7 days late
        }
    } else {
        die("Client not found.");
    }
} else {
    die("No client ID provided.");
}

// Handle form submission
// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $previousReading = $_POST['previousReading'];
    $presentReading = $_POST['presentReading'];
    $cubicPerMeter = $_POST['cubicPerMeter'];
    $status = $_POST['status']; // Get status from hidden input

    // Set staff ID from session
    $staffId = $_SESSION['staff_id'];
    if (!$staffId) {
        die("Staff ID is required.");
    }
    
    // Calculate consumption and bill amount
    $consumption = $presentReading - $previousReading;
    $bill_amount = $consumption * $cubicPerMeter + $penalty;

    // Insert billing data into the database with the current date for billing
    $sql = "INSERT INTO billing (client_id, previous_reading, present_reading, cubic_per_meter, consumption, billing_date, bill_amount, staff_id, status) 
            VALUES ($clientId, $previousReading, $presentReading, $cubicPerMeter, $consumption, NOW(), $bill_amount, $staffId, '$status')";
    
    if (!$conn->query($sql)) {
        die("Execution failed: " . $conn->error);
    }

    // After inserting, redirect to billing history page to view 'Unpaid' status
    header("Location: client_billing_history.php?id=$clientId");
    exit();
}


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Client Bill for <?php echo htmlspecialchars($client['client_name']); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        /* Your styles here */
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
        .header h1 {
            margin: 0;
        }
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
        .sidebar a:hover {
            background-color: #0099cc;
        }
        .content {
            margin-left: 250px;
            margin-top: 60px;
            padding: 20px;
        }
        input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 16px;
        }

        .billing-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 20px; /* Adjust space below as needed */
}
.billing-header span {
    margin-left: 10px; /* Space between elements */
}

.next-payment {
    color: green;
    font-size: 17px;
}

.back-link {
    text-decoration: none;
    color: black;
    font-size: 18px; /* Adjust the text size */
    display: flex;    /* Align icon and text horizontally */
    align-items: center; /* Center icon and text vertically */
}

.back-link i {
    font-size: 24px; /* Adjust the icon size */
    margin-right: 8px; /* Space between icon and text */
}


    </style>
</head>
<body>
    <div class="header">
        <h1>Water Billing Information System</h1>
        <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
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
    

    <div class="content">
    <a href="billing.php" class="back-link">
    <i class="fas fa-arrow-left"></i> Back 
</a>

    <div class="billing-header">
        <h1>Billing Client for <?php echo htmlspecialchars($client['client_name']); ?></h1>
        <span>Client ID: <?php echo htmlspecialchars($clientId); ?></span>
        <span class="next-payment">Next Payment Date: <?php echo $nextPaymentFormatted; ?></span>
        <span> | Penalty (if applicable):PHP <?php echo $penalty > 0 ? $penalty : 0; ?> </span>
    </div>

    <form method="post">
        <label for="previousReading">Previous Reading</label>
        <input type="number" id="previousReading" name="previousReading" value="<?php echo htmlspecialchars($previousReading); ?>" required>
        
        <label for="presentReading">Present Reading</label>
        <input type="number" id="presentReading" name="presentReading" required>
        
        <label for="cubicPerMeter">Cubic per Meter</label>
        <input type="number" id="cubicPerMeter" name="cubicPerMeter" required>

            <!-- Hidden status field set to 'Unpaid' -->
    <input type="hidden" name="status" value="Unpaid">
        
        <button type="submit">Submit</button>
    </form>
</div>


</body>
</html>

<?php
$conn->close();
?>
