<?php
/**
 * Barangay Document Request and Tracking System
 * Resident Registration Page
 */

require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Redirect if already logged in
if ($auth->isLoggedIn()) {
    if ($auth->isAdmin()) {
        header('Location: ../admin/dashboard.php');
    } else {
        header('Location: ../resident/dashboard.php');
    }
    exit();
}

$error = '';
$success = '';

// Handle registration
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate user data
        $userData = [
            'username' => sanitizeInput($_POST['username'] ?? ''),
            'email' => sanitizeInput($_POST['email'] ?? ''),
            'password' => $_POST['password'] ?? '',
            'confirm_password' => $_POST['confirm_password'] ?? ''
        ];
        
        // Validate resident data
        $residentData = [
            'first_name' => sanitizeInput($_POST['first_name'] ?? ''),
            'last_name' => sanitizeInput($_POST['last_name'] ?? ''),
            'middle_name' => sanitizeInput($_POST['middle_name'] ?? ''),
            'birth_date' => $_POST['birth_date'] ?? '',
            'gender' => sanitizeInput($_POST['gender'] ?? ''),
            'civil_status' => sanitizeInput($_POST['civil_status'] ?? ''),
            'nationality' => sanitizeInput($_POST['nationality'] ?? ''),
            'contact_number' => sanitizeInput($_POST['contact_number'] ?? ''),
            'address' => sanitizeInput($_POST['address'] ?? ''),
            'barangay' => sanitizeInput($_POST['barangay'] ?? ''),
            'city' => sanitizeInput($_POST['city'] ?? ''),
            'province' => sanitizeInput($_POST['province'] ?? ''),
            'postal_code' => sanitizeInput($_POST['postal_code'] ?? ''),
            'emergency_contact_name' => sanitizeInput($_POST['emergency_contact_name'] ?? ''),
            'emergency_contact_number' => sanitizeInput($_POST['emergency_contact_number'] ?? ''),
            'occupation' => sanitizeInput($_POST['occupation'] ?? ''),
            'monthly_income' => floatval($_POST['monthly_income'] ?? 0)
        ];
        
        // Validation
        $errors = [];
        
        // Username validation
        if (empty($userData['username']) || strlen($userData['username']) < 3) {
            $errors[] = 'Username must be at least 3 characters long.';
        }
        
        // Email validation
        if (empty($userData['email']) || !validateEmail($userData['email'])) {
            $errors[] = 'Please enter a valid email address.';
        }
        
        // Password validation
        if (empty($userData['password']) || strlen($userData['password']) < 6) {
            $errors[] = 'Password must be at least 6 characters long.';
        }
        
        if ($userData['password'] !== $userData['confirm_password']) {
            $errors[] = 'Passwords do not match.';
        }
        
        // Required resident fields
        $requiredFields = ['first_name', 'last_name', 'birth_date', 'gender', 'civil_status', 'address', 'barangay', 'city', 'province'];
        foreach ($requiredFields as $field) {
            if (empty($residentData[$field])) {
                $errors[] = ucfirst(str_replace('_', ' ', $field)) . ' is required.';
            }
        }
        
        // Check if username/email already exists
        if ($auth->userExists($userData['username'], $userData['email'])) {
            $errors[] = 'Username or email already exists.';
        }
        
        // If no errors, proceed with registration
        if (empty($errors)) {
            $userId = $auth->registerResident($userData, $residentData);
            $success = 'Registration successful! Your account is pending approval. You will be notified once approved.';
        } else {
            $error = implode('<br>', $errors);
        }
        
    } catch (Exception $e) {
        $error = 'Registration failed. Please try again.';
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
    <title>Register - <?php echo $barangayName; ?></title>
    
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
            <div class="row justify-content-center">
                <div class="col-md-10 col-lg-8">
                    <div class="auth-card">
                        <div class="text-center mb-4">
                            <h2 class="text-primary mb-2">
                                <i class="fas fa-user-plus me-2"></i>Resident Registration
                            </h2>
                            <p class="text-muted">Create your account to access document request services</p>
                        </div>
                        
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
                                <a href="index.php" class="btn btn-primary">
                                    <i class="fas fa-sign-in-alt me-2"></i>Go to Login
                                </a>
                            </div>
                        <?php else: ?>
                        
                        <form method="POST" class="needs-validation" novalidate>
                            <!-- Account Information -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="mb-0">
                                        <i class="fas fa-user me-2"></i>Account Information
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="username" class="form-label">Username *</label>
                                            <input type="text" class="form-control" id="username" name="username" 
                                                   value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" 
                                                   required minlength="3">
                                            <div class="invalid-feedback">
                                                Username must be at least 3 characters long.
                                            </div>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="email" class="form-label">Email Address *</label>
                                            <input type="email" class="form-control" id="email" name="email" 
                                                   value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" 
                                                   required>
                                            <div class="invalid-feedback">
                                                Please enter a valid email address.
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="password" class="form-label">Password *</label>
                                            <div class="input-group">
                                                <input type="password" class="form-control" id="password" name="password" 
                                                       required minlength="6">
                                                <button class="btn btn-outline-secondary" type="button" 
                                                        onclick="togglePasswordVisibility('password')">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            </div>
                                            <div class="invalid-feedback">
                                                Password must be at least 6 characters long.
                                            </div>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="confirm_password" class="form-label">Confirm Password *</label>
                                            <div class="input-group">
                                                <input type="password" class="form-control" id="confirm_password" 
                                                       name="confirm_password" required>
                                                <button class="btn btn-outline-secondary" type="button" 
                                                        onclick="togglePasswordVisibility('confirm_password')">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            </div>
                                            <div class="invalid-feedback">
                                                Passwords do not match.
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Personal Information -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="mb-0">
                                        <i class="fas fa-id-card me-2"></i>Personal Information
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-4 mb-3">
                                            <label for="first_name" class="form-label">First Name *</label>
                                            <input type="text" class="form-control" id="first_name" name="first_name" 
                                                   value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>" 
                                                   required>
                                            <div class="invalid-feedback">
                                                First name is required.
                                            </div>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label for="middle_name" class="form-label">Middle Name</label>
                                            <input type="text" class="form-control" id="middle_name" name="middle_name" 
                                                   value="<?php echo htmlspecialchars($_POST['middle_name'] ?? ''); ?>">
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label for="last_name" class="form-label">Last Name *</label>
                                            <input type="text" class="form-control" id="last_name" name="last_name" 
                                                   value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>" 
                                                   required>
                                            <div class="invalid-feedback">
                                                Last name is required.
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-4 mb-3">
                                            <label for="birth_date" class="form-label">Date of Birth *</label>
                                            <input type="date" class="form-control" id="birth_date" name="birth_date" 
                                                   value="<?php echo htmlspecialchars($_POST['birth_date'] ?? ''); ?>" 
                                                   required>
                                            <div class="invalid-feedback">
                                                Date of birth is required.
                                            </div>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label for="gender" class="form-label">Gender *</label>
                                            <select class="form-select" id="gender" name="gender" required>
                                                <option value="">Select Gender</option>
                                                <option value="male" <?php echo ($_POST['gender'] ?? '') === 'male' ? 'selected' : ''; ?>>Male</option>
                                                <option value="female" <?php echo ($_POST['gender'] ?? '') === 'female' ? 'selected' : ''; ?>>Female</option>
                                                <option value="other" <?php echo ($_POST['gender'] ?? '') === 'other' ? 'selected' : ''; ?>>Other</option>
                                            </select>
                                            <div class="invalid-feedback">
                                                Please select your gender.
                                            </div>
                                        </div>
                                                                                 <div class="col-md-4 mb-3">
                                             <label for="civil_status" class="form-label">Civil Status *</label>
                                             <select class="form-select" id="civil_status" name="civil_status" required>
                                                 <option value="">Select Civil Status</option>
                                                 <option value="single" <?php echo ($_POST['civil_status'] ?? '') === 'single' ? 'selected' : ''; ?>>Single</option>
                                                 <option value="married" <?php echo ($_POST['civil_status'] ?? '') === 'married' ? 'selected' : ''; ?>>Married</option>
                                                 <option value="widowed" <?php echo ($_POST['civil_status'] ?? '') === 'widowed' ? 'selected' : ''; ?>>Widowed</option>
                                                 <option value="divorced" <?php echo ($_POST['civil_status'] ?? '') === 'divorced' ? 'selected' : ''; ?>>Divorced</option>
                                             </select>
                                             <div class="invalid-feedback">
                                                 Please select your civil status.
                                             </div>
                                         </div>
                                         <div class="col-md-4 mb-3">
                                             <label for="nationality" class="form-label">Nationality</label>
                                             <input type="text" class="form-control" id="nationality" name="nationality" 
                                                    value="<?php echo htmlspecialchars($_POST['nationality'] ?? 'Filipino'); ?>" 
                                                    placeholder="e.g., Filipino">
                                         </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="contact_number" class="form-label">Contact Number</label>
                                            <input type="tel" class="form-control" id="contact_number" name="contact_number" 
                                                   value="<?php echo htmlspecialchars($_POST['contact_number'] ?? ''); ?>">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="occupation" class="form-label">Occupation</label>
                                            <input type="text" class="form-control" id="occupation" name="occupation" 
                                                   value="<?php echo htmlspecialchars($_POST['occupation'] ?? ''); ?>">
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="monthly_income" class="form-label">Monthly Income (₱)</label>
                                            <input type="number" class="form-control" id="monthly_income" name="monthly_income" 
                                                   value="<?php echo htmlspecialchars($_POST['monthly_income'] ?? ''); ?>" 
                                                   min="0" step="0.01">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Address Information -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="mb-0">
                                        <i class="fas fa-map-marker-alt me-2"></i>Address Information
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label for="address" class="form-label">Complete Address *</label>
                                        <textarea class="form-control" id="address" name="address" rows="3" required><?php echo htmlspecialchars($_POST['address'] ?? ''); ?></textarea>
                                        <div class="invalid-feedback">
                                            Complete address is required.
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-4 mb-3">
                                            <label for="barangay" class="form-label">Barangay *</label>
                                            <input type="text" class="form-control" id="barangay" name="barangay" 
                                                   value="<?php echo htmlspecialchars($_POST['barangay'] ?? ''); ?>" 
                                                   required>
                                            <div class="invalid-feedback">
                                                Barangay is required.
                                            </div>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label for="city" class="form-label">City/Municipality *</label>
                                            <input type="text" class="form-control" id="city" name="city" 
                                                   value="<?php echo htmlspecialchars($_POST['city'] ?? ''); ?>" 
                                                   required>
                                            <div class="invalid-feedback">
                                                City/Municipality is required.
                                            </div>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label for="province" class="form-label">Province *</label>
                                            <input type="text" class="form-control" id="province" name="province" 
                                                   value="<?php echo htmlspecialchars($_POST['province'] ?? ''); ?>" 
                                                   required>
                                            <div class="invalid-feedback">
                                                Province is required.
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="postal_code" class="form-label">Postal Code</label>
                                            <input type="text" class="form-control" id="postal_code" name="postal_code" 
                                                   value="<?php echo htmlspecialchars($_POST['postal_code'] ?? ''); ?>">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Emergency Contact -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="mb-0">
                                        <i class="fas fa-phone me-2"></i>Emergency Contact
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                                                                 <div class="col-md-6 mb-3">
                                             <label for="emergency_contact_name" class="form-label">Emergency Contact Person</label>
                                             <input type="text" class="form-control" id="emergency_contact_name" name="emergency_contact_name" 
                                                    value="<?php echo htmlspecialchars($_POST['emergency_contact_name'] ?? ''); ?>">
                                         </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="emergency_contact_number" class="form-label">Emergency Contact Number</label>
                                            <input type="tel" class="form-control" id="emergency_contact_number" name="emergency_contact_number" 
                                                   value="<?php echo htmlspecialchars($_POST['emergency_contact_number'] ?? ''); ?>">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-user-plus me-2"></i>Register Account
                                </button>
                            </div>
                        </form>
                        
                        <hr class="my-4">
                        
                        <div class="text-center">
                            <p class="mb-0">Already have an account?</p>
                            <a href="index.php" class="btn btn-outline-primary">
                                <i class="fas fa-sign-in-alt me-2"></i>Login
                            </a>
                        </div>
                        
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
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
        
        // Password confirmation validation
        document.getElementById('confirm_password').addEventListener('input', function() {
            const password = document.getElementById('password').value;
            const confirmPassword = this.value;
            
            if (password !== confirmPassword) {
                this.setCustomValidity('Passwords do not match');
            } else {
                this.setCustomValidity('');
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
