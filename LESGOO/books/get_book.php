<?php
session_start();
require_once '../config/database.php';

if(isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $sql = "SELECT * FROM books WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if($book = $result->fetch_assoc()) {
        echo json_encode($book);
    } else {
        echo json_encode(['error' => 'Book not found']);
    }
} else {
    echo json_encode(['error' => 'No ID provided']);
}