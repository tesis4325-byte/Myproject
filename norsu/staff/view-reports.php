<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'staff') {
    header("Location: ../index.php");
    exit();
}

require_once '../config/database.php';

// Get selected election
$election_id = isset($_GET['election']) ? (int)$_GET['election'] : 0;

// Get all elections for the dropdown
$elections = $conn->query("
    SELECT * FROM elections 
    ORDER BY created_at DESC
");

if ($election_id) {
    // Get election details
    $election = $conn->query("SELECT * FROM elections WHERE id = $election_id")->fetch_assoc();
    
    // Get voter turnout statistics
    $turnout_stats = $conn->query("
        SELECT 
            (SELECT COUNT(DISTINCT voter_id) FROM votes WHERE election_id = $election_id) as total_votes,
            (SELECT COUNT(*) FROM users WHERE role = 'voter' AND status = 'active') as total_voters
    ")->fetch_assoc();
    
    // Get position-wise results
    $positions = $conn->query("
        SELECT * FROM positions 
        WHERE election_id = $election_id
        ORDER BY position_name
    ");
    
    // Get hourly voting activity
    $voting_activity = $conn->query("
        SELECT 
            DATE_FORMAT(voted_at, '%Y-%m-%d %H:00:00') as hour,
            COUNT(*) as vote_count
        FROM votes 
        WHERE election_id = $election_id
        GROUP BY hour
        ORDER BY hour
    ");
      // Get voter demographics (basic count only for now)
    $demographics = $conn->query("
        SELECT 
            COUNT(DISTINCT v.voter_id) as voter_count,
            DATE(v.voted_at) as vote_date
        FROM votes v
        WHERE v.election_id = $election_id
        GROUP BY vote_date
        ORDER BY vote_date
    ");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Election Reports - E-BOTO Staff Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.4.0/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.3.2/html2canvas.min.js"></script>
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
                        <a class="nav-link active" href="view-reports.php">
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
                <div class="container-fluid" id="report-content">
                    <!-- Header -->
                    <div class="row mb-4">
                        <div class="col-12 d-flex justify-content-between align-items-center">
                            <h2>Election Reports</h2>
                            <?php if ($election_id): ?>
                                <div class="btn-group">
                                    <button class="btn btn-primary" onclick="generatePDF()">
                                        <i class="fas fa-download me-2"></i>Detailed PDF Report
                                    </button>
                                    <button class="btn btn-success" onclick="exportCSV()">
                                        <i class="fas fa-file-csv me-2"></i>Export CSV
                                    </button>
                                    <button class="btn btn-info" onclick="viewAnalytics()">
                                        <i class="fas fa-chart-line me-2"></i>Analytics Dashboard
                                    </button>
                                    <button class="btn btn-warning" onclick="viewSecurityReport()">
                                        <i class="fas fa-shield-alt me-2"></i>Security Report
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Election Selector -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <form method="GET" class="row align-items-center">
                                <div class="col-md-6">
                                    <label class="form-label">Select Election</label>
                                    <select class="form-select" name="election" onchange="this.form.submit()">
                                        <option value="">Choose an election...</option>
                                        <?php while ($election_option = $elections->fetch_assoc()): ?>
                                            <option value="<?php echo $election_option['id']; ?>"
                                                    <?php echo $election_option['id'] == $election_id ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($election_option['title']); ?>
                                                (<?php echo ucfirst($election_option['status']); ?>)
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                            </form>
                        </div>
                    </div>

                    <?php if ($election_id): ?>
                        <!-- Overall Statistics -->
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Overall Statistics</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6>Voter Turnout</h6>
                                        <div class="progress mb-2" style="height: 25px;">
                                            <?php 
                                            $turnout_percentage = $turnout_stats['total_voters'] > 0 
                                                ? ($turnout_stats['total_votes'] / $turnout_stats['total_voters']) * 100 
                                                : 0;
                                            ?>
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
                                    <div class="col-md-6">
                                        <h6>Election Details</h6>
                                        <p class="mb-1">
                                            <strong>Status:</strong> 
                                            <span class="badge bg-<?php 
                                                echo match($election['status']) {
                                                    'upcoming' => 'info',
                                                    'ongoing' => 'success',
                                                    'completed' => 'secondary',
                                                    default => 'secondary'
                                                };
                                            ?>">
                                                <?php echo ucfirst($election['status']); ?>
                                            </span>
                                        </p>
                                        <p class="mb-1">
                                            <strong>Duration:</strong>
                                            <?php echo date('M j, Y h:i A', strtotime($election['start_date'])); ?> to
                                            <?php echo date('M j, Y h:i A', strtotime($election['end_date'])); ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Position-wise Results -->
                        <div class="row">
                            <?php while ($position = $positions->fetch_assoc()): ?>
                                <?php
                                $candidates = $conn->query("
                                    SELECT c.*, 
                                           COUNT(v.id) as vote_count,
                                           (SELECT COUNT(DISTINCT voter_id) 
                                            FROM votes 
                                            WHERE position_id = {$position['id']}) as total_votes
                                    FROM candidates c
                                    LEFT JOIN votes v ON c.id = v.candidate_id 
                                    WHERE c.position_id = {$position['id']}
                                    GROUP BY c.id
                                    ORDER BY vote_count DESC
                                ");
                                ?>
                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-header">
                                            <h5 class="card-title mb-0">
                                                <?php echo htmlspecialchars($position['position_name']); ?> Results
                                            </h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="chart-container">
                                                <canvas id="chart-position-<?php echo $position['id']; ?>"></canvas>
                                            </div>
                                            <div class="table-responsive mt-3">
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
                                                        $labels = [];
                                                        $data = [];
                                                        while ($candidate = $candidates->fetch_assoc()): 
                                                            $percentage = $candidate['total_votes'] > 0 
                                                                ? ($candidate['vote_count'] / $candidate['total_votes']) * 100 
                                                                : 0;
                                                            $labels[] = $candidate['firstname'] . ' ' . $candidate['lastname'];
                                                            $data[] = $candidate['vote_count'];
                                                        ?>
                                                            <tr>
                                                                <td>
                                                                    <?php echo htmlspecialchars($candidate['firstname'] . ' ' . $candidate['lastname']); ?>
                                                                </td>
                                                                <td><?php echo $candidate['vote_count']; ?></td>
                                                                <td><?php echo number_format($percentage, 1); ?>%</td>
                                                            </tr>
                                                        <?php endwhile; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                        <script>
                                            new Chart(document.getElementById('chart-position-<?php echo $position['id']; ?>'), {
                                                type: 'bar',
                                                data: {
                                                    labels: <?php echo json_encode($labels); ?>,
                                                    datasets: [{
                                                        label: 'Votes',
                                                        data: <?php echo json_encode($data); ?>,
                                                        backgroundColor: '#1e3c72'
                                                    }]
                                                },
                                                options: {
                                                    responsive: true,
                                                    maintainAspectRatio: false,
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
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>

                        <!-- Voting Activity Timeline -->
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Voting Activity Timeline</h5>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="voting-timeline"></canvas>
                                </div>
                            </div>
                            <?php
                            $timeline_labels = [];
                            $timeline_data = [];
                            while ($activity = $voting_activity->fetch_assoc()) {
                                $timeline_labels[] = date('M j, Y H:i', strtotime($activity['hour']));
                                $timeline_data[] = (int)$activity['vote_count'];
                            }
                            ?>
                            <script>
                                new Chart(document.getElementById('voting-timeline'), {
                                    type: 'line',
                                    data: {
                                        labels: <?php echo json_encode($timeline_labels); ?>,
                                        datasets: [{
                                            label: 'Votes per Hour',
                                            data: <?php echo json_encode($timeline_data); ?>,
                                            fill: false,
                                            borderColor: '#1e3c72',
                                            tension: 0.1
                                        }]
                                    },
                                    options: {
                                        responsive: true,
                                        maintainAspectRatio: false,
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
                        </div>

                        <!-- Advanced Analytics Modal -->
                        <div class="modal fade" id="analyticsModal" tabindex="-1">
                            <div class="modal-dialog modal-xl">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Advanced Analytics Dashboard</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="row">
                                            <!-- Voting Pattern Chart -->
                                            <div class="col-md-6 mb-4">
                                                <div class="card">
                                                    <div class="card-header">
                                                        <h6 class="card-title mb-0">Hourly Voting Pattern</h6>
                                                    </div>
                                                    <div class="card-body">
                                                        <canvas id="hourlyVotingChart"></canvas>
                                                    </div>
                                                </div>
                                            </div>                                            <!-- Daily Voting Distribution -->
                                            <div class="col-md-6 mb-4">
                                                <div class="card">
                                                    <div class="card-header">
                                                        <h6 class="card-title mb-0">Daily Voting Distribution</h6>
                                                    </div>
                                                    <div class="card-body">
                                                        <canvas id="votingDistributionChart"></canvas>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Device Usage -->
                                            <div class="col-md-6">
                                                <div class="card">
                                                    <div class="card-header">
                                                        <h6 class="card-title mb-0">Device Usage Statistics</h6>
                                                    </div>
                                                    <div class="card-body">
                                                        <canvas id="deviceChart"></canvas>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Geographical Distribution -->
                                            <div class="col-md-6">
                                                <div class="card">
                                                    <div class="card-header">
                                                        <h6 class="card-title mb-0">Geographical Distribution</h6>
                                                    </div>
                                                    <div class="card-body">
                                                        <canvas id="geographicalChart"></canvas>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Security Report Modal -->
                        <div class="modal fade" id="securityModal" tabindex="-1">
                            <div class="modal-dialog modal-lg">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Security Report</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <!-- Security Events Timeline -->
                                        <div class="card mb-4">
                                            <div class="card-header">
                                                <h6 class="card-title mb-0">Security Events Timeline</h6>
                                            </div>
                                            <div class="card-body">
                                                <div class="timeline" id="securityTimeline"></div>
                                            </div>
                                        </div>

                                        <!-- Suspicious IP Activity -->
                                        <div class="card">
                                            <div class="card-header">
                                                <h6 class="card-title mb-0">Suspicious IP Activity</h6>
                                            </div>
                                            <div class="card-body">
                                                <div class="table-responsive">
                                                    <table class="table table-sm">
                                                        <thead>
                                                            <tr>
                                                                <th>IP Address</th>
                                                                <th>Total Attempts</th>
                                                                <th>Failed Attempts</th>
                                                                <th>Status</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody id="ipActivityTable"></tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    <?php else: ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>Please select an election to view its report.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
    // Functions to handle analytics data loading and chart rendering
    function viewAnalytics() {
        fetch(`reports/generate_analytics.php?election=${currentElectionId}`)
            .then(response => response.json())
            .then(data => {
                renderHourlyChart(data.hourly_pattern);
                renderVotingDistributionChart(data.demographics);
                renderDeviceChart(data.devices);
                renderGeographicalChart(data.geographical);
                $('#analyticsModal').modal('show');
            });
    }

    function viewSecurityReport() {
        fetch(`reports/security_report.php?election=${currentElectionId}`)
            .then(response => response.json())
            .then(data => {
                renderSecurityTimeline(data.security_events);
                renderIPActivity(data.ip_stats);
                $('#securityModal').modal('show');
            });
    }

    function exportCSV() {
        window.location.href = `reports/export_csv.php?election=${currentElectionId}`;
    }

    // Chart rendering functions
    function renderHourlyChart(data) {
        new Chart(document.getElementById('hourlyVotingChart'), {
            type: 'line',
            data: {
                labels: data.map(d => d.hour + ':00'),
                datasets: [{
                    label: 'Votes',
                    data: data.map(d => d.vote_count),
                    borderColor: '#1e3c72',
                    fill: false
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });
    }

    // Add similar rendering functions for other charts...

    // Enhanced PDF generation
    function generatePDF() {
        const loadingToast = new bootstrap.Toast(document.getElementById('loadingToast'));
        loadingToast.show();
        
        fetch(`reports/generate_pdf_report.php?election=${currentElectionId}`, {
            method: 'POST'
        })
        .then(response => response.blob())
        .then(blob => {
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'election-report.pdf';
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            loadingToast.hide();
        });
    }
    </script>
</body>
</html>
