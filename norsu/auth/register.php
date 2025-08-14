<?php
session_start();
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validate input
    $firstname = $conn->real_escape_string($_POST['firstname']);
    $lastname = $conn->real_escape_string($_POST['lastname']);
    $email = $conn->real_escape_string($_POST['email']);
    $phone = $conn->real_escape_string($_POST['phone']);
    $voter_id = $conn->real_escape_string($_POST['voter_id']);
    $dob = $conn->real_escape_string($_POST['dob']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validation
    if ($password !== $confirm_password) {
        $_SESSION['error'] = "Passwords do not match";
        header("Location: ../register.php");
        exit();
    }

    // Check if email already exists
    $check_email = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $check_email->bind_param("s", $email);
    $check_email->execute();
    if ($check_email->get_result()->num_rows > 0) {
        $_SESSION['error'] = "Email already registered";
        header("Location: ../register.php");
        exit();
    }

    // Check if voter ID already exists
    $check_voter = $conn->prepare("SELECT id FROM users WHERE voter_id = ?");
    $check_voter->bind_param("s", $voter_id);
    $check_voter->execute();
    if ($check_voter->get_result()->num_rows > 0) {
        $_SESSION['error'] = "Voter ID already registered";
        header("Location: ../register.php");
        exit();
    }

    // Handle profile photo upload
    $profile_photo = "default.jpg"; // Default photo
    if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['profile_photo']['name'];
        $filetype = pathinfo($filename, PATHINFO_EXTENSION);
        
        if (in_array(strtolower($filetype), $allowed)) {
            $new_name = uniqid() . "." . $filetype;
            $upload_path = "../uploads/profile_photos/" . $new_name;
            
            if (move_uploaded_file($_FILES['profile_photo']['tmp_name'], $upload_path)) {
                $profile_photo = $new_name;
            }
        }
    }    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Insert user
    $sql = "INSERT INTO users (firstname, lastname, email, phone, voter_id, dob, password, profile_photo, role, status, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'voter', 'pending', NOW())";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssssss", 
        $firstname, 
        $lastname, 
        $email, 
        $phone, 
        $voter_id, 
        $dob,
        $hashed_password, 
        $profile_photo
    );

    if ($stmt->execute()) {
        $_SESSION['success'] = "Registration successful! Please wait for admin approval.";
        header("Location: ../index.php");
        exit();
    } else {
        $_SESSION['error'] = "Registration failed: " . $conn->error;
        header("Location: ../register.php");
        exit();
    }
}
?>
