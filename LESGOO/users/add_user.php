<?php
session_start();
if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $full_name = mysqli_real_escape_string($conn, $_POST['full_name']);
    $role = mysqli_real_escape_string($conn, $_POST['role']);

    // Check if username already exists
    $check = $conn->query("SELECT id FROM users WHERE username = '$username'");
    if ($check->num_rows > 0) {
        header("Location: index.php?error=Username already exists");
        exit();
    }

    $sql = "INSERT INTO users (username, password, full_name, role) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $username, $password, $full_name, $role);
    
    if ($stmt->execute()) {
        header("Location: index.php?success=User added successfully");
    } else {
        header("Location: index.php?error=Failed to add user");
    }
    exit();
} else {
    header("Location: index.php");
    exit();
}