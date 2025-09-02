<?php
include 'config.php';

$sql = "SELECT * FROM clients";
$result = $conn->query($sql);
$clients = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $clients[] = $row;
    }
}

echo json_encode($clients);


$conn->close();
?>
