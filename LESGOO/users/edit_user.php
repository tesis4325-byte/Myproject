<?php
session_start();
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = (int)$_POST['id'];
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $full_name = mysqli_real_escape_string($conn, $_POST['full_name']);
    $role = mysqli_real_escape_string($conn, $_POST['role']);
    $password = !empty($_POST['password']) ? password_hash($_POST['password'], PASSWORD_DEFAULT) : null;

    // Check if username exists for other users
    $check = $conn->query("SELECT id FROM users WHERE username = '$username' AND id != $id");
    if ($check->num_rows > 0) {
        header("Location: index.php?error=Username already exists");
        exit();
    }

    // Don't allow editing admin username
    $check = $conn->query("SELECT username FROM users WHERE id = $id");
    $user = $check->fetch_assoc();
    if ($user['username'] === 'admin' && $username !== 'admin') {
        header("Location: index.php?error=Cannot change admin username");
        exit();
    }

    if ($password) {
        $sql = "UPDATE users SET username=?, full_name=?, role=?, password=? WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssi", $username, $full_name, $role, $password, $id);
    } else {
        $sql = "UPDATE users SET username=?, full_name=?, role=? WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssi", $username, $full_name, $role, $id);
    }
    
    if ($stmt->execute()) {
        header("Location: index.php?success=User updated successfully");
    } else {
        header("Location: index.php?error=Failed to update user");
    }
    exit();
} else {
    header("Location: index.php");
    exit();
}