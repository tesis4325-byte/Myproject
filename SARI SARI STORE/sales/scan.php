<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';
require_login();

header('Content-Type: application/json');

// Check if barcode parameter exists
if(!isset($_GET['barcode']) || empty($_GET['barcode'])) {
    echo json_encode(['success' => false, 'message' => 'No barcode provided']);
    exit;
}

$barcode = $_GET['barcode'];

// Search for product by barcode
$sql = "SELECT id FROM products WHERE barcode = ?";
if($stmt = $mysqli->prepare($sql)) {
    $stmt->bind_param("s", $barcode);
    
    if($stmt->execute()) {
        $result = $stmt->get_result();
        
        if($result->num_rows == 1) {
            $row = $result->fetch_assoc();
            echo json_encode(['success' => true, 'product_id' => $row['id']]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Product not found']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
    
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}

$mysqli->close();
?>