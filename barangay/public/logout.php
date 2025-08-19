<?php
/**
 * Barangay Document Request and Tracking System
 * Logout Page
 */

require_once '../includes/config.php';
require_once '../includes/auth.php';

// Logout user
$auth->logout();

// Redirect to login page with success message
header('Location: index.php?message=logged_out');
exit();
?>
