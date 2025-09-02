<?php
// Start the session
session_start();

// Check if the user is an admin
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: ../index.php"); // Redirect to the main login page
    exit; // Ensure no further code is executed
}

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Include database connection file
    include "config.php";

    // Define variables and initialize with empty values
    $username = $password = $contactnumber = $secret_question = $secret_answer = "";
    $error_message = "";

    // Processing form data when form is submitted
    $username = trim($_POST["username"]);
    $password = trim($_POST["password"]);
    $secret_question = trim($_POST["secret_question"]);
    $secret_answer = trim($_POST["secret_answer"]);

    // Set default status as 'active'
    $status = 'active';

    // Validate inputs (add your own validation rules as needed)
    if (empty($username) || empty($password) || empty($secret_question) || empty($secret_answer)) {
        $error_message = "All fields are required.";
    } elseif (strlen($password) < 6) {
        $error_message = "Password must be at least 6 characters long.";
    }

    // Check for duplicate username
    if (empty($error_message)) {
        $check_sql = "SELECT username FROM users WHERE username = ?";
        if ($check_stmt = $conn->prepare($check_sql)) {
            $check_stmt->bind_param("s", $username);
            $check_stmt->execute();
            $check_stmt->store_result();

            if ($check_stmt->num_rows > 0) {
                $error_message = "Username already exists. Please choose another username.";
            }
            $check_stmt->close();
        }
    }

    if (empty($error_message)) {
        // Prepare an insert statement
        $sql = "INSERT INTO users (username, password, secret_question, secret_answer, status) VALUES (?, ?, ?, ?, ?)";

        if ($stmt = $conn->prepare($sql)) {
            // Bind variables to the prepared statement as parameters
            $stmt->bind_param("sssss", $param_username, $param_password, $param_secret_question, $param_secret_answer, $param_status);

            // Set parameters
            $param_username = $username;
            $param_password = password_hash($password, PASSWORD_DEFAULT); // Hash the password before saving in database
            $param_secret_question = $secret_question;
            $param_secret_answer = $secret_answer; // Consider hashing this if security is a concern
            $param_status = $status; // Set default status

            // Attempt to execute the prepared statement
            if ($stmt->execute()) {
                // Set session variable to indicate successful registration
                $_SESSION["registration_success"] = true;

                // Redirect to login page after successful registration
                header("Location: login.php");
                exit(); // Ensure no further code is executed after redirect
            } else {
                echo "Something went wrong. Please try again later.";
            }

            // Close statement
            $stmt->close();
        }
    } else {
        // Display error message
        echo "<p>$error_message</p>";
    }

    // Close connection
    $conn->close();
}
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f3f3f3; /* Fallback background color */
        }

        body::before {
            content: '';
            background-image: url('water.jpg'); /* Change the path to your background image */
            background-size: cover;
            filter: blur(2px); /* Adjust the blur intensity as needed */
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: -1;
        }
        h2{
        text-align: center;
        }

        .title {
            position: absolute;
            top: 60px;
            left: 50%;
            transform: translateX(-50%);
            font-size: 52px;
            color: skyblue;
            text-align: center;
            -webkit-text-stroke: 1px black; /* For an outline effect */
            font-weight: bold;
            font-family: Roboto, Arial, sans-serif; /* Ensure fallback fonts */
            text-shadow: 2px 2px 6px rgba(255, 255, 255, 0.5); /* White shadow */
        }


        .container {
            background-color: rgba(255, 255, 255, 0.9);
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.5);
            width: 400px;
            text-align: left;
            position: absolute;
            top: 65%;
            left: 50%;
            transform: translate(-50%, -50%);
        }

        @media (max-width: 600px) {
            .container {
                max-width: 80%; /* Adjust the width for smaller screens */
            }
        }

        @media (max-width: 400px) {
            .container {
                margin-top: 20px; /* Reduce top margin for smaller screens */
                padding: 10px; /* Adjust padding for smaller screens */
                border-width: 2px; /* Adjust border width for smaller screens */
            }
        }

        input[type="text"],
        input[type="password"],
        input[type="submit"] {
            width: 100%; /* Set width to 100% for all input elements */
            padding: 10px;
            margin: 5px 0;
            border: 1px solid #ccc;
            border-radius: 5px;
            box-sizing: border-box;
            font-size: 20px;
        }

        input[type="submit"] {
            background-color: #4caf50;
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 20px;
        }

        input[type="submit"]:hover {
            background-color: #45a049;
        }


        label {
            font-weight: bold; /* Make labels bold */
            font-size: 20px;
        }

        .login-link {
            text-align: center;
            margin-top: 20px;
        }

        .login-link a {
            color: #007bff;
            text-decoration: none;
        }

        .login-link a:hover {
            text-decoration: underline;
        }

        .button-container {
            position: absolute;
            top: 40px;
            right: 20px;
        }

        .button-container a {
            background-color: #007bff;
            color: white;
            padding: 12px 16px;
            text-decoration: none;
            margin: 0 5px;
            border-radius: 4px;
            transition: background-color 0.3s;
            font-size: 22px;
        }

        .button-container a:hover {
            background-color: #0056b3;
        }

        .close-button {
            position: absolute;
            top: 20px;
            right: 20px;
            width: 30px;
            height: 30px;
            background-color: #ff5f57; /* Red color similar to close button */
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .close-button:before {
            content: '✕'; /* Unicode character for a multiplication sign */
            color: white;
            font-size: 16px;
            line-height: 1;
        }

        .error-message {
    color: red;
    font-size: 14px;
    margin-top: 5px;
}

    </style>
</head>
<body>
  
    <div class="title">Enhancing Water Billing Information System</div>
    <div class="container" id="register-container">
       
        <h2>Admin Sign Up</h2>
        <form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
            <label for="username">Username</label>
            <input type="text" name="username" placeholder="Username"><br>
            <div class="error-message"></div>

            <label for="password">Password</label>
            <input type="password" name="password" placeholder="Password"><br>
           
            <label for="secret_question">Secret Question</label>
            <select name="secret_question" required>
                <option value="">Select a secret question</option>
                <option value="What is your mother's maiden name?">What is your mother's maiden name?</option>
                <option value="What was your first pet's name?">What was your first pet's name?</option>
                <option value="What is your favorite color?">What is your favorite color?</option>
                <!-- Add more secret questions as needed -->
            </select><br>

            <label for="secret_answer">Secret Answer</label>
            <input type="text" name="secret_answer" placeholder="Secret Answer" required><br>

            <!-- Register button -->
            <input type="submit" value="Register">
        </form>
            

        <!-- Login link -->
        <div class="login-link">
            <p class="register-link">Already have an account? <a href="login.php">Please Login</a></p>
        </div>
    </div>

    <script>

document.addEventListener('DOMContentLoaded', function() {
    const usernameInput = document.querySelector('input[name="username"]');
    const errorMessage = document.createElement('div');
    errorMessage.className = 'error-message';
    usernameInput.parentNode.insertBefore(errorMessage, usernameInput.nextSibling);

    usernameInput.addEventListener('input', function() {
        const username = usernameInput.value;

        if (username.length > 0) {
            // Make an AJAX request to check username availability
            fetch('check_username.php?username=' + encodeURIComponent(username))
                .then(response => response.text())
                .then(data => {
                    if (data === 'exists') {
                        errorMessage.textContent = 'Username already exists. Please choose another.';
                    } else {
                        errorMessage.textContent = ''; // Clear the message
                    }
                })
                .catch(err => console.error('Error checking username:', err));
        } else {
            errorMessage.textContent = ''; // Clear message if input is empty
        }
    });
});


        function closeContainer() {
            const container = document.getElementById('register-container');
            container.style.display = 'none';
        }

        function togglePassword() {
            const passwordField = document.getElementById('password');
            const eyeIcon = document.querySelector('.eye-icon');

            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                eyeIcon.classList.remove('fa-eye');
                eyeIcon.classList.add('fa-eye-slash');
            } else {
                passwordField.type = 'password';
                eyeIcon.classList.remove('fa-eye-slash');
                eyeIcon.classList.add('fa-eye');
            }
        }
    </script>
</body>
</html>
