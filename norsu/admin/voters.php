<?php
require_once '../config/database.php';

// Handle voter actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_voter']) || isset($_POST['update_voter'])) {
        $firstname = $conn->real_escape_string($_POST['firstname']);
        $lastname = $conn->real_escape_string($_POST['lastname']);
        $email = $conn->real_escape_string($_POST['email']);
        $status = $conn->real_escape_string($_POST['status']);
        $age_group = $conn->real_escape_string($_POST['age_group']);
        $gender = $conn->real_escape_string($_POST['gender']);
        $location = $conn->real_escape_string($_POST['location']);
        
        if (isset($_POST['add_voter'])) {
            // Generate a random password and voter ID
            $password = bin2hex(random_bytes(8));
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $voter_id = 'V' . date('Y') . rand(1000, 9999);
            
            $sql = "INSERT INTO users (voter_id, firstname, lastname, email, password, role, status, age_group, gender, location) 
                    VALUES ('$voter_id', '$firstname', '$lastname', '$email', '$hashed_password', 'voter', '$status', '$age_group', '$gender', '$location')";
            
            if ($conn->query($sql)) {
                // TODO: Send email with credentials
                $_SESSION['message'] = "Voter added successfully. Temporary password: $password";
            } else {
                $_SESSION['error'] = "Error adding voter: " . $conn->error;
            }
        } else {
            // Update existing voter
            $voter_id = (int)$_POST['user_id'];
            $sql = "UPDATE users SET firstname = '$firstname', lastname = '$lastname', 
                    email = '$email', status = '$status', age_group = '$age_group',
                    gender = '$gender', location = '$location' 
                    WHERE id = $voter_id AND role = 'voter'";
            
            if ($conn->query($sql)) {
                $_SESSION['message'] = "Voter updated successfully";
            } else {
                $_SESSION['error'] = "Error updating voter: " . $conn->error;
            }
        }
    } elseif (isset($_POST['delete_voter'])) {
        $voter_id = (int)$_POST['user_id'];
        if ($conn->query("DELETE FROM users WHERE id = $voter_id AND role = 'voter'")) {
            $_SESSION['message'] = "Voter deleted successfully";
        } else {
            $_SESSION['error'] = "Error deleting voter: " . $conn->error;
        }
    } elseif (isset($_POST['reset_password'])) {
        $voter_id = (int)$_POST['user_id'];
        $new_password = bin2hex(random_bytes(8));
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        
        if ($conn->query("UPDATE users SET password = '$hashed_password' WHERE id = $voter_id AND role = 'voter'")) {
            // TODO: Send email with new password
            $_SESSION['message'] = "Password reset successfully. New password: $new_password";
        } else {
            $_SESSION['error'] = "Error resetting password: " . $conn->error;
        }
    }
    
    header("Location: voters.php");
    exit();
}

// Get all voters with detailed information
$voters = $conn->query("
    SELECT id, voter_id, firstname, lastname, email, status, age_group, gender, location, last_login, created_at
    FROM users 
    WHERE role = 'voter' 
    ORDER BY status, lastname, firstname
");

// Get voter statistics
$voter_stats = [
    'total' => $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'voter'")->fetch_assoc()['count'],
    'active' => $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'voter' AND status = 'active'")->fetch_assoc()['count'],
    'pending' => $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'voter' AND status = 'pending'")->fetch_assoc()['count'],
    'blocked' => $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'voter' AND status = 'blocked'")->fetch_assoc()['count']
];

include 'includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <h2>Manage Voters</h2>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#voterModal">
                <i class="fas fa-plus"></i> Add Voter
            </button>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h5>Total Voters</h5>
                    <h2><?php echo $voter_stats['total']; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h5>Active Voters</h5>
                    <h2><?php echo $voter_stats['active']; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <h5>Pending Approval</h5>
                    <h2><?php echo $voter_stats['pending']; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-danger text-white">
                <div class="card-body">
                    <h5>Blocked Voters</h5>
                    <h2><?php echo $voter_stats['blocked']; ?></h2>
                </div>
            </div>
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
                            <th>Voter ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Status</th>
                            <th>Demographics</th>
                            <th>Last Login</th>
                            <th>Registered</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($voter = $voters->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($voter['voter_id'] ?? ''); ?></td>
<td><?php echo htmlspecialchars(($voter['firstname'] ?? '') . ' ' . ($voter['lastname'] ?? '')); ?></td>
<td><?php echo htmlspecialchars($voter['email'] ?? ''); ?></td>
<td>    <span class="badge bg-<?php 
        echo match($voter['status'] ?? '') {
            'active' => 'success',
            'pending' => 'warning',
            'blocked' => 'danger',
            default => 'secondary'
        };
    ?>">
        <?php echo ucfirst(htmlspecialchars($voter['status'] ?? '')); ?>
    </span>
</td>
<td>    <small>
        <?php 
        echo htmlspecialchars($voter['age_group'] ?? 'Not Set') . ' | ' . 
             ucfirst(htmlspecialchars(($voter['gender'] ?? 'Not Set'))) . ' | ' . 
             htmlspecialchars($voter['location'] ?? 'Not Set');
        ?>
    </small>
</td>

                                <td><?php echo $voter['last_login'] ? date('M j, Y H:i', strtotime($voter['last_login'])) : 'Never'; ?></td>
                                <td><?php echo date('M j, Y', strtotime($voter['created_at'])); ?></td>
                                <td>                                    <button class="btn btn-sm btn-primary edit-voter" 
                                            data-id="<?php echo $voter['id'] ?? ''; ?>"
                                            data-firstname="<?php echo htmlspecialchars($voter['firstname'] ?? ''); ?>"
                                            data-lastname="<?php echo htmlspecialchars($voter['lastname'] ?? ''); ?>"
                                            data-email="<?php echo htmlspecialchars($voter['email'] ?? ''); ?>"
                                            data-status="<?php echo htmlspecialchars($voter['status'] ?? ''); ?>"
                                            data-age-group="<?php echo htmlspecialchars($voter['age_group'] ?? ''); ?>"
                                            data-gender="<?php echo htmlspecialchars($voter['gender'] ?? ''); ?>"
                                            data-location="<?php echo htmlspecialchars($voter['location'] ?? ''); ?>">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <form action="" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to reset the password?');">
                                        <input type="hidden" name="user_id" value="<?php echo $voter['id']; ?>">
                                        <button type="submit" name="reset_password" class="btn btn-sm btn-warning">
                                            <i class="fas fa-key"></i>
                                        </button>
                                    </form>
                                    <form action="" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this voter?');">
                                        <input type="hidden" name="user_id" value="<?php echo $voter['id']; ?>">
                                        <button type="submit" name="delete_voter" class="btn btn-sm btn-danger">
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

<!-- Voter Modal -->
<div class="modal fade" id="voterModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Voter Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="user_id" id="user_id">
                    
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

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Age Group</label>
                            <select name="age_group" class="form-select" required>
                                <option value="">Select Age Group</option>
                                <option value="18-25">18-25</option>
                                <option value="26-35">26-35</option>
                                <option value="36-45">36-45</option>
                                <option value="46-55">46-55</option>
                                <option value="56+">56+</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Gender</label>
                            <select name="gender" class="form-select" required>
                                <option value="">Select Gender</option>
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Location</label>
                        <input type="text" class="form-control" name="location" required>
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
                    <button type="submit" name="add_voter" class="btn btn-primary">Save Voter</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle edit button clicks
    document.querySelectorAll('.edit-voter').forEach(button => {
        button.addEventListener('click', function() {
            const modal = document.getElementById('voterModal');
            const form = modal.querySelector('form');
            
            // Set form values
            form.querySelector('[name="user_id"]').value = this.dataset.id;
            form.querySelector('[name="firstname"]').value = this.dataset.firstname;
            form.querySelector('[name="lastname"]').value = this.dataset.lastname;
            form.querySelector('[name="email"]').value = this.dataset.email;
            form.querySelector('[name="status"]').value = this.dataset.status;
            form.querySelector('[name="age_group"]').value = this.dataset.ageGroup;
            form.querySelector('[name="gender"]').value = this.dataset.gender;
            form.querySelector('[name="location"]').value = this.dataset.location;
            
            // Change submit button
            const submitBtn = form.querySelector('button[type="submit"]');
            submitBtn.name = 'update_voter';
            submitBtn.textContent = 'Update Voter';
            
            // Show modal
            new bootstrap.Modal(modal).show();
        });
    });

    // Reset form when modal is hidden
    const voterModal = document.getElementById('voterModal');
    voterModal.addEventListener('hidden.bs.modal', function() {
        const form = this.querySelector('form');
        form.reset();
        form.querySelector('[name="user_id"]').value = '';
        const submitBtn = form.querySelector('button[type="submit"]');
        submitBtn.name = 'add_voter';
        submitBtn.textContent = 'Save Voter';
    });
});
</script>

<?php include 'includes/footer.php'; ?>
