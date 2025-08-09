<?php
session_start();
require_once '../config/database.php';

if (isset($_GET['id'])) {
    $borrowing_id = (int)$_GET['id'];
    $return_date = date('Y-m-d');
    
    // Start transaction
    $conn->begin_transaction();

    try {
        // Get borrowing details
        $borrowing = $conn->query("SELECT * FROM borrowings WHERE id = $borrowing_id")->fetch_assoc();
        
        // Calculate fine if overdue (₱5 per day)
        $fine = 0;
        if (strtotime($return_date) > strtotime($borrowing['due_date'])) {
            $days_overdue = floor((strtotime($return_date) - strtotime($borrowing['due_date'])) / (60 * 60 * 24));
            $fine = $days_overdue * 5;
        }

        // Update borrowing record
        $sql = "UPDATE borrowings SET return_date = ?, status = 'returned', fine = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sdi", $return_date, $fine, $borrowing_id);
        $stmt->execute();

        // Update book availability
        $sql = "UPDATE books SET available = available + 1 WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $borrowing['book_id']);
        $stmt->execute();

        $conn->commit();
        header("Location: index.php?success=Book returned successfully" . ($fine > 0 ? " (Fine: ₱$fine)" : ""));
    } catch (Exception $e) {
        $conn->rollback();
        header("Location: index.php?error=Failed to process return");
    }
    exit();
} else {
    header("Location: index.php");
    exit();
}