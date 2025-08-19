<?php
/**
 * Barangay Document Request and Tracking System
 * Resident Request Status Page
 */

require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Require resident access
$auth->requireResident();

// Get current user data
$user = $auth->getCurrentUser();

// Check if user data exists
if (!$user) {
    header('Location: ../public/index.php?error=session_expired');
    exit();
}

$resident = getResidentByUserId($user['id']);

// Check if resident data exists
if (!$resident) {
    header('Location: profile.php?error=profile_incomplete');
    exit();
}

// Get filter parameters
$statusFilter = $_GET['status'] ?? '';
$dateFilter = $_GET['date'] ?? '';
$searchTerm = $_GET['search'] ?? '';

// Get all requests for this resident
$requests = getRequestsByResidentId($resident['id']);

// Apply filters
$filteredRequests = [];
foreach ($requests as $request) {
    $include = true;
    
    // Status filter
    if ($statusFilter && $request['status'] !== $statusFilter) {
        $include = false;
    }
    
    // Date filter
    if ($dateFilter) {
        $requestDate = date('Y-m-d', strtotime($request['created_at']));
        if ($requestDate !== $dateFilter) {
            $include = false;
        }
    }
    
    // Search filter
    if ($searchTerm) {
        $searchLower = strtolower($searchTerm);
        $requestNumber = strtolower($request['request_number']);
        $documentType = strtolower($request['document_type']);
        $purpose = strtolower($request['purpose']);
        
        if (strpos($requestNumber, $searchLower) === false && 
            strpos($documentType, $searchLower) === false && 
            strpos($purpose, $searchLower) === false) {
            $include = false;
        }
    }
    
    if ($include) {
        $filteredRequests[] = $request;
    }
}

// Get system settings
$barangayName = getSystemSetting('barangay_name', 'Sample Barangay');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Requests - <?php echo $barangayName; ?></title>
    
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
                <?php echo $barangayName; ?>
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
                        <a class="nav-link" href="request_new.php">
                            <i class="fas fa-plus me-1"></i>New Request
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="request_status.php">
                            <i class="fas fa-list me-1"></i>My Requests
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="profile.php">
                            <i class="fas fa-user me-1"></i>Profile
                        </a>
                    </li>
                </ul>
                
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle me-1"></i>
                            <?php echo htmlspecialchars(($resident['first_name'] ?? $user['username'] ?? 'Resident')); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="profile.php">
                                <i class="fas fa-user me-2"></i>Profile
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
    <div class="container mt-4">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>
                        <i class="fas fa-list me-2"></i>My Document Requests
                    </h2>
                    <div>
                        <button type="button" class="btn btn-info btn-sm me-2" onclick="testFunction()">
                            <i class="fas fa-cog me-1"></i>Test JS
                        </button>
                        <a href="request_new.php" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>New Request
                        </a>
                    </div>
                </div>

                <!-- Filters -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-3">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="">All Status</option>
                                    <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="processing" <?php echo $statusFilter === 'processing' ? 'selected' : ''; ?>>Processing</option>
                                    <option value="approved" <?php echo $statusFilter === 'approved' ? 'selected' : ''; ?>>Approved</option>
                                    <option value="released" <?php echo $statusFilter === 'released' ? 'selected' : ''; ?>>Released</option>
                                    <option value="rejected" <?php echo $statusFilter === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="date" class="form-label">Date</label>
                                <input type="date" class="form-control" id="date" name="date" value="<?php echo htmlspecialchars($dateFilter); ?>">
                            </div>
                            <div class="col-md-4">
                                <label for="search" class="form-label">Search</label>
                                <input type="text" class="form-control" id="search" name="search" 
                                       value="<?php echo htmlspecialchars($searchTerm); ?>" 
                                       placeholder="Search by request number, document type, or purpose">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">&nbsp;</label>
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-search me-1"></i>Filter
                                    </button>
                                </div>
                            </div>
                        </form>
                        
                        <?php if ($statusFilter || $dateFilter || $searchTerm): ?>
                            <div class="mt-3">
                                <a href="request_status.php" class="btn btn-outline-secondary btn-sm">
                                    <i class="fas fa-times me-1"></i>Clear Filters
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Requests List -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-file-alt me-2"></i>Document Requests
                            <span class="badge bg-primary ms-2"><?php echo count($filteredRequests); ?></span>
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($filteredRequests)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">No requests found</h5>
                                <p class="text-muted">
                                    <?php if ($statusFilter || $dateFilter || $searchTerm): ?>
                                        No requests match your current filters.
                                    <?php else: ?>
                                        You haven't submitted any document requests yet.
                                    <?php endif; ?>
                                </p>
                                <a href="request_new.php" class="btn btn-primary">
                                    <i class="fas fa-plus me-2"></i>Submit Your First Request
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Request #</th>
                                            <th>Document Type</th>
                                            <th>Purpose</th>
                                            <th>Status</th>
                                            <th>Submitted</th>
                                            <th>Updated</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($filteredRequests as $request): ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($request['request_number']); ?></strong>
                                                </td>
                                                <td>
                                                    <i class="fas fa-file-alt me-1"></i>
                                                    <?php echo htmlspecialchars($request['document_type']); ?>
                                                </td>
                                                <td>
                                                    <?php echo htmlspecialchars($request['purpose']); ?>
                                                </td>
                                                <td>
                                                    <span class="<?php echo getStatusBadgeClass($request['status']); ?>">
                                                        <?php echo getStatusText($request['status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <small class="text-muted">
                                                        <?php echo formatDate($request['created_at']); ?>
                                                    </small>
                                                </td>
                                                <td>
                                                    <small class="text-muted">
                                                        <?php echo formatDate($request['updated_at']); ?>
                                                    </small>
                                                </td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <button type="button" class="btn btn-outline-primary" 
                                                                onclick="viewRequestDetails(<?php echo $request['id']; ?>)">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                        <?php if ($request['status'] === 'approved' || $request['status'] === 'released'): ?>
                                                            <button type="button" class="btn btn-outline-success" 
                                                                    onclick="downloadDocument(<?php echo $request['id']; ?>)"
                                                                    title="Download Document">
                                                                <i class="fas fa-download"></i>
                                                            </button>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Help Information -->
                        <div class="mt-3">
                            <div class="alert alert-info">
                                <h6 class="alert-heading">
                                    <i class="fas fa-info-circle me-2"></i>About Document Downloads
                                </h6>
                                <ul class="mb-0">
                                    <li><strong>Download Button:</strong> Only appears for requests with "Approved" or "Released" status</li>
                                    <li><strong>Document Generation:</strong> Documents are generated by barangay staff after approval</li>
                                    <li><strong>Processing Time:</strong> Allow 1-3 business days for document generation</li>
                                    <li><strong>Contact Office:</strong> If download doesn't work, contact the barangay office</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Request Details Modal -->
    <div class="modal fade" id="requestDetailsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-file-alt me-2"></i>Request Details
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="requestDetailsContent">
                    <!-- Content will be loaded here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer mt-5">
        <div class="container">
            <div class="row">
                <div class="col-12 text-center">
                    <p>&copy; <?php echo date('Y'); ?> <?php echo $barangayName; ?>. All rights reserved.</p>
                    <small class="text-muted">Barangay Document Request and Tracking System v1.0</small>
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
        // Test function to verify JavaScript is working
        function testFunction() {
            console.log('Test function called');
            alert('JavaScript is working! Test function called successfully.');
        }
        
        // View request details
        function viewRequestDetails(requestId) {
            console.log('viewRequestDetails called with requestId:', requestId);
            
            // Show loading indicator
            $('#requestDetailsContent').html('<div class="text-center"><div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div></div>');
            $('#requestDetailsModal').modal('show');
            
            // Load request details via AJAX
            $.ajax({
                url: '../api/request_api.php',
                method: 'GET',
                data: {
                    action: 'get_request_details',
                    request_id: requestId
                },
                dataType: 'json',
                success: function(response) {
                    console.log('API response:', response);
                    if (response.success) {
                        $('#requestDetailsContent').html(response.html);
                    } else {
                        $('#requestDetailsContent').html('<div class="alert alert-danger">Error: ' + response.message + '</div>');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX error:', xhr, status, error);
                    $('#requestDetailsContent').html('<div class="alert alert-danger">Error loading request details. Please try again.</div>');
                }
            });
        }

        // Download document
        function downloadDocument(requestId) {
            console.log('Downloading document for request ID:', requestId);
            
            // Show loading indicator
            const downloadBtn = event.target.closest('button');
            const originalContent = downloadBtn.innerHTML;
            downloadBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            downloadBtn.disabled = true;
            
            // Debug: Log the API call
            const apiUrl = '../api/request_api.php?action=download_document&request_id=' + requestId;
            console.log('Calling API:', apiUrl);
            
            // Try to download
            fetch(apiUrl)
                .then(response => {
                    console.log('Response status:', response.status);
                    console.log('Response headers:', response.headers);
                    
                    if (response.ok) {
                        // If it's a file download, the browser will handle it
                        console.log('Response OK, getting blob...');
                        return response.blob();
                    } else {
                        // If it's an error response, get the error message
                        console.log('Response not OK, getting JSON error...');
                        return response.json();
                    }
                })
                .then(data => {
                    console.log('Response data:', data);
                    if (data && data.success === false) {
                        // Show error message
                        console.error('API Error:', data.message);
                        alert('Download Error: ' + data.message);
                    } else if (data instanceof Blob) {
                        console.log('Blob received, size:', data.size);
                        // Create download link
                        const url = window.URL.createObjectURL(data);
                        const a = document.createElement('a');
                        a.href = url;
                        a.download = 'document.pdf';
                        document.body.appendChild(a);
                        a.click();
                        window.URL.revokeObjectURL(url);
                        document.body.removeChild(a);
                    }
                })
                .catch(error => {
                    console.error('Download error:', error);
                    alert('Download failed. Please try again or contact the barangay office.');
                })
                .finally(() => {
                    // Restore button
                    downloadBtn.innerHTML = originalContent;
                    downloadBtn.disabled = false;
                });
        }

        // Auto-refresh status every 30 seconds
        setInterval(function() {
            // Only refresh if no modal is open
            if (!$('#requestDetailsModal').hasClass('show')) {
                location.reload();
            }
        }, 30000);
    </script>
</body>
</html>
