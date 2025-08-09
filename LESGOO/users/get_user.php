<?php
session_start();
require_once '../config/database.php';

if(isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $sql = "SELECT id, username, full_name, role FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if($user = $result->fetch_assoc()) {
        echo json_encode($user);
    } else {
        echo json_encode(['error' => 'User not found']);
    }
} else {
    echo json_encode(['error' => 'No ID provided']);
}