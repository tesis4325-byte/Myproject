<?php
session_start();
if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}
require_once '../config/database.php';

if (isset($_GET['id'])) {
    $borrowing_id = (int)$_GET['id'];
    
    // Get borrowing details to update book availability
    $borrowing = $conn->query("SELECT * FROM borrowings WHERE id = $borrowing_id")->fetch_assoc();
    
    if ($borrowing) {
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // Delete the borrowing record
            $sql = "DELETE FROM borrowings WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $borrowing_id);
            $stmt->execute();
            
            // If the book was borrowed (not returned), update book availability
            if ($borrowing['status'] == 'borrowed') {
                $sql = "UPDATE books SET available = available + 1 WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $borrowing['book_id']);
                $stmt->execute();
            }
            
            // Commit transaction
            $conn->commit();
            header("Location: index.php?success=Borrowing record deleted successfully");
            
        } catch (Exception $e) {
            // Rollback on error
            $conn->rollback();
            header("Location: index.php?error=Failed to delete borrowing record");
        }
    } else {
        header("Location: index.php?error=Borrowing record not found");
    }
    exit();
} else {
    header("Location: index.php");
    exit();
}