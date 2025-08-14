<?php
include 'includes/header.php';
require_once '../config/database.php';

// Handle candidate actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_candidate']) || isset($_POST['update_candidate'])) {
        $firstname = $conn->real_escape_string($_POST['firstname']);
        $lastname = $conn->real_escape_string($_POST['lastname']);
        $position_id = (int)$_POST['position_id'];
        $bio = $conn->real_escape_string($_POST['bio']);          // Handle photo upload
        $photo = '';
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../uploads/candidates/';
            
            // Create directory if it doesn't exist
            if (!file_exists($upload_dir)) {
                if (!mkdir($upload_dir, 0777, true)) {
                    $_SESSION['error'] = "Failed to create upload directory: " . error_get_last()['message'];
                    header("Location: candidates.php");
                    exit();
                }
            }
            
            // Verify upload directory is writable
            if (!is_writable($upload_dir)) {
                $_SESSION['error'] = "Upload directory is not writable. Please check permissions for: " . $upload_dir;
                header("Location: candidates.php");
                exit();
            }
            
            $file_extension = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
            $allowed_extensions = ['jpg', 'jpeg', 'png'];
            
            if (!in_array($file_extension, $allowed_extensions)) {
                $_SESSION['error'] = "Only JPG, JPEG & PNG files are allowed. Uploaded file type: " . $file_extension;
                header("Location: candidates.php");
                exit();
            }
            
            $filename = uniqid('candidate_') . '.' . $file_extension;
            $target_file = $upload_dir . $filename;
            
            // Add debug information
            error_log("Attempting to upload file to: " . $target_file);
            error_log("Temp file location: " . $_FILES['photo']['tmp_name']);
            error_log("Upload error code: " . $_FILES['photo']['error']);
            
            if (!move_uploaded_file($_FILES['photo']['tmp_name'], $target_file)) {
                $upload_error = error_get_last();
                $_SESSION['error'] = "Failed to upload file. PHP Error: " . ($upload_error ? $upload_error['message'] : 'Unknown error') . 
                                   ". File error code: " . $_FILES['photo']['error'];
                header("Location: candidates.php");
                exit();
            }
            
            if (!file_exists($target_file)) {
                $_SESSION['error'] = "File was not saved after upload. Target location: " . $target_file;
                header("Location: candidates.php");
                exit();
            }
            
            $photo = $filename;  // Store only the filename
        }
        
        if (isset($_POST['add_candidate'])) {
            $sql = "INSERT INTO candidates (firstname, lastname, position_id, bio, photo) 
                    VALUES ('$firstname', '$lastname', $position_id, '$bio', '$photo')";
            $message = "Candidate added successfully";
        } else {
            $candidate_id = (int)$_POST['candidate_id'];
            $sql = "UPDATE candidates SET firstname = '$firstname', lastname = '$lastname', 
                    position_id = $position_id, bio = '$bio'";
            if ($photo) {
                // Delete old photo if it exists
                $old_photo = $conn->query("SELECT photo FROM candidates WHERE id = $candidate_id")->fetch_assoc()['photo'];
                if ($old_photo && file_exists('../' . $old_photo)) {
                    unlink('../' . $old_photo);
                }
                $sql .= ", photo = '$photo'";
            }
            $sql .= " WHERE id = $candidate_id";
            $message = "Candidate updated successfully";
        }
        
        if ($conn->query($sql)) {
            $_SESSION['message'] = $message;
        } else {
            $_SESSION['error'] = "Error: " . $conn->error;
        }
    } elseif (isset($_POST['delete_candidate'])) {
        $candidate_id = (int)$_POST['candidate_id'];
        
        // Delete photo file if it exists
        $photo = $conn->query("SELECT photo FROM candidates WHERE id = $candidate_id")->fetch_assoc()['photo'];
        if ($photo && file_exists('../' . $photo)) {
            unlink('../' . $photo);
        }
        
        if ($conn->query("DELETE FROM candidates WHERE id = $candidate_id")) {
            $_SESSION['message'] = "Candidate deleted successfully";
        } else {
            $_SESSION['error'] = "Error deleting candidate: " . $conn->error;
        }
    }
    
    header("Location: candidates.php");
    exit();
}

// Get candidates with position and election details
$candidates = $conn->query("
    SELECT c.*, p.position_name, e.title as election_title, e.status as election_status
    FROM candidates c
    JOIN positions p ON c.position_id = p.id
    JOIN elections e ON p.election_id = e.id
    ORDER BY e.title, p.position_name, c.lastname, c.firstname
");

// Get positions for dropdown (only from upcoming or ongoing elections)
$positions = $conn->query("
    SELECT p.id, p.position_name, e.title as election_title
    FROM positions p
    JOIN elections e ON p.election_id = e.id
    WHERE e.status IN ('upcoming', 'ongoing')
    ORDER BY e.title, p.position_name
");

// Get statistics
$stats = [
    'total_candidates' => $conn->query("SELECT COUNT(*) as count FROM candidates")->fetch_assoc()['count'],
    'active_positions' => $conn->query("
        SELECT COUNT(*) as count FROM positions p 
        JOIN elections e ON p.election_id = e.id 
        WHERE e.status IN ('upcoming', 'ongoing')
    ")->fetch_assoc()['count'],
    'total_elections' => $conn->query("SELECT COUNT(*) as count FROM elections")->fetch_assoc()['count']
];
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <h2>Manage Candidates</h2>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#candidateModal">
                <i class="fas fa-plus"></i> Add Candidate
            </button>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h5>Total Candidates</h5>
                    <h2><?php echo $stats['total_candidates']; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h5>Active Positions</h5>
                    <h2><?php echo $stats['active_positions']; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h5>Total Elections</h5>
                    <h2><?php echo $stats['total_elections']; ?></h2>
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
                            <th>Photo</th>
                            <th>Name</th>
                            <th>Position</th>
                            <th>Election</th>
                            <th>Status</th>
                            <th>Biography</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($candidate = $candidates->fetch_assoc()): ?>
                            <tr>
                                <td>                                    <?php if ($candidate['photo']): ?>
                                        <img src="../uploads/candidates/<?php echo htmlspecialchars($candidate['photo']); ?>" 
                                             alt="Candidate Photo" 
                                             class="rounded-circle"
                                             style="width: 50px; height: 50px; object-fit: cover;">
                                    <?php else: ?>
                                        <div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center"
                                             style="width: 50px; height: 50px;">
                                            <i class="fas fa-user text-white"></i>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($candidate['firstname'] . ' ' . $candidate['lastname']); ?></td>
                                <td><?php echo htmlspecialchars($candidate['position_name']); ?></td>
                                <td><?php echo htmlspecialchars($candidate['election_title']); ?></td>
                                <td>
                                    <span class="badge bg-<?php 
                                        echo match($candidate['election_status']) {
                                            'upcoming' => 'warning',
                                            'ongoing' => 'success',
                                            'completed' => 'secondary',
                                            default => 'info'
                                        };
                                    ?>">
                                        <?php echo ucfirst(htmlspecialchars($candidate['election_status'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <small class="text-muted">
                                        <?php echo nl2br(htmlspecialchars($candidate['bio'])); ?>
                                    </small>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-primary edit-candidate" 
                                            data-id="<?php echo $candidate['id']; ?>"
                                            data-firstname="<?php echo htmlspecialchars($candidate['firstname']); ?>"
                                            data-lastname="<?php echo htmlspecialchars($candidate['lastname']); ?>"
                                            data-position="<?php echo $candidate['position_id']; ?>"
                                            data-bio="<?php echo htmlspecialchars($candidate['bio']); ?>">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <form action="" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this candidate?');">
                                        <input type="hidden" name="candidate_id" value="<?php echo $candidate['id']; ?>">
                                        <button type="submit" name="delete_candidate" class="btn btn-sm btn-danger">
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

<!-- Candidate Modal -->
<div class="modal fade" id="candidateModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Candidate Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="" method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="candidate_id" id="candidate_id">
                    
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
                        <label class="form-label">Position</label>
                        <select name="position_id" class="form-select" required>
                            <option value="">Select Position</option>
                            <?php while($position = $positions->fetch_assoc()): ?>
                                <option value="<?php echo $position['id']; ?>">
                                    <?php echo htmlspecialchars($position['election_title'] . ' - ' . $position['position_name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Biography</label>
                        <textarea class="form-control" name="bio" rows="3"></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Photo</label>
                        <input type="file" class="form-control" name="photo" accept="image/jpeg,image/png">
                        <small class="text-muted">Leave empty to keep existing photo when updating</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" name="add_candidate" class="btn btn-primary">Save Candidate</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle edit button clicks
    document.querySelectorAll('.edit-candidate').forEach(button => {
        button.addEventListener('click', function() {
            const modal = document.getElementById('candidateModal');
            const form = modal.querySelector('form');
            
            // Set form values
            form.querySelector('[name="candidate_id"]').value = this.dataset.id;
            form.querySelector('[name="firstname"]').value = this.dataset.firstname;
            form.querySelector('[name="lastname"]').value = this.dataset.lastname;
            form.querySelector('[name="position_id"]').value = this.dataset.position;
            form.querySelector('[name="bio"]').value = this.dataset.bio;
            
            // Change submit button
            const submitBtn = form.querySelector('button[type="submit"]');
            submitBtn.name = 'update_candidate';
            submitBtn.textContent = 'Update Candidate';
            
            // Show modal
            new bootstrap.Modal(modal).show();
        });
    });

    // Reset form when modal is hidden
    const candidateModal = document.getElementById('candidateModal');
    candidateModal.addEventListener('hidden.bs.modal', function() {
        const form = this.querySelector('form');
        form.reset();
        form.querySelector('[name="candidate_id"]').value = '';
        const submitBtn = form.querySelector('button[type="submit"]');
        submitBtn.name = 'add_candidate';
        submitBtn.textContent = 'Save Candidate';
    });
});
</script>

<?php include 'includes/footer.php'; ?>
