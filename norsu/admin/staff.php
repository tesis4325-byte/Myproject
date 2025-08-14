<?php
include 'includes/header.php';
require_once '../config/database.php';

// Handle staff actions first, before any output
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_staff']) || isset($_POST['update_staff'])) {
        $firstname = $conn->real_escape_string($_POST['firstname']);
        $lastname = $conn->real_escape_string($_POST['lastname']);
        $email = $conn->real_escape_string($_POST['email']);
        $status = $conn->real_escape_string($_POST['status']);
        
        if (isset($_POST['add_staff'])) {
            // Generate a random password
            $password = bin2hex(random_bytes(8));
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            $sql = "INSERT INTO users (firstname, lastname, email, password, role, status) 
                    VALUES ('$firstname', '$lastname', '$email', '$hashed_password', 'staff', '$status')";
            
            if ($conn->query($sql)) {
                // TODO: Send email with credentials
                $_SESSION['message'] = "Staff added successfully. Temporary password: $password";
            } else {
                $_SESSION['error'] = "Error adding staff: " . $conn->error;
            }
        } else {
            // Update existing staff
            $staff_id = (int)$_POST['staff_id'];
            $sql = "UPDATE users SET firstname = '$firstname', lastname = '$lastname', 
                    email = '$email', status = '$status' WHERE id = $staff_id AND role = 'staff'";
            
            if ($conn->query($sql)) {
                $_SESSION['message'] = "Staff updated successfully";
            } else {
                $_SESSION['error'] = "Error updating staff: " . $conn->error;
            }
        }
    } elseif (isset($_POST['delete_staff'])) {
        $staff_id = (int)$_POST['staff_id'];
        if ($conn->query("DELETE FROM users WHERE id = $staff_id AND role = 'staff'")) {
            $_SESSION['message'] = "Staff deleted successfully";
        } else {
            $_SESSION['error'] = "Error deleting staff: " . $conn->error;
        }
    } elseif (isset($_POST['reset_password'])) {
        $staff_id = (int)$_POST['staff_id'];
        $new_password = bin2hex(random_bytes(8));
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        
        if ($conn->query("UPDATE users SET password = '$hashed_password' WHERE id = $staff_id AND role = 'staff'")) {
            // TODO: Send email with new password
            $_SESSION['message'] = "Password reset successfully. New password: $new_password";
        } else {
            $_SESSION['error'] = "Error resetting password: " . $conn->error;
        }
    }
    
    header("Location: staff.php");
    exit();
}

// Get all staff members
$staff = $conn->query("
    SELECT id, firstname, lastname, email, status, last_login, created_at 
    FROM users 
    WHERE role = 'staff' 
    ORDER BY lastname, firstname
");
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <h2>Manage Staff</h2>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#staffModal">
                <i class="fas fa-plus"></i> Add Staff
            </button>
        </div>
    </div>

    <?php if (isset($_SESSION['message'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php 
            echo $_SESSION['message']; 
            unset($_SESSION['message']);
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php 
            echo $_SESSION['error']; 
            unset($_SESSION['error']);
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Status</th>
                            <th>Last Login</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($member = $staff->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($member['firstname'] . ' ' . $member['lastname']); ?></td>
                                <td><?php echo htmlspecialchars($member['email']); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $member['status'] === 'active' ? 'success' : 'warning'; ?>">
                                        <?php echo ucfirst(htmlspecialchars($member['status'])); ?>
                                    </span>
                                </td>
                                <td><?php echo $member['last_login'] ? date('M j, Y H:i', strtotime($member['last_login'])) : 'Never'; ?></td>
                                <td><?php echo date('M j, Y', strtotime($member['created_at'])); ?></td>
                                <td>
                                    <button class="btn btn-sm btn-primary edit-staff" 
                                            data-id="<?php echo $member['id']; ?>"
                                            data-firstname="<?php echo htmlspecialchars($member['firstname']); ?>"
                                            data-lastname="<?php echo htmlspecialchars($member['lastname']); ?>"
                                            data-email="<?php echo htmlspecialchars($member['email']); ?>"
                                            data-status="<?php echo htmlspecialchars($member['status']); ?>">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <form action="" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to reset the password?');">
                                        <input type="hidden" name="staff_id" value="<?php echo $member['id']; ?>">
                                        <button type="submit" name="reset_password" class="btn btn-sm btn-warning">
                                            <i class="fas fa-key"></i>
                                        </button>
                                    </form>
                                    <form action="" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this staff member?');">
                                        <input type="hidden" name="staff_id" value="<?php echo $member['id']; ?>">
                                        <button type="submit" name="delete_staff" class="btn btn-sm btn-danger">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Staff Modal -->
<div class="modal fade" id="staffModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Staff Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="staff_id" id="staff_id">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">First Name</label>
                            <input type="text" class="form-control" name="firstname" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Last Name</label>
                            <input type="text" class="form-control" name="lastname" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" name="email" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select" required>
                            <option value="active">Active</option>
                            <option value="pending">Pending</option>
                            <option value="blocked">Blocked</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" name="add_staff" class="btn btn-primary">Save Staff</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle edit button clicks
    document.querySelectorAll('.edit-staff').forEach(button => {
        button.addEventListener('click', function() {
            const modal = document.getElementById('staffModal');
            const form = modal.querySelector('form');
            
            // Set form values
            form.querySelector('[name="staff_id"]').value = this.dataset.id;
            form.querySelector('[name="firstname"]').value = this.dataset.firstname;
            form.querySelector('[name="lastname"]').value = this.dataset.lastname;
            form.querySelector('[name="email"]').value = this.dataset.email;
            form.querySelector('[name="status"]').value = this.dataset.status;
            
            // Change submit button
            const submitBtn = form.querySelector('button[type="submit"]');
            submitBtn.name = 'update_staff';
            submitBtn.textContent = 'Update Staff';
            
            // Show modal
            new bootstrap.Modal(modal).show();
        });
    });

    // Reset form when modal is hidden
    const staffModal = document.getElementById('staffModal');
    staffModal.addEventListener('hidden.bs.modal', function() {
        const form = this.querySelector('form');
        form.reset();
        form.querySelector('[name="staff_id"]').value = '';
        const submitBtn = form.querySelector('button[type="submit"]');
        submitBtn.name = 'add_staff';
        submitBtn.textContent = 'Save Staff';
    });
});
</script>

<?php include 'includes/footer.php'; ?>
