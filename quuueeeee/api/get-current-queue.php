<?php
require_once '../config/database.php';
header('Content-Type: application/json');

$database = new Database();
$db = $database->getConnection();

// Get current processing tickets and next waiting ticket
$stmt = $db->prepare("
    SELECT id, queue_number, window_number, status, 
           UNIX_TIMESTAMP(updated_at) as last_update,
           UNIX_TIMESTAMP(status_changed_at) as status_changed
    FROM queue_tickets 
    WHERE DATE(created_at) = CURRENT_DATE
    AND (status IN ('processing', 'waiting'))
    ORDER BY status_changed_at DESC
    LIMIT 2
");
$stmt->execute();
$tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

$response = [
    'windows' => [],
    'waiting' => [],
    'announcements' => []
];

foreach ($tickets as $ticket) {
    if ($ticket['status'] === 'processing') {
        $isNewStatus = (time() - $ticket['status_changed']) < 10;
        $response['windows'][] = [
            'number' => $ticket['window_number'],
            'ticket' => $ticket['queue_number'],
            'isNew' => $isNewStatus
        ];
        
        // Always include processing tickets in announcements
        $response['announcements'][] = [
            'ticket' => $ticket['queue_number'],
            'window' => $ticket['window_number']
        ];
    } else {
        $response['waiting'][] = $ticket['queue_number'];
    }
}

echo json_encode($response);