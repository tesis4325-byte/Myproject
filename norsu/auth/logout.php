<?php
session_start();

// Log the logout action if user was logged in
if (isset($_SESSION['user_id'])) {
    require_once '../config/database.php';
    
    $user_id = $_SESSION['user_id'];
    $sql = "INSERT INTO audit_logs (user_id, action, ip_address) VALUES (?, 'User logged out', ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $user_id, $_SERVER['REMOTE_ADDR']);
    $stmt->execute();
}

// Destroy all session data
session_destroy();

// Redirect to login page
header("Location: ../index.php");
exit();
?>
