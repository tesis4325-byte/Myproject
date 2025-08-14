<?php
session_start();
require_once '../config/database.php';
require_once '../config/security.php';

$security = new SecurityUtils($conn);

// Check if IP is blacklisted
if ($security->checkIPBlacklist()) {
    $_SESSION['error'] = 'Access temporarily blocked due to too many failed attempts. Please try again later.';
    header('Location: ../index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $conn->real_escape_string($_POST['username']);
    $password = $_POST['password'];    // Query user table
    $sql = "SELECT * FROM users WHERE email = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            // Record successful login
            $security->recordLoginAttempt($user['id'], true);
            
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['name'] = $user['firstname'];
            
            // Store device info
            $_SESSION['login_device'] = [
                'ip' => $security->getClientIP(),
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'time' => time()
            ];

            // Redirect based on role
            switch ($user['role']) {
                case 'admin':
                    header("Location: ../admin/dashboard.php");
                    break;
                case 'staff':
                    header("Location: ../staff/dashboard.php");
                    break;
                case 'voter':
                    header("Location: ../voter/dashboard.php");
                    break;
                default:
                    header("Location: ../index.php");
            }
            exit();
        } else {
            $_SESSION['error'] = "Invalid password";
        }
    } else {
        $_SESSION['error'] = "User not found";
    }
    
    header("Location: ../index.php");
    exit();
}
?>
