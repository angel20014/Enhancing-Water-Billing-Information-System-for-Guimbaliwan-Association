<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Page</title>
    <link rel="stylesheet" href="styles.css"> <!-- Link your CSS file -->
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh; /* Full height of the viewport */
            margin: 0;
            background-color: #f5f5f5; /* Light background color */
            font-family: Arial, sans-serif; /* Font style */
        }

        .login-container {
            text-align: center; /* Center text in the container */
            background-color: white; /* Background color for the container */
            padding: 40px; /* Adjusted padding */
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1); /* Subtle shadow */
        }

        .button-container {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 80px; /* Adjust margin for better spacing */
        }

        .button {
            padding: 15px 25px; /* Adjusted padding */
            font-size: 16px;
            cursor: pointer;
            border: none;
            border-radius: 5px;
            background-color: #007BFF;
            color: white;
            text-decoration: none; /* Remove underline from links */
            transition: background-color 0.3s;
        }

        .button:hover {
            background-color: #0056b3;
        }

        .logo {
            width: 200px; /* Adjust the width as needed */
            height: 200px; /* Ensure height is the same as width for a perfect circle */
            border-radius: 50%; /* This creates the circular shape */
            object-fit: cover; /* Ensures the image covers the entire circle without distortion */
            overflow: hidden; /* Prevents any overflow */
        }
    </style>
</head>
<body>
    <div class="login-container">
        <img src="admin/logo.png" alt="Logo" class="logo">
        <h4>GUIMBALIWAN ASSOCIATION</h4>
        <h2>Water Billing Information System</h2>

        <div class="button-container">
        <a href="#" class="button" onclick="confirmAdminLogin(event)">Admin Login</a>
            <a href="staff/login.php" class="button">Staff Login</a>
        </div>
    </div>

    <script>

function confirmAdminLogin(event) {
// Prevent the default action of the link
event.preventDefault();

// Ask for confirmation
var isAdmin = confirm("Are you an admin?");

// If confirmed, prompt for PIN
if (isAdmin) {
var pin = prompt("Please enter the admin PIN:");

// Check if the PIN matches
var correctPin = "1234"; // Example PIN
if (pin === correctPin) {
    // Send an AJAX request to set the session variable (using a PHP script)
    var xhr = new XMLHttpRequest();
    xhr.open("POST", "set_admin_session.php", true);
    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
    xhr.onreadystatechange = function () {
        if (xhr.readyState === XMLHttpRequest.DONE) {
            if (xhr.status === 200) {
                window.location.href = 'admin/login.php'; // Redirect to admin login page
            } else {
                alert("Error setting session. Access denied.");
            }
        }
    };
    xhr.send("isAdmin=true"); // Send admin session variable
} else {
    alert("Incorrect PIN. Access denied.");
}
}
}
</script>
</body>
</html>
