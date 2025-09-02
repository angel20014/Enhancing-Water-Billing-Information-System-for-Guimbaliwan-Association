<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
include_once 'config.php';

$staffMessage = ''; // Initialize the message variable

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_staff'])) {
        $fullName = $_POST['full_name'];
        $username = $_POST['username'];
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $contactNumber = $_POST['contact_number']; // New contact number field
        $dateAdded = date("Y-m-d H:i:s");

        // Check if username or full name already exists
        $checkSql = "SELECT * FROM staff WHERE LOWER(username) = LOWER(?) OR LOWER(full_name) = LOWER(?)";
        $stmt = $conn->prepare($checkSql);
        $stmt->bind_param("ss", $username, $fullName);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($existingUser = $result->fetch_assoc()) {
            if (strtolower($existingUser['username']) === strtolower($username)) {
                $staffMessage .= "Username '{$username}' already exists. ";
            }
            if (strtolower($existingUser['full_name']) === strtolower($fullName)) {
                $staffMessage .= "Full name '{$fullName}' already exists.";
            }
        }

        if (empty($staffMessage)) {
            // Insert new staff with contact number
            $sql = "INSERT INTO staff (full_name, username, password, contact_number, date_added) VALUES (?, ?, ?, ?, ?)";
            $insertStmt = $conn->prepare($sql);
            $insertStmt->bind_param("sssss", $fullName, $username, $password, $contactNumber, $dateAdded);
            
            if ($insertStmt->execute()) {
                $staffMessage = 'New staff member added successfully.';
            } else {
                $staffMessage = 'Error: ' . $conn->error;
            }
        }
    }


    // Handle Delete functionality
    if (isset($_POST['delete_staff'])) {
        $staffId = $_POST['staff_id'];
        $deleteSql = "DELETE FROM staff WHERE staff_id = ?";
        $deleteStmt = $conn->prepare($deleteSql);
        $deleteStmt->bind_param("i", $staffId);
        
        if ($deleteStmt->execute() && $deleteStmt->affected_rows > 0) {
            $staffMessage = "Staff ID {$staffId} deleted successfully.";
        } else {
            $staffMessage = "Failed to delete Staff ID {$staffId}.";
        }
        $deleteStmt->close();
    }
}

// Fetch search input if it exists
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Base SQL query
$sql = "SELECT staff_id, full_name, username, password, date_added FROM staff";

// Modify SQL if there's a search query
if ($search) {
    $sql .= " WHERE staff_id LIKE ? OR full_name LIKE ?";
    $stmt = $conn->prepare($sql);
    $searchParam = "%" . $search . "%"; // Use wildcards for partial matching
    $stmt->bind_param("ss", $searchParam, $searchParam);
} else {
    $stmt = $conn->prepare($sql);
}

// Execute the statement
$stmt->execute();
$result = $stmt->get_result();
$stmt->close();
?>




<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Staff</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

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


        /* Styles for the staff table */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        table, th, td {
            border: 1px solid black;
        }

        th, td {
            padding: 10px;
            text-align: center;
        }

        th {
            background-color: #0099cc;
            color: white;
        }

        .add-staff-btn {
            background-color: #4CAF50;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            margin-top: 10px;
            align-self: flex-end;
            margin-left: 1120px;
        }

         /* Modal styling */
         .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }
        .modal-content {
            background-color: white;
            padding: 20px;
            border-radius: 5px;
            width: 400px;
            text-align: center;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }
        .close-btn {
            color: red;
            float: right;
            font-size: 20px;
            cursor: pointer;
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

#staffMessage {
    color: red; /* Default color for error messages */
    margin-top: 10px;
    padding: 10px;
    border: 1px solid red;
    border-radius: 5px;
    background-color: rgba(255, 0, 0, 0.1);
    display: none; /* Hidden by default */
}

.staff-header {
    display: flex;
    justify-content: space-between; /* Space between title and search bar */
    align-items: center; /* Center vertically */
    margin-bottom: 20px; /* Space below the header */
}

.staff-controls {
    margin-left: auto; /* Pushes the search bar to the right */
}

#staffSearch {
    width: 300px; /* Width of the search bar */
    padding: 10px; /* Padding inside the input */
    border: 1px solid #ccc; /* Border styling */
    border-radius: 5px; /* Rounded corners */
    font-size: 16px; /* Font size */
    margin-left: 800px;
}




    </style>
</head>
<body>
<div class="header">
    <h1>Water Billing Information System</h1>
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
        <i class="fas fa-cogs"></i> Settings
    </a>
</div>

<div class="content">
<div class="staff-header">
        <h2>Staff List</h2>
        <div class="staff-controls">
            <input type="text" id="staffSearch" placeholder="Search by ID or Name" onkeyup="searchStaff()">
        </div>
    </div>

    <!-- Add Staff Button -->
    <button class="add-staff-btn" onclick="openAddStaffModal()">Add Staff</button>

    

<!-- Staff List Table -->
<table>
    <thead>
        <tr>
            <th>Staff ID</th>
            <th>Full Name</th>
            <th>Contact Number</th>
            <th>Username</th>
            <th>Password</th>
            <th>Date Added</th> <!-- New column for Date Added -->
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php
        $sql = "SELECT staff_id, full_name, password, contact_number, username, password, date_added FROM staff"; // Include date_added in the query
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                echo "<tr>
                        <td>{$row['staff_id']}</td>
                        <td>{$row['full_name']}</td>
                         <td>{$row['contact_number']}</td> 
                        <td>{$row['username']}</td>
                        <td>******</td>
                        <td>{$row['date_added']}</td> <!-- Display date added -->
                        <td>
                            <button onclick='openEditStaffModal({$row['staff_id']}, \"{$row['full_name']}\", \"{$row['username']}\")'><i class='fas fa-edit'></i> Edit</button>
                            <button onclick='openChangePasswordModal({$row['staff_id']})'><i class='fas fa-key'></i> Change Password</button>
                        </td>
                      </tr>";
            }
        } else {
            echo "<tr><td colspan='6'>No staff found</td></tr>"; // Updated colspan to 6
        }
        ?>
    </tbody>
</table>

   <!-- Add Staff Modal -->
   <div class="modal" id="addStaffModal">
    <div class="modal-content">
        <span class="close-btn" onclick="closeAddStaffModal()">&times;</span>
        <h3>Add New Staff</h3>
        <form method="POST" action="">
            <label>Full Name:</label>
            <input type="text" name="full_name" required><br><br>

            <label>Contact Number:</label>
            <input type="text" name="contact_number" required><br><br>

            <label>Username:</label>
            <input type="text" name="username" required><br><br>

            <label>Password:</label>
            <input type="password" name="password" required><br><br>

            <button type="submit" name="add_staff">Add Staff</button>
        </form>
        <div id="staffMessage" style="color: red; margin-top: 10px; display: <?= !empty($staffMessage) ? 'block' : 'none'; ?>;">
            <?= htmlspecialchars($staffMessage); ?>
        </div>
    </div>
</div>




    <!-- Add/Edit/Change Password Modals -->
<!-- Edit Staff Modal -->
<div class="modal" id="editStaffModal">
    <div class="modal-content">
        <span class="close-btn" onclick="closeEditStaffModal()">&times;</span>
        <h3>Edit Staff</h3>
        <form method="POST" action="edit_staff.php">
            <input type="hidden" id="edit_staff_id" name="id">

            <label>Full Name:</label>
            <input type="text" id="edit_full_name" name="full_name" required><br><br>

            <label>Contact Number:</label>
            <input type="text" id="edit_contact_number" name="contact_number" required><br><br>

            <label>Username:</label>
            <input type="text" id="edit_username" name="username" required><br><br>

            <button type="submit" name="update_staff">Update Staff</button>
        </form>
    </div>
</div>

<!-- Change Password Modal -->
<div class="modal" id="changePasswordModal">
    <div class="modal-content">
        <span class="close-btn" onclick="closeChangePasswordModal()">&times;</span>
        <h3>Change Password</h3>
        <form method="POST" action="change_password.php">
            <input type="hidden" id="change_password_id" name="id">
            <label>New Password:</label>
            <input type="password" name="new_password" required><br><br>
            <button type="submit" name="change_password">Change Password</button>
        </form>
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

<!-- JavaScript to handle modal operations -->
<script>
function openAddStaffModal() {
    console.log("Opening Add Staff Modal");
    document.getElementById("addStaffModal").style.display = "flex";
}

function closeAddStaffModal() {
    document.getElementById("addStaffModal").style.display = "none";
}

document.querySelector("form").addEventListener("submit", function(event) {
    if (!confirm("Are you sure you want to add this staff member?")) {
        event.preventDefault(); // Prevent form submission
    }
});

function openEditStaffModal(id, fullName, username) {
    document.getElementById("editStaffModal").style.display = "flex";
    document.getElementById("edit_staff_id").value = id;
    document.getElementById("edit_full_name").value = fullName;
    document.getElementById("edit_contact_number").value = contactNumber;
    document.getElementById("edit_username").value = username;
}

function closeEditStaffModal() {
    document.getElementById("editStaffModal").style.display = "none";
}

function openChangePasswordModal(id) {
    document.getElementById("changePasswordModal").style.display = "flex";
    document.getElementById("change_password_id").value = id;
}

function closeChangePasswordModal() {
    document.getElementById("changePasswordModal").style.display = "none";
}

function confirmLogout() {
    if (confirm("Are you sure you want to log out?")) {
        window.location.href = "logout.php";
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
    
    function searchStaff() {
    const input = document.getElementById('staffSearch');
    const filter = input.value.toLowerCase();
    const table = document.querySelector('table');
    const rows = table.getElementsByTagName('tr');

    // Loop through all table rows (except the header row)
    for (let i = 1; i < rows.length; i++) {
        const cells = rows[i].getElementsByTagName('td');
        let found = false;

        // Check each cell in the row
        for (let j = 0; j < cells.length; j++) {
            if (cells[j]) {
                const textValue = cells[j].textContent || cells[j].innerText;
                // If the text matches the search input, mark as found
                if (textValue.toLowerCase().indexOf(filter) > -1) {
                    found = true;
                    break;
                }
            }
        }

        // Show or hide the row based on the search
        if (found) {
            rows[i].style.display = '';
        } else {
            rows[i].style.display = 'none';
        }
    }
}



</script>

</body>
</html>