<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'staff') {
    header("Location: ../index.php");
    exit();
}

require_once '../config/database.php';

// Handle file upload
function handleFileUpload($file) {
    $target_dir = "../uploads/candidates/";
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $new_filename = uniqid() . '.' . $file_extension;
    $target_file = $target_dir . $new_filename;
    
    // Check file size (5MB max)
    if ($file['size'] > 5000000) {
        throw new Exception("File is too large. Maximum size is 5MB.");
    }
    
    // Allow only images
    $allowed_types = ['jpg', 'jpeg', 'png'];
    if (!in_array($file_extension, $allowed_types)) {
        throw new Exception("Only JPG, JPEG & PNG files are allowed.");
    }
    
    if (move_uploaded_file($file['tmp_name'], $target_file)) {
        return $new_filename;
    }
    
    throw new Exception("Error uploading file.");
}

// Handle candidate operations
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        try {
            switch ($_POST['action']) {
                case 'add_candidate':
                    $position_id = (int)$_POST['position_id'];
                    $firstname = $conn->real_escape_string($_POST['firstname']);
                    $lastname = $conn->real_escape_string($_POST['lastname']);
                    $platform = $conn->real_escape_string($_POST['platform']);
                    
                    // Handle photo upload
                    $photo = 'default.jpg';
                    if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
                        $photo = handleFileUpload($_FILES['photo']);
                    }
                    
                    $sql = "INSERT INTO candidates (position_id, firstname, lastname, photo, platform) 
                            VALUES (?, ?, ?, ?, ?)";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("issss", $position_id, $firstname, $lastname, $photo, $platform);
                    
                    if ($stmt->execute()) {
                        $_SESSION['success'] = "Candidate added successfully.";
                    } else {
                        throw new Exception("Error adding candidate.");
                    }
                    break;

                case 'edit_candidate':
                    $candidate_id = (int)$_POST['candidate_id'];
                    $firstname = $conn->real_escape_string($_POST['firstname']);
                    $lastname = $conn->real_escape_string($_POST['lastname']);
                    $platform = $conn->real_escape_string($_POST['platform']);
                    
                    // Get current photo
                    $current_photo = $conn->query("SELECT photo FROM candidates WHERE id = $candidate_id")->fetch_assoc()['photo'];
                    
                    // Handle photo upload if new photo provided
                    if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
                        $photo = handleFileUpload($_FILES['photo']);
                        // Delete old photo if not default
                        if ($current_photo != 'default.jpg' && file_exists("../uploads/candidates/" . $current_photo)) {
                            unlink("../uploads/candidates/" . $current_photo);
                        }
                    } else {
                        $photo = $current_photo;
                    }
                    
                    $sql = "UPDATE candidates 
                            SET firstname = ?, lastname = ?, photo = ?, platform = ? 
                            WHERE id = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("ssssi", $firstname, $lastname, $photo, $platform, $candidate_id);
                    
                    if ($stmt->execute()) {
                        $_SESSION['success'] = "Candidate updated successfully.";
                    } else {
                        throw new Exception("Error updating candidate.");
                    }
                    break;

                case 'delete_candidate':
                    $candidate_id = (int)$_POST['candidate_id'];
                    
                    // Get candidate photo before deletion
                    $photo = $conn->query("SELECT photo FROM candidates WHERE id = $candidate_id")->fetch_assoc()['photo'];
                    
                    $sql = "DELETE FROM candidates WHERE id = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("i", $candidate_id);
                    
                    if ($stmt->execute()) {
                        // Delete photo if not default
                        if ($photo != 'default.jpg' && file_exists("../uploads/candidates/" . $photo)) {
                            unlink("../uploads/candidates/" . $photo);
                        }
                        $_SESSION['success'] = "Candidate deleted successfully.";
                    } else {
                        throw new Exception("Error deleting candidate.");
                    }
                    break;
            }
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
        }
    }
}

// Get position ID from query string
$position_id = isset($_GET['position']) ? (int)$_GET['position'] : 0;

// Get position details
$position = $conn->query("
    SELECT p.*, e.title as election_title, e.status as election_status 
    FROM positions p 
    JOIN elections e ON p.election_id = e.id 
    WHERE p.id = $position_id
")->fetch_assoc();

if (!$position) {
    header("Location: manage-positions.php");
    exit();
}

// Get candidates for this position
$candidates = $conn->query("
    SELECT * FROM candidates 
    WHERE position_id = $position_id 
    ORDER BY lastname, firstname
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Candidates - E-BOTO Staff Panel</title>
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
                        <a class="nav-link" href="manage-positions.php">
                            <i class="fas fa-sitemap me-2"></i>Positions
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="manage-candidates.php">
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
                        <div class="col-12">
                            <a href="manage-positions.php" class="btn btn-outline-primary mb-3">
                                <i class="fas fa-arrow-left me-2"></i>Back to Positions
                            </a>
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h2><?php echo htmlspecialchars($position['position_name']); ?></h2>
                                    <p class="text-muted">
                                        <?php echo htmlspecialchars($position['election_title']); ?> -
                                        <span class="badge bg-<?php 
                                            echo match($position['election_status']) {
                                                'upcoming' => 'info',
                                                'ongoing' => 'success',
                                                'completed' => 'secondary',
                                                default => 'secondary'
                                            };
                                        ?>">
                                            <?php echo ucfirst($position['election_status']); ?>
                                        </span>
                                    </p>
                                </div>
                                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCandidateModal">
                                    <i class="fas fa-plus me-2"></i>Add Candidate
                                </button>
                            </div>
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

                    <!-- Candidates Grid -->
                    <div class="row">
                        <?php if ($candidates->num_rows > 0): ?>
                            <?php while ($candidate = $candidates->fetch_assoc()): ?>
                                <div class="col-md-6 col-lg-4">
                                    <div class="card candidate-card">
                                        <div class="card-body text-center">
                                            <img src="../uploads/candidates/<?php echo $candidate['photo']; ?>" 
                                                 alt="<?php echo htmlspecialchars($candidate['firstname'] . ' ' . $candidate['lastname']); ?>"
                                                 class="candidate-photo">
                                            <h5 class="card-title">
                                                <?php echo htmlspecialchars($candidate['firstname'] . ' ' . $candidate['lastname']); ?>
                                            </h5>
                                            <p class="card-text">
                                                <?php echo nl2br(htmlspecialchars($candidate['platform'])); ?>
                                            </p>
                                            <div class="btn-group">
                                                <button class="btn btn-sm btn-primary" 
                                                        onclick="editCandidate(<?php echo htmlspecialchars(json_encode($candidate)); ?>)">
                                                    <i class="fas fa-edit"></i> Edit
                                                </button>
                                                <button class="btn btn-sm btn-danger" 
                                                        onclick="deleteCandidate(<?php echo $candidate['id']; ?>)">
                                                    <i class="fas fa-trash"></i> Delete
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="col-12">
                                <div class="alert alert-info">
                                    No candidates have been added for this position yet.
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Candidate Modal -->
    <div class="modal fade" id="addCandidateModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Candidate</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add_candidate">
                        <input type="hidden" name="position_id" value="<?php echo $position_id; ?>">
                        
                        <div class="mb-3">
                            <label class="form-label">First Name</label>
                            <input type="text" class="form-control" name="firstname" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Last Name</label>
                            <input type="text" class="form-control" name="lastname" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Photo</label>
                            <input type="file" class="form-control" name="photo" accept="image/*">
                            <small class="text-muted">Maximum size: 5MB. Allowed types: JPG, JPEG, PNG</small>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Platform/Description</label>
                            <textarea class="form-control" name="platform" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Candidate</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Candidate Modal -->
    <div class="modal fade" id="editCandidateModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Candidate</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit_candidate">
                        <input type="hidden" name="candidate_id" id="edit_candidate_id">
                        
                        <div class="mb-3">
                            <label class="form-label">First Name</label>
                            <input type="text" class="form-control" name="firstname" id="edit_firstname" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Last Name</label>
                            <input type="text" class="form-control" name="lastname" id="edit_lastname" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Photo</label>
                            <input type="file" class="form-control" name="photo" accept="image/*">
                            <small class="text-muted">Leave empty to keep current photo</small>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Platform/Description</label>
                            <textarea class="form-control" name="platform" id="edit_platform" rows="3"></textarea>
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

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteCandidateModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Delete Candidate</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to delete this candidate? This action cannot be undone.
                </div>
                <div class="modal-footer">
                    <form method="POST">
                        <input type="hidden" name="action" value="delete_candidate">
                        <input type="hidden" name="candidate_id" id="delete_candidate_id">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editCandidate(candidate) {
            document.getElementById('edit_candidate_id').value = candidate.id;
            document.getElementById('edit_firstname').value = candidate.firstname;
            document.getElementById('edit_lastname').value = candidate.lastname;
            document.getElementById('edit_platform').value = candidate.platform;
            
            new bootstrap.Modal(document.getElementById('editCandidateModal')).show();
        }

        function deleteCandidate(candidateId) {
            document.getElementById('delete_candidate_id').value = candidateId;
            new bootstrap.Modal(document.getElementById('deleteCandidateModal')).show();
        }
    </script>
</body>
</html>
