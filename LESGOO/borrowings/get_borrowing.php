<?php
session_start();
require_once '../config/database.php';

if(isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $sql = "SELECT b.*, bk.title as book_title, bk.isbn, m.full_name as member_name 
            FROM borrowings b 
            JOIN books bk ON b.book_id = bk.id 
            JOIN members m ON b.member_id = m.id 
            WHERE b.id = ?";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if($borrowing = $result->fetch_assoc()) {
        // Format dates for display
        $borrowing['borrow_date'] = date('Y-m-d', strtotime($borrowing['borrow_date']));
        $borrowing['due_date'] = date('Y-m-d', strtotime($borrowing['due_date']));
        if($borrowing['return_date']) {
            $borrowing['return_date'] = date('Y-m-d', strtotime($borrowing['return_date']));
        }
        echo json_encode($borrowing);
    } else {
        echo json_encode(['error' => 'Borrowing not found']);
    }
} else {
    echo json_encode(['error' => 'No ID provided']);
}