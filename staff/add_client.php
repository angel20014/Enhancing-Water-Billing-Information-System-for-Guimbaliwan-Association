<?php
include_once 'config.php';

$response = ['success' => false];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get the input values
    $last_name = $_POST['last_name'];
    $first_name = $_POST['first_name'];
    $middle_initial = $_POST['middle_initial'];

    // Create the full name
    $full_name = trim($last_name) . ', ' . trim($first_name) . ' ' . (trim($middle_initial) ? strtoupper($middle_initial) . '.' : '');
    $address = $_POST['address'];
    $contact = $_POST['contact'];
    $meter = $_POST['meter'];
    $status = $_POST['status'];

    // Validate contact number (should be exactly 11 digits)
    if (!preg_match('/^\d{11}$/', $contact)) {
        $response['message'] = "Invalid contact number format. It must be exactly 11 digits.";
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }

    // Check if the meter number already exists
    $meterStmt = $conn->prepare("SELECT COUNT(*) FROM clients WHERE meter = ?");
    $meterStmt->bind_param("s", $meter);
    $meterStmt->execute();
    $meterStmt->bind_result($meterCount);
    $meterStmt->fetch();
    $meterStmt->close();

    if ($meterCount > 0) {
        $response['message'] = "The meter number already exists for another client.";
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }

    // Get the current date
    $date = date("Y-m-d");

    // Prepare the SQL statement for insertion
    $stmt = $conn->prepare("INSERT INTO clients (client_name, address, contact, meter, status, date_added) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssss", $full_name, $address, $contact, $meter, $status, $date);

    // Execute the statement
    if ($stmt->execute()) {
        $response['success'] = true;
        $response['message'] = "New client added successfully";
    } else {
        $response['message'] = "Error: " . $stmt->error;
    }

    // Close the statement
    $stmt->close();
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);

$conn->close();
?>
