<?php
session_start();

// Check if OTP verification is successful
if (!isset($_SESSION['otp_verified'])) {
    header("Location: forgot_password.php");
    exit();
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve password and confirm password from the form
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Check if passwords match
    if ($password === $confirm_password) {
        // Hash the password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Assuming you have established a database connection
        include('config.php'); 
        
        // Update password in the database
        $sql = "UPDATE users SET Password = '$hashed_password' WHERE ContactNumber = '{$_SESSION['contact_number']}'";
        if ($conn->query($sql) === TRUE) {
            // Password updated successfully
            // Unset OTP verification session variable
            unset($_SESSION['otp_verified']);
            // Redirect user to a relevant page
            header("Location: login.php"); // Redirect to login page, for example
            exit();
        } else {
            // Error updating password
            $message = "Error updating password: " . $conn->error;
        }

        // Close database connection
        $conn->close();
    } else {
        $message = "Passwords do not match. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create New Password</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: black;
            margin: 0;
            padding: 0;
        }
        .wrapper {
            max-width: 400px;
            margin: 50px auto;
            background-color: #fff;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        h2 {
            text-align: center;
        }
        form {
            margin-top: 20px;
        }
        label {
            display: block;
            margin-bottom: 5px;
        }
        input[type="password"] {
            width: 100%;
            padding: 10px;
            margin-bottom: 10px;
            border: 1px solid #ccc;
            border-radius: 3px;
            box-sizing: border-box;
        }
        input[type="submit"] {
            width: 100%;
            padding: 10px;
            background-color: #007bff;
            color: #fff;
            border: none;
            border-radius: 3px;
            cursor: pointer;
        }
        input[type="submit"]:hover {
            background-color: #0056b3;
        }
        .message {
            margin-top: 15px;
            text-align: center;
        }
        .message.error {
            color: red;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <h2>Create New Password</h2>
        <p>Please enter your new password:</p>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div>
                <label>Password</label>
                <input type="password" name="password" placeholder="Enter your password" required>
            </div>
            <div>
                <label>Confirm Password</label>
                <input type="password" name="confirm_password" placeholder="Confirm your password" required>
            </div>
            <div>
                <input type="submit" value="Update Password">
            </div>
        </form>
        <p class="message"><?php echo $message; ?></p>
    </div>
</body>
</html>
