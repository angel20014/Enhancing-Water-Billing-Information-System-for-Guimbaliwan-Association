<?php
            session_start();
            include('config.php'); // Include your database connection

            $message = '';

            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $username = $_POST['username']; // Change to a unique identifier like username or email

                // Check if the username exists in the database
                $sql = "SELECT * FROM users WHERE username = '$username'"; // Change the column to match your database structure
                $result = $conn->query($sql);

                if ($result->num_rows > 0) {
                    // If the username exists, fetch the secret question
                    $user = $result->fetch_assoc();
                    $_SESSION['username'] = $username; // Store username in session
                    $_SESSION['secret_question'] = $user['secret_question']; // Store secret question in session

                    header("Location: answer_secret_question.php"); // Redirect to answer secret question page
                    exit();
                } else {
                    $message = "Username not found."; // Adjust message accordingly
                }
            }
            $conn->close();
            ?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Forgot Password</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-image: url('water.jpg'); /* Add your background image URL here */
            background-size: cover;
            background-repeat: no-repeat;
            background-position: center;
        }
        .wrapper {
            width: 400px;
            margin: 100px auto;
            background-color: rgba(255, 255, 255, 0.9); /* Update background color with opacity */
            padding: 20px;
            margin-top: 200px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
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

        h2 {
            margin-top: 0;
            text-align: center;
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
        input[type="tel"] {
            width: 100%;
            padding: 10px;
            margin-bottom: 20px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
            font-size: 16px;
        }
        input[type="submit"] {
            width: 100%;
            padding: 10px;
            background-color: #007bff;
            color: #fff;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s;
        }
        input[type="submit"]:hover {
            background-color: #0056b3;
        }
        .error {
            color: red;
        }
        .back-btn {
            display: block;
            margin-top: 20px;
            text-align: center;
        }
    </style>
</head>
<body>
    

<div class="title">Enhancing Water Billing Information System</div>

    <div class="wrapper">
        <h2>Forgot Password</h2>
        
        <p>Please enter your username to reset your password.</p>
                    <form action="" method="post">
                        <div>
                            <label>Username</label> <!-- Changed from Contact Number -->
                            <input type="text" name="username" placeholder="Enter your username" required>
                        </div>
                        <div>
                            <input type="submit" value="Next">
                        </div>
                    </form>
                    <p class="error"><?php echo $message; ?></p>
                </div>
            </body>
            </html>
