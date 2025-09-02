<?php
include_once 'config.php';

// Check if billing_id is provided
if (isset($_POST['billing_id'])) {
    $billingId = $_POST['billing_id'];

    // Update the status to "paid" for the specified billing_id
    $sql = "UPDATE billing SET status = 'paid' WHERE billing_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $billingId);

    if ($stmt->execute()) {
        echo "success"; // Response for success
    } else {
        echo "error"; // Response for error
    }
} else {
    echo "error"; // Response if billing_id is not set
}
?>
