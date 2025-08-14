<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'staff') {
    header("Location: ../index.php");
    exit();
}

require_once '../config/database.php';

// Handle search and filters
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$status = isset($_GET['status']) ? $conn->real_escape_string($_GET['status']) : 'all';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Build query based on filters
$where_clause = "WHERE role = 'voter'";
if ($search) {
    $where_clause .= " AND (firstname LIKE '%$search%' OR lastname LIKE '%$search%' OR voter_id LIKE '%$search%' OR email LIKE '%$search%')";
}
if ($status !== 'all') {
    $where_clause .= " AND status = '$status'";
}

// Get total records for pagination
$total_records = $conn->query("SELECT COUNT(*) as count FROM users $where_clause")->fetch_assoc()['count'];
$total_pages = ceil($total_records / $per_page);

// Get voters with pagination
$voters = $conn->query("
    SELECT * FROM users 
    $where_clause
    ORDER BY created_at DESC 
    LIMIT $offset, $per_page
");

// Handle bulk actions
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && isset($_POST['voter_ids'])) {
    $action = $_POST['action'];
    $voter_ids = array_map('intval', $_POST['voter_ids']);
    $ids_string = implode(',', $voter_ids);
    
    switch ($action) {
        case 'approve':
            $conn->query("UPDATE users SET status = 'active' WHERE id IN ($ids_string)");
            $_SESSION['success'] = "Selected voters have been approved.";
            break;
        case 'reject':
            $conn->query("UPDATE users SET status = 'blocked' WHERE id IN ($ids_string)");
            $_SESSION['success'] = "Selected voters have been rejected.";
            break;
    }
    
    header("Location: manage-voters.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Voters - E-BOTO Staff Panel</title>
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
                        <a class="nav-link active" href="manage-voters.php">
                            <i class="fas fa-users me-2"></i>Manage Voters
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="monitor-elections.php">
                            <i class="fas fa-vote-yea me-2"></i>Monitor Elections
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="view-reports.php">
                            <i class="fas fa-chart-bar me-2"></i>View Reports
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
                    <div class="row mb-4">
                        <div class="col-12">
                            <h2>Manage Voters</h2>
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

                    <!-- Filters and Search -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <form method="GET" class="row g-3">
                                <div class="col-md-4">
                                    <input type="text" class="form-control" name="search" 
                                           placeholder="Search by name, ID, or email" 
                                           value="<?php echo htmlspecialchars($search); ?>">
                                </div>
                                <div class="col-md-3">
                                    <select class="form-select" name="status">
                                        <option value="all" <?php echo $status == 'all' ? 'selected' : ''; ?>>All Status</option>
                                        <option value="pending" <?php echo $status == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                        <option value="active" <?php echo $status == 'active' ? 'selected' : ''; ?>>Active</option>
                                        <option value="blocked" <?php echo $status == 'blocked' ? 'selected' : ''; ?>>Blocked</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="fas fa-search me-2"></i>Filter
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Voters Table -->
                    <div class="card">
                        <div class="card-body">
                            <form method="POST">
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>
                                                    <input type="checkbox" id="select-all">
                                                </th>
                                                <th>Name</th>
                                                <th>Voter ID</th>
                                                <th>Email</th>
                                                <th>Status</th>
                                                <th>Registered</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($voter = $voters->fetch_assoc()): ?>
                                                <tr>
                                                    <td>
                                                        <input type="checkbox" name="voter_ids[]" 
                                                               value="<?php echo $voter['id']; ?>"
                                                               class="voter-checkbox">
                                                    </td>                                                    <td>
                                                        <?php 
                                                            $fullname = ($voter['firstname'] ?? '') . ' ' . ($voter['lastname'] ?? '');
                                                            echo htmlspecialchars(trim($fullname)); 
                                                        ?>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($voter['voter_id'] ?? ''); ?></td>
                                                    <td><?php echo htmlspecialchars($voter['email'] ?? ''); ?></td>
                                                    <td>
                                                        <span class="badge bg-<?php 
                                                            echo match($voter['status']) {
                                                                'pending' => 'warning',
                                                                'active' => 'success',
                                                                'blocked' => 'danger',
                                                                default => 'secondary'
                                                            };
                                                        ?>">
                                                            <?php echo ucfirst($voter['status']); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <?php echo date('M j, Y', strtotime($voter['created_at'])); ?>
                                                    </td>
                                                    <td>
                                                        <button type="button" 
                                                                class="btn btn-sm btn-info"
                                                                onclick="viewVoter(<?php echo $voter['id']; ?>)">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                        <?php if ($voter['status'] == 'pending'): ?>
                                                            <button type="button" 
                                                                    class="btn btn-sm btn-success"
                                                                    onclick="approveVoter(<?php echo $voter['id']; ?>)">
                                                                <i class="fas fa-check"></i>
                                                            </button>
                                                            <button type="button" 
                                                                    class="btn btn-sm btn-danger"
                                                                    onclick="rejectVoter(<?php echo $voter['id']; ?>)">
                                                                <i class="fas fa-times"></i>
                                                            </button>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>

                                <!-- Bulk Actions -->
                                <div class="row mt-3">
                                    <div class="col-md-6">
                                        <select name="action" class="form-select w-auto d-inline-block me-2">
                                            <option value="">Bulk Actions</option>
                                            <option value="approve">Approve Selected</option>
                                            <option value="reject">Reject Selected</option>
                                        </select>
                                        <button type="submit" class="btn btn-primary">Apply</button>
                                    </div>
                                    <div class="col-md-6">
                                        <!-- Pagination -->
                                        <nav aria-label="Page navigation" class="float-end">
                                            <ul class="pagination mb-0">
                                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                                    <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                                                        <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo $status; ?>">
                                                            <?php echo $i; ?>
                                                        </a>
                                                    </li>
                                                <?php endfor; ?>
                                            </ul>
                                        </nav>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Select all checkbox functionality
        document.getElementById('select-all').addEventListener('change', function() {
            document.querySelectorAll('.voter-checkbox').forEach(checkbox => {
                checkbox.checked = this.checked;
            });
        });

        // Individual voter actions
        function viewVoter(id) {
            // Implement view functionality
            window.location.href = `view-voter.php?id=${id}`;
        }

        function approveVoter(id) {
            if (confirm('Are you sure you want to approve this voter?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="approve">
                    <input type="hidden" name="voter_ids[]" value="${id}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        function rejectVoter(id) {
            if (confirm('Are you sure you want to reject this voter?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="reject">
                    <input type="hidden" name="voter_ids[]" value="${id}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>
</html>
