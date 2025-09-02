<?php
session_start();
include "config.php"; // Include your database connection

if (isset($_GET['username'])) {
    $username = trim($_GET['username']);

    // Prepare a statement to check for existing username
    $sql = "SELECT * FROM users WHERE username = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            echo 'exists'; // Username already exists
        } else {
            echo 'available'; // Username is available
        }

        $stmt->close();
    }
}

$conn->close();
?>
