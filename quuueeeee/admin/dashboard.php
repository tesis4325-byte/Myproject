<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

require_once '../config/database.php';
$database = new Database();
$db = $database->getConnection();

// Get queue statistics
$stmt = $db->prepare("SELECT 
    COUNT(CASE WHEN status = 'waiting' THEN 1 END) as waiting,
    COUNT(CASE WHEN status = 'processing' THEN 1 END) as processing,
    COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed
    FROM queue_tickets WHERE DATE(created_at) = CURDATE()");
$stmt->execute();
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch current queue
$queue_stmt = $db->prepare("
    SELECT q.id, q.queue_number, q.service, q.status, q.created_at,
           s.name as student_name, s.student_number, s.course, s.year_level
    FROM queue_tickets q
    LEFT JOIN students s ON q.student_id = s.id
    WHERE DATE(q.created_at) = CURRENT_DATE
    AND q.status IN ('waiting', 'processing')
    ORDER BY q.created_at ASC
");
$queue_stmt->execute();
$current_queue = $queue_stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - NORSU Queue</title>
    <link rel="stylesheet" href="../css/admin.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="admin-container">
        <aside class="admin-sidebar">
            <div class="sidebar-header">
                <img src="../images/norsu-logo.png" alt="NORSU Logo">
                <h2>Admin Panel</h2>
            </div>
            <nav class="sidebar-nav">
                <a href="#" class="active"><i class="fas fa-dashboard"></i> Dashboard</a>
                <a href="queue.php"><i class="fas fa-list-ol"></i> Queue Management</a>
                <a href="students.php"><i class="fas fa-users"></i> Students</a>
                <a href="reports.php"><i class="fas fa-chart-bar"></i> Reports</a>
                <a href="settings.php"><i class="fas fa-cog"></i> Settings</a>
                <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </nav>
        </aside>

        <main class="admin-main">
            <?php $page_title = "Dashboard"; ?>
            <?php require_once 'includes/header.php'; ?>
            
           

            <div class="dashboard-stats">
                <div class="stat-card">
                    <div class="stat-icon waiting">
                        <i class="fas fa-user-clock"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Waiting</h3>
                        <p><?php echo $stats['waiting']; ?></p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon processing">
                        <i class="fas fa-spinner"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Processing</h3>
                        <p><?php echo $stats['processing']; ?></p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon completed">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Completed</h3>
                        <p><?php echo $stats['completed']; ?></p>
                    </div>
                </div>
            </div>

            <div class="dashboard-content">
                <div class="queue-section">
                    <h2>Current Queue</h2>
                    <div class="queue-list">
                        <?php if (empty($current_queue)): ?>
                            <p class="no-queue">No active queue tickets</p>
                        <?php else: ?>
                            <?php foreach ($current_queue as $item): ?>
                                <div class="queue-item">
                                    <div class="queue-info">
                                        <h3>Ticket #<?php echo htmlspecialchars($item['queue_number']); ?></h3>
                                        <p class="service"><?php echo htmlspecialchars($item['service']); ?></p>
                                        <p class="student">
                                            <i class="fas fa-user"></i> 
                                            <?php echo htmlspecialchars($item['student_name'] ?? 'Guest'); ?> 
                                            (<?php echo htmlspecialchars($item['student_number'] ?? 'N/A'); ?>)
                                        </p>
                                        <p class="details">
                                            <?php echo htmlspecialchars($item['course'] ?? 'N/A'); ?> - 
                                            Year <?php echo htmlspecialchars($item['year_level'] ?? 'N/A'); ?>
                                        </p>
                                        <span class="timestamp">
                                            <i class="fas fa-clock"></i> 
                                            <?php echo date('h:i A', strtotime($item['created_at'])); ?>
                                        </span>
                                    </div>
                                    <div class="queue-actions">
                                        <?php if ($item['status'] === 'waiting'): ?>
                                            <button class="action-btn process" onclick="processTicket(<?php echo $item['id']; ?>)">
                                                Process
                                            </button>
                                        <?php elseif ($item['status'] === 'processing'): ?>
                                            <button class="action-btn complete" onclick="completeTicket(<?php echo $item['id']; ?>)">
                                                Complete
                                            </button>
                                        <?php endif; ?>
                                        <button class="action-btn cancel" onclick="cancelTicket(<?php echo $item['id']; ?>)">
                                            Cancel
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
    <script src="../js/admin.js"></script>
<script>
    async function processTicket(id) {
        await updateTicketStatus(id, 'processing');
    }

    async function updateTicketStatus(id, status) {
        try {
            const response = await fetch('./api/update-queue.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ id, status })
            });

            const data = await response.json();
            if (data.success) {
                location.reload();
            } else {
                alert(data.message);
            }
        } catch (error) {
            console.error('Error:', error);
            alert('An error occurred. Please try again.');
        }
    }
</script>
</body>
</html>