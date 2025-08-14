<?php
session_start();
require_once '../../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = $data['id'] ?? null;
    $status = $data['status'] ?? null;
    
    $database = new Database();
    $db = $database->getConnection();
    
    try {
        $db->beginTransaction();

        if ($status === 'completed') {
            // Get current window number before completing
            $currentWindow = $db->prepare("
                SELECT window_number FROM queue_tickets WHERE id = ?
            ");
            $currentWindow->execute([$id]);
            $windowNum = $currentWindow->fetchColumn();

            // Complete current ticket
            $stmt = $db->prepare("
                UPDATE queue_tickets 
                SET status = ?,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ");
            $stmt->execute([$status, $id]);

            // Find and process next waiting ticket
            $nextTicket = $db->prepare("
                SELECT id, queue_number FROM queue_tickets 
                WHERE status = 'waiting' 
                AND DATE(created_at) = CURRENT_DATE
                ORDER BY created_at ASC 
                LIMIT 1
            ");
            $nextTicket->execute();
            $next = $nextTicket->fetch(PDO::FETCH_ASSOC);

            if ($next) {
                // Switch windows (1 -> 2 or 2 -> 1)
                $nextWindow = $windowNum == 1 ? 2 : 1;

                // Process next ticket with new window and force new announcement
                $stmt = $db->prepare("
                    UPDATE queue_tickets 
                    SET status = 'processing',
                        window_number = ?,
                        updated_at = CURRENT_TIMESTAMP,
                        status_changed_at = CURRENT_TIMESTAMP
                    WHERE id = ?
                ");
                $stmt->execute([$nextWindow, $next['id']]);

                // Return the next ticket info for immediate display
                echo json_encode([
                    'success' => true,
                    'nextTicket' => [
                        'number' => $next['queue_number'],
                        'window' => $nextWindow
                    ]
                ]);
            } else {
                echo json_encode(['success' => true]);
            }
            $db->commit();
            return;
        } elseif ($status === 'processing') {
            // Original processing logic
            $lastWindow = $db->prepare("
                SELECT window_number FROM queue_tickets 
                WHERE status = 'processing'
                ORDER BY updated_at DESC LIMIT 1
            ");
            $lastWindow->execute();
            $currentWindow = $lastWindow->fetch(PDO::FETCH_COLUMN);
            $nextWindow = $currentWindow == 1 ? 2 : 1;
            if (!$currentWindow) $nextWindow = 1;

            $stmt = $db->prepare("
                UPDATE queue_tickets 
                SET status = ?, 
                    window_number = ?,
                    updated_at = CURRENT_TIMESTAMP,
                    status_changed_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ");
            $stmt->execute([$status, $nextWindow, $id]);
        }

        $db->commit();
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        $db->rollBack();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}