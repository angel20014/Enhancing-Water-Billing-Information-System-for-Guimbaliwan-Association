<?php
session_start(); // Start the session

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['isAdmin']) && $_POST['isAdmin'] === 'true') {
        $_SESSION['is_admin'] = true; // Set session variable for admin access
        echo "Session set.";
    } else {
        http_response_code(403); // Forbidden
        echo "Access denied.";
    }
} else {
    http_response_code(405); // Method Not Allowed
}
?>
