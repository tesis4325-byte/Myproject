<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'staff') {
    header("Location: ../index.php");
    exit();
}

require_once '../config/database.php';

// Handle position operations
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_position':
                $election_id = (int)$_POST['election_id'];
                $position_name = $conn->real_escape_string($_POST['position_name']);
                $max_votes = (int)$_POST['max_votes'];
                $description = $conn->real_escape_string($_POST['description']);

                $sql = "INSERT INTO positions (election_id, position_name, max_votes, description) 
                        VALUES (?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("isis", $election_id, $position_name, $max_votes, $description);
                
                if ($stmt->execute()) {
                    $_SESSION['success'] = "Position added successfully.";
                } else {
                    $_SESSION['error'] = "Error adding position.";
                }
                break;

            case 'edit_position':
                $position_id = (int)$_POST['position_id'];
                $position_name = $conn->real_escape_string($_POST['position_name']);
                $max_votes = (int)$_POST['max_votes'];
                $description = $conn->real_escape_string($_POST['description']);

                $sql = "UPDATE positions 
                        SET position_name = ?, max_votes = ?, description = ? 
                        WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sisi", $position_name, $max_votes, $description, $position_id);
                
                if ($stmt->execute()) {
                    $_SESSION['success'] = "Position updated successfully.";
                } else {
                    $_SESSION['error'] = "Error updating position.";
                }
                break;
        }
    }
}

// Get elections for dropdown
$elections = $conn->query("
    SELECT * FROM elections 
    WHERE status IN ('upcoming', 'ongoing') 
    ORDER BY start_date DESC
");

// Get election ID from query string or first available election
$current_election_id = isset($_GET['election']) ? (int)$_GET['election'] : 
                      ($elections->num_rows > 0 ? $elections->fetch_assoc()['id'] : 0);
$elections->data_seek(0);

// Get positions for current election
$positions = $conn->query("
    SELECT p.*, 
           (SELECT COUNT(*) FROM candidates WHERE position_id = p.id) as candidate_count
    FROM positions p
    WHERE p.election_id = $current_election_id
    ORDER BY p.position_name
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Positions - E-BOTO Staff Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
   <style>
             .sidebar {
            min-height: 100vh;
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            position: fixed;
            top: 0;
            left: 0;
            bottom: 0;
            z-index: 100;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }
        .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 12px 20px;
            transition: all 0.3s ease;
            border-left: 4px solid transparent;
            margin: 4px 0;
        }
        .nav-link:hover {
            color: white;
            background: rgba(255,255,255,0.15);
            border-left: 4px solid #ffffff;
            transform: translateX(5px);
        }
        .nav-link.active {
            background: rgba(255,255,255,0.2);
            color: white;
            border-left: 4px solid #ffffff;
        }
        .content {
            padding: 20px;
            margin-left: 16.66%; /* Offset for fixed sidebar */
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        .card:hover {
            transform: translateY(-5px);
        }
        .brand-logo {
            width: 80px;
            height: auto;
            margin-bottom: 10px;
        }
        .brand-section {
            padding: 25px 15px;
            background: rgba(255,255,255,0.1);
            margin-bottom: 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .brand-section h4 {
            font-weight: 700;
            margin: 10px 0 5px;
            font-size: 24px;
            background: linear-gradient(45deg, #fff, #f0f0f0);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .brand-section p {
            font-size: 14px;
            opacity: 0.9;
            margin: 0;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 px-0 sidebar">
                <div class="brand-section text-center">
                    <img src="../uploads/logo/logo.png" alt="E-BOTO Logo" class="brand-logo">
                    <h4>E-BOTO</h4>
                    <p>Staff Panel</p>
                </div>
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">
                            <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="manage-voters.php">
                            <i class="fas fa-users me-2"></i>Manage Voters
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="monitor-elections.php">
                            <i class="fas fa-vote-yea me-2"></i>Monitor Elections
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="manage-positions.php">
                            <i class="fas fa-sitemap me-2"></i>Positions
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="manage-candidates.php">
                            <i class="fas fa-user-tie me-2"></i>Candidates
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="view-reports.php">
                            <i class="fas fa-chart-bar me-2"></i>Reports
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../auth/logout.php">
                            <i class="fas fa-sign-out-alt me-2"></i>Logout
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 content">
                <div class="container-fluid">
                    <!-- Header -->
                    <div class="row mb-4">
                        <div class="col-12 d-flex justify-content-between align-items-center">
                            <h2>Manage Positions</h2>
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPositionModal">
                                <i class="fas fa-plus me-2"></i>Add New Position
                            </button>
                        </div>
                    </div>

                    <?php if (isset($_SESSION['success'])): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?php 
                            echo $_SESSION['success'];
                            unset($_SESSION['success']);
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

                    <!-- Election Selector -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <form method="GET" class="row align-items-center">
                                <div class="col-md-6">
                                    <label class="form-label">Select Election</label>
                                    <select class="form-select" name="election" onchange="this.form.submit()">
                                        <?php while ($election = $elections->fetch_assoc()): ?>
                                            <option value="<?php echo $election['id']; ?>"
                                                    <?php echo $election['id'] == $current_election_id ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($election['title']); ?>
                                                (<?php echo ucfirst($election['status']); ?>)
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Positions Grid -->
                    <div class="row">
                        <?php if ($positions->num_rows > 0): ?>
                            <?php while ($position = $positions->fetch_assoc()): ?>
                                <div class="col-md-6 col-lg-4">
                                    <div class="card position-card">
                                        <div class="card-body">
                                            <h5 class="card-title"><?php echo htmlspecialchars($position['position_name']); ?></h5>
                                            <p class="card-text"><?php echo htmlspecialchars($position['description']); ?></p>
                                            <div class="mb-3">
                                                <small class="text-muted">
                                                    <i class="fas fa-users me-1"></i>
                                                    Max Votes: <?php echo $position['max_votes']; ?>
                                                </small>
                                                <br>
                                                <small class="text-muted">
                                                    <i class="fas fa-user-tie me-1"></i>
                                                    Candidates: <?php echo $position['candidate_count']; ?>
                                                </small>
                                            </div>
                                            <div class="btn-group">
                                                <button class="btn btn-sm btn-primary" 
                                                        onclick="editPosition(<?php echo htmlspecialchars(json_encode($position)); ?>)">
                                                    <i class="fas fa-edit"></i> Edit
                                                </button>
                                                <a href="manage-candidates.php?position=<?php echo $position['id']; ?>" 
                                                   class="btn btn-sm btn-info">
                                                    <i class="fas fa-users"></i> Candidates
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="col-12">
                                <div class="alert alert-info">
                                    No positions have been created for this election yet.
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Position Modal -->
    <div class="modal fade" id="addPositionModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Position</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add_position">
                        <input type="hidden" name="election_id" value="<?php echo $current_election_id; ?>">
                        
                        <div class="mb-3">
                            <label class="form-label">Position Name</label>
                            <input type="text" class="form-control" name="position_name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Maximum Votes Allowed</label>
                            <input type="number" class="form-control" name="max_votes" value="1" min="1" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Position</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Position Modal -->
    <div class="modal fade" id="editPositionModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Position</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit_position">
                        <input type="hidden" name="position_id" id="edit_position_id">
                        
                        <div class="mb-3">
                            <label class="form-label">Position Name</label>
                            <input type="text" class="form-control" name="position_name" id="edit_position_name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Maximum Votes Allowed</label>
                            <input type="number" class="form-control" name="max_votes" id="edit_max_votes" min="1" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" id="edit_description" rows="3"></textarea>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editPosition(position) {
            document.getElementById('edit_position_id').value = position.id;
            document.getElementById('edit_position_name').value = position.position_name;
            document.getElementById('edit_max_votes').value = position.max_votes;
            document.getElementById('edit_description').value = position.description;
            
            new bootstrap.Modal(document.getElementById('editPositionModal')).show();
        }
    </script>
</body>
</html>
