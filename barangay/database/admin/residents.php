<?php
/**
 * Admin - Residents Management
 * Manage all residents in the system
 */

require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Ensure admin access
$auth->requireAdmin();

$success = '';
$error = '';

// Handle bulk actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_action'])) {
    $action = $_POST['bulk_action'];
    $selected_residents = $_POST['selected_residents'] ?? [];
    
    if (!empty($selected_residents)) {
        $placeholders = str_repeat('?,', count($selected_residents) - 1) . '?';
        
        switch ($action) {
            case 'approve':
                $db->query("UPDATE users SET status = 'active' WHERE id IN ($placeholders) AND role = 'resident'", $selected_residents);
                $success = count($selected_residents) . ' resident(s) approved successfully.';
                break;
                
            case 'reject':
                $db->query("UPDATE users SET status = 'inactive' WHERE id IN ($placeholders) AND role = 'resident'", $selected_residents);
                $success = count($selected_residents) . ' resident(s) rejected successfully.';
                break;
                
            case 'delete':
                // First delete related records
                $db->query("DELETE FROM document_requests WHERE user_id IN ($placeholders)", $selected_residents);
                
                $db->query("DELETE FROM residents WHERE user_id IN ($placeholders)", $selected_residents);
                
                $db->query("DELETE FROM users WHERE id IN ($placeholders) AND role = 'resident'", $selected_residents);
                $success = count($selected_residents) . ' resident(s) deleted successfully.';
                break;
        }
    }
}

// Get filter parameters
$status_filter = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';

// Build query
$where_conditions = ["u.role = 'resident'"];
$params = [];

if ($status_filter) {
    $where_conditions[] = "u.status = ?";
    $params[] = $status_filter;
}

if ($search) {
    $where_conditions[] = "(u.username LIKE ? OR u.email LIKE ? OR r.first_name LIKE ? OR r.last_name LIKE ? OR r.contact_number LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param, $search_param]);
}

$where_clause = implode(' AND ', $where_conditions);

// Get total count for pagination
$count_sql = "
    SELECT COUNT(*) as total 
    FROM users u 
    LEFT JOIN residents r ON u.id = r.user_id 
    WHERE $where_clause
";
$total_residents = $db->fetch($count_sql, $params)['total'];

// Pagination
$page = max(1, $_GET['page'] ?? 1);
$per_page = 20;
$total_pages = ceil($total_residents / $per_page);
$offset = ($page - 1) * $per_page;

// Get residents
$residents_sql = "
    SELECT u.*, r.first_name, r.last_name, r.contact_number, r.address, r.created_at as registered_date
    FROM users u 
    LEFT JOIN residents r ON u.id = r.user_id 
    WHERE $where_clause
    ORDER BY u.created_at DESC 
    LIMIT ? OFFSET ?
";
$params[] = $per_page;
$params[] = $offset;
$residents = $db->fetchAll($residents_sql, $params);

// Get statistics
$stats_sql = "
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive
    FROM users 
    WHERE role = 'resident'
";
$stats = $db->fetch($stats_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Residents Management - Admin Dashboard</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- DataTables -->
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="../public/assets/css/style.css" rel="stylesheet">
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
                        <a class="nav-link active" href="residents.php">
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
                        <i class="fas fa-users me-2"></i>Residents Management
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="exportResidents()">
                                <i class="fas fa-download me-1"></i>Export
                            </button>
                        </div>
                    </div>
                </div>
                
                <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i>
                        <?php echo $success; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-primary shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                            Total Residents
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['total']; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-users fa-2x text-gray-300"></i>
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
                                            Active Residents
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['active']; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-check-circle fa-2x text-gray-300"></i>
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
                                            Pending Approval
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['pending']; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-clock fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-danger shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                            Inactive Residents
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['inactive']; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-times-circle fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Filters and Search -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-4">
                                <label for="search" class="form-label">Search</label>
                                <input type="text" class="form-control" id="search" name="search" 
                                       value="<?php echo htmlspecialchars($search); ?>" 
                                       placeholder="Search by name, email, or contact number">
                            </div>
                            <div class="col-md-3">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="">All Status</option>
                                    <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                                    <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="inactive" <?php echo $status_filter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">&nbsp;</label>
                                <div>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-search me-1"></i>Filter
                                    </button>
                                    <a href="residents.php" class="btn btn-outline-secondary">
                                        <i class="fas fa-times me-1"></i>Clear
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Residents Table -->
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">
                                <i class="fas fa-list me-2"></i>Residents List
                            </h5>
                            <span class="badge bg-primary"><?php echo $total_residents; ?> total</span>
                        </div>
                    </div>
                    <div class="card-body">
                        <form method="POST" id="residentsForm">
                            <div class="mb-3">
                                <div class="btn-group" role="group">
                                    <button type="submit" name="bulk_action" value="approve" class="btn btn-success btn-sm" 
                                            onclick="return confirmBulkAction('approve')">
                                        <i class="fas fa-check me-1"></i>Approve Selected
                                    </button>
                                    <button type="submit" name="bulk_action" value="reject" class="btn btn-warning btn-sm"
                                            onclick="return confirmBulkAction('reject')">
                                        <i class="fas fa-times me-1"></i>Reject Selected
                                    </button>
                                    <button type="submit" name="bulk_action" value="delete" class="btn btn-danger btn-sm"
                                            onclick="return confirmBulkAction('delete')">
                                        <i class="fas fa-trash me-1"></i>Delete Selected
                                    </button>
                                </div>
                            </div>
                            
                            <div class="table-responsive">
                                <table class="table table-striped table-hover" id="residentsTable">
                                    <thead>
                                        <tr>
                                            <th>
                                                <input type="checkbox" id="selectAll" class="form-check-input">
                                            </th>
                                            <th>Name</th>
                                            <th>Username/Email</th>
                                            <th>Contact</th>
                                            <th>Address</th>
                                            <th>Status</th>
                                            <th>Registered</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($residents as $resident): ?>
                                            <tr>
                                                <td>
                                                    <input type="checkbox" name="selected_residents[]" 
                                                           value="<?php echo $resident['id']; ?>" 
                                                           class="form-check-input resident-checkbox">
                                                </td>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($resident['first_name'] . ' ' . $resident['last_name']); ?></strong>
                                                </td>
                                                <td>
                                                    <div><?php echo htmlspecialchars($resident['username']); ?></div>
                                                    <small class="text-muted"><?php echo htmlspecialchars($resident['email']); ?></small>
                                                </td>
                                                <td><?php echo htmlspecialchars($resident['contact_number'] ?? 'N/A'); ?></td>
                                                <td>
                                                    <small><?php echo htmlspecialchars($resident['address'] ?? 'N/A'); ?></small>
                                                </td>
                                                <td>
                                                    <?php echo getStatusBadgeClass($resident['status']); ?>
                                                </td>
                                                <td>
                                                    <small><?php echo formatDate($resident['registered_date']); ?></small>
                                                </td>
                                                <td>
                                                    <div class="btn-group btn-group-sm" role="group">
                                                        <button type="button" class="btn btn-outline-primary" 
                                                                onclick="viewResident(<?php echo $resident['id']; ?>)">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-outline-secondary" 
                                                                onclick="editResident(<?php echo $resident['id']; ?>)">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <?php if ($resident['status'] === 'pending'): ?>
                                                            <button type="button" class="btn btn-outline-success" 
                                                                    onclick="approveResident(<?php echo $resident['id']; ?>)">
                                                                <i class="fas fa-check"></i>
                                                            </button>
                                                            <button type="button" class="btn btn-outline-danger" 
                                                                    onclick="rejectResident(<?php echo $resident['id']; ?>)">
                                                                <i class="fas fa-times"></i>
                                                            </button>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </form>
                        
                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                            <nav aria-label="Residents pagination">
                                <ul class="pagination justify-content-center">
                                    <?php if ($page > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?php echo $page - 1; ?>&status=<?php echo urlencode($status_filter); ?>&search=<?php echo urlencode($search); ?>">
                                                Previous
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                    
                                    <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo $i; ?>&status=<?php echo urlencode($status_filter); ?>&search=<?php echo urlencode($search); ?>">
                                                <?php echo $i; ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>
                                    
                                    <?php if ($page < $total_pages): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?php echo $page + 1; ?>&status=<?php echo urlencode($status_filter); ?>&search=<?php echo urlencode($search); ?>">
                                                Next
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                        <?php endif; ?>
                    </div>
                </div>
        </div>
    </div>
    
    <!-- View Resident Modal -->
    <div class="modal fade" id="viewResidentModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Resident Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="viewResidentContent">
                    <!-- Content will be loaded here -->
                </div>
            </div>
        </div>
    </div>
    
    <!-- Edit Resident Modal -->
    <div class="modal fade" id="editResidentModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Resident</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="editResidentContent">
                    <!-- Content will be loaded here -->
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- DataTables -->
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <!-- Custom JS -->
    <script src="../public/assets/js/app.js"></script>
    
    <script>
        // Select all functionality
        document.getElementById('selectAll').addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.resident-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
        });
        
        // Update select all when individual checkboxes change
        document.querySelectorAll('.resident-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const allCheckboxes = document.querySelectorAll('.resident-checkbox');
                const checkedCheckboxes = document.querySelectorAll('.resident-checkbox:checked');
                document.getElementById('selectAll').checked = allCheckboxes.length === checkedCheckboxes.length;
            });
        });
        
        // Confirm bulk actions
        function confirmBulkAction(action) {
            const checkedBoxes = document.querySelectorAll('.resident-checkbox:checked');
            if (checkedBoxes.length === 0) {
                alert('Please select at least one resident.');
                return false;
            }
            
            const actionText = action === 'approve' ? 'approve' : action === 'reject' ? 'reject' : 'delete';
            return confirm(`Are you sure you want to ${actionText} ${checkedBoxes.length} selected resident(s)?`);
        }
        
        // View resident details
        function viewResident(userId) {
            fetch(`../api/admin_api.php?action=view_resident&user_id=${userId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('viewResidentContent').innerHTML = data.html;
                        new bootstrap.Modal(document.getElementById('viewResidentModal')).show();
                    } else {
                        alert('Error loading resident details: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error loading resident details.');
                });
        }
        
        // Edit resident
        function editResident(userId) {
            fetch(`../api/admin_api.php?action=edit_resident&user_id=${userId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('editResidentContent').innerHTML = data.html;
                        new bootstrap.Modal(document.getElementById('editResidentModal')).show();
                    } else {
                        alert('Error loading edit form: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error loading edit form.');
                });
        }
        
        // Approve resident
        function approveResident(userId) {
            if (confirm('Are you sure you want to approve this resident?')) {
                fetch('../api/admin_api.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=approve_resident&user_id=${userId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error approving resident.');
                });
            }
        }
        
        // Reject resident
        function rejectResident(userId) {
            if (confirm('Are you sure you want to reject this resident?')) {
                fetch('../api/admin_api.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=reject_resident&user_id=${userId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error rejecting resident.');
                });
            }
        }
        
        // Export residents
        function exportResidents() {
            const search = document.getElementById('search').value;
            const status = document.getElementById('status').value;
            const url = `../api/admin_api.php?action=export_residents&search=${encodeURIComponent(search)}&status=${encodeURIComponent(status)}`;
            window.open(url, '_blank');
        }
    </script>
</body>
</html>
