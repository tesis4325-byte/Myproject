<?php
session_start();
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $book_id = (int)$_POST['book_id'];
    $member_id = (int)$_POST['member_id'];
    $due_date = $_POST['due_date'];
    $borrow_date = date('Y-m-d');

    // Check if book is available
    $book = $conn->query("SELECT available FROM books WHERE id = $book_id")->fetch_assoc();
    if ($book['available'] <= 0) {
        header("Location: index.php?error=Book is not available");
        exit();
    }

    // Start transaction
    $conn->begin_transaction();

    try {
        // Insert borrowing record
        $sql = "INSERT INTO borrowings (book_id, member_id, borrow_date, due_date) 
                VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iiss", $book_id, $member_id, $borrow_date, $due_date);
        $stmt->execute();

        // Update book availability
        $sql = "UPDATE books SET available = available - 1 WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $book_id);
        $stmt->execute();

        $conn->commit();
        header("Location: index.php?success=Borrowing added successfully");
    } catch (Exception $e) {
        $conn->rollback();
        header("Location: index.php?error=Failed to add borrowing");
    }
    exit();
} else {
    header("Location: index.php");
    exit();
}