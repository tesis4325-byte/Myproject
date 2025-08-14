<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'staff') {
    header("Location: ../index.php");
    exit();
}

require_once '../config/database.php';

$election_id = isset($_GET['election']) ? (int)$_GET['election'] : 0;
$position_id = isset($_GET['position']) ? (int)$_GET['position'] : 0;

// Verify election and position exist
$election = $conn->query("SELECT * FROM elections WHERE id = $election_id")->fetch_assoc();
$position = $conn->query("SELECT * FROM positions WHERE id = $position_id AND election_id = $election_id")->fetch_assoc();

if (!$election || !$position) {
    header("Location: monitor-elections.php");
    exit();
}

// Get candidates and their vote counts
$candidates = $conn->query("
    SELECT 
        c.*,
        COUNT(v.id) as vote_count,
        (SELECT COUNT(DISTINCT voter_id) FROM votes WHERE position_id = $position_id) as total_votes
    FROM candidates c
    LEFT JOIN votes v ON c.id = v.candidate_id AND v.position_id = $position_id
    WHERE c.position_id = $position_id
    GROUP BY c.id
    ORDER BY vote_count DESC
");

// Get voting timeline data
$voting_timeline = $conn->query("
    SELECT 
        DATE_FORMAT(voted_at, '%Y-%m-%d %H:00:00') as hour,
        COUNT(*) as votes
    FROM votes 
    WHERE position_id = $position_id
    GROUP BY hour
    ORDER BY hour ASC
");

$timeline_data = [];
while ($row = $voting_timeline->fetch_assoc()) {
    $timeline_data[] = [
        'hour' => $row['hour'],
        'votes' => (int)$row['votes']
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Position Results - E-BOTO Staff Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
   
        body {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            font-family: 'Arial', sans-serif;
        }
        .container-fluid {
            padding: 0;
        }
        .row {
            margin: 0;
        }
        body {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            font-family: 'Arial', sans-serif;
        }
        .container-fluid {
            padding: 0;
        }
        .row {
            margin: 0;
        }
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
            <!-- Sidebar -->            <div class="col-md-3 col-lg-2 px-0 sidebar">
                <div class="brand-section text-center">
                    <img src="../uploads/logo/images.png" alt="E-BOTO Logo" class="brand-logo">
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
                    <!-- Header -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <a href="monitor-elections.php" class="btn btn-outline-primary mb-3">
                                <i class="fas fa-arrow-left me-2"></i>Back to Elections
                            </a>
                            <h2><?php echo htmlspecialchars($election['title']); ?></h2>
                            <h4 class="text-muted"><?php echo htmlspecialchars($position['position_name']); ?> Results</h4>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Candidates Results -->
                        <div class="col-lg-8">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Candidate Rankings</h5>
                                </div>
                                <div class="card-body">
                                    <?php 
                                    $first = true;
                                    while ($candidate = $candidates->fetch_assoc()):
                                        $percentage = $candidate['total_votes'] > 0 
                                            ? ($candidate['vote_count'] / $candidate['total_votes']) * 100 
                                            : 0;
                                    ?>
                                        <div class="candidate-result mb-4 <?php echo $first ? 'winner' : ''; ?>">
                                            <div class="d-flex align-items-center mb-2">
                                                <img src="../uploads/candidates/<?php echo $candidate['photo']; ?>" 
                                                     alt="<?php echo htmlspecialchars($candidate['firstname'] . ' ' . $candidate['lastname']); ?>"
                                                     class="candidate-photo me-3">
                                                <div>
                                                    <h5 class="mb-0">
                                                        <?php echo htmlspecialchars($candidate['firstname'] . ' ' . $candidate['lastname']); ?>
                                                        <?php if ($first): ?>
                                                            <span class="badge bg-success ms-2">Leading</span>
                                                        <?php endif; ?>
                                                    </h5>
                                                    <p class="mb-0">
                                                        <?php echo $candidate['vote_count']; ?> votes
                                                        (<?php echo number_format($percentage, 1); ?>%)
                                                    </p>
                                                </div>
                                            </div>
                                            <div class="progress">
                                                <div class="progress-bar <?php echo $first ? 'bg-success' : 'bg-primary'; ?>" 
                                                     role="progressbar" 
                                                     style="width: <?php echo $percentage; ?>%">
                                                    <?php echo number_format($percentage, 1); ?>%
                                                </div>
                                            </div>
                                        </div>
                                        <?php $first = false; ?>
                                    <?php endwhile; ?>
                                </div>
                                <div class="card-footer">
                                    <small class="text-muted">
                                        Last updated: <?php echo date('F j, Y h:i:s A'); ?>
                                    </small>
                                </div>
                            </div>
                        </div>

                        <!-- Statistics -->
                        <div class="col-lg-4">
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Voting Statistics</h5>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label class="form-label">Total Votes Cast</label>
                                        <h3><?php echo $candidates->fetch_assoc()['total_votes'] ?? 0; ?></h3>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Election Status</label>
                                        <h5>
                                            <span class="badge bg-<?php 
                                                echo match($election['status']) {
                                                    'ongoing' => 'success',
                                                    'upcoming' => 'info',
                                                    'completed' => 'secondary',
                                                    default => 'secondary'
                                                };
                                            ?>">
                                                <?php echo ucfirst($election['status']); ?>
                                            </span>
                                        </h5>
                                    </div>
                                    <div>
                                        <label class="form-label">Time Remaining</label>
                                        <h5 class="countdown" data-end="<?php echo $election['end_date']; ?>"></h5>
                                    </div>
                                </div>
                            </div>

                            <!-- Voting Timeline -->
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Voting Timeline</h5>
                                </div>
                                <div class="card-body">
                                    <canvas id="votingTimeline"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Update countdown
        function updateCountdown() {
            const el = document.querySelector('.countdown');
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
        }

        setInterval(updateCountdown, 1000);
        updateCountdown();

        // Voting Timeline Chart
        const timelineData = <?php echo json_encode($timeline_data); ?>;
        const ctx = document.getElementById('votingTimeline').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: timelineData.map(data => {
                    const date = new Date(data.hour);
                    return date.toLocaleDateString() + ' ' + date.getHours() + ':00';
                }),
                datasets: [{
                    label: 'Votes per Hour',
                    data: timelineData.map(data => data.votes),
                    borderColor: '#1e3c72',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>
