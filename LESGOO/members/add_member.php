<?php
session_start();
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $member_id = mysqli_real_escape_string($conn, $_POST['member_id']);
    $full_name = mysqli_real_escape_string($conn, $_POST['full_name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);

    // Check if Member ID already exists
    $check = $conn->query("SELECT id FROM members WHERE member_id = '$member_id'");
    if ($check->num_rows > 0) {
        header("Location: index.php?error=Member ID already exists");
        exit();
    }

    // Check if email already exists
    $check = $conn->query("SELECT id FROM members WHERE email = '$email'");
    if ($check->num_rows > 0) {
        header("Location: index.php?error=Email already exists");
        exit();
    }

    $sql = "INSERT INTO members (member_id, full_name, email, phone, address) 
            VALUES (?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssss", $member_id, $full_name, $email, $phone, $address);
    
    if ($stmt->execute()) {
        header("Location: index.php?success=Member added successfully");
    } else {
        header("Location: index.php?error=Failed to add member");
    }
    exit();
} else {
    header("Location: index.php");
    exit();
}