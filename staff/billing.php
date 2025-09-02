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



// Get the search term from the URL if it exists
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Insert or update next payment dates for each client
$updatePaymentDateSQL = "INSERT INTO next_payment_reminders (client_id, next_payment_date) 
                          SELECT client_id, DATE_ADD(MAX(billing_date), INTERVAL 1 MONTH) AS next_payment_date
                          FROM billing
                          GROUP BY client_id 
                          ON DUPLICATE KEY UPDATE 
                              next_payment_date = VALUES(next_payment_date)";

if ($conn->query($updatePaymentDateSQL) === TRUE) {
    // Debugging: Next payment dates updated
} else {
    die("Error updating next payment dates: " . $conn->error);
}

// Prepare the SQL query to fetch clients and their next payment reminders
$sql = "SELECT c.client_id, c.client_name, c.meter, IFNULL(b.status, 'unpaid') AS status, 
               IFNULL(n.next_payment_date, 'No date set') AS next_payment_date
        FROM clients c 
        LEFT JOIN billing b ON c.client_id = b.client_id 
        LEFT JOIN next_payment_reminders n ON c.client_id = n.client_id";

// Include search functionality
if (!empty($search)) {
    $searchTerm = "%" . $conn->real_escape_string($search) . "%";
    $sql .= " WHERE c.client_id LIKE '$searchTerm' 
               OR c.client_name LIKE '$searchTerm' 
               OR c.meter LIKE '$searchTerm'";
}

$result = $conn->query($sql);

// Check for query execution errors
if (!$result) {
    die("Query failed: " . $conn->error);
}

// Check for upcoming billing reminders
$currentDate = date('Y-m-d');
$reminderMessage = "";

// Define the 'near' payment period (e.g., 7 days)
$nearPaymentDays = 7;

// Loop through results to find upcoming payment dates
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $nextPayment = $row['next_payment_date'];
        if ($nextPayment !== 'No date set' && 
            (strtotime($nextPayment) - strtotime($currentDate) <= $nearPaymentDays * 86400) && 
            (strtotime($nextPayment) - strtotime($currentDate) >= 0)) { // Ensure the date is not in the past
            $reminderMessage .= "Reminder: Client " . htmlspecialchars($row['client_name']) . " has a billing reading due on " . htmlspecialchars($nextPayment) . ".<br>";
        }
    }
}

if (!empty($reminderMessage)) {
    // Call the JavaScript function with reminders
    echo "<script>showReminders('" . addslashes($reminderMessage) . "');</script>";
} else {
    // Handle the case where there are no reminders
    echo "<script>showReminders('No upcoming billing reminders.');</script>";
}

// Prepare the SQL query to fetch clients
$sql = "SELECT c.client_id, c.client_name, c.meter, IFNULL(b.status, 'unpaid') AS status, 
               IFNULL(n.next_payment_date, 'No date set') AS next_payment_date
        FROM clients c 
        LEFT JOIN billing b ON c.client_id = b.client_id 
        LEFT JOIN next_payment_reminders n ON c.client_id = n.client_id";

// Include search functionality
if (!empty($search)) {
    $searchTerm = "%" . $conn->real_escape_string($search) . "%"; // Escape search term for safety
    $sql .= " WHERE c.client_id LIKE '$searchTerm' 
               OR c.client_name LIKE '$searchTerm' 
               OR c.meter LIKE '$searchTerm'"; // Search by client ID, name, or meter
}

$result = $conn->query($sql);

// Check for the current month and next billing cycle
$currentMonth = date('Y-m');
$nextMonth = date('Y-m', strtotime('+1 month'));

// Update billing status: Only unpaid bills for the next month
$billingUpdateSQL = "UPDATE billing 
                     SET status = 'unpaid' 
                     WHERE status = 'paid' 
                     AND billing_date < CURDATE() 
                     AND MONTH(billing_date) = MONTH(DATE_ADD(CURDATE(), INTERVAL 1 MONTH))";

if ($conn->query($billingUpdateSQL) === TRUE) {
    echo "Billing status updated successfully.";
} else {
    echo "Error updating billing status: " . $conn->error; // Show any error messages
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Billing</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css">
<script src="https://code.jquery.com/jquery-3.2.1.slim.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.11.0/umd/popper.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js"></script>

    <style>
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
 
    /* Notification style */
    .notification {
            background-color: #ffeb3b;
            color: black;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ffc107;
            border-radius: 5px;
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
            top: 50%;
            transform: translateY(-50%);
            right: 60px;
        }

        .logout-btn:hover {
            background-color: darkred;
        }

        .sidebar {
            width: 250px;
            background-color: black;
            color: white;
            height: 100%;
            position: fixed;
            top: 50px;
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

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: center;
        }

        th {
            background-color: #f2f2f2;
            color: #333;
        }

        .action-btn {
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            padding: 5px 10px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
            margin-right: 5px;
        }

        .action-btn:hover {
            background-color: #0056b3;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.4);
            padding-top: 60px;
        }

        .modal-content {
            background-color: #fff;
            margin: 5% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            max-width: 500px;
            border-radius: 10px;
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

        .btn {
            background-color: #04AA6D;
            color: white;
            padding: 10px 20px;
            border: none;
            cursor: pointer;
            width: 100%;
            margin: 10px 0;
        }

        .btn:hover {
            opacity: 0.9;
        }
        
        form input[type="text"] {
    padding: 15px;
    border: 1px solid #ccc;
    border-radius: 4px;
    margin-right: 10px;
    font-size: 16px; /* Increase font size */
    width: 300px; /* Width of the input */
}

form button {
    padding: 15px 25px; /* Increase button padding */
    background-color: #007bff;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 16px; /* Increase font size */
}

form button:hover {
    background-color: #0056b3;
}

.content a i.fas.fa-money-bill {
    font-size: 24px; /* Adjust size of the money bill icon */
    margin-right: 15px;
}

.content a i.fas.fa-eye {
    font-size: 24px; /* Adjust size of the eye icon */
    margin-right: 15px;
}

.content a i.fas.fa-print {
    font-size: 24px; /* Adjust size of the eye icon */
}

/* Modal Styling */
.modal {
    transition: opacity 0.15s linear, top 0.15s linear; /* Smooth transition for opening and closing */
}

.modal-header {
    background-color: #007bff; /* Bootstrap primary color */
    color: white; /* White text color */
    border-bottom: 1px solid #dee2e6; /* Light border for header */
}

.modal-title {
    font-weight: bold; /* Bold title */
    font-size: 1.5rem; /* Slightly larger font size */
}

.close {
    color: white; /* White color for close button */
}

.modal-body {
    padding: 20px; /* Space inside the modal body */
    font-size: 1rem; /* Default font size */
}

.modal-footer {
    border-top: 1px solid #dee2e6; /* Light border for footer */
}

.btn-secondary {
    background-color: #6c757d; /* Default secondary button color */
    border-color: #6c757d; /* Border color */
}

.btn-secondary:hover {
    background-color: #5a6268; /* Darker shade on hover */
    border-color: #545b62; /* Darker border on hover */
}

/* Responsive adjustments */
@media (max-width: 576px) {
    .modal-dialog {
        max-width: 90%; /* Modal takes 90% width on small screens */
    }
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

<div class="content">
    <h2> Clients Billing List</h2>
    <hr style="border: none; border-top: 7px solid skyblue; margin: 10px 0;">

   <!-- Search Bar -->
<div style="display: flex; justify-content: flex-end; margin-bottom: 20px; margin-top: 50px;">
    <form method="GET" action="">
        <input type="text" id="searchInput" placeholder="Search..." required onkeyup="searchFunction()">
        <button type="submit">Search</button>
    </form>
</div>
<table id="clientTable">
    <thead>
        <tr>
            <th>ID</th>
            <th>Client Name</th>
            <th>Meter Number</th>
            <th>Status</th>
            <th>Next Billing Reminder</th>
            <th>Tools</th>
        </tr>
    </thead>
    <tbody>
    <?php
    
        // Assume you have your database query here to fetch clients
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($row['client_id']) . "</td>"; // Use htmlspecialchars to prevent XSS
                echo "<td>" . htmlspecialchars($row['client_name']) . "</td>";
                echo "<td>" . htmlspecialchars($row['meter']) . "</td>";
                echo "<td>" . htmlspecialchars($row['status']) . "</td>";
                
                // Check if 'next_payment_date' exists, if not set it to a default value
                echo "<td>" . (isset($row['next_payment_date']) ? htmlspecialchars($row['next_payment_date']) : 'N/A') . "</td>";
                
                echo "<td>
                    <a href='client_bill.php?id=" . htmlspecialchars($row['client_id']) . "'><i class='fas fa-money-bill' title='Client Bill'></i></a>
                    <a href='client_billing_history.php?id=" . htmlspecialchars($row['client_id']) . "'><i class='fas fa-eye' title='Billing History'></i></a>
                    <a href='print_receipt.php?id=" . htmlspecialchars($row['client_id']) . "'><i class='fas fa-print' title='Print Receipt'></i></a>
                </td>";
                echo "</tr>";
            }
        } else {
            echo "<tr><td colspan='6'>No clients found</td></tr>";
        }
        
    ?>
    </tbody>
    </table>
</div>

<div class="modal fade" id="reminderModal" tabindex="-1" role="dialog" aria-labelledby="reminderModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="reminderModalLabel">Billing Reminders</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="reminderContent">
                <!-- Reminder messages will be injected here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
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



<script>

function confirmLogout() {
            if (confirm('Are you sure you want to logout?')) {
                window.location.href = 'logout.php'; // Replace with your actual logout URL
            }
        }
     document.getElementById('settingsForm').addEventListener('submit', function(e) {
        e.preventDefault(); // Prevent default form submission

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
                    setTimeout(closeModal, 2000); // Optionally close the modal after a delay
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

    const reminders = "<?php echo addslashes($reminderMessage); ?>"; // Get reminders from PHP
// Call the function to show reminders
showReminders(reminders);

function showReminders(reminders) {
    console.log(reminders); // Log the reminders to check its content
    if (reminders) {
        document.getElementById('reminderContent').innerHTML = reminders.replace(/\n/g, "<br>");
        $('#reminderModal').modal('show');
    } else {
        console.log("No reminders to display."); // Log if there are no reminders
    }
}

    function searchFunction() {
            // Get the input field and its value
            var input = document.getElementById("searchInput");
            var filter = input.value.toUpperCase();

            // Get the table and rows
            var table = document.getElementById("clientTable");
            var tr = table.getElementsByTagName("tr");

            // Loop through all table rows, and hide those that don't match the search query
            for (var i = 1; i < tr.length; i++) {
                var td = tr[i].getElementsByTagName("td")[1]; // The second column (client name)
                if (td) {
                    var txtValue = td.textContent || td.innerText;
                    if (txtValue.toUpperCase().indexOf(filter) > -1) {
                        tr[i].style.display = "";
                    } else {
                        tr[i].style.display = "none";
                    }
                }
            }
        }
        function searchFunction() {
    // Get the input field and its value
    var input = document.getElementById("searchInput");
    var filter = input.value.toUpperCase();

    // Get the table and rows
    var table = document.getElementById("clientTable");
    var tr = table.getElementsByTagName("tr");

    // Loop through all table rows, and hide those that don't match the search query
    for (var i = 1; i < tr.length; i++) {
        var idCell = tr[i].getElementsByTagName("td")[0]; // First column (ID)
        var nameCell = tr[i].getElementsByTagName("td")[1]; // Second column (Client Name)
        var meterCell = tr[i].getElementsByTagName("td")[2]; // Third column (Meter Number)

        var idValue = idCell ? idCell.textContent || idCell.innerText : '';
        var nameValue = nameCell ? nameCell.textContent || nameCell.innerText : '';
        var meterValue = meterCell ? meterCell.textContent || meterCell.innerText : '';

        // Check if the input matches ID, Client Name, or Meter Number
        if (idValue.toUpperCase().indexOf(filter) > -1 || 
            nameValue.toUpperCase().indexOf(filter) > -1 || 
            meterValue.toUpperCase().indexOf(filter) > -1) {
            tr[i].style.display = ""; // Show row
        } else {
            tr[i].style.display = "none"; // Hide row
        }
    }
}
</script>

</body>
</html>

<?php
// Close the database connection
$conn->close();
?>
