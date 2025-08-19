<?php
/**
 * Admin - Document Requests Management
 * Manage all document requests in the system
 */

require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Ensure admin access
$auth->requireAdmin();

$success = '';
$error = '';

// Get filter parameters
$status_filter = $_GET['status'] ?? '';
$document_type_filter = $_GET['document_type'] ?? '';
$search = $_GET['search'] ?? '';

// Build query
$where_conditions = ["1=1"];
$params = [];

if ($status_filter) {
    $where_conditions[] = "dr.status = ?";
    $params[] = $status_filter;
}

if ($document_type_filter) {
    $where_conditions[] = "dr.document_type_id = ?";
    $params[] = $document_type_filter;
}

if ($search) {
    $where_conditions[] = "(dr.request_number LIKE ? OR r.first_name LIKE ? OR r.last_name LIKE ? OR r.contact_number LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
}

$where_clause = implode(' AND ', $where_conditions);

// Get total count for pagination
$count_sql = "
    SELECT COUNT(*) as total 
    FROM document_requests dr
    LEFT JOIN residents r ON dr.resident_id = r.id
    WHERE $where_clause
";
$total_requests = $db->fetch($count_sql, $params)['total'];

// Pagination
$page = max(1, $_GET['page'] ?? 1);
$per_page = 20;
$total_pages = ceil($total_requests / $per_page);
$offset = ($page - 1) * $per_page;

// Get requests
$requests_sql = "
    SELECT dr.*, r.first_name, r.last_name, r.contact_number, r.address, 
                       dt.name as document_type, dt.processing_fee, dt.processing_days
    FROM document_requests dr
    LEFT JOIN residents r ON dr.resident_id = r.id
    LEFT JOIN document_types dt ON dr.document_type_id = dt.id
    WHERE $where_clause
    ORDER BY dr.submitted_at DESC 
    LIMIT ? OFFSET ?
";
$params[] = $per_page;
$params[] = $offset;
$requests = $db->fetchAll($requests_sql, $params);

// Get statistics
$stats_sql = "
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END) as processing,
        SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
        SUM(CASE WHEN status = 'released' THEN 1 ELSE 0 END) as released,
        SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
    FROM document_requests
";
$stats = $db->fetch($stats_sql);

// Get document types for filter
$document_types = getAllDocumentTypes();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document Requests Management - Admin Dashboard</title>
    
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
                        <a class="nav-link active" href="requests.php">
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
                    <i class="fas fa-file-alt me-2"></i>Document Requests Management
                </h1>
                <button type="button" class="btn btn-info btn-sm" onclick="testUpdateStatus()">
                    <i class="fas fa-cog me-1"></i>Test JS
                </button>
            </div>
            
            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-xl-2 col-md-4 mb-4">
                    <div class="card border-left-primary shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                        Total Requests
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['total']; ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-file-alt fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-xl-2 col-md-4 mb-4">
                    <div class="card border-left-warning shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                        Pending
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
                
                <div class="col-xl-2 col-md-4 mb-4">
                    <div class="card border-left-info shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                        Processing
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['processing']; ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-cogs fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-xl-2 col-md-4 mb-4">
                    <div class="card border-left-success shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                        Approved
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['approved']; ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-xl-2 col-md-4 mb-4">
                    <div class="card border-left-primary shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                        Released
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['released']; ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-handshake fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-xl-2 col-md-4 mb-4">
                    <div class="card border-left-danger shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                        Rejected
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['rejected']; ?></div>
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
                                   placeholder="Search by request number, name, or contact">
                        </div>
                        <div class="col-md-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="">All Status</option>
                                <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="processing" <?php echo $status_filter === 'processing' ? 'selected' : ''; ?>>Processing</option>
                                <option value="approved" <?php echo $status_filter === 'approved' ? 'selected' : ''; ?>>Approved</option>
                                <option value="released" <?php echo $status_filter === 'released' ? 'selected' : ''; ?>>Released</option>
                                <option value="rejected" <?php echo $status_filter === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="document_type" class="form-label">Document Type</label>
                            <select class="form-select" id="document_type" name="document_type">
                                <option value="">All Types</option>
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
                                <a href="requests.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-times me-1"></i>Clear
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Requests Table -->
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-list me-2"></i>Document Requests List
                        </h5>
                        <span class="badge bg-primary"><?php echo $total_requests; ?> total</span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="alert alert-info mb-3">
                        <h6 class="alert-heading">
                            <i class="fas fa-info-circle me-2"></i>Actions Guide
                        </h6>
                        <ul class="mb-0">
                            <li><strong>Eye Icon:</strong> View request details</li>
                            <li><strong>Edit Icon:</strong> Update request status</li>
                            <li><strong>Upload Icon:</strong> Upload document file (PDF/DOC/DOCX)</li>
                            <li><strong>Download Icon:</strong> Mark document as released (for approved requests)</li>
                        </ul>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Request #</th>
                                    <th>Resident</th>
                                    <th>Document Type</th>
                                    <th>Purpose</th>
                                    <th>Status</th>
                                    <th>Submitted</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($requests)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center text-muted">
                                            <i class="fas fa-inbox fa-3x mb-3"></i>
                                            <p>No document requests found.</p>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($requests as $request): ?>
                                                                                 <tr data-request-id="<?php echo $request['id']; ?>">
                                             <td>
                                                 <strong><?php echo htmlspecialchars($request['request_number']); ?></strong>
                                             </td>
                                            <td>
                                                <div><?php echo htmlspecialchars($request['first_name'] . ' ' . $request['last_name']); ?></div>
                                                <small class="text-muted"><?php echo htmlspecialchars($request['contact_number'] ?? 'N/A'); ?></small>
                                            </td>
                                            <td>
                                                <div><?php echo htmlspecialchars($request['document_type']); ?></div>
                                                <small class="text-muted">₱<?php echo number_format($request['processing_fee'], 2); ?></small>
                                            </td>
                                            <td>
                                                <small><?php echo htmlspecialchars($request['purpose']); ?></small>
                                            </td>
                                                                                         <td>
                                                 <span class="<?php echo getStatusBadgeClass($request['status']); ?>">
                                                     <?php echo getStatusText($request['status']); ?>
                                                 </span>
                                             </td>
                                            <td>
                                                <small><?php echo formatDate($request['submitted_at']); ?></small>
                                            </td>
                                            <td>
                                                                                                 <div class="btn-group btn-group-sm" role="group">
                                                     <button type="button" class="btn btn-outline-primary" 
                                                             onclick="viewRequest(<?php echo $request['id']; ?>)">
                                                         <i class="fas fa-eye"></i>
                                                     </button>
                                                     <button type="button" class="btn btn-outline-secondary" 
                                                             onclick="console.log('Button clicked for request:', <?php echo $request['id']; ?>); updateStatus(<?php echo $request['id']; ?>);" 
                                                             title="Update Status">
                                                         <i class="fas fa-edit"></i>
                                                     </button>
                                                     <?php if ($request['status'] === 'approved'): ?>
                                                         <button type="button" class="btn btn-outline-success" 
                                                                 onclick="releaseDocument(<?php echo $request['id']; ?>)">
                                                             <i class="fas fa-download"></i>
                                                         </button>
                                                     <?php endif; ?>
                                                     
                                                     <!-- Upload Document Button -->
                                                     <button type="button" class="btn btn-outline-info" 
                                                             onclick="uploadDocument(<?php echo $request['id']; ?>, '<?php echo htmlspecialchars($request['request_number']); ?>')"
                                                             title="Upload Document File">
                                                         <i class="fas fa-upload"></i>
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
                        <nav aria-label="Requests pagination">
                            <ul class="pagination justify-content-center">
                                <?php if ($page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?php echo $page - 1; ?>&status=<?php echo urlencode($status_filter); ?>&document_type=<?php echo urlencode($document_type_filter); ?>&search=<?php echo urlencode($search); ?>">
                                            Previous
                                        </a>
                                    </li>
                                <?php endif; ?>
                                
                                <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                    <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $i; ?>&status=<?php echo urlencode($status_filter); ?>&document_type=<?php echo urlencode($document_type_filter); ?>&search=<?php echo urlencode($search); ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>
                                
                                <?php if ($page < $total_pages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?php echo $page + 1; ?>&status=<?php echo urlencode($status_filter); ?>&document_type=<?php echo urlencode($document_type_filter); ?>&search=<?php echo urlencode($search); ?>">
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
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script src="../public/assets/js/app.js"></script>
    
    <script>
        // Test function to verify JavaScript is working
        function testUpdateStatus() {
            console.log('Test function called');
            alert('JavaScript is working! Test function called successfully.');
        }
        
        // Ensure jQuery is available globally
        window.$ = window.jQuery = jQuery;
        
        $(document).ready(function() {
            // Initialize any jQuery-dependent functionality
            console.log('jQuery loaded successfully');
        });
        
        // Rest of the script
         // View request details
         function viewRequest(requestId) {
             // Create modal for viewing request details
             const modal = `
                 <div class="modal fade" id="viewRequestModal" tabindex="-1">
                     <div class="modal-dialog modal-lg">
                         <div class="modal-content">
                             <div class="modal-header">
                                 <h5 class="modal-title">Request Details</h5>
                                 <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                             </div>
                             <div class="modal-body" id="requestDetailsContent">
                                 <div class="text-center">
                                     <div class="spinner-border" role="status">
                                         <span class="visually-hidden">Loading...</span>
                                     </div>
                                 </div>
                             </div>
                             <div class="modal-footer">
                                 <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                             </div>
                         </div>
                     </div>
                 </div>
             `;
             
             // Remove existing modal if any
             document.getElementById('viewRequestModal')?.remove();
             
             // Add modal to body
             document.body.insertAdjacentHTML('beforeend', modal);
             
             // Show modal
             const modalElement = new bootstrap.Modal(document.getElementById('viewRequestModal'));
             modalElement.show();
             
             // Load request details via AJAX
             fetch(`../api/admin_api.php`, {
                 method: 'POST',
                 headers: {
                     'Content-Type': 'application/x-www-form-urlencoded',
                 },
                 body: `action=get_request_details&request_id=${requestId}`
             })
             .then(response => response.json())
             .then(data => {
                 if (data.success) {
                     const request = data.request;
                     document.getElementById('requestDetailsContent').innerHTML = `
                         <div class="row">
                             <div class="col-md-6">
                                 <h6>Request Information</h6>
                                 <p><strong>Request Number:</strong> ${request.request_number}</p>
                                 <p><strong>Document Type:</strong> ${request.document_type}</p>
                                 <p><strong>Purpose:</strong> ${request.purpose}</p>
                                 <p><strong>Status:</strong> <span class="badge ${getStatusBadgeClass(request.status)}">${getStatusText(request.status)}</span></p>
                                 <p><strong>Submitted:</strong> ${formatDate(request.submitted_at)}</p>
                                 <p><strong>Processing Fee:</strong> ₱${parseFloat(request.processing_fee).toFixed(2)}</p>
                             </div>
                             <div class="col-md-6">
                                 <h6>Resident Information</h6>
                                 <p><strong>Name:</strong> ${request.first_name} ${request.last_name}</p>
                                 <p><strong>Contact:</strong> ${request.contact_number || 'N/A'}</p>
                                 <p><strong>Address:</strong> ${request.address}</p>
                             </div>
                         </div>
                         ${request.notes ? `<div class="mt-3"><h6>Additional Notes</h6><p>${request.notes}</p></div>` : ''}
                     `;
                 } else {
                     document.getElementById('requestDetailsContent').innerHTML = `
                         <div class="alert alert-danger">
                             Error loading request details: ${data.message}
                         </div>
                     `;
                 }
             })
             .catch(error => {
                 document.getElementById('requestDetailsContent').innerHTML = `
                     <div class="alert alert-danger">
                         Error: ${error.message}
                     </div>
                 `;
             });
         }
         
         // Update request status
         function updateStatus(requestId) {
             try {
                 console.log('updateStatus called with requestId:', requestId);
                 const statusOptions = ['pending', 'processing', 'approved', 'released', 'rejected'];
                 const row = document.querySelector(`tr[data-request-id="${requestId}"]`);
                 const statusBadge = row.querySelector('.badge');
                 const currentStatus = statusBadge ? statusBadge.textContent.trim().toLowerCase() : 'pending';
                 console.log('Current status:', currentStatus);
             
             const modal = `
                 <div class="modal fade" id="updateStatusModal" tabindex="-1" aria-labelledby="updateStatusModalLabel" aria-hidden="true">
                     <div class="modal-dialog">
                         <div class="modal-content">
                             <div class="modal-header">
                                 <h5 class="modal-title" id="updateStatusModalLabel">Update Request Status</h5>
                                 <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                             </div>
                             <div class="modal-body">
                                 <form id="updateStatusForm">
                                     <div class="mb-3">
                                         <label for="newStatus" class="form-label">New Status</label>
                                         <select class="form-select" id="newStatus" name="newStatus" required>
                                             ${statusOptions.map(status => 
                                                 `<option value="${status}" ${status === currentStatus ? 'selected' : ''}>${status.charAt(0).toUpperCase() + status.slice(1)}</option>`
                                             ).join('')}
                                         </select>
                                     </div>
                                     <div class="mb-3">
                                         <label for="statusNotes" class="form-label">Notes (Optional)</label>
                                         <textarea class="form-control" id="statusNotes" name="statusNotes" rows="3" placeholder="Add any notes about this status change..."></textarea>
                                     </div>
                                 </form>
                             </div>
                             <div class="modal-footer">
                                 <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                 <button type="button" class="btn btn-primary" onclick="submitStatusUpdate(${requestId})">Update Status</button>
                             </div>
                         </div>
                     </div>
                 </div>
             `;
             
             // Remove existing modal if any
             const existingModal = document.getElementById('updateStatusModal');
             if (existingModal) {
                 console.log('Removing existing modal');
                 existingModal.remove();
             }
             
             // Add modal to body
             document.body.insertAdjacentHTML('beforeend', modal);
             console.log('Modal added to body');
             console.log('Modal HTML created:', modal);
             
             // Show modal
             console.log('Creating Bootstrap modal...');
             const modalElement = new bootstrap.Modal(document.getElementById('updateStatusModal'));
             console.log('Modal element:', modalElement);
             
             // Check if modal element exists
             const modalDomElement = document.getElementById('updateStatusModal');
             console.log('Modal DOM element:', modalDomElement);
             
             if (modalDomElement) {
                 modalElement.show();
                 console.log('Modal show() called');
             } else {
                 console.error('Modal DOM element not found!');
                 alert('Error: Modal element not found');
             }
             } catch (error) {
                 console.error('Error in updateStatus:', error);
                 alert('Error opening status update modal: ' + error.message);
             }
         }
         
         // Submit status update
         function submitStatusUpdate(requestId) {
             console.log('submitStatusUpdate called with requestId:', requestId);
             const newStatus = document.getElementById('newStatus').value;
             const notes = document.getElementById('statusNotes').value;
             console.log('New status:', newStatus, 'Notes:', notes);
             
             fetch(`../api/admin_api.php`, {
                 method: 'POST',
                 headers: {
                     'Content-Type': 'application/x-www-form-urlencoded',
                 },
                 body: `action=update_request_status&request_id=${requestId}&new_status=${newStatus}&notes=${encodeURIComponent(notes)}`
             })
             .then(response => {
                 console.log('Response received:', response);
                 return response.json();
             })
             .then(data => {
                 console.log('Data received:', data);
                 if (data.success) {
                     // Close modal
                     bootstrap.Modal.getInstance(document.getElementById('updateStatusModal')).hide();
                     
                     // Show success message
                     showAlert('Status updated successfully!', 'success');
                     
                     // Reload page to reflect changes
                     setTimeout(() => location.reload(), 1500);
                 } else {
                     showAlert('Error updating status: ' + data.message, 'danger');
                 }
             })
             .catch(error => {
                 console.error('Fetch error:', error);
                 showAlert('Error: ' + error.message, 'danger');
             });
         }
         
         // Release document (for approved requests)
         function releaseDocument(requestId) {
             if (confirm('Are you sure you want to mark this document as released? This will allow the resident to download it.')) {
                 fetch(`../api/admin_api.php`, {
                     method: 'POST',
                     headers: {
                         'Content-Type': 'application/x-www-form-urlencoded',
                     },
                     body: `action=release_document&request_id=${requestId}`
                 })
                 .then(response => response.json())
                 .then(data => {
                     if (data.success) {
                         showAlert('Document marked as released successfully!', 'success');
                         setTimeout(() => location.reload(), 1500);
                     } else {
                         showAlert('Error releasing document: ' + data.message, 'danger');
                     }
                 })
                 .catch(error => {
                     showAlert('Error: ' + error.message, 'danger');
                 });
             }
         }
         
         // Upload document function
         function uploadDocument(requestId, requestNumber) {
             // Create and show upload modal
             const modal = `
                 <div class="modal fade" id="uploadDocumentModal" tabindex="-1">
                     <div class="modal-dialog">
                         <div class="modal-content">
                             <div class="modal-header">
                                 <h5 class="modal-title">
                                     <i class="fas fa-upload me-2"></i>Upload Document
                                 </h5>
                                 <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                             </div>
                             <div class="modal-body">
                                 <p><strong>Request Number:</strong> ${requestNumber}</p>
                                 <form id="uploadDocumentForm" enctype="multipart/form-data">
                                     <input type="hidden" name="request_id" value="${requestId}">
                                     <div class="mb-3">
                                         <label for="document_file" class="form-label">Select Document File</label>
                                         <input type="file" class="form-control" id="document_file" name="document_file" 
                                                accept=".pdf,.doc,.docx" required>
                                         <div class="form-text">Accepted formats: PDF, DOC, DOCX (max 5MB)</div>
                                     </div>
                                 </form>
                             </div>
                             <div class="modal-footer">
                                 <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                 <button type="button" class="btn btn-primary" onclick="submitDocumentUpload()">
                                     <i class="fas fa-upload me-2"></i>Upload Document
                                 </button>
                             </div>
                         </div>
                     </div>
                 </div>
             `;
             
             // Remove existing modal if any
             document.getElementById('uploadDocumentModal')?.remove();
             
             // Add modal to body
             document.body.insertAdjacentHTML('beforeend', modal);
             
             // Show modal
             const modalElement = new bootstrap.Modal(document.getElementById('uploadDocumentModal'));
             modalElement.show();
         }
         
         // Submit document upload
         function submitDocumentUpload() {
             const form = document.getElementById('uploadDocumentForm');
             const formData = new FormData(form);
             formData.append('action', 'upload_document');
             
             // Show loading state
             const submitBtn = document.querySelector('#uploadDocumentModal .btn-primary');
             const originalContent = submitBtn.innerHTML;
             submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Uploading...';
             submitBtn.disabled = true;
             
             fetch('../api/admin_api.php', {
                 method: 'POST',
                 body: formData
             })
             .then(response => response.json())
             .then(data => {
                 if (data.success) {
                     showAlert('Document uploaded successfully!', 'success');
                     bootstrap.Modal.getInstance(document.getElementById('uploadDocumentModal')).hide();
                     setTimeout(() => location.reload(), 1500);
                 } else {
                     showAlert('Error uploading document: ' + data.message, 'danger');
                 }
             })
             .catch(error => {
                 console.error('Upload error:', error);
                 showAlert('Error uploading document. Please try again.', 'danger');
             })
             .finally(() => {
                 // Restore button
                 submitBtn.innerHTML = originalContent;
                 submitBtn.disabled = false;
             });
         }
         
         // Helper function to show alerts
         function showAlert(message, type) {
             const alertDiv = document.createElement('div');
             alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
             alertDiv.innerHTML = `
                 ${message}
                 <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
             `;
             
             // Insert at the top of the main content
             const mainContent = document.querySelector('.main-content .container');
             mainContent.insertBefore(alertDiv, mainContent.firstChild);
             
             // Auto-remove after 5 seconds
             setTimeout(() => alertDiv.remove(), 5000);
         }
         
         // Helper functions for status display - these will be replaced by PHP functions
         function getStatusBadgeClass(status) {
             const statusClasses = {
                 'pending': 'badge bg-warning',
                 'processing': 'badge bg-info',
                 'approved': 'badge bg-success',
                 'rejected': 'badge bg-danger',
                 'released': 'badge bg-primary'
             };
             return statusClasses[status] || 'badge bg-secondary';
         }
         
         function getStatusText(status) {
             const statusTexts = {
                 'pending': 'Pending',
                 'processing': 'Processing',
                 'approved': 'Approved',
                 'rejected': 'Rejected',
                 'released': 'Released'
             };
             return statusTexts[status] || 'Unknown';
         }
         
         function formatDate(dateString) {
             if (!dateString) return 'N/A';
             const date = new Date(dateString);
             return date.toLocaleDateString('en-US', {
                 year: 'numeric',
                 month: 'short',
                 day: 'numeric'
             });
         }
     </script>
</body>
</html>
