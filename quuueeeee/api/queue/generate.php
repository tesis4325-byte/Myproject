<?php
session_start();
require_once '../../config/database.php';

header('Content-Type: application/json');

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    $service = $input['service'] ?? '';

    if (empty($service)) {
        throw new Exception('Service type is required');
    }

    // Initialize database connection
    $database = new Database();
    $db = $database->getConnection();

    // Get current date
    $today = date('Y-m-d');

    // Get last queue number for today and service
    $stmt = $db->prepare("SELECT MAX(CAST(SUBSTRING(queue_number, -3) AS UNSIGNED)) as last_number 
                         FROM queue_tickets 
                         WHERE DATE(created_at) = ? AND service = ?");
    $stmt->execute([$today, $service]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Generate new queue number
    $last_number = $result['last_number'] ?? 0;
    $new_number = $last_number + 1;
    $queue_number = $service . str_pad($new_number, 3, '0', STR_PAD_LEFT);

    // Calculate estimated time (15 minutes per person in queue)
    $waiting_time = $new_number * 15;

    // Insert new queue ticket
    $stmt = $db->prepare("INSERT INTO queue_tickets (queue_number, service, status, student_id, created_at) 
                         VALUES (?, ?, 'waiting', ?, NOW())");
    $student_id = $_SESSION['student_id'] ?? null;
    $stmt->execute([$queue_number, $service, $student_id]);

    echo json_encode([
        'success' => true,
        'queueNumber' => $queue_number,
        'estimatedTime' => $waiting_time,
        'message' => 'Queue number generated successfully'
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}