<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

require_once '../config/database.php';
$database = new Database();
$db = $database->getConnection();

// Get admin details first
$stmt = $db->prepare("SELECT * FROM admins WHERE id = ?");
$stmt->execute([$_SESSION['admin_id']]);
$admin = $stmt->fetch();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $profile_image = $admin['profile_image']; // Keep existing image by default

        // Handle profile image upload
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            $filename = $_FILES['profile_image']['name'];
            $file_ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

            if (in_array($file_ext, $allowed)) {
                $new_filename = 'admin_' . $_SESSION['admin_id'] . '_' . time() . '.' . $file_ext;
                $upload_dir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'profiles';
                
                // Create directory if it doesn't exist
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                $upload_path = $upload_dir . DIRECTORY_SEPARATOR . $new_filename;

                if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $upload_path)) {
                    // Delete old profile image if exists
                    if ($admin['profile_image'] && file_exists($upload_dir . DIRECTORY_SEPARATOR . $admin['profile_image'])) {
                        unlink($upload_dir . DIRECTORY_SEPARATOR . $admin['profile_image']);
                    }
                    $profile_image = $new_filename;
                }
            }
        }

        $stmt = $db->prepare("UPDATE admins SET name = ?, email = ?, profile_image = ? WHERE id = ?");
        $stmt->execute([
            $_POST['name'],
            $_POST['email'],
            $profile_image,
            $_SESSION['admin_id']
        ]);
        $_SESSION['admin_name'] = $_POST['name'];
        $success_message = "Profile updated successfully!";
    }

    if (isset($_POST['change_password'])) {
        $stmt = $db->prepare("SELECT password FROM admins WHERE id = ?");
        $stmt->execute([$_SESSION['admin_id']]);
        $admin = $stmt->fetch();

        if (password_verify($_POST['current_password'], $admin['password'])) {
            if ($_POST['new_password'] === $_POST['confirm_password']) {
                $new_password_hash = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
                $stmt = $db->prepare("UPDATE admins SET password = ? WHERE id = ?");
                $stmt->execute([$new_password_hash, $_SESSION['admin_id']]);
                $success_message = "Password changed successfully!";
            } else {
                $error_message = "New passwords do not match!";
            }
        } else {
            $error_message = "Current password is incorrect!";
        }
    }
}

// Get admin details
$stmt = $db->prepare("SELECT * FROM admins WHERE id = ?");
$stmt->execute([$_SESSION['admin_id']]);
$admin = $stmt->fetch();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - NORSU Queue</title>
    <link rel="stylesheet" href="../css/admin.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="admin-container">
        <aside class="admin-sidebar">
            <div class="sidebar-header">
                <img src="../images/norsu-logo.png" alt="NORSU Logo">
                <h2>Admin Panel</h2>
            </div>
            <nav class="sidebar-nav">
                <a href="dashboard.php"><i class="fas fa-dashboard"></i> Dashboard</a>
                <a href="queue.php"><i class="fas fa-list-ol"></i> Queue Management</a>
                <a href="students.php"><i class="fas fa-users"></i> Students</a>
                <a href="reports.php"><i class="fas fa-chart-bar"></i> Reports</a>
                <a href="settings.php" class="active"><i class="fas fa-cog"></i> Settings</a>
                <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </nav>
        </aside>

        <main class="admin-main">
            <?php $page_title = "Settings"; ?>
            <?php require_once 'includes/header.php'; ?>
            
            <?php if (isset($success_message)): ?>
                <div class="alert success">
                    <?php echo $success_message; ?>
                </div>
            <?php endif; ?>

            <?php if (isset($error_message)): ?>
                <div class="alert error">
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>

            <div class="settings-container">
                <div class="settings-card">
                    <h2>Profile Settings</h2>
                    <form method="POST" class="settings-form" enctype="multipart/form-data">
                        <div class="profile-image-upload">
                            <div class="current-image">
                                <img src="<?php echo $admin['profile_image'] ? '../uploads/profiles/' . htmlspecialchars($admin['profile_image']) : '../images/avatar.png'; ?>" 
                                     alt="Profile Picture" id="preview-image">
                            </div>
                            <div class="upload-controls">
                                <label for="profile_image" class="upload-btn">
                                    <i class="fas fa-camera"></i> Change Picture
                                </label>
                                <input type="file" id="profile_image" name="profile_image" accept="image/*" style="display: none;">
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="name">Name</label>
                            <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($admin['name']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($admin['email']); ?>" required>
                        </div>
                        <button type="submit" name="update_profile" class="btn primary">Update Profile</button>
                    </form>
                </div>

                <div class="settings-card">
                    <h2>Change Password</h2>
                    <form method="POST" class="settings-form">
                        <div class="form-group">
                            <label for="current_password">Current Password</label>
                            <input type="password" id="current_password" name="current_password" required>
                        </div>
                        <div class="form-group">
                            <label for="new_password">New Password</label>
                            <input type="password" id="new_password" name="new_password" required>
                        </div>
                        <div class="form-group">
                            <label for="confirm_password">Confirm New Password</label>
                            <input type="password" id="confirm_password" name="confirm_password" required>
                        </div>
                        <button type="submit" name="change_password" class="btn primary">Change Password</button>
                    </form>
                </div>

                <div class="settings-card">
                    <h2>System Settings</h2>
                    <form method="POST" class="settings-form">
                        <div class="form-group">
                            <label>Queue Operating Hours</label>
                            <div class="time-range">
                                <input type="time" name="queue_start_time" value="08:00">
                                <span>to</span>
                                <input type="time" name="queue_end_time" value="17:00">
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Max Daily Tickets</label>
                            <input type="number" name="max_daily_tickets" value="100">
                        </div>
                        <button type="submit" name="update_settings" class="btn primary">Save Settings</button>
                    </form>
                </div>
            </div>
        </main>
    </div>
</body>
</html>