<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'staff') {
    header("Location: ../index.php");
    exit();
}

require_once '../config/database.php';

// Get staff information
$staff_id = $_SESSION['user_id'];
$staff = $conn->query("SELECT * FROM users WHERE id = $staff_id")->fetch_assoc();

// Get active elections count
$active_elections = $conn->query("
    SELECT COUNT(*) as count 
    FROM elections 
    WHERE status = 'ongoing'
")->fetch_assoc()['count'];

// Get pending voter registrations
$pending_voters = $conn->query("
    SELECT COUNT(*) as count 
    FROM users 
    WHERE role = 'voter' 
    AND status = 'pending'
")->fetch_assoc()['count'];

// Get today's voter activity
$today_activity = $conn->query("
    SELECT COUNT(*) as count 
    FROM votes 
    WHERE DATE(voted_at) = CURDATE()
")->fetch_assoc()['count'];

// Get recent registrations
$recent_registrations = $conn->query("
    SELECT * FROM users 
    WHERE role = 'voter' 
    AND status = 'pending' 
    ORDER BY created_at DESC 
    LIMIT 5
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Dashboard - E-BOTO</title>
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
                        <a class="nav-link active" href="dashboard.php">
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
                    <!-- Welcome Message -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h2>Welcome, <?php echo htmlspecialchars($staff['firstname']); ?>!</h2>
                            <p class="text-muted">Here's what's happening today in the E-BOTO system.</p>
                        </div>
                    </div>

                    <!-- Statistics Cards -->
                    <div class="row g-4 mb-4">
                        <div class="col-md-4">
                            <div class="card stat-card bg-primary text-white">
                                <div class="card-body">
                                    <h5 class="card-title">Active Elections</h5>
                                    <h2><?php echo $active_elections; ?></h2>
                                    <p class="mb-0">Currently ongoing</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card stat-card bg-warning text-white">
                                <div class="card-body">
                                    <h5 class="card-title">Pending Approvals</h5>
                                    <h2><?php echo $pending_voters; ?></h2>
                                    <p class="mb-0">Voter registrations</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card stat-card bg-success text-white">
                                <div class="card-body">
                                    <h5 class="card-title">Today's Activity</h5>
                                    <h2><?php echo $today_activity; ?></h2>
                                    <p class="mb-0">Votes cast today</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Registrations -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Recent Voter Registration Requests</h5>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table">
                                            <thead>
                                                <tr>
                                                    <th>Name</th>
                                                    <th>Voter ID</th>
                                                    <th>Email</th>
                                                    <th>Registered On</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php while ($voter = $recent_registrations->fetch_assoc()): ?>
                                                    <tr>                                                        <td>
                                                            <?php 
                                                                $fullname = ($voter['firstname'] ?? '') . ' ' . ($voter['lastname'] ?? '');
                                                                echo htmlspecialchars(trim($fullname)); 
                                                            ?>
                                                        </td>
                                                        <td><?php echo htmlspecialchars($voter['voter_id'] ?? ''); ?></td>
                                                        <td><?php echo htmlspecialchars($voter['email'] ?? ''); ?></td>
                                                        <td>
                                                            <?php echo date('M j, Y', strtotime($voter['created_at'])); ?>
                                                        </td>
                                                        <td>
                                                            <a href="approve-voter.php?id=<?php echo $voter['id']; ?>" 
                                                               class="btn btn-sm btn-success">
                                                                <i class="fas fa-check"></i> Approve
                                                            </a>
                                                            <a href="reject-voter.php?id=<?php echo $voter['id']; ?>" 
                                                               class="btn btn-sm btn-danger">
                                                                <i class="fas fa-times"></i> Reject
                                                            </a>
                                                        </td>
                                                    </tr>
                                                <?php endwhile; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="text-end mt-3">
                                        <a href="manage-voters.php" class="btn btn-primary">
                                            View All Requests
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
