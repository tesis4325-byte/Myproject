<?php
require_once '../config/database.php';
require_once '../config/security.php';

// Ensure user is logged in and is an admin
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Election ID not provided']);
    exit;
}

$id = (int)$_GET['id'];

$stmt = $conn->prepare("SELECT * FROM elections WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$election = $result->fetch_assoc();

if (!$election) {
    http_response_code(404);
    echo json_encode(['error' => 'Election not found']);
    exit;
}

// Format dates to be compatible with datetime-local input
$election['start_date'] = date('Y-m-d\TH:i', strtotime($election['start_date']));
$election['end_date'] = date('Y-m-d\TH:i', strtotime($election['end_date']));

echo json_encode($election);
