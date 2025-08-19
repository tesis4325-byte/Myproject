<?php
/**
 * Barangay Document Request and Tracking System
 * Resident Dashboard
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
    // User not found, redirect to login
    header('Location: ../public/index.php?error=session_expired');
    exit();
}

$resident = getResidentByUserId($user['id']);

// Check if resident data exists
$profileComplete = $resident && !empty($resident['first_name']) && !empty($resident['last_name']) && !empty($resident['contact_number']) && !empty($resident['address']);

// Get recent requests
$recentRequests = [];
$stats = [
    'total' => 0,
    'pending' => 0,
    'processing' => 0,
    'approved' => 0,
    'released' => 0,
    'rejected' => 0
];

if ($resident && $resident['id']) {
    $recentRequests = getRequestsByResidentId($resident['id'], 5);
    $allRequests = getRequestsByResidentId($resident['id']);
    if ($allRequests) {
        foreach ($allRequests as $request) {
            $stats['total']++;
            $stats[$request['status']]++;
        }
    }
}

// Get available document types
$documentTypes = getAllDocumentTypes();

// Get system settings
$barangayName = getSystemSetting('barangay_name', 'Sample Barangay');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo $barangayName; ?></title>
    
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
                        <a class="nav-link active" href="dashboard.php">
                            <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="request_new.php">
                            <i class="fas fa-plus me-1"></i>New Request
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="request_status.php">
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
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" 
                           data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle me-1"></i>
                            <?php echo htmlspecialchars(($resident['first_name'] ?? $user['username'] ?? 'Resident')); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="profile.php">
                                <i class="fas fa-user me-2"></i>My Profile
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
            <!-- Profile Completion Alert -->
            <?php if (!$profileComplete): ?>
                <div class="alert alert-warning alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Profile Incomplete!</strong> Please complete your profile information to access all features.
                    <a href="profile.php" class="btn btn-warning btn-sm ms-3">
                        <i class="fas fa-user-edit me-1"></i>Complete Profile
                    </a>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <!-- Welcome Section -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-md-8">
                                    <h4 class="text-primary mb-2">
                                        <i class="fas fa-user-circle me-2"></i>
                                        Welcome back, <?php echo htmlspecialchars($resident['first_name'] ?? $user['username'] ?? 'Resident'); ?>!
                                    </h4>
                                    <p class="text-muted mb-0">
                                        Manage your document requests and track their status here.
                                    </p>
                                </div>
                                <div class="col-md-4 text-end">
                                    <?php if ($profileComplete): ?>
                                        <a href="request_new.php" class="btn btn-primary">
                                            <i class="fas fa-plus me-2"></i>New Request
                                        </a>
                                    <?php else: ?>
                                        <a href="profile.php" class="btn btn-warning">
                                            <i class="fas fa-user-edit me-2"></i>Complete Profile First
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-md-2 mb-3">
                    <div class="stats-card">
                        <div class="stats-number"><?php echo $stats['total']; ?></div>
                        <div class="stats-label">Total Requests</div>
                    </div>
                </div>
                <div class="col-md-2 mb-3">
                    <div class="stats-card warning">
                        <div class="stats-number"><?php echo $stats['pending']; ?></div>
                        <div class="stats-label">Pending</div>
                    </div>
                </div>
                <div class="col-md-2 mb-3">
                    <div class="stats-card info">
                        <div class="stats-number"><?php echo $stats['processing']; ?></div>
                        <div class="stats-label">Processing</div>
                    </div>
                </div>
                <div class="col-md-2 mb-3">
                    <div class="stats-card success">
                        <div class="stats-number"><?php echo $stats['approved']; ?></div>
                        <div class="stats-label">Approved</div>
                    </div>
                </div>
                <div class="col-md-2 mb-3">
                    <div class="stats-card">
                        <div class="stats-number"><?php echo $stats['released']; ?></div>
                        <div class="stats-label">Released</div>
                    </div>
                </div>
                <div class="col-md-2 mb-3">
                    <div class="stats-card danger">
                        <div class="stats-number"><?php echo $stats['rejected']; ?></div>
                        <div class="stats-label">Rejected</div>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Recent Requests -->
                <div class="col-lg-8 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-clock me-2"></i>Recent Requests
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php if (!$profileComplete): ?>
                                <div class="text-center py-4">
                                    <i class="fas fa-user-edit text-warning fa-3x mb-3"></i>
                                    <h5 class="text-warning">Profile Incomplete</h5>
                                    <p class="text-muted">Please complete your profile information to submit document requests.</p>
                                    <a href="profile.php" class="btn btn-warning">
                                        <i class="fas fa-user-edit me-2"></i>Complete Profile
                                    </a>
                                </div>
                            <?php elseif (empty($recentRequests)): ?>
                                <div class="text-center py-4">
                                    <i class="fas fa-file-alt text-muted fa-3x mb-3"></i>
                                    <h5 class="text-muted">No requests yet</h5>
                                    <p class="text-muted">Start by submitting your first document request.</p>
                                    <a href="request_new.php" class="btn btn-primary">
                                        <i class="fas fa-plus me-2"></i>Submit Request
                                    </a>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Request #</th>
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
                                                    <td><?php echo htmlspecialchars($request['document_type']); ?></td>
                                                    <td>
                                                        <span class="<?php echo getStatusBadgeClass($request['status']); ?>">
                                                            <?php echo getStatusText($request['status']); ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo formatDate($request['submitted_at']); ?></td>
                                                    <td>
                                                        <a href="request_status.php?id=<?php echo $request['id']; ?>" 
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
                                    <a href="request_status.php" class="btn btn-outline-primary">
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
                                <?php if ($profileComplete): ?>
                                    <a href="request_new.php" class="btn btn-primary">
                                        <i class="fas fa-plus me-2"></i>New Document Request
                                    </a>
                                    <a href="request_status.php" class="btn btn-outline-primary">
                                        <i class="fas fa-list me-2"></i>View All Requests
                                    </a>
                                <?php else: ?>
                                    <a href="profile.php" class="btn btn-warning">
                                        <i class="fas fa-user-edit me-2"></i>Complete Profile
                                    </a>
                                <?php endif; ?>
                                <a href="profile.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-user me-2"></i><?php echo $profileComplete ? 'Update Profile' : 'Manage Profile'; ?>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Available Documents -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-file-alt me-2"></i>Available Documents
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="list-group list-group-flush">
                                <?php foreach ($documentTypes as $docType): ?>
                                    <div class="list-group-item d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-1"><?php echo htmlspecialchars($docType['name']); ?></h6>
                                            <small class="text-muted">₱<?php echo number_format($docType['processing_fee'], 2); ?></small>
                                        </div>
                                        <a href="request_new.php?type=<?php echo $docType['id']; ?>" 
                                           class="btn btn-sm btn-outline-primary">
                                            Request
                                        </a>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Contact Information -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-info-circle me-2"></i>Contact Information
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <strong>Barangay Office</strong><br>
                                <small class="text-muted">
                                    <?php echo getSystemSetting('barangay_address', 'Sample Address'); ?>
                                </small>
                            </div>
                            <div class="mb-3">
                                <strong>Contact Number</strong><br>
                                <small class="text-muted">
                                    <?php echo getSystemSetting('barangay_contact', '+63 912 345 6789'); ?>
                                </small>
                            </div>
                            <div class="mb-3">
                                <strong>Email</strong><br>
                                <small class="text-muted">
                                    <?php echo getSystemSetting('barangay_email', 'barangay@example.com'); ?>
                                </small>
                            </div>
                            <div>
                                <strong>Office Hours</strong><br>
                                <small class="text-muted">Monday - Friday: 8:00 AM - 5:00 PM</small>
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
</body>
</html>
