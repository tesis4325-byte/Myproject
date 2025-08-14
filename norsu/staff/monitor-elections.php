<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'staff') {
    header("Location: ../index.php");
    exit();
}

require_once '../config/database.php';

// Get all elections with basic statistics
$elections = $conn->query("
    SELECT 
        e.*,
        (SELECT COUNT(DISTINCT voter_id) FROM votes WHERE election_id = e.id) as total_votes,
        (SELECT COUNT(*) FROM users WHERE role = 'voter' AND status = 'active') as total_voters,
        (SELECT COUNT(DISTINCT p.id) FROM positions p WHERE p.election_id = e.id) as total_positions
    FROM elections e 
    ORDER BY 
        CASE 
            WHEN status = 'ongoing' THEN 1
            WHEN status = 'upcoming' THEN 2
            ELSE 3
        END,
        start_date DESC
");

// Get real-time voting statistics for active elections
$active_election_ids = [];
$active_elections_stats = [];
while ($election = $elections->fetch_assoc()) {
    if ($election['status'] == 'ongoing') {
        $active_election_ids[] = $election['id'];
        
        // Get position-wise voting statistics
        $positions = $conn->query("
            SELECT 
                p.id,
                p.position_name,
                (SELECT COUNT(DISTINCT voter_id) FROM votes WHERE position_id = p.id) as votes_cast,
                (SELECT COUNT(*) FROM candidates WHERE position_id = p.id) as total_candidates
            FROM positions p
            WHERE p.election_id = {$election['id']}
        ");
        
        $active_elections_stats[$election['id']] = [
            'positions' => [],
            'total_votes' => $election['total_votes'],
            'voter_turnout' => ($election['total_votes'] / $election['total_voters']) * 100
        ];
        
        while ($position = $positions->fetch_assoc()) {
            $active_elections_stats[$election['id']]['positions'][] = $position;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monitor Elections - E-BOTO Staff Panel</title>
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
                        <a class="nav-link active" href="monitor-elections.php">
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
                        <div class="col-12 d-flex justify-content-between align-items-center">
                            <h2>Election Monitoring</h2>
                            <button class="btn btn-primary refresh-btn" onclick="location.reload()">
                                <i class="fas fa-sync-alt"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Active Elections -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h3 class="mb-3">Active Elections</h3>
                            <?php
                            $elections->data_seek(0);
                            $found_active = false;
                            while ($election = $elections->fetch_assoc()):
                                if ($election['status'] == 'ongoing'):
                                    $found_active = true;
                                    $stats = $active_elections_stats[$election['id']];
                            ?>
                                <div class="card">
                                    <div class="card-header bg-primary text-white">
                                        <h5 class="card-title mb-0"><?php echo htmlspecialchars($election['title']); ?></h5>
                                    </div>
                                    <div class="card-body">
                                        <!-- Overall Statistics -->
                                        <div class="row mb-4">
                                            <div class="col-md-6">
                                                <h6>Voter Turnout</h6>
                                                <div class="progress mb-2">
                                                    <div class="progress-bar bg-success" 
                                                         role="progressbar" 
                                                         style="width: <?php echo $stats['voter_turnout']; ?>%">
                                                        <?php echo number_format($stats['voter_turnout'], 1); ?>%
                                                    </div>
                                                </div>
                                                <small class="text-muted">
                                                    <?php echo $stats['total_votes']; ?> out of <?php echo $election['total_voters']; ?> voters
                                                </small>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="d-flex justify-content-between">
                                                    <span>Time Remaining:</span>
                                                    <span class="countdown" data-end="<?php echo $election['end_date']; ?>"></span>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Position-wise Statistics -->
                                        <h6>Position-wise Statistics</h6>
                                        <div class="table-responsive">
                                            <table class="table">
                                                <thead>
                                                    <tr>
                                                        <th>Position</th>
                                                        <th>Votes Cast</th>
                                                        <th>Progress</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($stats['positions'] as $position): ?>
                                                        <tr>
                                                            <td><?php echo htmlspecialchars($position['position_name']); ?></td>
                                                            <td>
                                                                <?php echo $position['votes_cast']; ?> votes
                                                                <br>
                                                                <small class="text-muted">
                                                                    <?php echo $position['total_candidates']; ?> candidates
                                                                </small>
                                                            </td>
                                                            <td style="width: 40%;">
                                                                <div class="progress">
                                                                    <div class="progress-bar" 
                                                                         role="progressbar" 
                                                                         style="width: <?php echo ($position['votes_cast'] / $election['total_voters']) * 100; ?>%">
                                                                        <?php echo number_format(($position['votes_cast'] / $election['total_voters']) * 100, 1); ?>%
                                                                    </div>
                                                                </div>
                                                            </td>
                                                            <td>
                                                                <button class="btn btn-sm btn-info" 
                                                                        onclick="viewResults(<?php echo $election['id']; ?>, <?php echo $position['id']; ?>)">
                                                                    <i class="fas fa-chart-bar"></i> View Results
                                                                </button>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            <?php
                                endif;
                            endwhile;
                            if (!$found_active):
                            ?>
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>There are no active elections at the moment.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Upcoming Elections -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <h3 class="mb-3">Upcoming Elections</h3>
                            <?php
                            $elections->data_seek(0);
                            $found_upcoming = false;
                            while ($election = $elections->fetch_assoc()):
                                if ($election['status'] == 'upcoming'):
                                    $found_upcoming = true;
                            ?>
                                <div class="card">
                                    <div class="card-body">
                                        <h5 class="card-title"><?php echo htmlspecialchars($election['title']); ?></h5>
                                        <p class="card-text"><?php echo htmlspecialchars($election['description']); ?></p>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <p class="mb-1">
                                                    <strong>Start Date:</strong> 
                                                    <?php echo date('F j, Y h:i A', strtotime($election['start_date'])); ?>
                                                </p>
                                                <p class="mb-1">
                                                    <strong>End Date:</strong>
                                                    <?php echo date('F j, Y h:i A', strtotime($election['end_date'])); ?>
                                                </p>
                                            </div>
                                            <div class="col-md-6">
                                                <p class="mb-1">
                                                    <strong>Positions:</strong> <?php echo $election['total_positions']; ?>
                                                </p>
                                                <p class="mb-1">
                                                    <strong>Status:</strong>
                                                    <span class="badge bg-info">Upcoming</span>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php
                                endif;
                            endwhile;
                            if (!$found_upcoming):
                            ?>
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>There are no upcoming elections.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Update countdowns
        function updateCountdowns() {
            document.querySelectorAll('.countdown').forEach(el => {
                const endDate = new Date(el.dataset.end);
                const now = new Date();
                const diff = endDate - now;

                if (diff <= 0) {
                    el.textContent = 'Election ended';
                    return;
                }

                const days = Math.floor(diff / (1000 * 60 * 60 * 24));
                const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
                const seconds = Math.floor((diff % (1000 * 60)) / 1000);

                el.textContent = `${days}d ${hours}h ${minutes}m ${seconds}s`;
            });
        }

        setInterval(updateCountdowns, 1000);
        updateCountdowns();

        function viewResults(electionId, positionId) {
            window.location.href = `view-position-results.php?election=${electionId}&position=${positionId}`;
        }
    </script>
</body>
</html>
