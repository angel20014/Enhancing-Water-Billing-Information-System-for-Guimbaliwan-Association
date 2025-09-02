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

    // Fetch billing records including the status field
 // Fetch billing records including the staff_id and status field
 $sql = "SELECT billing_id, client_id, previous_reading, present_reading, consumption, cubic_per_meter, billing_date, bill_amount, status, staff_id FROM billing WHERE client_id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $clientId);
    $stmt->execute();
    $result = $stmt->get_result();

    // Fetch client details
    $clientSql = "SELECT client_name FROM clients WHERE client_id = ?";
    $clientStmt = $conn->prepare($clientSql);
    $clientStmt->bind_param('i', $clientId);
    $clientStmt->execute();
    $clientResult = $clientStmt->get_result();
    $client = $clientResult->fetch_assoc();
}

// Handle AJAX request to mark as paid
if (isset($_POST['billing_id'])) {
    $billingId = $_POST['billing_id'];
    $updateStatusSql = "UPDATE billing SET status = 'paid' WHERE billing_id = ?";
    $updateStmt = $conn->prepare($updateStatusSql);
    $updateStmt->bind_param('i', $billingId);
    $updateStmt->execute();
    echo "success";
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Billing History for <?php echo htmlspecialchars($client['client_name']); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

    <style>
        /* Add your styles here */
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

        .header .admin-settings-icon {
    color: white;
    font-size: 24px;
    cursor: pointer;
    text-decoration: none;
    display: flex;
    align-items: center;
    transition: color 0.3s;
}

.header .admin-settings-icon:hover {
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
            top: 20%;
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

        .sidebar a i {
            margin-right: 10px;
        }

        .sidebar a:hover {
            background-color: #0099cc;
        }
        .content {
            margin-left:20px;
            margin-top: 20px;
            padding: 20px;
        }

        .contents {
            margin-left: 240px;
            margin-top: 60px;
            padding: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: center;
        }
        th {
            background-color: #f2f2f2;
        }
        tr:hover {
            background-color: #f5f5f5;
        }
        a {
            display: inline-block;
            margin-top: 20px;
            text-decoration: none;
            color: blue;
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

.pay-icon {
    cursor: pointer;
    color: green;
    font-size: 20px; /* Adjust the size as needed */
    transition: color 0.3s ease; /* Smooth color transition */
}

.pay-icon:hover {
    color: darkgreen; /* Change color on hover for better UX */
}

    </style>
</head>
<body>
    <div class="header">
        <h1>Water Billing Information System</h1>
        <a href="logout.php" class="logout-btn" style="color: white; margin-left: auto;"><i class="fas fa-sign-out-alt"></i> Logout</a>
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
    

    <div class="contents">
    <a href="billing.php" class="back-link">
    <i class="fas fa-arrow-left"></i> Back 
</a>
<div class="content">
    <h1>Billing History for <?php echo htmlspecialchars($client['client_name']); ?></h1>
    <table>
    <thead>
        <tr>
            <th>Billing ID</th>
            <th>Previous Reading</th>
            <th>Present Reading</th>
            <th>Consumption</th>
            <th>Cubic per Meter</th>
            <th>Billing Date</th>
            <th>Amount</th>
            <th>Pay</th>
        </tr>
    </thead>
    <tbody>
        <?php
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($row['billing_id']) . "</td>";
                echo "<td>" . htmlspecialchars($row['previous_reading']) . "</td>";
                echo "<td>" . htmlspecialchars($row['present_reading']) . "</td>";
                echo "<td>" . htmlspecialchars($row['consumption']) . "</td>";
                echo "<td>" . htmlspecialchars($row['cubic_per_meter']) . "</td>";
                echo "<td>" . date('F j, Y', strtotime($row['billing_date'])) . "</td>";
                echo "<td>" . htmlspecialchars($row['bill_amount']) . "</td>"; // Added amount column
                echo "<td>
                <a href='pay_billing.php?id=" . $row['client_id'] . "'><i class='fas fa-money-bill' title='Client Bill'></i></a>
                </td>";
                echo "</tr>";
            
            }
        } else {
            echo "<tr><td colspan='7'>No billing records found</td></tr>";
        }
        ?>
    </tbody>
</table>

</div>

<script>
  function markAsPaid(billingId) {
            if (confirm("Are you sure you want to mark this as paid?")) {
                const xhr = new XMLHttpRequest();
                xhr.open("POST", "update_billing_status.php", true); // Replace with your PHP file's name
                xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
                xhr.onreadystatechange = function () {
                    if (xhr.readyState === 4 && xhr.status === 200) {
                        if (xhr.responseText === "success") {
                            document.getElementById("status-" + billingId).innerHTML = "Paid";
                        } else {
                            alert("Failed to update status.");
                        }
                    }
                };
                xhr.send("billing_id=" + billingId);
            }
        }
</script>

</body>
</html>
