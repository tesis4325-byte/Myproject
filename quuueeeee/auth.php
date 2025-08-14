<?php
session_start();
require_once 'config/database.php';

header('Content-Type: application/json');

try {
    $database = new Database();
    $db = $database->getConnection();

    $student_number = $_POST['student_number'] ?? '';
    $name = $_POST['name'] ?? '';

    // Validate input
    if (empty($student_number) || empty($name)) {
        throw new Exception('Please enter both Student ID and Name.');
    }

    // Get student details
    $stmt = $db->prepare("SELECT * FROM students WHERE student_number = ? AND name = ?");
    $stmt->execute([$student_number, $name]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$student) {
        throw new Exception('Invalid Student ID or Name. Please check your credentials or register if you haven\'t yet.');
    }

    // Set session variables
    $_SESSION['student_id'] = $student['id'];
    $_SESSION['student_name'] = $student['name'];
    $_SESSION['student_number'] = $student['student_number'];
    $_SESSION['course'] = $student['course'];
    $_SESSION['year_level'] = $student['year_level'];

    echo json_encode([
        'success' => true,
        'message' => 'Login successful'
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}