<?php
session_start();
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $isbn = mysqli_real_escape_string($conn, $_POST['isbn']);
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $author = mysqli_real_escape_string($conn, $_POST['author']);
    $category = mysqli_real_escape_string($conn, $_POST['category']);
    $quantity = (int)$_POST['quantity'];
    $location = mysqli_real_escape_string($conn, $_POST['location']);

    // Check if ISBN already exists
    $check = $conn->query("SELECT id FROM books WHERE isbn = '$isbn'");
    if ($check->num_rows > 0) {
        header("Location: index.php?error=ISBN already exists");
        exit();
    }

    $sql = "INSERT INTO books (isbn, title, author, category, quantity, available, location) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssiss", $isbn, $title, $author, $category, $quantity, $quantity, $location);
    
    if ($stmt->execute()) {
        header("Location: index.php?success=Book added successfully");
    } else {
        header("Location: index.php?error=Failed to add book");
    }
    exit();
} else {
    header("Location: index.php");
    exit();
}