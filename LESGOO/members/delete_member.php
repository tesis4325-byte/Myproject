<?php
session_start();
if(!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}
require_once '../config/database.php';

if (isset($_GET['id'])) {
    $member_id = (int)$_GET['id'];
    
    // Check if member has active borrowings
    $check = $conn->query("SELECT id FROM borrowings WHERE member_id = $member_id AND status = 'borrowed'");
    if ($check->num_rows > 0) {
        header("Location: index.php?error=Cannot delete member with active borrowings");
        exit();
    }

    $sql = "DELETE FROM members WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $member_id);
    
    if ($stmt->execute()) {
        header("Location: index.php?success=Member deleted successfully");
    } else {
        header("Location: index.php?error=Failed to delete member");
    }
    exit();
} else {
    header("Location: index.php");
    exit();
}