<?php
include_once 'config.php';

if (isset($_GET['id'])) {
    $clientId = $_GET['id'];
    // Fetch billing details for the client
    $billingSql = "SELECT * FROM billing WHERE client_id = $clientId ORDER BY billing_date DESC LIMIT 1";
    $billingResult = $conn->query($billingSql);

    if ($billingResult && $billingResult->num_rows > 0) {
        $billing = $billingResult->fetch_assoc();
        // Fetch client details
        $clientSql = "SELECT * FROM clients WHERE client_id = $clientId";
        $clientResult = $conn->query($clientSql);
        $client = $clientResult->fetch_assoc();
    } else {
        die("No billing record found.");
    }
} else {
    die("No client ID provided.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Receipt for <?php echo htmlspecialchars($client['client_name']); ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
        }
        .receipt {
            width: 30%;
            margin: 20px auto;
            border: 1px solid #000;
            padding: 20px;
        }
        .header {
    display: flex;
    align-items: center; /* Align items vertically centered */
    text-align: left; /* Align text to the left */
}

.header img {
    width: 100px; /* Adjust logo size */
    height: auto;
    margin-right: 20px; /* Space between logo and title */
}

.header-text {
    display: flex;
    flex-direction: column; /* Stack title and address vertically */
}

.address {
    margin-top: 0.5em; /* Space between title and address */
    font-style: italic; /* Italicize the address */
}

.association-title {
    font-size: 18px;
    font-weight: bold;
    line-height: 1; /* Adjust line spacing to 1.0 */
    text-align: center;
}



        h5, h2 {
            text-align: left;
        }
        .details {
            display: flex;
            justify-content: space-between;
            width: 100%;
            margin: 20px 0;
        }
        .details div {
            width: 45%; /* Adjust width for two columns */
        }
        .details p {
            margin: 5px 0; /* Spacing between items */
        }
        .button {
            margin: 20px auto;
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            border: none;
            cursor: pointer;
            display: block;
        }
        .association-title {
            font-size: 18px;
            font-weight: bold;
           
            text-align: center;
        }
        .details {
            display: flex;
            justify-content: space-between;
            width: 100%;
            margin: 20px 0;
        }
        .details div {
            width: 45%; /* Adjust width for two columns */
        }
        .details p {
            margin: 5px 0; /* Spacing between items */
        }
        .address {
            text-align: center;
            margin-top: 10px;
            font-style: italic;
        }
        .button {
            margin: 20px auto;
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            border: none;
            cursor: pointer;
            display: block;
        }
    </style>
</head>
<body>
<div class="receipt">
<div class="header">
    <img src="logo.png" alt="Company Logo"> <!-- Replace with your logo path -->
    <div class="header-text">
        <div class="association-title">GUIMBALIWAN ASSOCIATION Water Billing</div>
        <div class="address">
            <p>Can-asujan, Carcar City, Cebu, 6019</p> <!-- Replace with actual address -->
        </div>
    </div>
</div>

        <h2>Client Name: <?php echo htmlspecialchars($client['client_name']); ?></h2>
        <h2>Client ID: <?php echo htmlspecialchars($clientId); ?></h2>
        
        <div class="details">
            <div>
                <p><strong>Previous Reading:</strong></p>
                <p><strong>Present Reading:</strong></p>
                <p><strong>Consumption:</strong></p>
            </div>
            <div>
                <p><?php echo htmlspecialchars($billing['previous_reading']); ?></p>
                <p><?php echo htmlspecialchars($billing['present_reading']); ?></p>
                <p><?php echo htmlspecialchars($billing['consumption']); ?> m³</p>
            </div>
        </div>
        <div class="details">
            <div>
                <p><strong>Cubic per Meter:</strong></p>
                <p><strong>Bill Amount:</strong></p>
                <p><strong>Billing Date:</strong></p>
            </div>
            <div>
                <p>PHP <?php echo htmlspecialchars($billing['cubic_per_meter']); ?></p>
                <p>PHP <?php echo htmlspecialchars($billing['bill_amount']); ?></p>
                <p><?php echo date('F j, Y', strtotime($billing['billing_date'])); ?></p>
            </div>
        </div>
        
        
        
        <button class="button" onclick="window.print()">Print Receipt</button>
    </div>
</body>
</html>

<?php
$conn->close();
?>
