<?php
/**
 * Admin - Document Templates Management
 * Manage document templates and types in the system
 */

require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Ensure admin access
$auth->requireAdmin();

$success = '';
$error = '';

// Get filter parameters
$document_type_filter = $_GET['document_type'] ?? '';
$search = $_GET['search'] ?? '';

// Build query for templates
$where_conditions = ["1=1"];
$params = [];

if ($document_type_filter) {
    $where_conditions[] = "dt.document_type_id = ?";
    $params[] = $document_type_filter;
}

if ($search) {
    $where_conditions[] = "(dt.template_name LIKE ? OR dt.template_content LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param]);
}

$where_clause = implode(' AND ', $where_conditions);

// Get total count for pagination
$count_sql = "
    SELECT COUNT(*) as total 
    FROM document_templates dt
    JOIN document_types dty ON dt.document_type_id = dty.id
    WHERE $where_clause
";
$total_templates = $db->fetch($count_sql, $params)['total'];

// Pagination
$page = max(1, $_GET['page'] ?? 1);
$per_page = 20;
$total_pages = ceil($total_templates / $per_page);
$offset = ($page - 1) * $per_page;

// Get templates
$templates_sql = "
    SELECT dt.*, dty.name as document_type
    FROM document_templates dt
    JOIN document_types dty ON dt.document_type_id = dty.id
    WHERE $where_clause
    ORDER BY dt.created_at DESC 
    LIMIT ? OFFSET ?
";
$params[] = $per_page;
$params[] = $offset;
$templates = $db->fetchAll($templates_sql, $params);

// Get statistics
$stats_sql = "
    SELECT 
        COUNT(*) as total_templates,
        COUNT(DISTINCT document_type_id) as document_types_with_templates,
        SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_templates
    FROM document_templates
";
$stats = $db->fetch($stats_sql);

// Get document types for filter and stats
$document_types = getAllDocumentTypes();
$total_document_types = count($document_types);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document Templates Management - Admin Dashboard</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
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
                        <a class="nav-link active" href="documents.php">
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
                    <i class="fas fa-file me-2"></i>Document Templates Management
                </h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <button type="button" class="btn btn-primary" onclick="addTemplate()">
                        <i class="fas fa-plus me-1"></i>Add Template
                    </button>
                </div>
            </div>
            
            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-primary shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                        Total Templates
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['total_templates']; ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-file fa-2x text-gray-300"></i>
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
                                        Active Templates
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['active_templates']; ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-check-circle fa-2x text-gray-300"></i>
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
                                        Document Types
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_document_types; ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-list fa-2x text-gray-300"></i>
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
                                        Types with Templates
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['document_types_with_templates']; ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-clipboard-list fa-2x text-gray-300"></i>
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
                        <div class="col-md-6">
                            <label for="search" class="form-label">Search</label>
                            <input type="text" class="form-control" id="search" name="search" 
                                   value="<?php echo htmlspecialchars($search); ?>" 
                                   placeholder="Search by template name or content">
                        </div>
                        <div class="col-md-4">
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
                                    <i class="fas fa-search me-1"></i>Filter
                                </button>
                                <a href="documents.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-times me-1"></i>Clear
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Templates Table -->
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-list me-2"></i>Document Templates List
                        </h5>
                        <span class="badge bg-primary"><?php echo $total_templates; ?> total</span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Template Name</th>
                                    <th>Document Type</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                    <th>Updated</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($templates)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">
                                            <i class="fas fa-file-alt fa-3x mb-3"></i>
                                            <p>No document templates found.</p>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($templates as $template): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($template['template_name']); ?></strong>
                                            </td>
                                            <td>
                                                <?php echo htmlspecialchars($template['document_type']); ?>
                                            </td>
                                            <td>
                                                <?php if ($template['is_active']): ?>
                                                    <span class="badge bg-success">Active</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Inactive</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <small><?php echo formatDate($template['created_at']); ?></small>
                                            </td>
                                            <td>
                                                <small><?php echo formatDate($template['updated_at']); ?></small>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm" role="group">
                                                    <button type="button" class="btn btn-outline-primary" 
                                                            onclick="viewTemplate(<?php echo $template['id']; ?>)">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-outline-secondary" 
                                                            onclick="editTemplate(<?php echo $template['id']; ?>)">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-outline-danger" 
                                                            onclick="deleteTemplate(<?php echo $template['id']; ?>)">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <nav aria-label="Templates pagination">
                            <ul class="pagination justify-content-center">
                                <?php if ($page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?php echo $page - 1; ?>&document_type=<?php echo urlencode($document_type_filter); ?>&search=<?php echo urlencode($search); ?>">
                                            Previous
                                        </a>
                                    </li>
                                <?php endif; ?>
                                
                                <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                    <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $i; ?>&document_type=<?php echo urlencode($document_type_filter); ?>&search=<?php echo urlencode($search); ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>
                                
                                <?php if ($page < $total_pages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?php echo $page + 1; ?>&document_type=<?php echo urlencode($document_type_filter); ?>&search=<?php echo urlencode($search); ?>">
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
        
        // Rest of the script
        // Add new template
        function addTemplate() {
            alert('Add new template functionality will be implemented');
            // TODO: Implement add template functionality
        }
        
        // View template details
        function viewTemplate(templateId) {
            alert('View template details for ID: ' + templateId);
            // TODO: Implement view template functionality
        }
        
        // Edit template
        function editTemplate(templateId) {
            alert('Edit template for ID: ' + templateId);
            // TODO: Implement edit template functionality
        }
        
        // Delete template
        function deleteTemplate(templateId) {
            if (confirm('Are you sure you want to delete this template?')) {
                alert('Delete template for ID: ' + templateId);
                // TODO: Implement delete template functionality
            }
        }
    </script>
</body>
</html>
