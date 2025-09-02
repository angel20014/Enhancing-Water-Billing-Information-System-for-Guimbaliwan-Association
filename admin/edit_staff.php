<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

include_once 'config.php';

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['update_staff'])) {
    $id = $_POST['id'];
    $full_name = $_POST['full_name'];
    $contactNumber = $_POST['contact_number']; // New contact number
    $username = $_POST['username'];

    // Prepare and execute the update query
    $query = "UPDATE staff SET full_name = ?,  contact_number = ?, username = ? WHERE staff_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sssi", $full_name,  $contactNumber, $username, $id);

    if ($stmt->execute()) {
        echo "<script>alert('Staff updated successfully'); window.location.href='staff.php';</script>";
    } else {
        echo "<script>alert('Error updating staff'); window.location.href='staff.php';</script>";
    }
}
?>
