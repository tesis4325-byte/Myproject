<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

require_once '../config/database.php';
$database = new Database();
$db = $database->getConnection();

// Get date range
$end_date = date('Y-m-d');
$start_date = date('Y-m-d', strtotime('-30 days'));

if (isset($_GET['start_date']) && isset($_GET['end_date'])) {
    $start_date = $_GET['start_date'];
    $end_date = $_GET['end_date'];
}

// Fetch queue statistics
// Modify the queue statistics query
$stmt = $db->prepare("
    SELECT 
        DATE(created_at) as date,
        COUNT(*) as total_tickets,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
        SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled,
        COALESCE(
            AVG(CASE 
                WHEN status = 'completed' AND updated_at IS NOT NULL
                THEN TIMESTAMPDIFF(MINUTE, created_at, updated_at)
                ELSE NULL 
            END), 0
        ) as avg_wait_time
    FROM queue_tickets
    WHERE DATE(created_at) BETWEEN ? AND ?
    GROUP BY DATE(created_at)
    ORDER BY date DESC
");

$stmt->execute([$start_date, $end_date]);
$daily_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get service type distribution
$service_stats = $db->prepare("
    SELECT 
        service,
        COUNT(*) as count,
        COUNT(*) * 100.0 / (SELECT COUNT(*) FROM queue_tickets WHERE created_at BETWEEN ? AND ?) as percentage
    FROM queue_tickets
    WHERE created_at BETWEEN ? AND ?
    GROUP BY service
    ORDER BY count DESC
");
$service_stats->execute([$start_date, $end_date, $start_date, $end_date]);
$services = $service_stats->fetchAll(PDO::FETCH_ASSOC);

// Get peak hours
$peak_hours = $db->prepare("
    SELECT 
        HOUR(created_at) as hour,
        COUNT(*) as count
    FROM queue_tickets
    WHERE created_at BETWEEN ? AND ?
    GROUP BY HOUR(created_at)
    ORDER BY count DESC
    LIMIT 5
");
$peak_hours->execute([$start_date, $end_date]);
$busy_hours = $peak_hours->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports & Analytics - NORSU Queue</title>
    <link rel="stylesheet" href="../css/admin.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                <a href="queue.php"><i class="fas fa-list-ol"></i> Queue Management</a>
                <a href="students.php"><i class="fas fa-users"></i> Students</a>
                <a href="reports.php" class="active"><i class="fas fa-chart-bar"></i> Reports</a>
                <a href="settings.php"><i class="fas fa-cog"></i> Settings</a>
                <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </nav>
        </aside>

        <main class="admin-main">
            <?php $page_title = "Reports & Analytics"; ?>
            <?php require_once 'includes/header.php'; ?>
            
            <div class="reports-container">
                <div class="report-section">
                    <h2>Queue Performance Overview</h2>
                    <div class="chart-container">
                        <canvas id="queueChart"></canvas>
                    </div>
                </div>

                <div class="report-grid">
                    <div class="report-card">
                        <h3>Service Distribution</h3>
                        <div class="chart-container">
                            <canvas id="serviceChart"></canvas>
                        </div>
                        <div class="service-stats">
                            <?php foreach ($services as $service): ?>
                            <div class="service-item">
                                <span class="service-name"><?php echo htmlspecialchars($service['service']); ?></span>
                                <span class="service-count"><?php echo $service['count']; ?></span>
                                <span class="service-percentage"><?php echo number_format($service['percentage'], 1); ?>%</span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="report-card">
                        <h3>Peak Hours</h3>
                        <div class="chart-container">
                            <canvas id="peakHoursChart"></canvas>
                        </div>
                        <div class="peak-hours-list">
                            <?php foreach ($busy_hours as $hour): ?>
                            <div class="peak-hour-item">
                                <span class="hour"><?php echo date('h:i A', strtotime($hour['hour'] . ':00')); ?></span>
                                <span class="count"><?php echo $hour['count']; ?> tickets</span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                // Update the table display section
                ?>
                <div class="report-section">
                    <h2>Daily Statistics</h2>
                    <div class="date-filter">
                        <form method="GET" class="filter-form">
                            <div class="form-group">
                                <label>From:</label>
                                <input type="date" name="start_date" value="<?php echo $start_date; ?>">
                            </div>
                            <div class="form-group">
                                <label>To:</label>
                                <input type="date" name="end_date" value="<?php echo $end_date; ?>">
                            </div>
                            <button type="submit" class="filter-btn">Apply Filter</button>
                        </form>
                    </div>
                    <div class="table-container">
                        <?php if (empty($daily_stats)): ?>
                            <p class="no-data">No statistics available for the selected date range.</p>
                        <?php else: ?>
                            <table class="stats-table">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Total Tickets</th>
                                        <th>Completed</th>
                                        <th>Cancelled</th>
                                        <th>Avg. Wait Time</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($daily_stats as $stat): ?>
                                    <tr>
                                        <td><?php echo date('M d, Y', strtotime($stat['date'])); ?></td>
                                        <td><?php echo $stat['total_tickets']; ?></td>
                                        <td><?php echo $stat['completed']; ?></td>
                                        <td><?php echo $stat['cancelled']; ?></td>
                                        <td><?php echo round($stat['avg_wait_time']) . ' mins'; ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Queue Performance Chart
        const queueCtx = document.getElementById('queueChart').getContext('2d');
        new Chart(queueCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_column(array_reverse($daily_stats), 'date')); ?>,
                datasets: [{
                    label: 'Total Tickets',
                    data: <?php echo json_encode(array_column(array_reverse($daily_stats), 'total_tickets')); ?>,
                    borderColor: '#1a237e',
                    tension: 0.4
                }, {
                    label: 'Completed',
                    data: <?php echo json_encode(array_column(array_reverse($daily_stats), 'completed')); ?>,
                    borderColor: '#28a745',
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });

        // Service Distribution Chart
        const serviceCtx = document.getElementById('serviceChart').getContext('2d');
        new Chart(serviceCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode(array_column($services, 'service')); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_column($services, 'count')); ?>,
                    backgroundColor: ['#1a237e', '#3949ab', '#5c6bc0', '#7986cb', '#9fa8da']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });

        // Peak Hours Chart
        const peakCtx = document.getElementById('peakHoursChart').getContext('2d');
        new Chart(peakCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_map(function($h) { 
                    return date('h:i A', strtotime($h['hour'] . ':00')); 
                }, $busy_hours)); ?>,
                datasets: [{
                    label: 'Number of Tickets',
                    data: <?php echo json_encode(array_column($busy_hours, 'count')); ?>,
                    backgroundColor: '#1a237e'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });
    </script>
</body>
</html>