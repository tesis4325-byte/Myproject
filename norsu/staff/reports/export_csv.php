<?php
require_once __DIR__ . '/../../config/database.php';

session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'staff') {
    header("Location: ../../index.php");
    exit();
}

$election_id = isset($_GET['election']) ? (int)$_GET['election'] : 0;

if (!$election_id) {
    die('Election ID required');
}

// Set headers for CSV download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="election_results_' . $election_id . '_' . date('Y-m-d') . '.csv"');

// Create output stream
$output = fopen('php://output', 'w');

// Get election details
$election = $conn->query("SELECT * FROM elections WHERE id = $election_id")->fetch_assoc();

// Write election information
fputcsv($output, ['Election Report']);
fputcsv($output, ['Title', $election['title']]);
fputcsv($output, ['Status', ucfirst($election['status'])]);
fputcsv($output, ['Duration', date('M j, Y h:i A', strtotime($election['start_date'])) . ' to ' . date('M j, Y h:i A', strtotime($election['end_date']))]);
fputcsv($output, []); // Empty line for spacing

// Get voter turnout
$turnout = $conn->query("
    SELECT 
        (SELECT COUNT(DISTINCT voter_id) FROM votes WHERE election_id = $election_id) as total_votes,
        (SELECT COUNT(*) FROM users WHERE role = 'voter' AND status = 'active') as total_voters
")->fetch_assoc();

fputcsv($output, ['Voter Turnout']);
fputcsv($output, ['Total Votes', $turnout['total_votes']]);
fputcsv($output, ['Total Eligible Voters', $turnout['total_voters']]);
fputcsv($output, ['Turnout Percentage', number_format(($turnout['total_votes'] / $turnout['total_voters'] * 100), 1) . '%']);
fputcsv($output, []); // Empty line for spacing

// Get positions and their results
$positions = $conn->query("SELECT * FROM positions WHERE election_id = $election_id ORDER BY position_name");

while ($position = $positions->fetch_assoc()) {
    fputcsv($output, ['Position: ' . $position['position_name']]);
    fputcsv($output, ['Candidate', 'Votes', 'Percentage']);
    
    $candidates = $conn->query("
        SELECT 
            c.*,
            COUNT(v.id) as vote_count,
            (SELECT COUNT(DISTINCT voter_id) FROM votes WHERE position_id = {$position['id']}) as total_votes
        FROM candidates c
        LEFT JOIN votes v ON c.id = v.candidate_id AND v.position_id = {$position['id']}
        WHERE c.position_id = {$position['id']}
        GROUP BY c.id
        ORDER BY vote_count DESC
    ");
    
    while ($candidate = $candidates->fetch_assoc()) {
        $percentage = $candidate['total_votes'] > 0 ? 
            ($candidate['vote_count'] / $candidate['total_votes'] * 100) : 0;
            
        fputcsv($output, [
            $candidate['firstname'] . ' ' . $candidate['lastname'],
            $candidate['vote_count'],
            number_format($percentage, 1) . '%'
        ]);
    }
    
    fputcsv($output, []); // Empty line between positions
}

// Add voting timeline
fputcsv($output, ['Voting Timeline']);
fputcsv($output, ['Hour', 'Number of Votes']);

$timeline = $conn->query("
    SELECT 
        DATE_FORMAT(voted_at, '%Y-%m-%d %H:00:00') as hour,
        COUNT(*) as vote_count
    FROM votes 
    WHERE election_id = $election_id
    GROUP BY hour
    ORDER BY hour
");

while ($hour = $timeline->fetch_assoc()) {
    fputcsv($output, [
        date('M j, Y h:i A', strtotime($hour['hour'])),
        $hour['vote_count']
    ]);
}

fputcsv($output, []); // Empty line for spacing

// Add device statistics
fputcsv($output, ['Device Usage Statistics']);
fputcsv($output, ['Device Type', 'Number of Votes', 'Percentage']);

$devices = $conn->query("
    SELECT 
        device_type,
        COUNT(*) as vote_count,
        (COUNT(*) / (SELECT COUNT(*) FROM votes WHERE election_id = $election_id) * 100) as percentage
    FROM votes
    WHERE election_id = $election_id
    GROUP BY device_type
    ORDER BY vote_count DESC
");

while ($device = $devices->fetch_assoc()) {
    fputcsv($output, [
        ucfirst($device['device_type']),
        $device['vote_count'],
        number_format($device['percentage'], 1) . '%'
    ]);
}

// Add security statistics
fputcsv($output, []); // Empty line for spacing
fputcsv($output, ['Security Statistics']);
fputcsv($output, ['Metric', 'Count']);

$security_stats = $conn->query("
    SELECT 
        action_type,
        COUNT(*) as count
    FROM security_logs
    WHERE created_at BETWEEN '{$election['start_date']}' AND '{$election['end_date']}'
    GROUP BY action_type
    ORDER BY count DESC
");

while ($stat = $security_stats->fetch_assoc()) {
    fputcsv($output, [
        ucwords(str_replace('_', ' ', $stat['action_type'])),
        $stat['count']
    ]);
}

// Close the output stream
fclose($output);
