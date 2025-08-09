<?php
session_start();
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = (int)$_POST['id'];
    $isbn = mysqli_real_escape_string($conn, $_POST['isbn']);
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $author = mysqli_real_escape_string($conn, $_POST['author']);
    $category = mysqli_real_escape_string($conn, $_POST['category']);
    $quantity = (int)$_POST['quantity'];
    $location = mysqli_real_escape_string($conn, $_POST['location']);

    // Check if ISBN exists for other books
    $check = $conn->query("SELECT id FROM books WHERE isbn = '$isbn' AND id != $id");
    if ($check->num_rows > 0) {
        header("Location: index.php?error=ISBN already exists");
        exit();
    }

    $sql = "UPDATE books SET isbn=?, title=?, author=?, category=?, quantity=?, location=? WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssisi", $isbn, $title, $author, $category, $quantity, $location, $id);
    
    if ($stmt->execute()) {
        header("Location: index.php?success=Book updated successfully");
    } else {
        header("Location: index.php?error=Failed to update book");
    }
    exit();
} else {
    header("Location: index.php");
    exit();
}