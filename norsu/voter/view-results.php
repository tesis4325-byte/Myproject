<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'voter') {
    header("Location: ../index.php");
    exit();
}

require_once '../config/database.php';

$voter_id = $_SESSION['user_id'];
$election_id = isset($_GET['election']) ? (int)$_GET['election'] : 0;

// Get election details
$election = $conn->query("
    SELECT * FROM elections 
    WHERE id = $election_id 
    AND status = 'completed'
")->fetch_assoc();

if (!$election) {
    header("Location: dashboard.php");
    exit();
}

// Verify that this voter participated in the election
$participated = $conn->query("
    SELECT COUNT(*) as count 
    FROM votes 
    WHERE election_id = $election_id 
    AND voter_id = $voter_id
")->fetch_assoc()['count'] > 0;

if (!$participated) {
    header("Location: dashboard.php");
    exit();
}

// Get positions and results
$positions = $conn->query("
    SELECT * FROM positions 
    WHERE election_id = $election_id 
    ORDER BY position_name
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Election Results - E-BOTO</title>
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
        .card-header {
            border-radius: 20px 20px 0 0 !important;
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%) !important;
            padding: 1.2rem 1.5rem;
        }
        .card-header h5 {
            font-weight: 600;
            letter-spacing: 0.5px;
            margin: 0;
        }
        .progress {
            height: 25px;
            border-radius: 15px;
            background-color: #e9ecef;
            box-shadow: inset 0 2px 4px rgba(0,0,0,0.08);
            overflow: hidden;
        }
        .progress-bar {
            transition: width 1.5s ease;
            background-image: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
        }
        .progress-bar.bg-success {
            background-image: linear-gradient(135deg, #28a745 0%, #20c997 100%);
        }
        .winner {
            background: linear-gradient(to right, #d4edda 0%, rgba(212,237,218,0.3) 100%);
            border-left: 5px solid #28a745;
            border-radius: 15px;
            transform: scale(1.02);
            box-shadow: 0 5px 15px rgba(40,167,69,0.1);
        }
        .candidate-result {
            border-radius: 15px;
            transition: all 0.3s ease;
        }
        .candidate-result:hover {
            transform: translateX(5px);
            background-color: rgba(0,0,0,0.02);
        }
        .badge {
            padding: 0.5rem 1rem;
            font-weight: 500;
            letter-spacing: 0.5px;
        }
        .badge.bg-success {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%) !important;
        }
        .card-title {
            color: #1e3c72;
            font-weight: 700;
            font-size: 2rem;
        }
        .card-footer {
            background: rgba(0,0,0,0.02);
            border-top: 1px solid rgba(0,0,0,0.05);
            border-radius: 0 0 20px 20px !important;
            padding: 1rem 1.5rem;
        }
        .btn-light {
            background: rgba(255,255,255,0.9);
            border: none;
            font-weight: 500;
            padding: 8px 20px;
            transition: all 0.3s ease;
        }
        .btn-light:hover {
            background: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        @media (max-width: 768px) {
            .card-title {
                font-size: 1.5rem;
            }
            .progress {
                height: 20px;
            }
        }
    </style>
</head>
<body>
   <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="#">
                <img src="../uploads/logo/logo.png" alt="E-BOTO Logo" style="height: 40px; margin-right: 10px;">
                E-BOTO
            </a>
            <a href="dashboard.php" class="btn btn-light btn-sm">
                <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
            </a>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="card mb-4">
                    <div class="card-body">
                        <h2 class="card-title text-center mb-4">
                            <?php echo htmlspecialchars($election['title']); ?> - Results
                        </h2>
                        <p class="text-center text-muted">
                            Election ended on <?php echo date('F j, Y h:i A', strtotime($election['end_date'])); ?>
                        </p>
                    </div>
                </div>

                <?php while ($position = $positions->fetch_assoc()): ?>
                    <?php
                    // Get candidates and their vote counts
                    $candidates = $conn->query("
                        SELECT c.*, 
                               COUNT(v.id) as vote_count,
                               (SELECT COUNT(*) FROM votes WHERE position_id = {$position['id']}) as total_votes
                        FROM candidates c
                        LEFT JOIN votes v ON c.id = v.candidate_id AND v.position_id = {$position['id']}
                        WHERE c.position_id = {$position['id']}
                        GROUP BY c.id
                        ORDER BY vote_count DESC
                    ");

                    $first = true; // To mark the winner
                    ?>
                    <div class="card mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><?php echo htmlspecialchars($position['position_name']); ?></h5>
                        </div>
                        <div class="card-body">
                            <?php while ($candidate = $candidates->fetch_assoc()): ?>
                                <?php
                                $vote_percentage = $candidate['total_votes'] > 0 
                                    ? ($candidate['vote_count'] / $candidate['total_votes']) * 100 
                                    : 0;
                                ?>
                                <div class="candidate-result mb-4 p-3 <?php echo $first ? 'winner' : ''; ?>">
                                    <div class="d-flex align-items-center mb-2">
                                        <img src="../uploads/candidates/<?php echo $candidate['photo']; ?>" 
                                             alt="<?php echo htmlspecialchars($candidate['firstname'] . ' ' . $candidate['lastname']); ?>"
                                             class="rounded-circle me-3"
                                             style="width: 50px; height: 50px; object-fit: cover;">
                                        <div>
                                            <h5 class="mb-0">
                                                <?php echo htmlspecialchars($candidate['firstname'] . ' ' . $candidate['lastname']); ?>
                                                <?php if ($first): ?>
                                                    <span class="badge bg-success ms-2">Winner</span>
                                                <?php endif; ?>
                                            </h5>
                                            <small class="text-muted">
                                                <?php echo $candidate['vote_count']; ?> votes
                                                (<?php echo number_format($vote_percentage, 1); ?>%)
                                            </small>
                                        </div>
                                    </div>
                                    <div class="progress">
                                        <div class="progress-bar <?php echo $first ? 'bg-success' : 'bg-primary'; ?>" 
                                             role="progressbar" 
                                             style="width: <?php echo $vote_percentage; ?>%">
                                            <?php echo number_format($vote_percentage, 1); ?>%
                                        </div>
                                    </div>
                                </div>
                                <?php $first = false; ?>
                            <?php endwhile; ?>
                        </div>
                        <div class="card-footer text-muted">
                            Total votes cast: <?php 
                            echo $conn->query("
                                SELECT COUNT(DISTINCT voter_id) as count 
                                FROM votes 
                                WHERE position_id = {$position['id']}
                            ")->fetch_assoc()['count']; 
                            ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</body>
</html>
