<?php
include_once 'config.php';

if (isset($_GET['id'])) {
    $client_id = $_GET['id'];

    // Update the status to "Paid"
    $sql = "UPDATE billing SET status = 'Paid' WHERE client_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $client_id);

    if ($stmt->execute()) {
        // Redirect back to billing page after successful update
        header("Location: billing.php");
        exit();
    } else {
        echo "Error updating record: " . $conn->error;
    }
    $stmt->close();
}
$conn->close();
?>
