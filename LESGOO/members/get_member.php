<?php
session_start();
require_once '../config/database.php';

if(isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $sql = "SELECT * FROM members WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if($member = $result->fetch_assoc()) {
        echo json_encode($member);
    } else {
        echo json_encode(['error' => 'Member not found']);
    }
} else {
    echo json_encode(['error' => 'No ID provided']);
}