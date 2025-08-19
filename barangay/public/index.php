<?php
/**
 * Barangay Document Request and Tracking System
 * Main Landing Page
 */

require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Redirect if already logged in
// Temporarily disabled to prevent redirect loop
/*
if ($auth->isLoggedIn()) {
    if ($auth->isAdmin()) {
        header('Location: ../admin/dashboard.php');
    } else {
        header('Location: ../resident/dashboard.php');
    }
    exit();
}
*/

$error = '';
$success = '';

// Handle success messages
if (isset($_GET['success'])) {
    switch ($_GET['success']) {
        case 'session_cleared':
            $success = 'Session cleared successfully.';
            break;
        case 'logged_out':
            $success = 'You have been logged out successfully.';
            break;
    }
}

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitizeInput($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } else {
        if ($auth->login($username, $password)) {
            if ($auth->isAdmin()) {
                header('Location: ../admin/dashboard.php');
            } else {
                header('Location: ../resident/dashboard.php');
            }
            exit();
        } else {
            $error = 'Invalid username or password.';
        }
    }
}

// Get system settings
$barangayName = getSystemSetting('barangay_name', 'Sample Barangay');
$barangayAddress = getSystemSetting('barangay_address', 'Sample Address, City, Province');
$barangayContact = getSystemSetting('barangay_contact', '+63 912 345 6789');
$barangayEmail = getSystemSetting('barangay_email', 'barangay@example.com');

// Debug information (temporary)
$debugInfo = '';
if (isset($_GET['debug'])) {
    $debugInfo = '<div class="alert alert-info">';
    $debugInfo .= '<strong>Debug Info:</strong><br>';
    $debugInfo .= 'Session ID: ' . session_id() . '<br>';
    $debugInfo .= 'Session Status: ' . session_status() . '<br>';
    $debugInfo .= 'isLoggedIn(): ' . ($auth->isLoggedIn() ? 'TRUE' : 'FALSE') . '<br>';
    $debugInfo .= 'isAdmin(): ' . ($auth->isAdmin() ? 'TRUE' : 'FALSE') . '<br>';
    $debugInfo .= 'Session Variables: ' . print_r($_SESSION, true) . '<br>';
    $debugInfo .= '</div>';
}

// Clear session if requested
if (isset($_GET['clear_session'])) {
    session_destroy();
    header('Location: index.php?success=session_cleared');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $barangayName; ?> - Document Request System</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="auth-container">
        <div class="container">
            <div class="row justify-content-center align-items-center min-vh-100">
                <div class="col-md-6 col-lg-5 col-xl-4">
                    <div class="auth-card">
                        <div class="text-center mb-4">
                            <h2 class="text-primary mb-2">
                                <i class="fas fa-building me-2"></i>
                                <?php echo $barangayName; ?>
                            </h2>
                            <p class="text-muted">Document Request and Tracking System</p>
                        </div>
                        
                        <?php if ($debugInfo): ?>
                            <?php echo $debugInfo; ?>
                        <?php endif; ?>
                        
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
                        <?php endif; ?>
                        
                        <form method="POST" class="needs-validation" novalidate>
                            <div class="mb-3">
                                <label for="username" class="form-label">
                                    <i class="fas fa-user me-2"></i>Username or Email
                                </label>
                                <input type="text" class="form-control" id="username" name="username" 
                                       value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" 
                                       required>
                                <div class="invalid-feedback">
                                    Please enter your username or email.
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="password" class="form-label">
                                    <i class="fas fa-lock me-2"></i>Password
                                </label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="password" name="password" required>
                                    <button class="btn btn-outline-secondary" type="button" 
                                            onclick="togglePasswordVisibility('password')">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <div class="invalid-feedback">
                                    Please enter your password.
                                </div>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-sign-in-alt me-2"></i>Login
                                </button>
                            </div>
                        </form>
                        
                        <hr class="my-4">
                        
                        <div class="text-center">
                            <p class="mb-2">Don't have an account?</p>
                            <a href="register.php" class="btn btn-outline-primary">
                                <i class="fas fa-user-plus me-2"></i>Register as Resident
                            </a>
                        </div>
                        
                        <div class="mt-4 text-center">
                            <small class="text-muted">
                                <i class="fas fa-info-circle me-1"></i>
                                For any problem or issue, contact the barangay office.
                            </small>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- System Information -->
            <div class="row mt-5">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body text-center">
                            <h4 class="text-primary mb-3">
                                <i class="fas fa-info-circle me-2"></i>System Information
                            </h4>
                            <div class="row">
                                <div class="col-md-3 mb-3">
                                    <div class="d-flex align-items-center justify-content-center">
                                        <i class="fas fa-map-marker-alt text-primary me-2"></i>
                                        <div>
                                            <strong>Address</strong><br>
                                            <small><?php echo $barangayAddress; ?></small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <div class="d-flex align-items-center justify-content-center">
                                        <i class="fas fa-phone text-primary me-2"></i>
                                        <div>
                                            <strong>Contact</strong><br>
                                            <small><?php echo $barangayContact; ?></small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <div class="d-flex align-items-center justify-content-center">
                                        <i class="fas fa-envelope text-primary me-2"></i>
                                        <div>
                                            <strong>Email</strong><br>
                                            <small><?php echo $barangayEmail; ?></small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <div class="d-flex align-items-center justify-content-center">
                                        <i class="fas fa-clock text-primary me-2"></i>
                                        <div>
                                            <strong>Office Hours</strong><br>
                                            <small>Mon-Fri: 8AM-5PM</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Features -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <h4 class="text-primary mb-3 text-center">
                                <i class="fas fa-star me-2"></i>System Features
                            </h4>
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <div class="text-center">
                                        <i class="fas fa-file-alt text-primary fa-2x mb-2"></i>
                                        <h6>Document Requests</h6>
                                        <small class="text-muted">Submit and track various document requests</small>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <div class="text-center">
                                        <i class="fas fa-chart-line text-primary fa-2x mb-2"></i>
                                        <h6>Real-time Tracking</h6>
                                        <small class="text-muted">Monitor request status in real-time</small>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <div class="text-center">
                                        <i class="fas fa-download text-primary fa-2x mb-2"></i>
                                        <h6>Digital Documents</h6>
                                        <small class="text-muted">Download approved documents instantly</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
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
    <script src="assets/js/app.js"></script>
    
    <script>
        // Toggle password visibility
        function togglePasswordVisibility(inputId) {
            const input = document.getElementById(inputId);
            const icon = document.querySelector(`[onclick="togglePasswordVisibility('${inputId}')"] i`);
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.className = 'fas fa-eye-slash';
            } else {
                input.type = 'password';
                icon.className = 'fas fa-eye';
            }
        }
        
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
