<?php
/**
 * Admin - System Settings
 * Manage system settings and configuration
 */

require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Ensure admin access
$auth->requireAdmin();

$success = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_settings') {
        try {
            // Update system settings
            $settings = [
                'barangay_name' => $_POST['barangay_name'] ?? '',
                'barangay_address' => $_POST['barangay_address'] ?? '',
                'barangay_contact' => $_POST['barangay_contact'] ?? '',
                'barangay_email' => $_POST['barangay_email'] ?? '',
                'system_title' => $_POST['system_title'] ?? '',
                'max_file_size' => $_POST['max_file_size'] ?? '5242880',
                'allowed_file_types' => $_POST['allowed_file_types'] ?? 'jpg,jpeg,png,pdf,doc,docx'
            ];
            
            foreach ($settings as $key => $value) {
                $db->query("
                    INSERT INTO system_settings (setting_key, setting_value) 
                    VALUES (?, ?) 
                    ON DUPLICATE KEY UPDATE setting_value = ?
                ", [$key, $value, $value]);
            }
            
            $success = 'System settings updated successfully!';
        } catch (Exception $e) {
            $error = 'Error updating settings: ' . $e->getMessage();
        }
    } elseif ($action === 'update_password') {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        if ($new_password !== $confirm_password) {
            $error = 'New passwords do not match!';
        } elseif (strlen($new_password) < 6) {
            $error = 'Password must be at least 6 characters long!';
        } else {
            try {
                // Verify current password
                $user = $db->fetch("SELECT password FROM users WHERE id = ?", [$_SESSION['user_id']]);
                if (!password_verify($current_password, $user['password'])) {
                    $error = 'Current password is incorrect!';
                } else {
                    // Update password
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $db->query("UPDATE users SET password = ? WHERE id = ?", [$hashed_password, $_SESSION['user_id']]);
                    $success = 'Password updated successfully!';
                }
            } catch (Exception $e) {
                $error = 'Error updating password: ' . $e->getMessage();
            }
        }
    }
}

// Get current settings
$current_settings = [];
$settings_result = $db->fetchAll("SELECT setting_key, setting_value FROM system_settings");
foreach ($settings_result as $setting) {
    $current_settings[$setting['setting_key']] = $setting['setting_value'];
}

// Get system information
$system_info = [
    'php_version' => PHP_VERSION,
    'mysql_version' => $db->fetch("SELECT VERSION() as version")['version'],
    'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
    'upload_max_filesize' => ini_get('upload_max_filesize'),
    'post_max_size' => ini_get('post_max_size'),
    'max_execution_time' => ini_get('max_execution_time'),
    'memory_limit' => ini_get('memory_limit')
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Settings - Admin Dashboard</title>
    
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
                        <a class="nav-link dropdown-toggle active" href="#" id="navbarDropdown" role="button" 
                           data-bs-toggle="dropdown">
                            <i class="fas fa-user-shield me-1"></i>
                            <?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item active" href="settings.php">
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
                    <i class="fas fa-cog me-2"></i>System Settings
                </h1>
            </div>
            
            <!-- Alerts -->
            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <div class="row">
                <!-- System Settings -->
                <div class="col-lg-8">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">
                                <i class="fas fa-cogs me-2"></i>System Configuration
                            </h6>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="action" value="update_settings">
                                
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="barangay_name" class="form-label">Barangay Name</label>
                                        <input type="text" class="form-control" id="barangay_name" name="barangay_name" 
                                               value="<?php echo htmlspecialchars($current_settings['barangay_name'] ?? ''); ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="system_title" class="form-label">System Title</label>
                                        <input type="text" class="form-control" id="system_title" name="system_title" 
                                               value="<?php echo htmlspecialchars($current_settings['system_title'] ?? ''); ?>" required>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="barangay_address" class="form-label">Barangay Address</label>
                                    <textarea class="form-control" id="barangay_address" name="barangay_address" rows="2"><?php echo htmlspecialchars($current_settings['barangay_address'] ?? ''); ?></textarea>
                                </div>
                                
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="barangay_contact" class="form-label">Contact Number</label>
                                        <input type="text" class="form-control" id="barangay_contact" name="barangay_contact" 
                                               value="<?php echo htmlspecialchars($current_settings['barangay_contact'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="barangay_email" class="form-label">Email Address</label>
                                        <input type="email" class="form-control" id="barangay_email" name="barangay_email" 
                                               value="<?php echo htmlspecialchars($current_settings['barangay_email'] ?? ''); ?>">
                                    </div>
                                </div>
                                
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="max_file_size" class="form-label">Maximum File Size (bytes)</label>
                                        <input type="number" class="form-control" id="max_file_size" name="max_file_size" 
                                               value="<?php echo htmlspecialchars($current_settings['max_file_size'] ?? '5242880'); ?>">
                                        <small class="form-text text-muted">5MB = 5242880 bytes</small>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="allowed_file_types" class="form-label">Allowed File Types</label>
                                        <input type="text" class="form-control" id="allowed_file_types" name="allowed_file_types" 
                                               value="<?php echo htmlspecialchars($current_settings['allowed_file_types'] ?? 'jpg,jpeg,png,pdf,doc,docx'); ?>">
                                        <small class="form-text text-muted">Comma-separated (e.g., jpg,png,pdf)</small>
                                    </div>
                                </div>
                                
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i>Save Settings
                                </button>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Change Password -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">
                                <i class="fas fa-key me-2"></i>Change Password
                            </h6>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="action" value="update_password">
                                
                                <div class="mb-3">
                                    <label for="current_password" class="form-label">Current Password</label>
                                    <input type="password" class="form-control" id="current_password" name="current_password" required>
                                </div>
                                
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="new_password" class="form-label">New Password</label>
                                        <input type="password" class="form-control" id="new_password" name="new_password" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="confirm_password" class="form-label">Confirm New Password</label>
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                    </div>
                                </div>
                                
                                <button type="submit" class="btn btn-warning">
                                    <i class="fas fa-key me-1"></i>Change Password
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- System Information -->
                <div class="col-lg-4">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">
                                <i class="fas fa-info-circle me-2"></i>System Information
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <strong>PHP Version:</strong>
                                <span class="badge bg-info"><?php echo $system_info['php_version']; ?></span>
                            </div>
                            
                            <div class="mb-3">
                                <strong>MySQL Version:</strong>
                                <span class="badge bg-info"><?php echo $system_info['mysql_version']; ?></span>
                            </div>
                            
                            <div class="mb-3">
                                <strong>Server Software:</strong>
                                <small class="text-muted d-block"><?php echo $system_info['server_software']; ?></small>
                            </div>
                            
                            <hr>
                            
                            <h6 class="font-weight-bold">PHP Configuration</h6>
                            <div class="mb-2">
                                <small><strong>Upload Max Filesize:</strong> <?php echo $system_info['upload_max_filesize']; ?></small>
                            </div>
                            <div class="mb-2">
                                <small><strong>Post Max Size:</strong> <?php echo $system_info['post_max_size']; ?></small>
                            </div>
                            <div class="mb-2">
                                <small><strong>Max Execution Time:</strong> <?php echo $system_info['max_execution_time']; ?>s</small>
                            </div>
                            <div class="mb-2">
                                <small><strong>Memory Limit:</strong> <?php echo $system_info['memory_limit']; ?></small>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Quick Actions -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">
                                <i class="fas fa-tools me-2"></i>Quick Actions
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <button type="button" class="btn btn-outline-primary btn-sm" onclick="backupDatabase()">
                                    <i class="fas fa-download me-1"></i>Backup Database
                                </button>
                                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="clearCache()">
                                    <i class="fas fa-broom me-1"></i>Clear Cache
                                </button>
                                <button type="button" class="btn btn-outline-info btn-sm" onclick="viewLogs()">
                                    <i class="fas fa-file-alt me-1"></i>View Logs
                                </button>
                                <button type="button" class="btn btn-outline-warning btn-sm" onclick="systemCheck()">
                                    <i class="fas fa-check-circle me-1"></i>System Check
                                </button>
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
        
        // Backup database
        function backupDatabase() {
            if (confirm('This will create a backup of the database. Continue?')) {
                alert('Database backup functionality will be implemented');
                // TODO: Implement database backup functionality
            }
        }
        
        // Clear cache
        function clearCache() {
            if (confirm('This will clear all cached data. Continue?')) {
                alert('Cache clearing functionality will be implemented');
                // TODO: Implement cache clearing functionality
            }
        }
        
        // View logs
        function viewLogs() {
            alert('Log viewing functionality will be implemented');
            // TODO: Implement log viewing functionality
        }
        
        // System check
        function systemCheck() {
            alert('System check functionality will be implemented');
            // TODO: Implement system check functionality
        }
        
        // Password strength validation
        document.getElementById('new_password').addEventListener('input', function() {
            const password = this.value;
            const strength = calculatePasswordStrength(password);
            updatePasswordStrengthIndicator(strength);
        });
        
        function calculatePasswordStrength(password) {
            let strength = 0;
            if (password.length >= 8) strength++;
            if (/[a-z]/.test(password)) strength++;
            if (/[A-Z]/.test(password)) strength++;
            if (/[0-9]/.test(password)) strength++;
            if (/[^A-Za-z0-9]/.test(password)) strength++;
            return strength;
        }
        
        function updatePasswordStrengthIndicator(strength) {
            const indicator = document.getElementById('password-strength');
            if (!indicator) return;
            
            const colors = ['danger', 'warning', 'info', 'primary', 'success'];
            const labels = ['Very Weak', 'Weak', 'Fair', 'Good', 'Strong'];
            
            indicator.className = `badge bg-${colors[strength - 1] || 'secondary'}`;
            indicator.textContent = labels[strength - 1] || 'Very Weak';
        }
    </script>
</body>
</html>
