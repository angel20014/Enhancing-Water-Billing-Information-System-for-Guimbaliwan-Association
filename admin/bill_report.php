<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
include_once 'config.php';

$totalAmount = 0;
$result = null; // Initialize the result variable

if (isset($_GET['month'], $_GET['year'], $_GET['staff'])) {
    $month = intval($_GET['month']);
    $year = intval($_GET['year']);
    $staffId = $_GET['staff'];

    // Prepare the SQL query to fetch billing records and join with clients
    $query = "
        SELECT b.*, c.client_name 
        FROM billing b
        JOIN clients c ON b.client_id = c.client_id 
        WHERE MONTH(b.billing_date) = $month 
        AND YEAR(b.billing_date) = $year
    ";

    // Only add the staff filter if a specific staff member is selected
    if ($staffId !== 'all') {
        $query .= " AND b.staff_id = " . intval($staffId);
    }

    $result = mysqli_query($conn, $query);
}
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
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
        .header .logout-icon {
            color: white;
            font-size: 24px;
            margin: 0 20px;
            cursor: pointer;
            text-decoration: none;
            display: flex;
    justify-content: space-between; /* Adjust alignment */
    align-items: center;
            transition: color 0.3s;
        }

        .header .logout-icon:hover {
            color: #cc0000;
        }

        .header .logout-icon i {
            margin-right: 0;
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
    padding: 20px;
    display: flex;
    flex-wrap: wrap;
    justify-content: space-around; /* Changed to space-around for better spacing */
}

.content h1 {
    text-align: center; /* Center the h1 text */
    
}

.content table {
    width: 100%;
    border-collapse: collapse;
}
.content table, .content th, .content td {
    border: 1px solid black;
}
.content th, .content td {
    padding: 10px;
    text-align: center;
}
.content th {
    background-color: #f2f2f2;
}

/* Other styles remain unchanged */


        .search-form {
    display: flex; /* Use Flexbox to align items in a row */
    align-items: center; /* Align items vertically in the center */
    gap: 10px; /* Space between elements */
}

label {
    margin-right: 5px; /* Space between label and select */
}

select {
    padding: 10px;
    border: 1px solid #ccc;
    border-radius: 4px;
    background-color: white;
    color: #333;
    font-size: 20px;
    margin-right: 25px;
    margin-bottom: 100px;
    text-align: center;
}

button {
    padding: 8px 12px; /* Add some padding for the button */
    border: none;
    border-radius: 4px;
    background-color: black; /* Button color */
    color: white; /* Text color */
    cursor: pointer;
    transition: background-color 0.3s ease;
}

button:hover {
    background-color: #45a049; /* Darker shade on hover */
}

.print-btn {
            background-color: blue; /* Button color */
            color: white; /* Text color */
            border: none;
            border-radius: 5px;
            padding: 15px 25px;
            cursor: pointer;
            margin-left: 1070px; /* Space between the button and the select elements */
            transition: background-color 0.3s ease;
            margin-top: 60px;
            margin-bottom: 90px;
            font-size: 22px;
        }
        .print-btn:hover {
            background-color: #45a049; /* Darker shade on hover */
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

</style>
</head>
</body>
<div class="header">
        <h1> Water Billing Information System</h1>
        <a href="#" class="logout-btn" onclick="confirmLogout()"><i class="fas fa-sign-out-alt"></i> Logout</a>

    </div>
    <div class="sidebar">
    <div class="user-info-sidebar">
        <i class="fas fa-user user-icon"></i>
        <span class="user-text">ADMIN</span>
    </div>
        <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
        <a href="staff.php"><i class="fas fa-users"></i> Staff</a>  
        <a href="bill_report.php"><i class="fas fa-file-invoice"></i> Bill Report</a>

        <a href="#" class="admin-settings-icon" onclick="confirmSettings()">
                <i class="fas fa-cogs">Settings</i>
            </a>
</div>

<div class="content">
    <button type="button" class="print-btn" onclick="printReport()"><i class="fas fa-print"></i> Print</button>
    <div style="text-align: center; margin-top: 90px;">
        <h1>Bill Report</h1>
    </div>

    <!-- Search Form for Month, Year, and Staff -->
    <form method="GET" action="bill_report.php">
    <label for="staff">Select Staff:</label>
    <select name="staff" id="staff" required>
        <option value="all">All Staff</option> <!-- All Staff option added -->
        <?php
        // Fetch staff members from the database
        $staffQuery = "SELECT staff_id, full_name FROM staff";
        $staffResult = mysqli_query($conn, $staffQuery);
        while ($staffRow = mysqli_fetch_assoc($staffResult)) {
            echo "<option value='" . $staffRow['staff_id'] . "'>" . htmlspecialchars($staffRow['full_name']) . "</option>";
        }
        ?>
    </select>

    <label for="month">Select Month:</label>
    <select name="month" id="month" required>
        <option value="">--Select Month--</option>
        <?php
        // Generate month options
        for ($m = 1; $m <= 12; $m++) {
            $monthName = date('F', mktime(0, 0, 0, $m, 10));
            echo "<option value='$m'>$monthName</option>";
        }
        ?>
    </select>

    <label for="year">Select Year:</label>
    <select name="year" id="year" required>
        <option value="">--Select Year--</option>
        <?php
        // Generate year options (from 2020 to current year)
        for ($y = 2020; $y <= date('Y'); $y++) {
            echo "<option value='$y'>$y</option>";
        }
        ?>
    </select>

    <button type="submit">OK</button>
</form>


    <table>
        <thead>
            <tr>
                <th>Client Name</th>
                <th>Billing Date</th>
                <th>Amount</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
        <?php
        // Check if the result variable has been defined and contains data
        if ($result && mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($row['client_name']) . "</td>";
                echo "<td>" . date('F Y', strtotime($row['billing_date'])) . "</td>";
                echo "<td>₱" . number_format($row['bill_amount'], 2) . "</td>";
                echo "<td>" . ucfirst($row['status']) . "</td>";
                echo "</tr>";
                $totalAmount += $row['bill_amount'];
            }
        } else {
            echo "<tr><td colspan='4'>No billing records found for the selected staff, month, and year.</td></tr>";
        }
        ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="2" style="text-align: right;"><strong>Total Amount:</strong></td>
                <td colspan="2"><strong>₱<?php echo number_format($totalAmount, 2); ?></strong></td>
            </tr>
        </tfoot>
    </table>
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
    
    function printReport() {
    // Create a new window
    var printWindow = window.open('', '_blank');

    // Add table HTML content to the new window
    printWindow.document.write('<html><head><title>Print Bill Report</title>');
    printWindow.document.write('<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">'); // Include any required CSS
    printWindow.document.write('<style>table { width: 100%; border-collapse: collapse; } th, td { border: 1px solid black; padding: 10px; text-align: center; } th { background-color: #f2f2f2; } h1 { text-align: center; }</style>'); // Add styles
    printWindow.document.write('</head><body>');
    printWindow.document.write('<h1>Monthly Bill Report</h1>'); // Add title
    printWindow.document.write(document.querySelector('.content table').outerHTML); // Get the table HTML
    printWindow.document.write('</body></html>');

    // Close the document and print
    printWindow.document.close();
    printWindow.print();
}

    </script> <!-- Link your JavaScript file here -->
</body>
</htm>
