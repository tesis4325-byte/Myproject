<?php
session_start();
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password'];

    // Debug log
    error_log("Login attempt for username: " . $username);

    $sql = "SELECT * FROM users WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['full_name'] = $user['full_name'];
            
            error_log("Login successful for user: " . $username);
            header("Location: ../dashboard.php");
            exit();
        } else {
            error_log("Invalid password for user: " . $username);
            header("Location: ../index.php?error=Invalid username or password");
            exit();
        }
    } else {
        error_log("User not found: " . $username);
        header("Location: ../index.php?error=Invalid username or password");
        exit();
    }
} else {
    header("Location: ../index.php");
    exit();
}
?>