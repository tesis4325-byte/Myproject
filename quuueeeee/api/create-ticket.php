<?php
// Database connection
$host = "localhost";
$username = "root";
$password = "";
$database = "quuueeeee";

// Connect to database
$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    die(json_encode(['error' => "Connection failed: " . $conn->connect_error]));
}

// Get the last ticket number from the database
$sql = "SELECT ticket_number FROM queue WHERE ticket_number LIKE 'C%' ORDER BY id DESC LIMIT 1";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $lastTicket = $row['ticket_number'];
    $number = intval(substr($lastTicket, 1)) + 1;
} else {
    $number = 1;
}

// Create new ticket number (C1, C2, C3, etc.)
$ticketNumber = 'C' . $number;

try {
    // Insert new ticket into database
    $sql = "INSERT INTO queue (ticket_number, status) VALUES (?, 'waiting')";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $ticketNumber);
    
    if ($stmt->execute()) {
        echo json_encode(['ticket' => $ticketNumber]);
    } else {
        throw new Exception("Error inserting ticket");
    }
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}

$conn->close();
?>