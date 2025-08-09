<?php
session_start();
if(!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}
require_once '../config/database.php';

if (isset($_GET['id'])) {
    $book_id = (int)$_GET['id'];
    
    // Check if book has active borrowings
    $check = $conn->query("SELECT id FROM borrowings WHERE book_id = $book_id AND status = 'borrowed'");
    if ($check->num_rows > 0) {
        header("Location: index.php?error=Cannot delete book with active borrowings");
        exit();
    }

    $sql = "DELETE FROM books WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $book_id);
    
    if ($stmt->execute()) {
        header("Location: index.php?success=Book deleted successfully");
    } else {
        header("Location: index.php?error=Failed to delete book");
    }
    exit();
} else {
    header("Location: index.php");
    exit();
}