<?php
include 'includes/header.php';
require_once '../config/database.php';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create':
                $title = $conn->real_escape_string($_POST['title']);
                $description = $conn->real_escape_string($_POST['description']);
                $start_date = $conn->real_escape_string($_POST['start_date']);
                $end_date = $conn->real_escape_string($_POST['end_date']);
                
                $sql = "INSERT INTO elections (title, description, start_date, end_date, created_by) 
                        VALUES (?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssssi", $title, $description, $start_date, $end_date, $_SESSION['user_id']);
                
                if ($stmt->execute()) {
                    $_SESSION['success'] = "Election created successfully!";
                } else {
                    $_SESSION['error'] = "Error creating election: " . $conn->error;
                }
                break;

            case 'update':
                $id = (int)$_POST['election_id'];
                $title = $conn->real_escape_string($_POST['title']);
                $description = $conn->real_escape_string($_POST['description']);
                $start_date = $conn->real_escape_string($_POST['start_date']);
                $end_date = $conn->real_escape_string($_POST['end_date']);
                $status = $conn->real_escape_string($_POST['status']);
                
                $sql = "UPDATE elections SET title = ?, description = ?, start_date = ?, end_date = ?, status = ? WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sssssi", $title, $description, $start_date, $end_date, $status, $id);
                
                if ($stmt->execute()) {
                    $_SESSION['success'] = "Election updated successfully!";
                } else {
                    $_SESSION['error'] = "Error updating election: " . $conn->error;
                }
                break;

            case 'delete':
                $id = (int)$_POST['election_id'];
                
                // First check if the election has any votes
                $check_votes = $conn->query("SELECT COUNT(*) as count FROM votes WHERE election_id = $id")->fetch_assoc();
                
                if ($check_votes['count'] > 0) {
                    $_SESSION['error'] = "Cannot delete election: Votes have already been cast.";
                    break;
                }
                
                $sql = "DELETE FROM elections WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $id);
                
                if ($stmt->execute()) {
                    $_SESSION['success'] = "Election deleted successfully!";
                } else {
                    $_SESSION['error'] = "Error deleting election: " . $conn->error;
                }
                break;
        }
    }
}

// Get all elections
$elections = $conn->query("
    SELECT e.*, CONCAT(u.firstname, ' ', u.lastname) as creator 
    FROM elections e 
    LEFT JOIN users u ON e.created_by = u.id 
    ORDER BY e.created_at DESC
");
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <h2>Elections Management</h2>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createElectionModal">
                <i class="fas fa-plus me-2"></i>Create New Election
            </button>
        </div>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
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

    <!-- Elections Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Description</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th>Status</th>
                            <th>Created By</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($election = $elections->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($election['title']); ?></td>
                                <td><?php echo htmlspecialchars($election['description']); ?></td>
                                <td><?php echo date('M j, Y H:i', strtotime($election['start_date'])); ?></td>
                                <td><?php echo date('M j, Y H:i', strtotime($election['end_date'])); ?></td>
                                <td>
                                    <span class="badge bg-<?php 
                                        echo match($election['status']) {
                                            'upcoming' => 'info',
                                            'ongoing' => 'success',
                                            'completed' => 'secondary',
                                            'cancelled' => 'danger',
                                            default => 'secondary'
                                        };
                                    ?>">
                                        <?php echo ucfirst($election['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($election['creator']); ?></td>
                                <td>
                                    <button class="btn btn-sm btn-info" onclick="window.location.href='election-details.php?id=<?php echo $election['id']; ?>'">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="btn btn-sm btn-warning" onclick="editElection(<?php echo $election['id']; ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-danger" onclick="deleteElection(<?php echo $election['id']; ?>)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Create Election Modal -->
<div class="modal fade" id="createElectionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Create New Election</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="create">
                    
                    <div class="mb-3">
                        <label class="form-label">Title</label>
                        <input type="text" class="form-control" name="title" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="3"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Start Date</label>
                        <input type="datetime-local" class="form-control" name="start_date" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">End Date</label>
                        <input type="datetime-local" class="form-control" name="end_date" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Election</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Election Modal -->
<div class="modal fade" id="editElectionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Election</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="election_id" id="edit_election_id">
                    
                    <div class="mb-3">
                        <label class="form-label">Title</label>
                        <input type="text" class="form-control" name="title" id="edit_title" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" id="edit_description" rows="3"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Start Date</label>
                        <input type="datetime-local" class="form-control" name="start_date" id="edit_start_date" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">End Date</label>
                        <input type="datetime-local" class="form-control" name="end_date" id="edit_end_date" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select class="form-control" name="status" id="edit_status" required>
                            <option value="upcoming">Upcoming</option>
                            <option value="ongoing">Ongoing</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Form -->
<form id="deleteForm" method="POST" style="display: none;">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="election_id" id="delete_election_id">
</form>

<script>
function editElection(id) {
    // Fetch election data
    fetch(`get_election.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            // Populate the edit form
            document.getElementById('edit_election_id').value = data.id;
            document.getElementById('edit_title').value = data.title;
            document.getElementById('edit_description').value = data.description;
            document.getElementById('edit_start_date').value = data.start_date.slice(0, 16); // Remove seconds
            document.getElementById('edit_end_date').value = data.end_date.slice(0, 16); // Remove seconds
            document.getElementById('edit_status').value = data.status;
            
            // Show the modal
            new bootstrap.Modal(document.getElementById('editElectionModal')).show();
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error fetching election details');
        });
}

function deleteElection(id) {
    if (confirm('Are you sure you want to delete this election? This action cannot be undone.')) {
        document.getElementById('delete_election_id').value = id;
        document.getElementById('deleteForm').submit();
    }
}
</script>

<?php include 'includes/footer.php'; ?>
