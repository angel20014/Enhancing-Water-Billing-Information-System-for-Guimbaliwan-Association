<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

include_once 'config.php';

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['change_password'])) {
    // Check if id and new password are provided
    if (!empty($_POST['id']) && !empty($_POST['new_password'])) {
        $id = $_POST['id'];
        $new_password = password_hash($_POST['new_password'], PASSWORD_DEFAULT);

        // Prepare and execute the update query
        $query = "UPDATE staff SET password = ? WHERE staff_id = ?";
        $stmt = $conn->prepare($query);

        if ($stmt) {
            $stmt->bind_param("si", $new_password, $id);

            if ($stmt->execute()) {
                echo "<script>alert('Password changed successfully'); window.location.href='staff.php';</script>";
            } else {
                error_log("Error executing statement: " . $stmt->error); // Log specific error
                echo "<script>alert('Error changing password'); window.location.href='staff.php';</script>";
            }
            $stmt->close();
        } else {
            error_log("Failed to prepare statement: " . $conn->error); // Log if prepare fails
            echo "<script>alert('Error preparing password change.'); window.location.href='staff.php';</script>";
        }
    } else {
        echo "<script>alert('Invalid request.'); window.location.href='staff.php';</script>";
    }
}

$conn->close();
?>
