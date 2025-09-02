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
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Prepare the SQL statement
if ($search) {
    $stmt = $conn->prepare("SELECT * FROM clients WHERE 
        client_id LIKE ? OR 
        meter LIKE ? OR 
        client_name LIKE ? OR 
        contact LIKE ?");
    $likeSearch = "%$search%";
    $stmt->bind_param("ssss", $likeSearch, $likeSearch, $likeSearch, $likeSearch);
} else {
    $stmt = $conn->prepare("SELECT * FROM clients");
}

$stmt->execute();
$result = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Clients</title>
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
            margin-top: 60px;
            padding: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align:center;
        }


        th {
            background-color: #f2f2f2;
            color: #333;
        }

        .status {
            color: green;
        }

        .status.disconnected {
            color: red;
        }
/* Adjust add clients button*/
        .add-register-btn {
            background-color: blue;
            color: white;
            border: none;
            border-radius: 5px;
            padding: 10px 20px;
            cursor: pointer;
            text-decoration: none;
            font-size: 16px;
            margin-bottom: 20px;
            display: inline-block;
            margin-left: 10px;
            transition: background-color 0.3s;
        }

        .add-register-btn:hover {
            background-color: #0056b3;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1001;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgb(0,0,0);
            background-color: rgba(0,0,0,0.4);
            padding-top: 60px;
        }

        .modal-content {
            background-color: #fefefe;
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

        .modal input[type=text], .modal input[type=number] {
            width: 100%;
            padding: 10px;
            margin: 5px 0 22px 0;
            display: inline-block;
            border: none;
            background: #f1f1f1;
        }

        .modal input[type=text]:focus, .modal input[type=number]:focus {
            background-color: #ddd;
            outline: none;
        }

        .modal .btn {
            background-color: #04AA6D;
            color: white;
            padding: 10px 20px;
            margin: 10px 0;
            border: none;
            cursor: pointer;
            width: 100%;
            opacity: 0.9;
        }

        .modal .btn:hover {
            opacity: 1;
        }

        /* Style for the search form */
.search-form {
    display: flex;
    justify-content: flex-end; /* Align the search form to the right */
    margin-bottom: 20px;
}

.search-form input[type="text"] {
    padding: 10px;
    border: 1px solid #ccc;
    border-radius: 5px;
    width: 300px;
    margin-right: 10px; /* Add space between input and button */
}

.search-form .btn-search {
    padding: 10px 20px;
    background-color: #007bff;
    color: white;
    border: none;
    border-radius: 5px;
    cursor: pointer;
}

.search-form .btn-search:hover {
    background-color: #0056b3;
}

.disconnected-row {
        background-color: #ffcccc; /* Light red background for disconnected clients */
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
    <h2>Clients List</h2>
    <form method="GET" action="clients.php" class="search-form">
        <input type="text" name="search" placeholder="Search..." style="width: 300px; padding: 10px;">
        <button type="submit" class="btn-search">Search</button>
    </form>
    <button class="add-register-btn" onclick="document.getElementById('addClientModal').style.display='block'">
        <i class="fas fa-user-plus"></i> Add Clients
    </button>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Address</th>
                <th>Contact number</th>
                <th>Date added</th>
                <th>Meter number</th>
                <th>Status</th>
                <th>Tools</th>
            </tr>
        </thead>
        <tbody>
        <?php
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                // Apply the "disconnected-row" class if the client is disconnected
                $rowClass = ($row['status'] == 'Disconnected') ? 'disconnected-row' : '';

                echo "<tr class='$rowClass'>";
                echo "<td>" . htmlspecialchars($row['client_id']) . "</td>";
                echo "<td>" . htmlspecialchars($row['client_name']) . "</td>";
                echo "<td>" . (isset($row['address']) ? htmlspecialchars($row['address']) : 'N/A') . "</td>";
                echo "<td>" . htmlspecialchars($row['contact']) . "</td>";
                echo "<td>" . (isset($row['date_added']) ? htmlspecialchars($row['date_added']) : 'N/A') . "</td>";
                echo "<td>" . htmlspecialchars($row['meter']) . "</td>";
                
                // Display status with checkbox
                echo "<td>
                        <input type='checkbox' class='status-checkbox' data-client-id='" . htmlspecialchars($row['client_id']) . "' " . ($row['status'] == 'Active' ? 'checked' : '') . ">
                        <span class='status " . ($row['status'] == 'Active' ? '' : 'disconnected') . "'>" . htmlspecialchars($row['status']) . "</span>
                      </td>";

                echo "<td>
                        <a href='#' onclick='editClient(" . json_encode($row) . ")' style='text-decoration: none;'><i class='fas fa-edit'></i></a>
                        <a href='#' onclick='viewClientDetails(" . json_encode($row) . ")' style='text-decoration: none;'><i class='fas fa-eye'></i></a>
                      </td>";
                echo "</tr>";
            }
        } else {
            echo "<tr><td colspan='8'>No clients found</td></tr>";
        }
        ?>
        </tbody>
    </table>
</div>

    <!-- Modal for adding clients -->
    <div id="addClientModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="document.getElementById('addClientModal').style.display='none'">&times;</span>
            <h2>Add Client</h2>
            <form method="POST" action="">
                <label for="last_name">Last Name</label>
                <input type="text" id="last_name" name="last_name" required>
                
                <label for="first_name">First Name</label>
                <input type="text" id="first_name" name="first_name" required>
                
                <label for="middle_initial">Middle Initial</label>
                <input type="text" id="middle_initial" name="middle_initial" maxlength="1">

                <label for="address">Address</label>
                <input type="text" id="address" name="address" required>
                
                <label for="contact">Contact Number</label>
                <input type="number" id="contact" name="contact" required>
                
                <label for="meter">Meter Number</label>
                <input type="text" id="meter" name="meter" required>

                <input type="hidden" id="status" name="status" value="Active">
                
                <button type="submit" class="btn">Add Client</button>
            </form>
            <div id="responseMessage" class="response-message"></div>
        </div>
       
    </div>

    <!-- Other modal and script content... -->



    <!-- Modal for editing clients -->
<div id="editClientModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="document.getElementById('editClientModal').style.display='none'">&times;</span>
        <h2>Edit Client</h2>
        <form id="editClientForm" onsubmit="updateClient(event)">
            <input type="hidden" id="edit-id" name="id">
            <label for="edit-name">Name</label>
            <input type="text" id="edit-name" name="name" required>
            <label for="edit-address">Address</label>
            <input type="text" id="edit-address" name="address" required>
            <label for="edit-contact">Contact Number</label>
            <input type="number" id="edit-contact" name="contact" required>
            <label for="edit-meter">Meter Number</label>
            <input type="text" id="edit-meter" name="meter" required>
            <button type="submit" class="btn">Update Client</button>
        </form>
    </div>
</div>


 <!-- Modal for viewing client details -->
<div id="viewClientModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="document.getElementById('viewClientModal').style.display='none'">&times;</span>
        <h2>Client Details</h2>
        <p><strong>ID:</strong> <span id="view-client-id"></span></p>
        <p><strong>Name:</strong> <span id="view-client-name"></span></p>
        <p><strong>Address:</strong> <span id="view-client-address"></span></p>
        <p><strong>Contact Number:</strong> <span id="view-client-contact"></span></p>
        <p><strong>Date Added:</strong> <span id="view-client-date"></span></p>
        <p><strong>Meter Number:</strong> <span id="view-client-meter"></span></p>
        <p><strong>Status:</strong> <span id="view-client-status"></span></p>
    </div>
</div>





    <script>

function confirmLogout() {
            if (confirm('Are you sure you want to logout?')) {
                window.location.href = 'logout.php'; // Replace with your actual logout URL
            }
        }
        // Get the modal
        var addClientModal = document.getElementById('addClientModal');
        var editClientModal = document.getElementById('editClientModal');
        var viewClientModal = document.getElementById('viewClientModal');

        // When the user clicks anywhere outside of the modal, close it
        window.onclick = function(event) {
            if (event.target == addClientModal) {
                addClientModal.style.display = "none";
            } else if (event.target == editClientModal) {
                editClientModal.style.display = "none";
            }
        }

        // Function to open the edit modal and populate it with the selected client's data
        function editClient(client) {
    document.getElementById('edit-id').value = client.client_id; // Make sure this matches your DB structure
    document.getElementById('edit-name').value = client.client_name;
    document.getElementById('edit-address').value = client.address || '';
    document.getElementById('edit-contact').value = client.contact;
    document.getElementById('edit-meter').value = client.meter;
    document.getElementById('editClientModal').style.display = 'block';
}

function updateClient(event) {
    event.preventDefault(); // Prevent default form submission

    const formData = new FormData(document.getElementById('editClientForm'));
    const xhr = new XMLHttpRequest();
    xhr.open('POST', 'edit_client.php', true);

    xhr.onload = function() {
        const responseMessage = document.createElement('div');
        document.querySelector('.content').insertBefore(responseMessage, document.querySelector('table'));
        
        if (xhr.status === 200) {
            const response = JSON.parse(xhr.responseText);
            responseMessage.textContent = response.success ? 'Client updated successfully!' : 'Update failed: ' + response.error;
            responseMessage.style.color = response.success ? 'green' : 'red';
            
            // Refresh the table if the update was successful
            if (response.success) {
                refreshClientTable();
                document.getElementById('editClientModal').style.display = 'none'; // Close the modal
                setTimeout(() => {
                    responseMessage.remove(); // Remove message after a few seconds
                }, 3000); // Adjust time as needed
            }
        } else {
            responseMessage.textContent = 'An error occurred.';
            responseMessage.style.color = 'red';
        }
    };

    xhr.send(formData);
}

function viewClientDetails(client) {
    document.getElementById('view-client-id').textContent = client.client_id;
    document.getElementById('view-client-name').textContent = client.client_name;
    document.getElementById('view-client-address').textContent = client.address || 'N/A';
    document.getElementById('view-client-contact').textContent = client.contact;
    document.getElementById('view-client-date').textContent = client.date_added || 'N/A';
    document.getElementById('view-client-meter').textContent = client.meter;
    document.getElementById('view-client-status').textContent = client.status;

    document.getElementById('viewClientModal').style.display = 'block';
}

// Close the modal when the user clicks anywhere outside of it
window.onclick = function(event) {
    var modal = document.getElementById('viewClientModal');
    if (event.target == modal) {
        modal.style.display = "none";
    }
}


function refreshClientTable() {
    const xhr = new XMLHttpRequest();
    xhr.open('GET', 'fetch_clients.php', true); // Create a new PHP file to fetch updated clients

    xhr.onload = function() {
        if (xhr.status === 200) {
            const data = JSON.parse(xhr.responseText);
            const tbody = document.querySelector('tbody');
            tbody.innerHTML = ''; // Clear existing rows

            data.forEach(client => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${client.client_id}</td>
                    <td>${client.client_name}</td>
                    <td>${client.address || 'N/A'}</td>
                    <td>${client.contact}</td>
                    <td>${client.date_added || 'N/A'}</td>
                    <td>${client.meter}</td>
                    <td>
                        <input type='checkbox' class='status-checkbox' data-client-id='${client.client_id}' ${client.status === 'Active' ? 'checked' : ''}>
                        <span class='${client.status === 'Active' ? 'status' : 'status disconnected'}'>${client.status}</span>
                    </td>
                    <td><a href='#' onclick='editClient(${JSON.stringify(client)})' style='text-decoration: none;'><i class='fas fa-edit'></i></a></td>
                `;
                tbody.appendChild(row);
            });
        }
    };

    xhr.send();
}


        function confirmLogout() {
            if (confirm('Are you sure you want to logout?')) {
                window.location.href = 'logout.php'; // Replace with your actual logout URL
            }
        }
    
    document.querySelectorAll('.status-checkbox').forEach(checkbox => {
    checkbox.addEventListener('change', function() {
        const clientId = this.getAttribute('data-client-id');
        const newStatus = this.checked ? 'Active' : 'Disconnected';
        
        // Make an AJAX request to update the status in the database
        const xhr = new XMLHttpRequest();
        xhr.open('POST', 'update_status.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        
        xhr.onload = function() {
            if (xhr.status === 200) {
                console.log('Status updated successfully');
            } else {
                console.error('Error updating status');
            }
        };
        
        xhr.send('id=' + clientId + '&status=' + newStatus);
    });
});

document.getElementById('addClientModal').querySelector('form').addEventListener('submit', function(event) {
    event.preventDefault(); // Prevent default form submission

    const formData = new FormData(this);
    const xhr = new XMLHttpRequest();
    xhr.open('POST', 'add_client.php', true); // Adjust to your PHP file

    xhr.onload = function() {
        const responseMessage = document.getElementById('responseMessage'); // Reference the message area

        if (xhr.status === 200) {
            const response = JSON.parse(xhr.responseText);
            responseMessage.textContent = response.success ? 'Client added successfully!' : 'Error: ' + response.message;
            responseMessage.style.color = response.success ? 'green' : 'red';

            // Refresh the table if the addition was successful
            if (response.success) {
                refreshClientTable();
                document.getElementById('addClientModal').style.display = 'none'; // Close the modal
            }

            setTimeout(() => {
                responseMessage.textContent = ''; // Clear message after a few seconds
            }, 3000);
        } else {
            responseMessage.textContent = 'An error occurred.';
            responseMessage.style.color = 'red';
        }
    };

    xhr.send(formData);
});

    </script>
</body>
</html>

<?php
// Close the database connection
$conn->close();
?>
