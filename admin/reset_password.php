<?php
session_start();
include('config.php'); // Include your database connection

// Check if the session variable for the username is set
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Retrieve the stored username from the session
    $username = $_SESSION['username'];

    // Check if the new password and confirm password match
    if ($new_password === $confirm_password) {
        // Hash the new password for security
        $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);

        // Prepare SQL statement to update the password
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE username = ?");
        if ($stmt) {
            $stmt->bind_param('ss', $hashed_password, $username);

            if ($stmt->execute()) {
                // Password reset successful, redirect to login page
                header("Location: login.php");
                exit();
            } else {
                $message = "Error updating password. Please try again.";
                error_log("Error updating password for user $username: " . $stmt->error);
            }

            $stmt->close();
        } else {
            $message = "Failed to prepare statement.";
            error_log("Failed to prepare statement for password update.");
        }
    } else {
        $message = "Passwords do not match. Please try again.";
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reset Password</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            font-family: Roboto, Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
            height: 100vh;
            position: relative;
            overflow: hidden;
        }

        body::before {
            content: '';
            background-image: url('water.jpg'); /* Adjust this path */
            background-size: cover;
            background-position: center;
            filter: blur(3px);
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: -1;
        }

        .title {
            position: absolute;
            top: 40px;
            left: 50%;
            transform: translateX(-50%);
            text-align: center;
            z-index: 1;
        }

        .title-main {
            font-size: 52px;
            color: blue;
            font-weight: bold;
            font-family: Roboto, Arial, sans-serif;
            -webkit-text-stroke: 1px black;
            text-shadow: 2px 2px 6px rgba(255, 255, 255, 0.5);
        }

        .title-sub {
            font-size: 32px;
            color: blue;
            margin-top: 10px;
        }

        .wrapper {
            background-color: rgba(255, 255, 255, 0.9);
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.6);
            width: 400px;
            text-align: center;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 1;
        }

        h2 {
            margin-top: 0;
            color: #333;
        }

        p {
            color: #888;
            text-align: center;
        }

        form {
            margin-top: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #555;
        }

        input[type="password"], input[type="submit"] {
            width: 100%;
            padding: 10px;
            border-radius: 4px;
            font-size: 16px;
            box-sizing: border-box;
        }

        input[type="password"] {
            border: 1px solid #ccc;
            margin-bottom: 20px;
        }

        input[type="submit"] {
            border: none;
            background-color: #007bff;
            color: #fff;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        input[type="submit"]:hover {
            background-color: #0056b3;
        }

        .error {
            color: red;
        }
    </style>
</head>
<body>
    <div class="title">
        <div class="title-main">Guimbaliwan Association</div>
        <div class="title-sub">Water Billing Information System</div>
    </div>

    <div class="wrapper">
        <h2>Reset Password</h2>
        <p>Please enter your new password below.</p>
        <form method="post" action="">
            <div>
                <label>New Password</label>
                <input type="password" name="new_password" placeholder="Enter your new password" required>
            </div>
            <div>
                <label>Confirm Password</label>
                <input type="password" name="confirm_password" placeholder="Confirm your new password" required>
            </div>
            <div>
                <input type="submit" value="Reset Password">
            </div>
        </form>
        <p class="error"><?php echo htmlspecialchars($message); ?></p>
    </div>
</body>
</html>
