<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'voter') {
    header("Location: ../index.php");
    exit();
}

require_once '../config/database.php';

$user_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// Fetch current voter information
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ? AND role = 'voter'");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$voter = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id']; // Use session user ID consistently
    $firstname = trim($_POST['firstname']);
    $lastname = trim($_POST['lastname']);
    $email = trim($_POST['email']);
    $form_voter_id = trim($_POST['voter_id']);
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
      // Validate required fields
    if (empty($firstname) || empty($lastname) || empty($email) || empty($form_voter_id)) {
        $error_message = "All fields are required.";
    } else {
        // Start transaction
        $conn->begin_transaction();
        try {
            // Update basic information
            $update_stmt = $conn->prepare("UPDATE users SET firstname = ?, lastname = ?, email = ?, voter_id = ? WHERE id = ? AND role = 'voter'");
            $update_stmt->bind_param("ssssi", $firstname, $lastname, $email, $form_voter_id, $user_id);
            $update_stmt->execute();
            $update_stmt->close();

            // Handle password change if requested
            if (!empty($current_password) && !empty($new_password)) {
                if ($new_password !== $confirm_password) {
                    throw new Exception("New passwords do not match.");
                }                // Verify current password
                $pwd_check_stmt = $conn->prepare("SELECT password FROM users WHERE id = ? AND role = 'voter'");
                $pwd_check_stmt->bind_param("i", $user_id);
                $pwd_check_stmt->execute();
                $result = $pwd_check_stmt->get_result()->fetch_assoc();
                $pwd_check_stmt->close();

                if (!password_verify($current_password, $result['password'])) {
                    throw new Exception("Current password is incorrect.");
                }                // Update password
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $pwd_update_stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ? AND role = 'voter'");
                $pwd_update_stmt->bind_param("si", $hashed_password, $user_id);
                $pwd_update_stmt->execute();
                $pwd_update_stmt->close();
            }

            // Handle profile photo upload
            if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
                $file_extension = strtolower(pathinfo($_FILES['profile_photo']['name'], PATHINFO_EXTENSION));
                $allowed_extensions = ['jpg', 'jpeg', 'png'];

                if (!in_array($file_extension, $allowed_extensions)) {
                    throw new Exception("Only JPG, JPEG, and PNG files are allowed.");
                }

                $new_filename = uniqid() . '.' . $file_extension;
                $upload_path = "../uploads/profile_photos/" . $new_filename;

                if (move_uploaded_file($_FILES['profile_photo']['tmp_name'], $upload_path)) {                    // Update profile photo in database
                    $photo_update_stmt = $conn->prepare("UPDATE users SET profile_photo = ? WHERE id = ? AND role = 'voter'");
                    $photo_update_stmt->bind_param("si", $new_filename, $user_id);
                    $photo_update_stmt->execute();
                    $photo_update_stmt->close();
                } else {
                    throw new Exception("Failed to upload profile photo.");
                }
            }            $conn->commit();
            $success_message = "Profile updated successfully!";
              // Refresh voter data
            $refresh_stmt = $conn->prepare("SELECT * FROM users WHERE id = ? AND role = 'voter'");
            $refresh_stmt->bind_param("i", $user_id);
            $refresh_stmt->execute();
            $voter = $refresh_stmt->get_result()->fetch_assoc();
            $refresh_stmt->close();        } catch (Exception $e) {
            $conn->rollback();
            $error_message = $e->getMessage();
        }
    }
} // Close POST condition

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - E-BOTO</title>
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
        }
        .card:hover {
            box-shadow: 0 15px 30px rgba(0,0,0,0.12);
        }
        .profile-photo {
            width: 180px;
            height: 180px;
            object-fit: cover;
            border-radius: 50%;
            border: 4px solid #fff;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }
        .profile-photo:hover {
            transform: scale(1.02);
            box-shadow: 0 12px 30px rgba(0,0,0,0.15);
        }
        .profile-photo-container {
            position: relative;
            display: inline-block;
            margin: 20px 0;
        }
        .photo-edit-badge {
            position: absolute;
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            border: 3px solid white;
            border-radius: 50%;
            padding: 10px;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            transition: all 0.3s ease;            bottom: 10px;
            right: 10px;
            transition: all 0.3s ease;
        }
        .photo-edit-badge:hover {
            transform: translateY(-2px) scale(1.05);
            box-shadow: 0 6px 20px rgba(0,0,0,0.25);
        }
        .form-label {
            font-weight: 600;
            color: #1e3c72;
            margin-bottom: 0.5rem;
        }
        .form-control {
            border: 1px solid rgba(0,0,0,0.1);
            border-radius: 10px;
            padding: 12px 15px;
            transition: all 0.3s ease;
        }
        .form-control:focus {
            box-shadow: 0 0 0 3px rgba(30,60,114,0.1);
            border-color: #1e3c72;
        }
        .btn {
            padding: 12px 25px;
            font-weight: 600;
            letter-spacing: 0.5px;
            border-radius: 10px;
            transition: all 0.3s ease;
        }
        .btn-primary {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            border: none;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(30,60,114,0.3);
        }
        .btn-light {
            background: rgba(255,255,255,0.9);
            border: none;
            color: #1e3c72;
            font-weight: 500;
        }
        .btn-light:hover {
            background: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .alert {
            border: none;
            border-radius: 15px;
            padding: 1rem 1.5rem;
        }
        .alert-success {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
        }
        .alert-danger {
            background: linear-gradient(135deg, #dc3545 0%, #ff6b6b 100%);
            color: white;
        }
        h3 {
            color: #1e3c72;
            font-weight: 700;
            margin: 1rem 0;
        }
        hr {
            opacity: 0.1;
            margin: 2rem 0;
        }
        h5 {
            color: #1e3c72;
            font-weight: 600;
            margin-bottom: 1.5rem;
        }
        .text-muted {
            color: #6c757d !important;
            font-size: 1.1rem;
        }
        @media (max-width: 768px) {
            .profile-photo {
                width: 150px;
                height: 150px;
            }
            .card {
                margin: 10px;
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
            <div class="col-lg-8">
                <?php if ($success_message): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($success_message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if ($error_message): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($error_message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-body">
                        <div class="text-center mb-4">
                            <div class="profile-photo-container">                                <img src="<?php echo $voter['profile_photo'] ? '../uploads/profile_photos/' . htmlspecialchars($voter['profile_photo']) : 'https://via.placeholder.com/150'; ?>" 
                                     alt="Profile Photo" 
                                     class="profile-photo mb-3">
                                <label for="profile_photo" class="photo-edit-badge">
                                    <i class="fas fa-camera"></i>
                                </label>
                            </div>
                            <h3><?php echo htmlspecialchars(($voter['firstname'] ?? '') . ' ' . ($voter['lastname'] ?? '')); ?></h3>
                            <p class="text-muted">Voter ID: <?php echo htmlspecialchars($voter['voter_id'] ?? ''); ?></p>
                        </div>

                        <form method="POST" enctype="multipart/form-data">
                            <input type="file" id="profile_photo" name="profile_photo" class="d-none" accept="image/jpeg,image/png">
                              <div class="mb-3">
                                <label for="firstname" class="form-label">First Name</label>
                                <input type="text" class="form-control" id="firstname" name="firstname" 
                                       value="<?php echo htmlspecialchars($voter['firstname'] ?? ''); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="lastname" class="form-label">Last Name</label>
                                <input type="text" class="form-control" id="lastname" name="lastname" 
                                       value="<?php echo htmlspecialchars($voter['lastname'] ?? ''); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?php echo htmlspecialchars($voter['email'] ?? ''); ?>" required>
                            </div>                            <div class="mb-3">
                                <label for="voter_id" class="form-label">Voter ID</label>
                                <input type="text" class="form-control" id="voter_id" name="voter_id" 
                                       value="<?php echo htmlspecialchars($voter['voter_id'] ?? ''); ?>" required>
                            </div>

                            <hr class="my-4">

                            <h5>Change Password</h5>
                            <div class="mb-3">
                                <label for="current_password" class="form-label">Current Password</label>
                                <input type="password" class="form-control" id="current_password" name="current_password">
                            </div>

                            <div class="mb-3">
                                <label for="new_password" class="form-label">New Password</label>
                                <input type="password" class="form-control" id="new_password" name="new_password">
                            </div>

                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Confirm New Password</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                            </div>

                            <div class="text-center">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Save Changes
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Handle profile photo upload
        document.getElementById('profile_photo').addEventListener('change', function(e) {
            if (e.target.files && e.target.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.querySelector('.profile-photo').src = e.target.result;
                };
                reader.readAsDataURL(e.target.files[0]);
            }
        });
    </script>
</body>
</html>
