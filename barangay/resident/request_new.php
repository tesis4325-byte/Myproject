<?php
/**
 * Barangay Document Request and Tracking System
 * New Request Submission
 */

require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Require resident access
$auth->requireResident();

// Get current user data
$user = $auth->getCurrentUser();
$resident = getResidentByUserId($user['id']);

// Check if resident data exists and is complete
$profileComplete = $resident && !empty($resident['first_name']) && !empty($resident['last_name']) && !empty($resident['contact_number']) && !empty($resident['address']);

// Redirect to profile if incomplete
if (!$profileComplete) {
    header('Location: profile.php?error=profile_incomplete&redirect=request_new');
    exit();
}

// Get available document types
$documentTypes = getAllDocumentTypes();

$error = '';
$success = '';

// Check for success message from profile completion
if (isset($_GET['success']) && $_GET['success'] === 'profile_completed') {
    $success = 'Profile completed successfully! You can now submit document requests.';
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $documentTypeId = intval($_POST['document_type_id'] ?? 0);
    $purpose = sanitizeInput($_POST['purpose'] ?? '');
    $notes = sanitizeInput($_POST['notes'] ?? '');
    
    // Validation
    if (!$documentTypeId) {
        $error = 'Please select a document type.';
    } elseif (empty($purpose)) {
        $error = 'Please provide the purpose for the request.';
    } else {
        try {
            // Generate request number
            $requestNumber = generateRequestNumber();
            
            // Insert request
            $sql = "INSERT INTO document_requests (resident_id, document_type_id, request_number, purpose, resident_notes, status) 
                    VALUES (?, ?, ?, ?, ?, 'pending')";
            
            $db->query($sql, [
                $resident['id'],
                $documentTypeId,
                $requestNumber,
                $purpose,
                $notes
            ]);
            
            $success = "Request submitted successfully! Your request number is: <strong>{$requestNumber}</strong>";
            
        } catch (Exception $e) {
            $error = 'Error submitting request. Please try again.';
        }
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
    <title>New Request - <?php echo $barangayName; ?></title>
    
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
                        <a class="nav-link active" href="request_new.php">
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
                            <?php echo htmlspecialchars(($resident['first_name'] ?? '') . ' ' . ($resident['last_name'] ?? '')); ?>
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
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="mb-0">
                                <i class="fas fa-plus me-2"></i>Submit New Document Request
                            </h4>
                        </div>
                        <div class="card-body">
                            <?php if ($error): ?>
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    <?php echo $error; ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($success): ?>
                                <div class="alert alert-success alert-dismissible fade show" role="alert">
                                    <i class="fas fa-check-circle me-2"></i>
                                    <?php echo $success; ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                                <div class="text-center">
                                    <a href="request_status.php" class="btn btn-primary">
                                        <i class="fas fa-list me-2"></i>View My Requests
                                    </a>
                                    <a href="request_new.php" class="btn btn-outline-primary">
                                        <i class="fas fa-plus me-2"></i>Submit Another Request
                                    </a>
                                </div>
                            <?php else: ?>
                            
                            <form method="POST" class="needs-validation" novalidate>
                                <!-- Document Type Selection -->
                                <div class="mb-4">
                                    <label for="document_type_id" class="form-label">
                                        <i class="fas fa-file-alt me-2"></i>Document Type *
                                    </label>
                                    <select class="form-select" id="document_type_id" name="document_type_id" required>
                                        <option value="">Select Document Type</option>
                                        <?php foreach ($documentTypes as $docType): ?>
                                            <option value="<?php echo $docType['id']; ?>" 
                                                    data-fee="<?php echo $docType['processing_fee']; ?>"
                                                    data-days="<?php echo $docType['processing_days']; ?>"
                                                    data-requirements="<?php echo htmlspecialchars($docType['requirements']); ?>">
                                                <?php echo htmlspecialchars($docType['name']); ?> 
                                                (₱<?php echo number_format($docType['processing_fee'], 2); ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="invalid-feedback">
                                        Please select a document type.
                                    </div>
                                </div>
                                
                                <!-- Document Information -->
                                <div id="document-info" class="mb-4" style="display: none;">
                                    <div class="card bg-light">
                                        <div class="card-body">
                                            <h6 class="card-title">
                                                <i class="fas fa-info-circle me-2"></i>Document Information
                                            </h6>
                                            <div class="row">
                                                <div class="col-md-4">
                                                    <strong>Processing Fee:</strong><br>
                                                    <span id="processing-fee" class="text-primary"></span>
                                                </div>
                                                <div class="col-md-4">
                                                    <strong>Processing Time:</strong><br>
                                                    <span id="processing-days" class="text-info"></span>
                                                </div>
                                                <div class="col-md-4">
                                                    <strong>Requirements:</strong><br>
                                                    <small id="requirements" class="text-muted"></small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Purpose -->
                                <div class="mb-4">
                                    <label for="purpose" class="form-label">
                                        <i class="fas fa-bullseye me-2"></i>Purpose of Request *
                                    </label>
                                    <textarea class="form-control" id="purpose" name="purpose" rows="3" 
                                              placeholder="Please specify the purpose for requesting this document..." required><?php echo htmlspecialchars($_POST['purpose'] ?? ''); ?></textarea>
                                    <div class="invalid-feedback">
                                        Please provide the purpose for the request.
                                    </div>
                                </div>
                                
                                <!-- Additional Notes -->
                                <div class="mb-4">
                                    <label for="notes" class="form-label">
                                        <i class="fas fa-sticky-note me-2"></i>Additional Notes (Optional)
                                    </label>
                                    <textarea class="form-control" id="notes" name="notes" rows="3" 
                                              placeholder="Any additional information or special requests..."><?php echo htmlspecialchars($_POST['notes'] ?? ''); ?></textarea>
                                </div>
                                
                                <!-- Resident Information -->
                                <div class="mb-4">
                                    <h6 class="text-primary">
                                        <i class="fas fa-user me-2"></i>Requestor Information
                                    </h6>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <label class="form-label">Full Name</label>
                                            <input type="text" class="form-control" 
                                                   value="<?php echo htmlspecialchars(($resident['first_name'] ?? '') . ' ' . ($resident['last_name'] ?? '')); ?>" 
                                                   readonly>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Contact Number</label>
                                            <input type="text" class="form-control" 
                                                   value="<?php echo htmlspecialchars($resident['contact_number'] ?? ''); ?>" 
                                                   readonly>
                                        </div>
                                    </div>
                                    <div class="row mt-2">
                                        <div class="col-12">
                                            <label class="form-label">Address</label>
                                            <input type="text" class="form-control" 
                                                   value="<?php echo htmlspecialchars(($resident['address'] ?? '') . ', ' . ($resident['barangay'] ?? '') . ', ' . ($resident['city'] ?? '') . ', ' . ($resident['province'] ?? '')); ?>" 
                                                   readonly>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Terms and Conditions -->
                                <div class="mb-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="terms" required>
                                        <label class="form-check-label" for="terms">
                                            I agree to the <a href="#" data-bs-toggle="modal" data-bs-target="#termsModal">terms and conditions</a> *
                                        </label>
                                        <div class="invalid-feedback">
                                            You must agree to the terms and conditions.
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        <i class="fas fa-paper-plane me-2"></i>Submit Request
                                    </button>
                                    <a href="dashboard.php" class="btn btn-outline-secondary">
                                        <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                                    </a>
                                </div>
                            </form>
                            
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Terms and Conditions Modal -->
    <div class="modal fade" id="termsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-file-contract me-2"></i>Terms and Conditions
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <h6>Document Request Terms and Conditions</h6>
                    <ol>
                        <li>All information provided must be accurate and truthful.</li>
                        <li>Processing fees are non-refundable once the request is submitted.</li>
                        <li>Processing time may vary depending on the complexity of the request.</li>
                        <li>Required documents must be submitted within the specified timeframe.</li>
                        <li>The barangay office reserves the right to reject requests that do not meet requirements.</li>
                        <li>Generated documents are valid for the specified period only.</li>
                        <li>Requests may be subject to verification and validation.</li>
                        <li>False information may result in request rejection and account suspension.</li>
                    </ol>
                    
                    <h6>Privacy Policy</h6>
                    <p>Your personal information will be used solely for the purpose of processing your document request and will be kept confidential in accordance with data protection laws.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">I Understand</button>
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
    
    <script>
        // Show document information when type is selected
        document.getElementById('document_type_id').addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const infoDiv = document.getElementById('document-info');
            
            if (this.value) {
                const fee = selectedOption.dataset.fee;
                const days = selectedOption.dataset.days;
                const requirements = selectedOption.dataset.requirements;
                
                document.getElementById('processing-fee').textContent = '₱' + parseFloat(fee).toFixed(2);
                document.getElementById('processing-days').textContent = days + ' day(s)';
                document.getElementById('requirements').textContent = requirements;
                
                infoDiv.style.display = 'block';
            } else {
                infoDiv.style.display = 'none';
            }
        });
        
        // Form validation
        (function() {
            'use strict';
            window.addEventListener('load', function() {
                var forms = document.getElementsByClassName('needs-validation');
                var validation = Array.prototype.filter.call(forms, function(form) {
                    form.addEventListener('submit', function(event) {
                        if (form.checkValidity() === false) {
                            event.preventDefault();
                            event.stopPropagation();
                        }
                        form.classList.add('was-validated');
                    }, false);
                });
            }, false);
        })();
    </script>
</body>
</html>
