<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'voter') {
    header("Location: ../index.php");
    exit();
}

require_once '../config/database.php';
require_once '../config/security.php';

$security = new SecurityUtils($conn);
$election_id = isset($_GET['election']) ? (int)$_GET['election'] : 0;
$verification_hash = $_SESSION['vote_verification'] ?? '';

if (!$verification_hash) {
    header("Location: dashboard.php");
    exit();
}

// Clear the verification hash from session to prevent reuse
unset($_SESSION['vote_verification']);

// Get election details
$election = $conn->query("
    SELECT * FROM elections 
    WHERE id = $election_id
")->fetch_assoc();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vote Confirmation - E-BOTO</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .navbar {
            background: #1e3c72;
        }
        .confirmation-card {
            max-width: 600px;
            margin: 2rem auto;
            border: none;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
        }
        .verification-code {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 5px;
            font-family: monospace;
            word-break: break-all;
        }
        .success-icon {
            font-size: 4rem;
            color: #28a745;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="#">E-BOTO</a>
            <a href="dashboard.php" class="btn btn-light btn-sm">
                <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
            </a>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container py-4">
        <div class="card confirmation-card">
            <div class="card-body text-center">
                <i class="fas fa-check-circle success-icon mb-3"></i>
                <h2 class="card-title mb-4">Vote Successfully Cast!</h2>
                <p class="lead">Your vote for <?php echo htmlspecialchars($election['title']); ?> has been recorded.</p>
                
                <hr>
                
                <div class="mb-4">
                    <h5>Vote Verification Code</h5>
                    <p class="text-muted small">Keep this code for your records. You can use it to verify your vote later.</p>
                    <div class="verification-code">
                        <?php echo htmlspecialchars($verification_hash); ?>
                    </div>
                </div>
                
                <div class="mb-4">
                    <h5>Vote Details</h5>
                    <p class="mb-1">
                        <strong>Election:</strong> <?php echo htmlspecialchars($election['title']); ?>
                    </p>
                    <p class="mb-1">
                        <strong>Time:</strong> <?php echo date('F j, Y h:i A'); ?>
                    </p>
                    <p class="small text-muted">
                        <i class="fas fa-shield-alt me-1"></i>
                        Your vote has been securely recorded and encrypted
                    </p>
                </div>
                
                <div class="text-center">
                    <a href="view-results.php?election=<?php echo $election_id; ?>" class="btn btn-primary">
                        <i class="fas fa-chart-bar me-2"></i>View Election Results
                    </a>
                    <a href="dashboard.php" class="btn btn-secondary ms-2">
                        <i class="fas fa-home me-2"></i>Return to Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
