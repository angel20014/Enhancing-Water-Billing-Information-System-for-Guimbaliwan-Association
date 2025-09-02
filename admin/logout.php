<?php
// Start session (if not already started)
session_start();

// Unset all session variables
$_SESSION = [];

// Destroy the session
session_destroy();

// Redirect to the login page or any desired page after logout
header("Location: login.php"); // Change "login.php" to the actual login page
exit;
?>
