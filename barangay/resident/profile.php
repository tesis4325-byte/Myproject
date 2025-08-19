<?php
/**
 * Barangay Document Request and Tracking System
 * Resident Profile Management
 */

require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Require resident access
$auth->requireResident();

$success = '';
$error = '';

// Get current user data
$user = $auth->getCurrentUser();
$resident = getResidentByUserId($user['id']);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_profile') {
        try {
            // Validate required fields
            $required_fields = ['first_name', 'last_name', 'contact_number', 'address'];
            foreach ($required_fields as $field) {
                if (empty($_POST[$field])) {
                    throw new Exception("Field '$field' is required.");
                }
            }
            
            // Prepare resident data
            $resident_data = [
                'first_name' => sanitizeInput($_POST['first_name']),
                'last_name' => sanitizeInput($_POST['last_name']),
                'middle_name' => sanitizeInput($_POST['middle_name'] ?? ''),
                'birth_date' => $_POST['birth_date'] ?? null,
                'gender' => $_POST['gender'] ?? '',
                'contact_number' => sanitizeInput($_POST['contact_number']),
                'address' => sanitizeInput($_POST['address']),
                'barangay' => sanitizeInput($_POST['barangay'] ?? ''),
                'city' => sanitizeInput($_POST['city'] ?? ''),
                'province' => sanitizeInput($_POST['province'] ?? ''),
                'postal_code' => sanitizeInput($_POST['postal_code'] ?? ''),
                'civil_status' => $_POST['civil_status'] ?? '',
                'nationality' => sanitizeInput($_POST['nationality'] ?? ''),
                'occupation' => sanitizeInput($_POST['occupation'] ?? ''),
                'emergency_contact_name' => sanitizeInput($_POST['emergency_contact_name'] ?? ''),
                'emergency_contact_number' => sanitizeInput($_POST['emergency_contact_number'] ?? ''),
                'emergency_contact_relationship' => sanitizeInput($_POST['emergency_contact_relationship'] ?? ''),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            if ($resident) {
                // Update existing resident
                $sql = "UPDATE residents SET 
                        first_name = ?, last_name = ?, middle_name = ?, birth_date = ?, 
                        gender = ?, contact_number = ?, address = ?, barangay = ?, 
                        city = ?, province = ?, postal_code = ?, civil_status = ?, nationality = ?, 
                        occupation = ?, emergency_contact_name = ?, emergency_contact_number = ?, 
                        emergency_contact_relationship = ?, updated_at = ?
                        WHERE user_id = ?";
                
                $params = array_merge(array_values($resident_data), [$user['id']]);
                $db->query($sql, $params);
                
                                 $success = 'Profile updated successfully!';
                 
                 // Check if user was redirected from request_new page
                 if (isset($_GET['redirect']) && $_GET['redirect'] === 'request_new') {
                     header('Location: request_new.php?success=profile_completed');
                     exit();
                 }
             } else {
                 // Create new resident record
                 $sql = "INSERT INTO residents (
                         user_id, first_name, last_name, middle_name, birth_date, gender, 
                         contact_number, address, barangay, city, province, postal_code, 
                         civil_status, nationality, occupation, emergency_contact_name, 
                         emergency_contact_number, emergency_contact_relationship, created_at, updated_at
                     ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
                 
                 $params = array_merge([$user['id']], array_values($resident_data));
                 $db->query($sql, $params);
                 
                 $success = 'Profile created successfully!';
                 
                 // Check if user was redirected from request_new page
                 if (isset($_GET['redirect']) && $_GET['redirect'] === 'request_new') {
                     header('Location: request_new.php?success=profile_completed');
                     exit();
                 }
             }
            
            // Refresh resident data
            $resident = getResidentByUserId($user['id']);
            
        } catch (Exception $e) {
            $error = 'Error updating profile: ' . $e->getMessage();
        }
    } elseif ($action === 'change_password') {
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
                if (!password_verify($current_password, $user['password'])) {
                    $error = 'Current password is incorrect!';
                } else {
                    // Update password
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $db->query("UPDATE users SET password = ? WHERE id = ?", [$hashed_password, $user['id']]);
                    $success = 'Password changed successfully!';
                }
            } catch (Exception $e) {
                $error = 'Error changing password: ' . $e->getMessage();
            }
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
    <title>My Profile - <?php echo $barangayName; ?></title>
    
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
                        <a class="nav-link" href="request_status.php">
                            <i class="fas fa-list me-1"></i>My Requests
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="profile.php">
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
                            <li><a class="dropdown-item active" href="profile.php">
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
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">
                    <i class="fas fa-user me-2"></i>My Profile
                </h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="dashboard.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i>Back to Dashboard
                    </a>
                </div>
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
            
                         <?php if (isset($_GET['error']) && $_GET['error'] === 'profile_incomplete'): ?>
                 <div class="alert alert-warning alert-dismissible fade show" role="alert">
                     <i class="fas fa-exclamation-triangle me-2"></i>
                     <?php if (isset($_GET['redirect']) && $_GET['redirect'] === 'request_new'): ?>
                         Please complete your profile information to submit document requests.
                     <?php else: ?>
                         Please complete your profile information to continue.
                     <?php endif; ?>
                     <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                 </div>
             <?php endif; ?>
            
            <div class="row">
                <!-- Profile Information -->
                <div class="col-lg-8">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">
                                <i class="fas fa-user-edit me-2"></i>Personal Information
                            </h6>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="action" value="update_profile">
                                
                                <!-- Personal Details -->
                                <h6 class="text-primary mb-3">Personal Details</h6>
                                <div class="row mb-3">
                                    <div class="col-md-4">
                                        <label for="first_name" class="form-label">First Name *</label>
                                        <input type="text" class="form-control" id="first_name" name="first_name" 
                                               value="<?php echo htmlspecialchars($resident['first_name'] ?? ''); ?>" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="last_name" class="form-label">Last Name *</label>
                                        <input type="text" class="form-control" id="last_name" name="last_name" 
                                               value="<?php echo htmlspecialchars($resident['last_name'] ?? ''); ?>" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="middle_name" class="form-label">Middle Name</label>
                                        <input type="text" class="form-control" id="middle_name" name="middle_name" 
                                               value="<?php echo htmlspecialchars($resident['middle_name'] ?? ''); ?>">
                                    </div>
                                </div>
                                
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="birth_date" class="form-label">Date of Birth</label>
                                        <input type="date" class="form-control" id="birth_date" name="birth_date" 
                                               value="<?php echo $resident['birth_date'] ?? ''; ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="gender" class="form-label">Gender</label>
                                        <select class="form-select" id="gender" name="gender">
                                            <option value="">Select Gender</option>
                                            <option value="Male" <?php echo ($resident['gender'] ?? '') === 'Male' ? 'selected' : ''; ?>>Male</option>
                                            <option value="Female" <?php echo ($resident['gender'] ?? '') === 'Female' ? 'selected' : ''; ?>>Female</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="civil_status" class="form-label">Civil Status</label>
                                        <select class="form-select" id="civil_status" name="civil_status">
                                            <option value="">Select Civil Status</option>
                                            <option value="Single" <?php echo ($resident['civil_status'] ?? '') === 'Single' ? 'selected' : ''; ?>>Single</option>
                                            <option value="Married" <?php echo ($resident['civil_status'] ?? '') === 'Married' ? 'selected' : ''; ?>>Married</option>
                                            <option value="Widowed" <?php echo ($resident['civil_status'] ?? '') === 'Widowed' ? 'selected' : ''; ?>>Widowed</option>
                                            <option value="Divorced" <?php echo ($resident['civil_status'] ?? '') === 'Divorced' ? 'selected' : ''; ?>>Divorced</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="nationality" class="form-label">Nationality</label>
                                        <input type="text" class="form-control" id="nationality" name="nationality" 
                                               value="<?php echo htmlspecialchars($resident['nationality'] ?? ''); ?>">
                                    </div>
                                </div>
                                
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="occupation" class="form-label">Occupation</label>
                                        <input type="text" class="form-control" id="occupation" name="occupation" 
                                               value="<?php echo htmlspecialchars($resident['occupation'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="contact_number" class="form-label">Contact Number *</label>
                                        <input type="text" class="form-control" id="contact_number" name="contact_number" 
                                               value="<?php echo htmlspecialchars($resident['contact_number'] ?? ''); ?>" required>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email Address</label>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           value="<?php echo htmlspecialchars($user['email']); ?>" readonly>
                                    <small class="text-muted">Email address cannot be changed. Contact admin for updates.</small>
                                </div>
                                
                                <!-- Address Information -->
                                <h6 class="text-primary mb-3 mt-4">Address Information</h6>
                                <div class="mb-3">
                                    <label for="address" class="form-label">Complete Address *</label>
                                    <textarea class="form-control" id="address" name="address" rows="3" required><?php echo htmlspecialchars($resident['address'] ?? ''); ?></textarea>
                                </div>
                                
                                <div class="row mb-3">
                                    <div class="col-md-4">
                                        <label for="barangay" class="form-label">Barangay</label>
                                        <input type="text" class="form-control" id="barangay" name="barangay" 
                                               value="<?php echo htmlspecialchars($resident['barangay'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label for="city" class="form-label">City/Municipality</label>
                                        <input type="text" class="form-control" id="city" name="city" 
                                               value="<?php echo htmlspecialchars($resident['city'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label for="province" class="form-label">Province</label>
                                        <input type="text" class="form-control" id="province" name="province" 
                                               value="<?php echo htmlspecialchars($resident['province'] ?? ''); ?>">
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="postal_code" class="form-label">Postal Code</label>
                                    <input type="text" class="form-control" id="postal_code" name="postal_code" 
                                           value="<?php echo htmlspecialchars($resident['postal_code'] ?? ''); ?>">
                                </div>
                                
                                <!-- Emergency Contact -->
                                <h6 class="text-primary mb-3 mt-4">Emergency Contact</h6>
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="emergency_contact_name" class="form-label">Emergency Contact Name</label>
                                        <input type="text" class="form-control" id="emergency_contact_name" name="emergency_contact_name" 
                                               value="<?php echo htmlspecialchars($resident['emergency_contact_name'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="emergency_contact_number" class="form-label">Emergency Contact Number</label>
                                        <input type="text" class="form-control" id="emergency_contact_number" name="emergency_contact_number" 
                                               value="<?php echo htmlspecialchars($resident['emergency_contact_number'] ?? ''); ?>">
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="emergency_contact_relationship" class="form-label">Relationship</label>
                                    <input type="text" class="form-control" id="emergency_contact_relationship" name="emergency_contact_relationship" 
                                           value="<?php echo htmlspecialchars($resident['emergency_contact_relationship'] ?? ''); ?>">
                                </div>
                                
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i>Save Profile
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Account Settings -->
                <div class="col-lg-4">
                    <!-- Account Information -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">
                                <i class="fas fa-user-shield me-2"></i>Account Information
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <strong>Username:</strong><br>
                                <span class="text-muted"><?php echo htmlspecialchars($user['username']); ?></span>
                            </div>
                            <div class="mb-3">
                                <strong>Email:</strong><br>
                                <span class="text-muted"><?php echo htmlspecialchars($user['email']); ?></span>
                            </div>
                            <div class="mb-3">
                                <strong>Account Status:</strong><br>
                                <span class="badge bg-<?php echo $user['status'] === 'active' ? 'success' : 'warning'; ?>">
                                    <?php echo ucfirst($user['status']); ?>
                                </span>
                            </div>
                            <div class="mb-3">
                                <strong>Member Since:</strong><br>
                                <span class="text-muted"><?php echo formatDate($user['created_at']); ?></span>
                            </div>
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
                                <input type="hidden" name="action" value="change_password">
                                
                                <div class="mb-3">
                                    <label for="current_password" class="form-label">Current Password</label>
                                    <input type="password" class="form-control" id="current_password" name="current_password" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="new_password" class="form-label">New Password</label>
                                    <input type="password" class="form-control" id="new_password" name="new_password" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="confirm_password" class="form-label">Confirm New Password</label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                </div>
                                
                                <button type="submit" class="btn btn-warning">
                                    <i class="fas fa-key me-1"></i>Change Password
                                </button>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Quick Actions -->
                    <div class="card shadow">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">
                                <i class="fas fa-bolt me-2"></i>Quick Actions
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <a href="request_new.php" class="btn btn-primary">
                                    <i class="fas fa-plus me-2"></i>New Document Request
                                </a>
                                <a href="request_status.php" class="btn btn-outline-primary">
                                    <i class="fas fa-list me-2"></i>View My Requests
                                </a>
                                <a href="dashboard.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-tachometer-alt me-2"></i>Back to Dashboard
                                </a>
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
    <!-- Custom JS -->
    <script src="../public/assets/js/app.js"></script>
</body>
</html>
