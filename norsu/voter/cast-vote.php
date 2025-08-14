<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'voter') {
    header("Location: ../index.php");
    exit();
}

require_once '../config/database.php';
require_once '../config/security.php';
require_once '../config/utils.php';

$security = new SecurityUtils($conn);

$voter_id = $_SESSION['user_id'];
$election_id = isset($_GET['election']) ? (int)$_GET['election'] : 0;

// Verify election exists and is active
$stmt = $conn->prepare("
    SELECT * FROM elections 
    WHERE id = ? 
    AND status = 'ongoing' 
    AND DATE(start_date) <= CURDATE()
    AND DATE(end_date) >= CURDATE()
");
$stmt->bind_param('i', $election_id);
$stmt->execute();
$election = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$election) {
    header("Location: dashboard.php");
    exit();
}

// Check if voter has already voted
$stmt = $conn->prepare("
    SELECT COUNT(*) as count 
    FROM votes 
    WHERE election_id = ? 
    AND voter_id = ?
");
$stmt->bind_param('ii', $election_id, $voter_id);
$stmt->execute();
$has_voted = $stmt->get_result()->fetch_assoc()['count'] > 0;
$stmt->close();

if ($has_voted) {
    header("Location: dashboard.php");
    exit();
}

// Get positions and candidates for this election
$positions = $conn->query("
    SELECT * FROM positions 
    WHERE election_id = $election_id 
    ORDER BY position_name
");

// Handle vote submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $conn->begin_transaction();
        
        $voter_id = $_SESSION['user_id'];
        $election_id = (int)$_GET['election']; // Get from URL parameter
        
        // Process votes for each position
        foreach ($_POST['votes'] as $position_id => $candidate_id) {
            $position_id = (int)$position_id;
            $candidate_id = (int)$candidate_id;
            
            // Verify vote hasn't already been cast for this position
            $stmt = $conn->prepare("
                SELECT id FROM votes 
                WHERE voter_id = ? 
                AND position_id = ? 
                AND election_id = ?");            $stmt->bind_param('iii', $voter_id, $position_id, $election_id);
            $stmt->execute();
            $existing_vote = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($existing_vote) {
                throw new Exception("You have already voted for this position.");
            }
            
            // Insert vote with device information
            $stmt = $conn->prepare("
                INSERT INTO votes (voter_id, election_id, position_id, candidate_id, device_type, ip_address, user_agent, voted_from_location)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $device_type = Utils::detectDeviceType();
            $ip_address = $security->getClientIP();
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
            $location = ''; // Would come from geolocation service in production
            
            $stmt->bind_param("iiisssss", 
                $voter_id, 
                $election_id, 
                $position_id, 
                $candidate_id,
                $device_type,
                $ip_address,
                $user_agent,
                $location
            );
            
            $stmt->execute();
            $vote_id = $conn->insert_id;
              // Generate vote verification for this vote
            $verification_hash = $security->generateVoteVerification($vote_id, $voter_id);
        }
        
        // Log the voting activity
        $security->logSecurityEvent($voter_id, 'vote_cast', "Vote cast for position $position_id");
        
        $conn->commit();
        
        // Store verification hash in session for confirmation page
        $_SESSION['vote_verification'] = $verification_hash;
        
        header("Location: vote-confirmation.php?election=$election_id");
        exit();
        
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error'] = $e->getMessage();
        header("Location: cast-vote.php?election=$election_id");
        exit();    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cast Your Vote - E-BOTO</title>
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
            backdrop-filter: blur(5px);
            background: rgba(255, 255, 255, 0.95);
            transition: all 0.3s ease;
        }
        .card:hover {
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
        .candidate-card {
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            background: #ffffff;
            border-radius: 15px;
            overflow: hidden;
        }
        .candidate-card:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 0 15px 30px rgba(30,60,114,0.2);
        }
        .candidate-card.selected {
            border: 3px solid #1e3c72;
            background: linear-gradient(135deg, #ffffff 0%, #e8f0fe 100%);
            transform: translateY(-5px) scale(1.02);
        }
        .candidate-photo {
            width: 140px;            height: 140px;
            object-fit: cover;
            border-radius: 50%;
            margin-bottom: 15px;
            border: 4px solid #ffffff;
            box-shadow: 0 5px 15px rgba(0,0,0,0.15);
            transition: all 0.3s ease;
        }
        .candidate-card:hover .candidate-photo {
            transform: scale(1.05);
            border-color: #1e3c72;
        }
        .form-check-input {
            width: 1.2em;
            height: 1.2em;
            margin-top: 0.25em;
            cursor: pointer;
        }
        .form-check-input:checked {
            background-color: #1e3c72;
            border-color: #1e3c72;
        }
        .form-check-label {
            cursor: pointer;
            font-weight: 500;
            color: #495057;
        }
        .btn-primary {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            border: none;
            padding: 12px 25px;
            font-weight: 600;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(30,60,114,0.3);
        }
        .btn-light {
            background: rgba(255,255,255,0.9);
            border: none;
            font-weight: 500;
            padding: 8px 20px;
            transition: all 0.3s ease;
        }
        .btn-light:hover {
            background: #ffffff;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        h5.mb-2 {
            color: #1e3c72;
            font-weight: 600;
            margin-top: 10px;
        }
        .card-title {
            color: #1e3c72;
            font-weight: 700;
            font-size: 2.2rem;
            margin-bottom: 1rem;
        }
        .text-muted {
            color: #6c757d !important;
            font-size: 1.1rem;
            line-height: 1.6;
        }
        #confirm-vote:checked ~ label {
            color: #1e3c72;
            font-weight: 600;
        }
        @media (max-width: 768px) {
            .candidate-photo {
                width: 120px;
                height: 120px;
            }
            .card-title {
                font-size: 1.8rem;
            }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="#">E-BOTO</a>
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
                        <h2 class="card-title text-center mb-4"><?php echo htmlspecialchars($election['title']); ?></h2>
                        <p class="text-center text-muted">
                            Please select one candidate for each position.
                            Your vote is important and confidential.
                        </p>
                    </div>
                </div>                <form method="POST" id="voting-form">
                    <?php while ($position = $positions->fetch_assoc()): ?>
                        <?php
                        $stmt = $conn->prepare("
                            SELECT * FROM candidates 
                            WHERE position_id = ? 
                            ORDER BY lastname, firstname
                        ");
                        $stmt->bind_param('i', $position['id']);
                        $stmt->execute();
                        $candidates = $stmt->get_result();
                        ?>
                        <div class="card mb-4">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0"><?php echo htmlspecialchars($position['position_name']); ?></h5>
                                <small>Select <?php echo $position['max_votes']; ?> candidate(s)</small>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <?php while ($candidate = $candidates->fetch_assoc()): ?>
                                        <div class="col-md-4 mb-3">
                                            <div class="card candidate-card h-100" 
                                                 onclick="selectCandidate(this, <?php echo $position['id']; ?>)">
                                                <div class="card-body text-center">                                    <img src="../uploads/candidates/<?php echo htmlspecialchars($candidate['photo']); ?>" 
                                         alt="<?php echo htmlspecialchars($candidate['firstname'] . ' ' . $candidate['lastname']); ?>"
                                         class="candidate-photo"
                                         onerror="this.src='../uploads/candidates/default.png'">
                                                    <h5 class="mb-2">
                                                        <?php echo htmlspecialchars($candidate['firstname'] . ' ' . $candidate['lastname']); ?>
                                                    </h5>
                                                    <div class="form-check justify-content-center">
                                                        <input type="radio" 
                                                               name="votes[<?php echo $position['id']; ?>]" 
                                                               value="<?php echo $candidate['id']; ?>"
                                                               class="form-check-input"
                                                               required>
                                                        <label class="form-check-label">Select Candidate</label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endwhile; ?>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>

                    <div class="card">
                        <div class="card-body">
                            <div class="form-check mb-3">
                                <input type="checkbox" class="form-check-input" id="confirm-vote" required>
                                <label class="form-check-label" for="confirm-vote">
                                    I confirm that these are my final choices and I understand that I cannot change my vote once submitted.
                                </label>
                            </div>
                            <button type="submit" name="submit_vote" class="btn btn-primary btn-lg w-100" id="submit-vote" disabled>
                                <i class="fas fa-vote-yea me-2"></i>Submit Your Vote
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function selectCandidate(card, positionId) {
            // Remove selection from other candidates in the same position
            document.querySelectorAll(`input[name="votes[${positionId}]"]`)
                .forEach(input => input.closest('.candidate-card').classList.remove('selected'));
            
            // Select the clicked candidate
            card.classList.add('selected');
            card.querySelector('input[type="radio"]').checked = true;
        }

        // Enable/disable submit button based on confirmation checkbox
        document.getElementById('confirm-vote').addEventListener('change', function() {
            document.getElementById('submit-vote').disabled = !this.checked;
        });

        // Validate form before submission
        document.getElementById('voting-form').addEventListener('submit', function(e) {
            const requiredVotes = document.querySelectorAll('input[type="radio"][required]');
            let allVoted = true;

            requiredVotes.forEach(voteGroup => {
                const name = voteGroup.getAttribute('name');
                const voted = document.querySelector(`input[name="${name}"]:checked`);
                if (!voted) {
                    allVoted = false;
                }
            });

            if (!allVoted) {
                e.preventDefault();
                alert('Please select a candidate for all positions before submitting your vote.');
            }
        });
    </script>
</body>
</html>
