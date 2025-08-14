<?php
session_start();
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

$queue_number = isset($_GET['queue']) ? $_GET['queue'] : '';
$ticket = null;

if ($queue_number) {
    $stmt = $db->prepare("
        SELECT q.queue_number, q.service, q.status, q.created_at,
               s.name as student_name, s.student_number
        FROM queue_tickets q
        LEFT JOIN students s ON q.student_id = s.id
        WHERE q.queue_number = ?
    ");
    $stmt->execute([$queue_number]);
    $ticket = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Track Queue Status - NORSU Queue</title>
    <link rel="stylesheet" href="css/styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="track-container">
        <div class="track-box">
            <h2>Track Queue Status</h2>
            <form method="GET" class="track-form">
                <div class="input-group">
                    <input type="text" name="queue" placeholder="Enter Queue Number" value="<?php echo htmlspecialchars($queue_number); ?>" required>
                    <button type="submit">Track</button>
                </div>
            </form>

            <?php if ($queue_number && !$ticket): ?>
                <div class="error-message">
                    Queue number not found.
                </div>
            <?php elseif ($ticket): ?>
                <div class="ticket-status">
                    <h3>Ticket #<?php echo htmlspecialchars($ticket['queue_number']); ?></h3>
                    <div class="status-info">
                        <p><strong>Service:</strong> <?php echo htmlspecialchars($ticket['service']); ?></p>
                        <p><strong>Status:</strong> 
                            <span class="status-badge <?php echo strtolower($ticket['status']); ?>">
                                <?php echo ucfirst($ticket['status']); ?>
                            </span>
                        </p>
                        <p><strong>Time:</strong> <?php echo date('h:i A', strtotime($ticket['created_at'])); ?></p>
                    </div>
                </div>
            <?php endif; ?>
            
            <div class="back-link">
                <a href="index.php"><i class="fas fa-arrow-left"></i> Back to Home</a>
            </div>
        </div>
    </div>
</body>
</html>