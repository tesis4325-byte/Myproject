<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'staff') {
    header("Location: ../index.php");
    exit();
}

require_once '../config/database.php';

$voter_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$action = basename($_SERVER['PHP_SELF']) == 'approve-voter.php' ? 'approve' : 'reject';

if ($voter_id) {
    $status = $action == 'approve' ? 'active' : 'blocked';
    
    // Update voter status
    $sql = "UPDATE users SET status = ? WHERE id = ? AND role = 'voter'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $status, $voter_id);
    
    if ($stmt->execute()) {
        // Log the action
        $sql = "INSERT INTO audit_logs (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $action_desc = $action == 'approve' ? 'Approved voter registration' : 'Rejected voter registration';
        $details = "Voter ID: " . $voter_id;
        $ip = $_SERVER['REMOTE_ADDR'];
        $stmt->bind_param("isss", $_SESSION['user_id'], $action_desc, $details, $ip);
        $stmt->execute();
        
        $_SESSION['success'] = "Voter has been " . ($action == 'approve' ? 'approved' : 'rejected') . " successfully.";
    } else {
        $_SESSION['error'] = "Error updating voter status.";
    }
}

header("Location: " . (isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'manage-voters.php'));
exit();
?>
