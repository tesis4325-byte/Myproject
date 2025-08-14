<?php
include 'includes/header.php';
require_once '../config/database.php';
require_once '../staff/reports/generate_analytics.php';

// Handle report generation requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $election_id = isset($_POST['election_id']) ? (int)$_POST['election_id'] : null;
    $report_type = isset($_POST['report_type']) ? $_POST['report_type'] : null;
    $format = isset($_POST['format']) ? $_POST['format'] : 'web';
    
    if ($election_id && $report_type) {
        switch($format) {
            case 'pdf':
                require_once '../staff/reports/generate_pdf_report.php';
                // PDF generation is handled by the included file
                break;
            case 'csv':
                require_once '../staff/reports/export_csv.php';
                // CSV export is handled by the included file
                break;
        }
    }
}

// Get all completed elections
$completed_elections = $conn->query("
    SELECT id, title, start_date, end_date 
    FROM elections 
    WHERE status = 'completed' 
    ORDER BY end_date DESC
");

// Get overall voting statistics
$voting_stats = [
    'total_elections' => $conn->query("SELECT COUNT(*) as count FROM elections")->fetch_assoc()['count'],
    'completed_elections' => $conn->query("SELECT COUNT(*) as count FROM elections WHERE status = 'completed'")->fetch_assoc()['count'],
    'total_votes' => $conn->query("SELECT COUNT(*) as count FROM votes")->fetch_assoc()['count'],
    'total_voters' => $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'voter'")->fetch_assoc()['count']
];

// Calculate average voter turnout
if ($voting_stats['completed_elections'] > 0) {
    $turnout_result = $conn->query("
        SELECT AVG(turnout) as avg_turnout FROM (
            SELECT e.id, 
                   (COUNT(DISTINCT v.voter_id) * 100.0 / 
                    (SELECT COUNT(*) FROM users WHERE role = 'voter' AND status = 'active')) as turnout
            FROM elections e
            LEFT JOIN votes v ON e.id = v.election_id
            WHERE e.status = 'completed'
            GROUP BY e.id
        ) as turnouts
    ");
    $avg_turnout = $turnout_result->fetch_assoc()['avg_turnout'];
} else {
    $avg_turnout = 0;
}
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h2>Election Reports</h2>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h5>Total Elections</h5>
                    <h2><?php echo $voting_stats['total_elections']; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h5>Completed Elections</h5>
                    <h2><?php echo $voting_stats['completed_elections']; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h5>Total Votes Cast</h5>
                    <h2><?php echo $voting_stats['total_votes']; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <h5>Avg. Voter Turnout</h5>
                    <h2><?php echo number_format($avg_turnout, 1); ?>%</h2>
                </div>
            </div>
        </div>
    </div>

    <!-- Report Generation Form -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0">Generate Report</h5>
        </div>
        <div class="card-body">
            <form action="" method="POST" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Select Election</label>
                    <select name="election_id" class="form-select" required>
                        <option value="">Choose an election...</option>
                        <?php while($election = $completed_elections->fetch_assoc()): ?>
                            <option value="<?php echo $election['id']; ?>">
                                <?php echo htmlspecialchars($election['title']); ?> 
                                (<?php echo date('M j, Y', strtotime($election['end_date'])); ?>)
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="col-md-4">
                    <label class="form-label">Report Type</label>
                    <select name="report_type" class="form-select" required>
                        <option value="">Choose report type...</option>
                        <option value="summary">Election Summary</option>
                        <option value="detailed">Detailed Results</option>
                    </select>
                </div>
                
                <div class="col-md-4">
                    <label class="form-label">Format</label>
                    <select name="format" class="form-select" required>
                        <option value="web">View on Web</option>
                    </select>
                </div>
                
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-file-alt"></i> Generate Report
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Report Display Area -->
    <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && $format === 'web'): ?>
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Report Results</h5>
            </div>
            <div class="card-body">
                <?php
                // Get election details
                $election = $conn->query("SELECT * FROM elections WHERE id = $election_id")->fetch_assoc();
                ?>
                <h4><?php echo htmlspecialchars($election['title']); ?></h4>
                <p class="text-muted">
                    <?php echo date('F j, Y', strtotime($election['start_date'])); ?> - 
                    <?php echo date('F j, Y', strtotime($election['end_date'])); ?>
                </p>

                <?php if ($report_type === 'summary'): ?>
                    <!-- Election Summary -->
                    <?php
                    $positions = $conn->query("
                        SELECT p.*, 
                               COUNT(DISTINCT v.voter_id) as total_votes,
                               (SELECT COUNT(*) FROM users WHERE role = 'voter' AND status = 'active') as total_voters
                        FROM positions p
                        LEFT JOIN votes v ON p.id = v.position_id AND v.election_id = p.election_id
                        WHERE p.election_id = $election_id
                        GROUP BY p.id
                    ");
                    ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Position</th>
                                    <th>Candidates</th>
                                    <th>Total Votes</th>
                                    <th>Turnout</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($position = $positions->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($position['position_name']); ?></td>
                                        <td>
                                            <?php
                                            $candidates = $conn->query("SELECT COUNT(*) as count FROM candidates WHERE position_id = {$position['id']}");
                                            echo $candidates->fetch_assoc()['count'];
                                            ?>
                                        </td>
                                        <td><?php echo $position['total_votes']; ?></td>
                                        <td>
                                            <?php 
                                            $turnout = ($position['total_votes'] / $position['total_voters']) * 100;
                                            echo number_format($turnout, 1) . '%';
                                            ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>

                <?php elseif ($report_type === 'detailed'): ?>
                    <!-- Detailed Results -->
                    <?php
                    $positions = $conn->query("SELECT * FROM positions WHERE election_id = $election_id");
                    while($position = $positions->fetch_assoc()):
                        $candidates = $conn->query("
                            SELECT c.*, COUNT(v.id) as vote_count
                            FROM candidates c
                            LEFT JOIN votes v ON c.id = v.candidate_id
                            WHERE c.position_id = {$position['id']}
                            GROUP BY c.id
                            ORDER BY vote_count DESC
                        ");
                    ?>
                        <h5 class="mt-4"><?php echo htmlspecialchars($position['position_name']); ?></h5>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Candidate</th>
                                        <th>Votes</th>
                                        <th>Percentage</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $total_votes = 0;
                                    $candidate_data = [];
                                    while($candidate = $candidates->fetch_assoc()) {
                                        $total_votes += $candidate['vote_count'];
                                        $candidate_data[] = $candidate;
                                    }
                                    foreach($candidate_data as $candidate):
                                        $percentage = $total_votes > 0 ? ($candidate['vote_count'] / $total_votes) * 100 : 0;
                                    ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($candidate['firstname'] . ' ' . $candidate['lastname']); ?></td>
                                            <td><?php echo $candidate['vote_count']; ?></td>
                                            <td>
                                                <div class="progress">
                                                    <div class="progress-bar" role="progressbar" 
                                                         style="width: <?php echo $percentage; ?>%"
                                                         aria-valuenow="<?php echo $percentage; ?>" 
                                                         aria-valuemin="0" 
                                                         aria-valuemax="100">
                                                        <?php echo number_format($percentage, 1); ?>%
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endwhile; ?>

                <?php elseif ($report_type === 'demographics'): ?>
                    <!-- Demographic Analysis -->
                    <?php
                    // Age group distribution
                    $age_groups = $conn->query("
                        SELECT u.age_group, COUNT(DISTINCT v.voter_id) as voters
                        FROM users u
                        LEFT JOIN votes v ON u.id = v.voter_id AND v.election_id = $election_id
                        WHERE u.role = 'voter'
                        GROUP BY u.age_group
                        ORDER BY u.age_group
                    ");

                    // Gender distribution
                    $genders = $conn->query("
                        SELECT u.gender, COUNT(DISTINCT v.voter_id) as voters
                        FROM users u
                        LEFT JOIN votes v ON u.id = v.voter_id AND v.election_id = $election_id
                        WHERE u.role = 'voter'
                        GROUP BY u.gender
                    ");
                    ?>

                    <div class="row">
                        <div class="col-md-6">
                            <h5>Age Group Distribution</h5>
                            <canvas id="ageChart"></canvas>
                        </div>
                        <div class="col-md-6">
                            <h5>Gender Distribution</h5>
                            <canvas id="genderChart"></canvas>
                        </div>
                    </div>

                    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
                    <script>
                    // Age distribution chart
                    new Chart(document.getElementById('ageChart'), {
                        type: 'bar',
                        data: {
                            labels: [<?php
                                $age_data = [];
                                while($age = $age_groups->fetch_assoc()) {
                                    echo "'" . $age['age_group'] . "',";
                                    $age_data[] = $age['voters'];
                                }
                            ?>],
                            datasets: [{
                                label: 'Voters',
                                data: [<?php echo implode(',', $age_data); ?>],
                                backgroundColor: 'rgba(54, 162, 235, 0.5)'
                            }]
                        }
                    });

                    // Gender distribution chart
                    new Chart(document.getElementById('genderChart'), {
                        type: 'pie',
                        data: {
                            labels: [<?php
                                while($gender = $genders->fetch_assoc()) {
                                    echo "'" . ucfirst($gender['gender']) . "',";
                                }
                            ?>],
                            datasets: [{
                                data: [<?php
                                    $genders->data_seek(0);
                                    while($gender = $genders->fetch_assoc()) {
                                        echo $gender['voters'] . ",";
                                    }
                                ?>],
                                backgroundColor: [
                                    'rgba(54, 162, 235, 0.5)',
                                    'rgba(255, 99, 132, 0.5)',
                                    'rgba(255, 206, 86, 0.5)'
                                ]
                            }]
                        }
                    });
                    </script>

                <?php elseif ($report_type === 'turnout'): ?>
                    <!-- Turnout Analysis -->
                    <?php
                    // Hourly voting pattern
                    $hourly_votes = $conn->query("
                        SELECT HOUR(voted_at) as hour,
                               COUNT(*) as vote_count
                        FROM votes
                        WHERE election_id = $election_id
                        GROUP BY HOUR(voted_at)
                        ORDER BY HOUR(voted_at)
                    ");

                    // Device type distribution
                    $devices = $conn->query("
                        SELECT device_type,
                               COUNT(*) as vote_count
                        FROM votes
                        WHERE election_id = $election_id
                        GROUP BY device_type
                    ");
                    ?>

                    <div class="row">
                        <div class="col-md-8">
                            <h5>Hourly Voting Pattern</h5>
                            <canvas id="hourlyChart"></canvas>
                        </div>
                        <div class="col-md-4">
                            <h5>Device Distribution</h5>
                            <canvas id="deviceChart"></canvas>
                        </div>
                    </div>

                    <script>
                    // Hourly voting pattern chart
                    new Chart(document.getElementById('hourlyChart'), {
                        type: 'line',
                        data: {
                            labels: [<?php
                                while($hour = $hourly_votes->fetch_assoc()) {
                                    echo "'" . sprintf("%02d:00", $hour['hour']) . "',";
                                }
                            ?>],
                            datasets: [{
                                label: 'Votes Cast',
                                data: [<?php
                                    $hourly_votes->data_seek(0);
                                    while($hour = $hourly_votes->fetch_assoc()) {
                                        echo $hour['vote_count'] . ",";
                                    }
                                ?>],
                                fill: false,
                                borderColor: 'rgb(75, 192, 192)',
                                tension: 0.1
                            }]
                        }
                    });

                    // Device distribution chart
                    new Chart(document.getElementById('deviceChart'), {
                        type: 'doughnut',
                        data: {
                            labels: [<?php
                                while($device = $devices->fetch_assoc()) {
                                    echo "'" . $device['device_type'] . "',";
                                }
                            ?>],
                            datasets: [{
                                data: [<?php
                                    $devices->data_seek(0);
                                    while($device = $devices->fetch_assoc()) {
                                        echo $device['vote_count'] . ",";
                                    }
                                ?>],
                                backgroundColor: [
                                    'rgba(255, 99, 132, 0.5)',
                                    'rgba(54, 162, 235, 0.5)',
                                    'rgba(255, 206, 86, 0.5)'
                                ]
                            }]
                        }
                    });
                    </script>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
