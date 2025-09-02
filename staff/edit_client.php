<?php
include 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if ID is provided
    if (isset($_POST['id'])) {
        $client_id = $_POST['id'];
        $client_name = $_POST['name'];
        $address = $_POST['address'];
        $contact = $_POST['contact'];
        $meter = $_POST['meter'];

        // Prepare the SQL update statement
        $sql = "UPDATE clients SET client_name = ?, address = ?, contact = ?, meter = ? WHERE client_id = ?";

        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param('ssssi', $client_name, $address, $contact, $meter, $client_id);

            if ($stmt->execute()) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Update failed']);
            }
            $stmt->close();
        } else {
            echo json_encode(['success' => false, 'error' => 'Database error']);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'No ID provided']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
}

$conn->close();
?>
