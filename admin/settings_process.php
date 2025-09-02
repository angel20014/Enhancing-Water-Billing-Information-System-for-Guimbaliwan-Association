<?php
include 'config.php';

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get form data
    $currentUsername = trim($_POST['currentUsername']);
    $currentPassword = $_POST['currentPassword'];
    $newUsername = trim($_POST['newUsername']);
    $newPassword = $_POST['newPassword'];
    $confirmPassword = $_POST['confirmPassword'];

    // Validate inputs
    if (empty($currentUsername) || empty($currentPassword) || empty($newUsername) || empty($newPassword) || empty($confirmPassword)) {
        $response['message'] = 'All fields are required.';
        echo json_encode($response);
        exit();
    }

    if ($newPassword !== $confirmPassword) {
        $response['message'] = 'Passwords do not match.';
        echo json_encode($response);
        exit();
    }

    // Check current username and password
    $stmt = $conn->prepare("SELECT password FROM users WHERE username = ?");
    $stmt->bind_param("s", $currentUsername);
    $stmt->execute();
    $stmt->bind_result($storedPassword);
    $stmt->fetch();
    $stmt->close();

    if (!$storedPassword || !password_verify($currentPassword, $storedPassword)) {
        $response['message'] = 'Current username or password is incorrect.';
        echo json_encode($response);
        exit();
    }

    // Check if the new username already exists
    $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
    $stmt->bind_param("s", $newUsername);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();

    if ($count > 0) {
        $response['message'] = 'New username is already taken.';
        echo json_encode($response);
        exit();
    }

    // Update username and password
    $newPasswordHash = password_hash($newPassword, PASSWORD_BCRYPT);
    
    $stmt = $conn->prepare("UPDATE users SET username = ?, password = ? WHERE username = ?");
    $stmt->bind_param("sss", $newUsername, $newPasswordHash, $currentUsername);
    
    if ($stmt->execute()) {
        $response['success'] = true;
        $response['message'] = 'Settings updated successfully.';
    } else {
        $response['message'] = 'Error updating settings: ' . $stmt->error; // Include the error for debugging
    }
    
    $stmt->close();
}

$conn->close();
echo json_encode($response);
?>
