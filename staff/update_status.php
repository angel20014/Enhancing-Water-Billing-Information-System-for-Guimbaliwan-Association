<?php
include_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id']) && isset($_POST['status'])) {
    $client_id = intval($_POST['id']);
    $status = $_POST['status'] === 'Active' ? 'Active' : 'Disconnected';

    $sql = "UPDATE clients SET status = ? WHERE client_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $status, $client_id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => $conn->error]);
    }

    $stmt->close();
}

$conn->close();
?>
