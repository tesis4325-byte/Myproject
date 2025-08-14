<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'voter') {
    header("Location: ../index.php");
    exit();
}

require_once '../config/database.php';

$voter_id = $_SESSION['user_id'];

// Get all votes by this voter with election and position details
$votes = $conn->query("
    SELECT 
        v.voted_at,
        v.device_type,
        v.ip_address,
        e.title as election_title,
        e.status as election_status,
        p.position_name,
        CONCAT(c.firstname, ' ', c.lastname) as candidate_name,
        c.photo as candidate_photo
    FROM votes v
    JOIN elections e ON v.election_id = e.id
    JOIN positions p ON v.position_id = p.id
    JOIN candidates c ON v.candidate_id = c.id
    WHERE v.voter_id = $voter_id
    ORDER BY v.voted_at DESC
");

// Get statistics
$stats = [
    'total_votes' => $conn->query("SELECT COUNT(*) as count FROM votes WHERE voter_id = $voter_id")->fetch_assoc()['count'],
    'elections_participated' => $conn->query("
        SELECT COUNT(DISTINCT election_id) as count 
        FROM votes 
        WHERE voter_id = $voter_id
    ")->fetch_assoc()['count']
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Voting History - E-BOTO</title>
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
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            transition: all 0.3s ease;
            margin-bottom: 25px;
        }
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.12);
        }
        .candidate-photo {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 50%;
            border: 3px solid #ffffff;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }
        .timeline {
            position: relative;
            padding: 30px 0;
        }
        .timeline-item {
            position: relative;
            padding-left: 50px;
            margin-bottom: 40px;
            transition: all 0.3s ease;
        }
        .timeline-item:hover {
            transform: translateX(5px);
        }
        .timeline-item:before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            width: 2px;
            background: linear-gradient(to bottom, #1e3c72 0%, #2a5298 100%);
            border-radius: 2px;
        }
        .timeline-item:after {
            content: '';
            position: absolute;
            left: -6px;
            top: 0;
            width: 14px;
            height: 14px;
            border-radius: 50%;
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            box-shadow: 0 0 10px rgba(30,60,114,0.3);
            transition: all 0.3s ease;
        }
        .timeline-item:hover:after {
            transform: scale(1.2);
            box-shadow: 0 0 15px rgba(30,60,114,0.4);
        }        h2 {
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
        .bg-primary {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%) !important;
        }
        .bg-success {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%) !important;
        }
        .card.bg-primary, .card.bg-success {
            border: none;
            overflow: hidden;
        }
        .card.bg-primary:before, .card.bg-success:before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            bottom: 0;
            left: 0;
            background: linear-gradient(45deg, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0) 100%);
            z-index: 1;
        }
        .card.bg-primary .card-body, .card.bg-success .card-body {
            position: relative;
            z-index: 2;
            padding: 2rem;
        }
        .card h5 {
            font-size: 1.1rem;
            font-weight: 500;
            margin-bottom: 1rem;
            opacity: 0.9;
        }
        .card h2 {
            font-size: 2.5rem;
            font-weight: 700;
            margin: 0;
        }
        .card h2:after {
            display: none;
        }
        .badge {
            padding: 0.5rem 1rem;
            font-weight: 500;
            letter-spacing: 0.5px;
            border-radius: 8px;
        }
        .badge.bg-warning {
            background: linear-gradient(135deg, #ffc107 0%, #ff9800 100%) !important;
            color: #fff;
        }
        .badge.bg-secondary {
            background: linear-gradient(135deg, #6c757d 0%, #495057 100%) !important;
        }
        .badge.bg-info {
            background: linear-gradient(135deg, #0dcaf0 0%, #0d6efd 100%) !important;
        }
        .text-primary {
            color: #1e3c72 !important;
        }
        .text-muted {
            color: #6c757d !important;
        }
        .small {
            font-size: 0.875rem;
            line-height: 1.5;
        }
        @media (max-width: 768px) {
            .timeline-item {
                padding-left: 35px;
            }
            .card h2 {
                font-size: 2rem;
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
        <div class="row">
            <div class="col-12">
                <h2 class="mb-4">My Voting History</h2>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row g-4 mb-4">
            <div class="col-md-6">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <h5>Total Votes Cast</h5>
                        <h2><?php echo $stats['total_votes']; ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <h5>Elections Participated</h5>
                        <h2><?php echo $stats['elections_participated']; ?></h2>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="timeline">
                    <?php 
                    $current_election = null;
                    while($vote = $votes->fetch_assoc()): 
                        if ($current_election !== $vote['election_title']):
                            if ($current_election !== null) {
                                echo '</div>'; // Close previous election group
                            }
                            $current_election = $vote['election_title'];
                    ?>
                        <div class="mb-4">
                            <h5 class="text-primary">
                                <?php echo htmlspecialchars($vote['election_title']); ?>
                                <span class="badge bg-<?php 
                                    echo match($vote['election_status']) {
                                        'upcoming' => 'warning',
                                        'ongoing' => 'success',
                                        'completed' => 'secondary',
                                        default => 'info'
                                    };
                                ?>">
                                    <?php echo ucfirst(htmlspecialchars($vote['election_status'])); ?>
                                </span>
                            </h5>
                    <?php endif; ?>

                    <div class="timeline-item">
                        <div class="d-flex align-items-center mb-2">
                            <?php if ($vote['candidate_photo']): ?>
                                <img src="../uploads/candidates/<?php echo htmlspecialchars($vote['candidate_photo']); ?>" 
                                     alt="Candidate Photo" 
                                     class="candidate-photo me-3">
                            <?php else: ?>
                                <div class="candidate-photo bg-secondary d-flex align-items-center justify-content-center me-3">
                                    <i class="fas fa-user text-white"></i>
                                </div>
                            <?php endif; ?>
                            <div>
                                <h6 class="mb-0"><?php echo htmlspecialchars($vote['position_name']); ?></h6>
                                <div><?php echo htmlspecialchars($vote['candidate_name']); ?></div>
                            </div>
                        </div>
                        <div class="text-muted small">
                            <div>
                                <i class="fas fa-clock me-1"></i>
                                <?php echo date('F j, Y g:i A', strtotime($vote['voted_at'])); ?>
                            </div>
                            <div>
                                <i class="fas fa-desktop me-1"></i>
                                <?php echo htmlspecialchars($vote['device_type']); ?>
                            </div>
                            <div>
                                <i class="fas fa-network-wired me-1"></i>
                                <?php echo htmlspecialchars($vote['ip_address']); ?>
                            </div>
                        </div>
                    </div>

                    <?php endwhile; 
                    if ($current_election !== null) {
                        echo '</div>'; // Close last election group
                    }
                    if ($votes->num_rows === 0): 
                    ?>
                        <div class="text-center py-5">
                            <i class="fas fa-box-ballot fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No voting history yet</h5>
                            <p class="text-muted">You haven't participated in any elections yet.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
