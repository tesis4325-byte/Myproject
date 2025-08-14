<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'voter') {
    header("Location: ../index.php");
    exit();
}

require_once '../config/database.php';

// Get voter information
$voter_id = $_SESSION['user_id'];
$voter = $conn->query("SELECT * FROM users WHERE id = $voter_id")->fetch_assoc();

// Get active elections with more lenient date comparison
$active_elections = $conn->query("
    SELECT * FROM elections 
    WHERE status = 'ongoing' 
    AND DATE(start_date) <= CURDATE() 
    AND DATE(end_date) >= CURDATE()
    ORDER BY start_date DESC
");

// Get completed elections where the voter participated
$completed_elections = $conn->query("
    SELECT DISTINCT e.* 
    FROM elections e 
    INNER JOIN votes v ON e.id = v.election_id 
    WHERE v.voter_id = $voter_id 
    AND e.status = 'completed'
    ORDER BY e.end_date DESC
");

// Debug information (will only show to admin/staff)
if (isset($_SESSION['role']) && in_array($_SESSION['role'], ['admin', 'staff'])) {
    $debug_elections = $conn->query("
        SELECT id, title, status,
               start_date, 
               NOW() as current_time,
               end_date,
               IF(status = 'ongoing', 'Yes', 'No') as is_ongoing,
               IF(DATE(start_date) <= CURDATE(), 'Yes', 'No') as has_started,
               IF(DATE(end_date) >= CURDATE(), 'Yes', 'No') as not_ended
        FROM elections 
        ORDER BY start_date DESC
    ");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-BOTO - Voter Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">    <style>
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
        }
        .navbar {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            padding: 1rem 0;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
        }
        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
            text-transform: uppercase;
            letter-spacing: 2px;
            color: white !important;
        }
        .nav-link {
            color: rgba(255,255,255,0.8) !important;
            font-weight: 500;
            padding: 0.5rem 1rem;
            margin: 0 0.2rem;
            border-radius: 5px;
            transition: all 0.3s ease;
        }
        .nav-link:hover, .nav-link.active {
            color: white !important;
            background: rgba(255,255,255,0.1);
            transform: translateY(-2px);
        }
        .card {
            border: none;
            border-radius: 20px;
            box-shadow: 0 10px 20px rgba(0,0,0,0.08);
            margin-bottom: 25px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            transition: all 0.3s ease;
        }
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.12);
        }
        .election-card {
            border: 1px solid rgba(0,0,0,0.05);
            overflow: hidden;
        }
        .election-card .card-body {
            padding: 2rem;
        }
        .election-card .card-title {
            color: #1e3c72;
            font-weight: 700;
            font-size: 1.4rem;
            margin-bottom: 1rem;
        }
        .election-card .card-text {
            color: #6c757d;
            margin-bottom: 1.5rem;
            line-height: 1.6;
        }
        .btn {
            padding: 0.8rem 1.5rem;
            font-weight: 600;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
            border-radius: 10px;
        }
        .btn-primary {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            border: none;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(30,60,114,0.3);
        }
        .btn-success {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            border: none;
        }
        .btn-light {
            background: rgba(255,255,255,0.9);
            border: none;
            color: #1e3c72;
            font-weight: 500;
        }
        .btn-light:hover {
            background: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .btn-info {
            background: linear-gradient(135deg, #0dcaf0 0%, #0d6efd 100%);
            border: none;
            color: white;
        }
        .btn-info:hover {
            color: white;
            transform: translateY(-2px);
        }
        h2 {
            color: #1e3c72;
            font-weight: 700;
            margin-bottom: 1.5rem;
            position: relative;
            padding-bottom: 0.5rem;
        }
        h2:after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 50px;
            height: 3px;
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            border-radius: 3px;
        }
        .table {
            margin: 0;
        }
        .table th {
            font-weight: 600;
            color: #1e3c72;
            border-bottom-width: 1px;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
        }
        .table td {
            vertical-align: middle;
            color: #6c757d;
            border-color: rgba(0,0,0,0.05);
        }
        .badge {
            padding: 0.5rem 1rem;
            font-weight: 500;
            letter-spacing: 0.5px;
        }
        .text-muted {
            color: #6c757d !important;
        }
        .alert {
            border: none;
            border-radius: 15px;
            padding: 1rem 1.5rem;
        }
        .alert-info {
            background: linear-gradient(135deg, #0dcaf0 0%, #0d6efd 100%);
            color: white;
        }
        @media (max-width: 768px) {
            .navbar-brand {
                font-size: 1.2rem;
            }
            .election-card .card-body {
                padding: 1.5rem;
            }
            h2 {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="#">
                <img src="../uploads/logo/logo.png" alt="E-BOTO Logo" style="height: 40px; margin-right: 10px;">
                E-BOTO
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="dashboard.php">
                            <i class="fas fa-home me-2"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="profile.php">
                            <i class="fas fa-user me-2"></i>My Profile
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="voting-history.php">
                            <i class="fas fa-history me-2"></i>Voting History
                        </a>
                    </li>
                </ul>
                <div class="d-flex align-items-center">
                    <div class="text-white me-3 d-flex align-items-center">
                        <i class="fas fa-user-circle me-2"></i>
                        <span>Welcome, <?php echo htmlspecialchars($voter['firstname']); ?>!</span>
                    </div>
                    <a href="../auth/logout.php" class="btn btn-light btn-sm">
                        <i class="fas fa-sign-out-alt me-2"></i>Logout
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Debug Information (only shown to admin/staff) -->
    <?php if (isset($debug_elections) && $debug_elections->num_rows > 0): ?>
        <div class="container mt-4">
            <div class="alert alert-warning">
                <h4>Debug Information (Admin/Staff Only)</h4>
                <?php while ($e = $debug_elections->fetch_assoc()): ?>
                    <div class="border p-3 mb-3">
                        <h5>Election: <?php echo htmlspecialchars($e['title']); ?></h5>
                        <pre>
Status: <?php echo $e['status']; ?> (Should be 'ongoing')
Start Date: <?php echo $e['start_date']; ?>
Current Time: <?php echo $e['current_time']; ?>
End Date: <?php echo $e['end_date']; ?>
Is Ongoing: <?php echo $e['is_ongoing']; ?>
Has Started: <?php echo $e['has_started']; ?>
Not Ended: <?php echo $e['not_ended']; ?>
                        </pre>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Main Content -->
    <div class="container py-4">
        <!-- Active Elections -->
        <div class="row mb-4">
            <div class="col-12">
                <h2 class="mb-4">Active Elections</h2>
                <?php if ($active_elections->num_rows > 0): ?>
                    <div class="row">
                        <?php while ($election = $active_elections->fetch_assoc()): ?>
                            <?php                            // Check if voter has already voted in this election
                            $stmt = $conn->prepare("
                                SELECT COUNT(*) as count 
                                FROM votes 
                                WHERE election_id = ? 
                                AND voter_id = ?
                            ");
                            $stmt->bind_param('ii', $election['id'], $voter_id);
                            $stmt->execute();
                            $voted = $stmt->get_result()->fetch_assoc()['count'] > 0;
                            $stmt->close();
                            ?>
                            <div class="col-md-6 mb-4">
                                <div class="card election-card h-100">
                                    <div class="card-body">
                                        <h5 class="card-title"><?php echo htmlspecialchars($election['title']); ?></h5>
                                        <p class="card-text"><?php echo htmlspecialchars($election['description']); ?></p>
                                        <div class="small text-muted mb-3">
                                            Starts: <?php echo date('M j, Y h:i A', strtotime($election['start_date'])); ?><br>
                                            Ends: <?php echo date('M j, Y h:i A', strtotime($election['end_date'])); ?>
                                        </div>
                                        <?php if ($voted): ?>
                                            <button class="btn btn-success" disabled>
                                                <i class="fas fa-check-circle me-2"></i>Vote Submitted
                                            </button>
                                        <?php else: ?>
                                            <a href="cast-vote.php?election=<?php echo $election['id']; ?>" 
                                               class="btn btn-primary">
                                                <i class="fas fa-vote-yea me-2"></i>Cast Your Vote
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>There are no active elections at the moment.
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Voting History -->
        <div class="row">
            <div class="col-12">
                <h2 class="mb-4">Your Voting History</h2>
                <?php if ($completed_elections->num_rows > 0): ?>
                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Election</th>
                                            <th>Date Voted</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($election = $completed_elections->fetch_assoc()): ?>
                                            <?php
                                            $vote_date = $conn->query("
                                                SELECT voted_at 
                                                FROM votes 
                                                WHERE election_id = {$election['id']} 
                                                AND voter_id = $voter_id 
                                                LIMIT 1
                                            ")->fetch_assoc()['voted_at'];
                                            ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($election['title']); ?></td>
                                                <td><?php echo date('M j, Y h:i A', strtotime($vote_date)); ?></td>
                                                <td>
                                                    <span class="badge bg-secondary">Completed</span>
                                                </td>
                                                <td>
                                                    <a href="view-results.php?election=<?php echo $election['id']; ?>" 
                                                       class="btn btn-sm btn-info">
                                                        <i class="fas fa-chart-bar me-1"></i>View Results
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>You haven't participated in any elections yet.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
