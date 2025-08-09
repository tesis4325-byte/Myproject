<?php
session_start();
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = (int)$_POST['id'];
    $member_id = mysqli_real_escape_string($conn, $_POST['member_id']);
    $full_name = mysqli_real_escape_string($conn, $_POST['full_name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);

    // Check if member ID exists for other members
    $check = $conn->query("SELECT id FROM members WHERE member_id = '$member_id' AND id != $id");
    if ($check->num_rows > 0) {
        header("Location: index.php?error=Member ID already exists");
        exit();
    }

    // Check if email exists for other members
    $check = $conn->query("SELECT id FROM members WHERE email = '$email' AND id != $id");
    if ($check->num_rows > 0) {
        header("Location: index.php?error=Email already exists");
        exit();
    }

    $sql = "UPDATE members SET member_id=?, full_name=?, email=?, phone=?, address=? WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssi", $member_id, $full_name, $email, $phone, $address, $id);
    
    if ($stmt->execute()) {
        header("Location: index.php?success=Member updated successfully");
    } else {
        header("Location: index.php?error=Failed to update member");
    }
    exit();
} else {
    header("Location: index.php");
    exit();
}