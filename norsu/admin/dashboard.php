<?php 
include 'includes/header.php';
require_once '../config/database.php';

// Get counts for dashboard
$stats = [
    'total_voters' => $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'voter'")->fetch_assoc()['count'],
    'pending_voters' => $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'voter' AND status = 'pending'")->fetch_assoc()['count'],
    'active_elections' => $conn->query("SELECT COUNT(*) as count FROM elections WHERE status = 'ongoing'")->fetch_assoc()['count'],
    'completed_elections' => $conn->query("SELECT COUNT(*) as count FROM elections WHERE status = 'completed'")->fetch_assoc()['count']
];

// Get recent activities
$recent_activities = $conn->query("
    SELECT al.*, CONCAT(u.firstname, ' ', u.lastname) as user_fullname 
    FROM audit_logs al 
    LEFT JOIN users u ON al.user_id = u.id 
    ORDER BY al.created_at DESC 
    LIMIT 10
");

// Get upcoming elections
$upcoming_elections = $conn->query("
    SELECT * FROM elections 
    WHERE status = 'upcoming' 
    ORDER BY start_date ASC 
    LIMIT 5
");
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h2>Dashboard</h2>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h5>Total Voters</h5>
                    <h2><?php echo $stats['total_voters']; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <h5>Pending Approvals</h5>
                    <h2><?php echo $stats['pending_voters']; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h5>Active Elections</h5>
                    <h2><?php echo $stats['active_elections']; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h5>Completed Elections</h5>
                    <h2><?php echo $stats['completed_elections']; ?></h2>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Upcoming Elections -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Upcoming Elections</h5>
                </div>
                <div class="card-body">
                    <?php if ($upcoming_elections->num_rows > 0): ?>
                        <div class="list-group">
                            <?php while ($election = $upcoming_elections->fetch_assoc()): ?>
                                <div class="list-group-item">
                                    <h6 class="mb-1"><?php echo htmlspecialchars($election['title']); ?></h6>
                                    <p class="mb-1">Starts: <?php echo date('M j, Y', strtotime($election['start_date'])); ?></p>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">No upcoming elections.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Recent Activities -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Recent Activities</h5>
                </div>
                <div class="card-body">
                    <?php if ($recent_activities->num_rows > 0): ?>
                        <div class="list-group">
                            <?php while ($activity = $recent_activities->fetch_assoc()): ?>
                                <div class="list-group-item">                                    <small class="text-muted float-end">
                                        <?php echo date('M j, Y H:i', strtotime($activity['created_at'])); ?>
                                    </small>                                    <h6 class="mb-1"><?php echo htmlspecialchars($activity['action']); ?></h6>
                                    <p class="mb-1">by <?php echo htmlspecialchars($activity['user_fullname'] ?? 'System'); ?></p>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">No recent activities.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
