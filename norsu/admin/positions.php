<?php
include 'includes/header.php';
require_once '../config/database.php';

// Handle position deletion
if (isset($_POST['delete_position'])) {
    $position_id = (int)$_POST['position_id'];
    
    // Check if there are any candidates for this position
    $check_candidates = $conn->prepare("SELECT COUNT(*) as count FROM candidates WHERE position_id = ?");
    $check_candidates->bind_param("i", $position_id);
    $check_candidates->execute();
    $result = $check_candidates->get_result()->fetch_assoc();
    
    if ($result['count'] > 0) {
        $_SESSION['error'] = "Cannot delete position: There are candidates assigned to this position.";
    } else {
        $stmt = $conn->prepare("DELETE FROM positions WHERE id = ?");
        $stmt->bind_param("i", $position_id);
        if ($stmt->execute()) {
            $_SESSION['success'] = "Position deleted successfully";
        } else {
            $_SESSION['error'] = "Error deleting position: " . $conn->error;
        }
    }
    header("Location: positions.php");
    exit();
}

// Handle position addition/update
if (isset($_POST['save_position'])) {
    $position_name = $conn->real_escape_string($_POST['position_name']);
    $description = $conn->real_escape_string($_POST['description']);
    $election_id = (int)$_POST['election_id'];
    $max_votes = (int)$_POST['max_votes'];
      if (isset($_POST['position_id']) && !empty($_POST['position_id'])) {
        // Update existing position
        $position_id = (int)$_POST['position_id'];
        $stmt = $conn->prepare("UPDATE positions SET position_name = ?, description = ?, 
                election_id = ?, max_votes = ? WHERE id = ?");
        $stmt->bind_param("ssiii", $position_name, $description, $election_id, $max_votes, $position_id);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Position updated successfully";
        } else {
            $_SESSION['error'] = "Error updating position: " . $conn->error;
        }
    } else {
        // Add new position
        $stmt = $conn->prepare("INSERT INTO positions (position_name, description, election_id, max_votes) 
                VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssii", $position_name, $description, $election_id, $max_votes);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Position added successfully";
        } else {
            $_SESSION['error'] = "Error adding position: " . $conn->error;
        }
    }
    
    header("Location: positions.php");
    exit();
}

// Get all elections for dropdown
$elections = $conn->query("SELECT id, title FROM elections ORDER BY start_date DESC");

// Get all positions with election titles
$positions = $conn->query("
    SELECT p.*, e.title as election_title 
    FROM positions p 
    JOIN elections e ON p.election_id = e.id 
    ORDER BY e.start_date DESC, p.position_name ASC
");
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <h2>Manage Positions</h2>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#positionModal">
                <i class="fas fa-plus"></i> Add Position
            </button>
        </div>
    </div>    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php 
            echo $_SESSION['success']; 
            unset($_SESSION['success']);
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php 
            echo $_SESSION['error']; 
            unset($_SESSION['error']);
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Position Name</th>
                            <th>Election</th>
                            <th>Description</th>
                            <th>Max Votes</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($position = $positions->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($position['position_name']); ?></td>
                                <td><?php echo htmlspecialchars($position['election_title']); ?></td>
                                <td><?php echo htmlspecialchars($position['description']); ?></td>
                                <td><?php echo htmlspecialchars($position['max_votes']); ?></td>
                                <td>
                                    <button class="btn btn-sm btn-primary edit-position" 
                                            data-id="<?php echo $position['id']; ?>"
                                            data-name="<?php echo htmlspecialchars($position['position_name']); ?>"
                                            data-description="<?php echo htmlspecialchars($position['description']); ?>"
                                            data-election="<?php echo $position['election_id']; ?>"
                                            data-max-votes="<?php echo $position['max_votes']; ?>">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <form action="" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this position?');">
                                        <input type="hidden" name="position_id" value="<?php echo $position['id']; ?>">
                                        <button type="submit" name="delete_position" class="btn btn-sm btn-danger">
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

<!-- Position Modal -->
<div class="modal fade" id="positionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Position Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="position_id" id="position_id">
                    
                    <div class="mb-3">
                        <label class="form-label">Election</label>
                        <select name="election_id" class="form-select" required>
                            <option value="">Select Election</option>
                            <?php while($election = $elections->fetch_assoc()): ?>
                                <option value="<?php echo $election['id']; ?>">
                                    <?php echo htmlspecialchars($election['title']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Position Name</label>
                        <input type="text" class="form-control" name="position_name" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="3"></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Maximum Votes Allowed</label>
                        <input type="number" class="form-control" name="max_votes" value="1" min="1" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" name="save_position" class="btn btn-primary">Save Position</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle edit button clicks
    document.querySelectorAll('.edit-position').forEach(button => {
        button.addEventListener('click', function() {
            const modal = document.getElementById('positionModal');
            
            // Set form values
            modal.querySelector('[name="position_id"]').value = this.dataset.id;
            modal.querySelector('[name="position_name"]').value = this.dataset.name;
            modal.querySelector('[name="description"]').value = this.dataset.description;
            modal.querySelector('[name="election_id"]').value = this.dataset.election;
            modal.querySelector('[name="max_votes"]').value = this.dataset.maxVotes;
            
            // Show modal
            new bootstrap.Modal(modal).show();
        });
    });
});
</script>

<?php include 'includes/footer.php'; ?>
