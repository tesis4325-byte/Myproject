<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

require_once '../config/database.php';
$database = new Database();
$db = $database->getConnection();

// Fetch current queue
// Update the query section
// Fetch current queue
// Get status filter from URL parameter
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'active';

// Modify the query based on status filter
$stmt = $db->prepare("
    SELECT q.id, q.queue_number, q.service, q.status, q.created_at,
           s.name as student_name, s.student_number, s.course, s.year_level
    FROM queue_tickets q
    LEFT JOIN students s ON q.student_id = s.id
    WHERE DATE(q.created_at) = CURRENT_DATE
    " . ($status_filter === 'active' ? "AND q.status IN ('waiting', 'processing')" : "") . "
    ORDER BY q.created_at ASC
");
$stmt->execute();
$queue_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Queue Management - NORSU Queue</title>
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
                <a href="dashboard.php"><i class="fas fa-dashboard"></i> Dashboard</a>
                <a href="queue.php" class="active"><i class="fas fa-list-ol"></i> Queue Management</a>
                <a href="students.php"><i class="fas fa-users"></i> Students</a>
                <a href="reports.php"><i class="fas fa-chart-bar"></i> Reports</a>
                <a href="settings.php"><i class="fas fa-cog"></i> Settings</a>
                <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </nav>
        </aside>

        <main class="admin-main">
            <?php $page_title = "Queue Management"; ?>
            <?php require_once 'includes/header.php'; ?>
            
            <div class="queue-management">
                <div class="queue-header">
                    <h2>Queue Management</h2>
                    <div class="queue-filters">
                        <select id="statusFilter">
                            <option value="active" <?php echo ($status_filter === 'active' ? 'selected' : ''); ?>>Active Queues</option>
                            <option value="all" <?php echo ($status_filter === 'all' ? 'selected' : ''); ?>>All Status</option>
                            <option value="waiting" <?php echo ($status_filter === 'waiting' ? 'selected' : ''); ?>>Waiting</option>
                            <option value="processing" <?php echo ($status_filter === 'processing' ? 'selected' : ''); ?>>Processing</option>
                            <option value="completed" <?php echo ($status_filter === 'completed' ? 'selected' : ''); ?>>Completed</option>
                            <option value="cancelled" <?php echo ($status_filter === 'cancelled' ? 'selected' : ''); ?>>Cancelled</option>
                        </select>
                        <select id="serviceFilter">
                            <option value="all">All Services</option>
                            <option value="registration">Registration</option>
                            <option value="consultation">Consultation</option>
                            <option value="documents">Documents</option>
                        </select>
                    </div>
                </div>

                <div class="queue-list">
                    <?php foreach ($queue_items as $item): ?>
                        <div class="queue-item" data-status="<?php echo htmlspecialchars($item['status']); ?>">
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
                </div>
            </div>
        </main>
    </div>

    <script>
        // Queue management functions
        async function processTicket(id) {
            await updateTicketStatus(id, 'processing');
        }

        async function completeTicket(id) {
            await updateTicketStatus(id, 'completed');
        }

        async function cancelTicket(id) {
            if (confirm('Are you sure you want to cancel this ticket?')) {
                await updateTicketStatus(id, 'cancelled');
            }
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

        // Search and filter functionality
        document.getElementById('queueSearch').addEventListener('input', filterQueue);
        document.getElementById('statusFilter').addEventListener('change', filterQueue);
        document.getElementById('serviceFilter').addEventListener('change', filterQueue);

        function filterQueue() {
            const statusFilter = document.getElementById('statusFilter').value;
            const serviceFilter = document.getElementById('serviceFilter').value;

            // Fetch the updated queue list based on filters
            window.location.href = `queue.php?status=${statusFilter}&service=${serviceFilter}`;
        }
    </script>
</body>
</html>