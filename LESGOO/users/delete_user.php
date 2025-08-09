<?php
session_start();
if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}
require_once '../config/database.php';

if (isset($_GET['id'])) {
    $user_id = (int)$_GET['id'];
    
    // Prevent deletion of admin user
    $check = $conn->query("SELECT username FROM users WHERE id = $user_id");
    $user = $check->fetch_assoc();
    if ($user['username'] === 'admin') {
        header("Location: index.php?error=Cannot delete admin user");
        exit();
    }

    $sql = "DELETE FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    
    if ($stmt->execute()) {
        header("Location: index.php?success=User deleted successfully");
    } else {
        header("Location: index.php?error=Failed to delete user");
    }
    exit();
} else {
    header("Location: index.php");
    exit();
}