<?php
/**
 * Barangay Document Request and Tracking System
 * Admin Dashboard
 */

require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Require admin access
$auth->requireAdmin();

// Get current user data
$user = $auth->getCurrentUser();

// Get dashboard statistics
$stats = getDashboardStats();

// Get recent requests
$recentRequests = getAllRequests(['limit' => 10]);

// Get pending residents
$pendingResidents = $db->fetchAll("
    SELECT r.*, u.username, u.email, u.created_at 
    FROM residents r 
    JOIN users u ON r.user_id = u.id 
    WHERE u.role = 'resident' AND u.status = 'pending' 
    ORDER BY u.created_at DESC 
    LIMIT 5
");

// Get system settings
$barangayName = getSystemSetting('barangay_name', 'Sample Barangay');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - <?php echo $barangayName; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="../public/assets/css/style.css" rel="stylesheet">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-building me-2"></i>
                <?php echo $barangayName; ?> - Admin
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="dashboard.php">
                            <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="residents.php">
                            <i class="fas fa-users me-1"></i>Residents
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="requests.php">
                            <i class="fas fa-file-alt me-1"></i>Requests
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="documents.php">
                            <i class="fas fa-file me-1"></i>Documents
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="reports.php">
                            <i class="fas fa-chart-bar me-1"></i>Reports
                        </a>
                    </li>
                </ul>
                
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" 
                           data-bs-toggle="dropdown">
                            <i class="fas fa-user-shield me-1"></i>
                            <?php echo htmlspecialchars($user['username']); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="settings.php">
                                <i class="fas fa-cog me-2"></i>Settings
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="../public/logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i>Logout
                            </a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container">
            <!-- Welcome Section -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-md-8">
                                    <h4 class="text-primary mb-2">
                                        <i class="fas fa-user-shield me-2"></i>
                                        Welcome, Administrator!
                                    </h4>
                                    <p class="text-muted mb-0">
                                        Manage document requests, residents, and system settings from this dashboard.
                                    </p>
                                </div>
                                <div class="col-md-4 text-end">
                                    <a href="requests.php" class="btn btn-primary">
                                        <i class="fas fa-eye me-2"></i>View All Requests
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-md-3 mb-3">
                    <div class="stats-card">
                        <div class="stats-number"><?php echo $stats['total_requests']; ?></div>
                        <div class="stats-label">Total Requests</div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="stats-card warning">
                        <div class="stats-number"><?php echo $stats['pending_requests']; ?></div>
                        <div class="stats-label">Pending Requests</div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="stats-card success">
                        <div class="stats-number"><?php echo $stats['total_residents']; ?></div>
                        <div class="stats-label">Total Residents</div>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="stats-card info">
                        <div class="stats-number"><?php echo $stats['pending_residents']; ?></div>
                        <div class="stats-label">Pending Approvals</div>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Recent Requests -->
                <div class="col-lg-8 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-clock me-2"></i>Recent Document Requests
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($recentRequests)): ?>
                                <div class="text-center py-4">
                                    <i class="fas fa-file-alt text-muted fa-3x mb-3"></i>
                                    <h5 class="text-muted">No requests yet</h5>
                                    <p class="text-muted">No document requests have been submitted.</p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Request #</th>
                                                <th>Resident</th>
                                                <th>Document Type</th>
                                                <th>Status</th>
                                                <th>Submitted</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($recentRequests as $request): ?>
                                                <tr>
                                                    <td>
                                                        <strong><?php echo htmlspecialchars($request['request_number']); ?></strong>
                                                    </td>
                                                    <td>
                                                        <?php echo htmlspecialchars($request['first_name'] . ' ' . $request['last_name']); ?>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($request['document_type']); ?></td>
                                                    <td>
                                                        <span class="<?php echo getStatusBadgeClass($request['status']); ?>">
                                                            <?php echo getStatusText($request['status']); ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo formatDate($request['submitted_at']); ?></td>
                                                    <td>
                                                        <a href="requests.php?id=<?php echo $request['id']; ?>" 
                                                           class="btn btn-sm btn-outline-primary">
                                                            <i class="fas fa-eye me-1"></i>View
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="text-center mt-3">
                                    <a href="requests.php" class="btn btn-outline-primary">
                                        <i class="fas fa-list me-2"></i>View All Requests
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions & Information -->
                <div class="col-lg-4 mb-4">
                    <!-- Quick Actions -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-bolt me-2"></i>Quick Actions
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <a href="residents.php" class="btn btn-primary">
                                    <i class="fas fa-users me-2"></i>Manage Residents
                                </a>
                                <a href="requests.php" class="btn btn-outline-primary">
                                    <i class="fas fa-file-alt me-2"></i>Process Requests
                                </a>
                                <a href="documents.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-file me-2"></i>Document Templates
                                </a>
                                <a href="reports.php" class="btn btn-outline-info">
                                    <i class="fas fa-chart-bar me-2"></i>Generate Reports
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Pending Resident Approvals -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-user-clock me-2"></i>Pending Approvals
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($pendingResidents)): ?>
                                <p class="text-muted text-center mb-0">No pending resident approvals.</p>
                            <?php else: ?>
                                <div class="list-group list-group-flush">
                                    <?php foreach ($pendingResidents as $resident): ?>
                                        <div class="list-group-item">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <h6 class="mb-1"><?php echo htmlspecialchars($resident['first_name'] . ' ' . $resident['last_name']); ?></h6>
                                                    <small class="text-muted"><?php echo htmlspecialchars($resident['email']); ?></small>
                                                </div>
                                                <div class="btn-group btn-group-sm">
                                                    <button class="btn btn-success" onclick="approveResident(<?php echo $resident['user_id']; ?>)">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                    <button class="btn btn-danger" onclick="rejectResident(<?php echo $resident['user_id']; ?>)">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <div class="text-center mt-3">
                                    <a href="residents.php" class="btn btn-outline-primary btn-sm">
                                        <i class="fas fa-list me-1"></i>View All
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- System Information -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-info-circle me-2"></i>System Information
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <strong>System Version</strong><br>
                                <small class="text-muted">v1.0.0</small>
                            </div>
                            <div class="mb-3">
                                <strong>Last Updated</strong><br>
                                <small class="text-muted"><?php echo date('M d, Y H:i'); ?></small>
                            </div>
                            <div class="mb-3">
                                <strong>Database Status</strong><br>
                                <small class="text-success">Connected</small>
                            </div>
                            <div>
                                <strong>Server Time</strong><br>
                                <small class="text-muted"><?php echo date('Y-m-d H:i:s'); ?></small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Additional Statistics -->
            <div class="row">
                <div class="col-md-6 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-chart-pie me-2"></i>Request Status Distribution
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row text-center">
                                <div class="col-4">
                                    <div class="text-warning">
                                        <h4><?php echo $stats['pending_requests']; ?></h4>
                                        <small>Pending</small>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="text-info">
                                        <h4><?php echo $stats['processing_requests']; ?></h4>
                                        <small>Processing</small>
                                    </div>
                                </div>
                                <div class="col-4">
                                    <div class="text-success">
                                        <h4><?php echo $stats['approved_requests']; ?></h4>
                                        <small>Approved</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-chart-line me-2"></i>Recent Activity
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="list-group list-group-flush">
                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong>New Requests</strong><br>
                                        <small class="text-muted">Today</small>
                                    </div>
                                    <span class="badge bg-primary rounded-pill">
                                        <?php echo $db->fetch("SELECT COUNT(*) as count FROM document_requests WHERE DATE(submitted_at) = CURDATE()")['count']; ?>
                                    </span>
                                </div>
                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong>Processed Today</strong><br>
                                        <small class="text-muted">Requests</small>
                                    </div>
                                    <span class="badge bg-success rounded-pill">
                                        <?php echo $db->fetch("SELECT COUNT(*) as count FROM document_requests WHERE DATE(processed_at) = CURDATE()")['count']; ?>
                                    </span>
                                </div>
                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong>New Residents</strong><br>
                                        <small class="text-muted">This Week</small>
                                    </div>
                                    <span class="badge bg-info rounded-pill">
                                        <?php echo $db->fetch("SELECT COUNT(*) as count FROM users WHERE role = 'resident' AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")['count']; ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-12 text-center">
                    <p>&copy; <?php echo date('Y'); ?> <?php echo $barangayName; ?>. All rights reserved.</p>
                    <small class="text-muted">Barangay Document Request and Tracking System v1.0 - Admin Panel</small>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Custom JS -->
    <script src="../public/assets/js/app.js"></script>
    
    <script>
        // Approve resident
        function approveResident(userId) {
            if (confirm('Are you sure you want to approve this resident?')) {
                $.ajax({
                    url: '../api/admin_api.php',
                    method: 'POST',
                    data: {
                        action: 'approve_resident',
                        user_id: userId
                    },
                    success: function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert('Error: ' + response.message);
                        }
                    }
                });
            }
        }
        
        // Reject resident
        function rejectResident(userId) {
            if (confirm('Are you sure you want to reject this resident?')) {
                $.ajax({
                    url: '../api/admin_api.php',
                    method: 'POST',
                    data: {
                        action: 'reject_resident',
                        user_id: userId
                    },
                    success: function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert('Error: ' + response.message);
                        }
                    }
                });
            }
        }
    </script>
</body>
</html>
