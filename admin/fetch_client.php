<?php
include 'config.php'; // Database connection file

error_reporting(E_ALL);
ini_set('display_errors', 1); // Enable error reporting

$type = $_GET['type'] ?? '';

// Validate the type parameter
if (!in_array($type, ['unpaid', 'overdue', 'disconnected'])) {
    echo json_encode([]);
    exit;
}

$query = '';
switch ($type) {
    case 'unpaid':
        // Fetch details from billing and clients tables for unpaid bills
        $query = "
            SELECT clients.client_id, clients.client_name, clients.address, clients.contact, 
                   billing.billing_id, billing.consumption, billing.bill_amount, 
                   billing.billing_date 
            FROM billing
            JOIN clients ON billing.client_id = clients.client_id
            WHERE LOWER(billing.status) = 'unpaid'
        ";
        break;
    case 'overdue':
        $query = "
            SELECT clients.client_id, clients.client_name, clients.address, clients.contact, 
                   billing.billing_id, billing.previous_reading, 
                   billing.present_reading, billing.cubic_per_meter, 
                   billing.consumption, billing.bill_amount, 
                   billing.billing_date 
            FROM billing
            JOIN clients ON billing.client_id = clients.client_id
            WHERE LOWER(billing.status) = 'overdue'
        ";
        break;
    case 'disconnected':
        // Only fetch client details for disconnected clients
        $query = "
            SELECT clients.client_id, clients.client_name, clients.address, clients.contact 
            FROM clients
            WHERE clients.status = 'Disconnected'
        ";
        break;
}

$result = mysqli_query($conn, $query);

if (!$result) {
    echo json_encode(['error' => 'SQL Error: ' . mysqli_error($conn)]); // Return JSON on error
    exit; // Stop execution if there's an error
}

$data = [];
while ($row = mysqli_fetch_assoc($result)) {
    $data[] = $row; // Populate the data array
}

// Check if there are results to return
header('Content-Type: application/json'); // Set the content type to JSON
echo json_encode($data); // Return the data as JSON
?>
