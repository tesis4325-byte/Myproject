<?php
/**
 * Admin - Reports and Analytics
 * Generate various reports and analytics for the system
 */

require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Ensure admin access
$auth->requireAdmin();

$success = '';
$error = '';

// Get report parameters
$report_type = $_GET['report'] ?? 'overview';
$date_from = $_GET['date_from'] ?? date('Y-m-01'); // First day of current month
$date_to = $_GET['date_to'] ?? date('Y-m-d'); // Today
$document_type_filter = $_GET['document_type'] ?? '';

// Get dashboard statistics
$stats = getDashboardStats();

// Get document types for filter
$document_types = getAllDocumentTypes();

// Get monthly request statistics for the current year
$current_year = date('Y');
$monthly_stats_sql = "
    SELECT 
        MONTH(submitted_at) as month,
        COUNT(*) as total_requests,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END) as processing,
        SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
        SUM(CASE WHEN status = 'released' THEN 1 ELSE 0 END) as released,
        SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
    FROM document_requests 
    WHERE YEAR(submitted_at) = ?
    GROUP BY MONTH(submitted_at)
    ORDER BY month
";
$monthly_stats = $db->fetchAll($monthly_stats_sql, [$current_year]);

// Get recent activity
$recent_activity_sql = "
    SELECT 
        'request' as type,
        dr.request_number as identifier,
        CONCAT(r.first_name, ' ', r.last_name) as resident_name,
        dt.name as document_type,
        dr.status,
        dr.submitted_at as activity_date
    FROM document_requests dr
    JOIN residents r ON dr.resident_id = r.id
    JOIN document_types dt ON dr.document_type_id = dt.id
    WHERE dr.submitted_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    ORDER BY dr.submitted_at DESC
    LIMIT 20
";
$recent_activity = $db->fetchAll($recent_activity_sql);

// Get top document types
$top_document_types_sql = "
    SELECT 
        dt.name,
        COUNT(*) as request_count,
        SUM(CASE WHEN dr.status = 'approved' THEN 1 ELSE 0 END) as approved_count,
        SUM(CASE WHEN dr.status = 'rejected' THEN 1 ELSE 0 END) as rejected_count
    FROM document_requests dr
    JOIN document_types dt ON dr.document_type_id = dt.id
    WHERE dr.submitted_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY dt.id, dt.name
    ORDER BY request_count DESC
    LIMIT 10
";
$top_document_types = $db->fetchAll($top_document_types_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports and Analytics - Admin Dashboard</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="../public/assets/css/style.css" rel="stylesheet">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-building me-2"></i>
                <?php echo getSystemSetting('barangay_name', 'Sample Barangay'); ?> - Admin
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">
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
                        <a class="nav-link active" href="reports.php">
                            <i class="fas fa-chart-bar me-1"></i>Reports
                        </a>
                    </li>
                </ul>
                
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" 
                           data-bs-toggle="dropdown">
                            <i class="fas fa-user-shield me-1"></i>
                            <?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?>
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
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">
                    <i class="fas fa-chart-bar me-2"></i>Reports and Analytics
                </h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <button type="button" class="btn btn-success" onclick="exportReport()">
                        <i class="fas fa-download me-1"></i>Export Report
                    </button>
                </div>
            </div>
            
            <!-- Report Filters -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
                            <label for="report" class="form-label">Report Type</label>
                            <select class="form-select" id="report" name="report">
                                <option value="overview" <?php echo $report_type === 'overview' ? 'selected' : ''; ?>>Overview</option>
                                <option value="requests" <?php echo $report_type === 'requests' ? 'selected' : ''; ?>>Request Analysis</option>
                                <option value="residents" <?php echo $report_type === 'residents' ? 'selected' : ''; ?>>Resident Statistics</option>
                                <option value="documents" <?php echo $report_type === 'documents' ? 'selected' : ''; ?>>Document Types</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="date_from" class="form-label">From Date</label>
                            <input type="date" class="form-control" id="date_from" name="date_from" 
                                   value="<?php echo $date_from; ?>">
                        </div>
                        <div class="col-md-2">
                            <label for="date_to" class="form-label">To Date</label>
                            <input type="date" class="form-control" id="date_to" name="date_to" 
                                   value="<?php echo $date_to; ?>">
                        </div>
                        <div class="col-md-3">
                            <label for="document_type" class="form-label">Document Type</label>
                            <select class="form-select" id="document_type" name="document_type">
                                <option value="">All Document Types</option>
                                <?php foreach ($document_types as $type): ?>
                                    <option value="<?php echo $type['id']; ?>" <?php echo $document_type_filter == $type['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($type['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search me-1"></i>Generate
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Overview Statistics -->
            <div class="row mb-4">
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-primary shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                        Total Requests
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['total_requests']; ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-file-alt fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-success shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                        Total Residents
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['total_residents']; ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-users fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-warning shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                        Pending Requests
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['pending_requests']; ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-clock fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-info shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                        Pending Residents
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['pending_residents']; ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-user-clock fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Charts Row -->
            <div class="row mb-4">
                <!-- Monthly Requests Chart -->
                <div class="col-xl-8 col-lg-7">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                            <h6 class="m-0 font-weight-bold text-primary">Monthly Request Trends (<?php echo $current_year; ?>)</h6>
                        </div>
                        <div class="card-body">
                            <div class="chart-area">
                                <canvas id="monthlyRequestsChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Request Status Pie Chart -->
                <div class="col-xl-4 col-lg-5">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                            <h6 class="m-0 font-weight-bold text-primary">Request Status Distribution</h6>
                        </div>
                        <div class="card-body">
                            <div class="chart-pie pt-4 pb-2">
                                <canvas id="requestStatusChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Data Tables Row -->
            <div class="row">
                <!-- Top Document Types -->
                <div class="col-xl-6 col-lg-6">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Top Document Types (Last 30 Days)</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>Document Type</th>
                                            <th>Total Requests</th>
                                            <th>Approved</th>
                                            <th>Rejected</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($top_document_types)): ?>
                                            <tr>
                                                <td colspan="4" class="text-center text-muted">No data available</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($top_document_types as $type): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($type['name']); ?></td>
                                                    <td><?php echo $type['request_count']; ?></td>
                                                    <td><?php echo $type['approved_count']; ?></td>
                                                    <td><?php echo $type['rejected_count']; ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Activity -->
                <div class="col-xl-6 col-lg-6">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Recent Activity (Last 30 Days)</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>Request #</th>
                                            <th>Resident</th>
                                            <th>Document Type</th>
                                            <th>Status</th>
                                            <th>Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($recent_activity)): ?>
                                            <tr>
                                                <td colspan="5" class="text-center text-muted">No recent activity</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($recent_activity as $activity): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($activity['identifier']); ?></td>
                                                    <td><?php echo htmlspecialchars($activity['resident_name']); ?></td>
                                                    <td><?php echo htmlspecialchars($activity['document_type']); ?></td>
                                                    <td><?php echo getStatusBadgeClass($activity['status']); ?></td>
                                                    <td><small><?php echo formatDate($activity['activity_date']); ?></small></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script src="../public/assets/js/app.js"></script>
    
    <script>
        // Ensure jQuery is available globally
        window.$ = window.jQuery = jQuery;
        
        $(document).ready(function() {
            // Initialize any jQuery-dependent functionality
            console.log('jQuery loaded successfully');
        });
        
        // Monthly Requests Chart
        const monthlyCtx = document.getElementById('monthlyRequestsChart').getContext('2d');
        const monthlyChart = new Chart(monthlyCtx, {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                datasets: [{
                    label: 'Total Requests',
                    data: <?php echo json_encode(array_fill(0, 12, 0)); ?>,
                    borderColor: 'rgb(75, 192, 192)',
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
        
        // Request Status Pie Chart
        const statusCtx = document.getElementById('requestStatusChart').getContext('2d');
        const statusChart = new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: ['Pending', 'Processing', 'Approved', 'Released', 'Rejected'],
                datasets: [{
                    data: [
                        <?php echo $stats['pending_requests']; ?>,
                        <?php echo $stats['processing_requests']; ?>,
                        <?php echo $stats['approved_requests']; ?>,
                        <?php echo $stats['released_requests']; ?>,
                        <?php echo $stats['rejected_requests']; ?>
                    ],
                    backgroundColor: [
                        '#f6c23e',
                        '#36b9cc',
                        '#1cc88a',
                        '#4e73df',
                        '#e74a3b'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
        
        // Export report function
        function exportReport() {
            alert('Export functionality will be implemented');
            // TODO: Implement export functionality
        }
    </script>
</body>
</html>
