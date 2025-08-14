<?php
include 'includes/header.php';
require_once '../config/database.php';

if (!isset($_GET['id'])) {
    header("Location: elections.php");
    exit();
}

$election_id = (int)$_GET['id'];

// Get election details with creator information
$election = $conn->query("
    SELECT e.*, CONCAT(u.firstname, ' ', u.lastname) as creator_name
    FROM elections e 
    LEFT JOIN users u ON e.created_by = u.id 
    WHERE e.id = $election_id
")->fetch_assoc();

if (!$election) {
    $_SESSION['error'] = "Election not found.";
    header("Location: elections.php");
    exit();
}

// Get positions and their candidates
$positions = $conn->query("
    SELECT p.*, 
           COUNT(DISTINCT c.id) as candidate_count,
           COUNT(DISTINCT v.voter_id) as votes_cast
    FROM positions p
    LEFT JOIN candidates c ON p.id = c.position_id
    LEFT JOIN votes v ON p.id = v.position_id AND v.election_id = p.election_id
    WHERE p.election_id = $election_id
    GROUP BY p.id
    ORDER BY p.position_name
");

// Get voter turnout statistics
$turnout_stats = $conn->query("
    SELECT 
        COUNT(DISTINCT v.voter_id) as total_votes,
        (SELECT COUNT(*) FROM users WHERE role = 'voter' AND status = 'active') as total_voters
    FROM votes v
    WHERE v.election_id = $election_id
")->fetch_assoc();

// Get voting activity over time
$voting_activity = $conn->query("
    SELECT 
        DATE_FORMAT(voted_at, '%Y-%m-%d %H:00:00') as hour,
        COUNT(*) as vote_count
    FROM votes 
    WHERE election_id = $election_id
    GROUP BY hour
    ORDER BY hour
");

// Get device statistics
$device_stats = $conn->query("
    SELECT device_type, COUNT(*) as count
    FROM votes
    WHERE election_id = $election_id
    GROUP BY device_type
");

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Election Details - E-BOTO Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="container-fluid py-4">
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <h2>Election Details</h2>
                    <a href="elections.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Back to Elections
                    </a>
                </div>
            </div>
        </div>

        <!-- Election Overview -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h3><?php echo htmlspecialchars($election['title']); ?></h3>
                        <p class="text-muted"><?php echo htmlspecialchars($election['description']); ?></p>
                        
                        <div class="row mt-4">
                            <div class="col-md-6">
                                <table class="table table-sm">
                                    <tr>
                                        <th>Status:</th>
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
                                    </tr>
                                    <tr>
                                        <th>Start Date:</th>
                                        <td><?php echo date('F j, Y h:i A', strtotime($election['start_date'])); ?></td>
                                    </tr>
                                    <tr>
                                        <th>End Date:</th>
                                        <td><?php echo date('F j, Y h:i A', strtotime($election['end_date'])); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Created By:</th>
                                        <td><?php echo htmlspecialchars($election['creator_name']); ?></td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <h5>Voter Turnout</h5>
                                        <?php 
                                        $turnout_percentage = $turnout_stats['total_voters'] > 0 
                                            ? ($turnout_stats['total_votes'] / $turnout_stats['total_voters']) * 100 
                                            : 0;
                                        ?>
                                        <div class="progress mb-2" style="height: 25px;">
                                            <div class="progress-bar bg-success" 
                                                 role="progressbar" 
                                                 style="width: <?php echo $turnout_percentage; ?>%">
                                                <?php echo number_format($turnout_percentage, 1); ?>%
                                            </div>
                                        </div>
                                        <small class="text-muted">
                                            <?php echo $turnout_stats['total_votes']; ?> out of 
                                            <?php echo $turnout_stats['total_voters']; ?> registered voters
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Positions and Candidates -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title mb-0">Positions and Candidates</h4>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Position</th>
                                        <th>Candidates</th>
                                        <th>Votes Cast</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($position = $positions->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($position['position_name']); ?></td>
                                            <td><?php echo $position['candidate_count']; ?></td>
                                            <td>
                                                <?php echo $position['votes_cast']; ?>
                                                <?php if ($turnout_stats['total_voters'] > 0): ?>
                                                    (<?php echo number_format(($position['votes_cast'] / $turnout_stats['total_voters']) * 100, 1); ?>%)
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <a href="positions.php?election=<?php echo $election_id; ?>&position=<?php echo $position['id']; ?>" 
                                                   class="btn btn-sm btn-primary">
                                                    <i class="fas fa-eye me-1"></i>View Details
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Analytics -->
        <div class="row">
            <!-- Voting Activity Chart -->
            <div class="col-md-8 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title mb-0">Voting Activity Over Time</h4>
                    </div>
                    <div class="card-body">
                        <canvas id="votingActivityChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Device Distribution -->
            <div class="col-md-4 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title mb-0">Device Distribution</h4>
                    </div>
                    <div class="card-body">
                        <canvas id="deviceChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Voting Activity Chart
        new Chart(document.getElementById('votingActivityChart'), {
            type: 'line',
            data: {
                labels: [<?php 
                    $voting_activity->data_seek(0);
                    while ($hour = $voting_activity->fetch_assoc()) {
                        echo "'" . date('M j, ga', strtotime($hour['hour'])) . "',";
                    }
                ?>],
                datasets: [{
                    label: 'Votes Cast',
                    data: [<?php 
                        $voting_activity->data_seek(0);
                        while ($hour = $voting_activity->fetch_assoc()) {
                            echo $hour['vote_count'] . ",";
                        }
                    ?>],
                    borderColor: '#1e3c72',
                    tension: 0.1,
                    fill: false
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

        // Device Distribution Chart
        new Chart(document.getElementById('deviceChart'), {
            type: 'pie',
            data: {
                labels: [<?php 
                    while ($device = $device_stats->fetch_assoc()) {
                        echo "'" . ucfirst($device['device_type']) . "',";
                    }
                ?>],
                datasets: [{
                    data: [<?php 
                        $device_stats->data_seek(0);
                        while ($device = $device_stats->fetch_assoc()) {
                            echo $device['count'] . ",";
                        }
                    ?>],
                    backgroundColor: [
                        '#1e3c72',
                        '#2a5298',
                        '#3667be',
                        '#427ce4'
                    ]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    </script>
</body>
</html>
