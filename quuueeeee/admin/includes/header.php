<?php
// Get admin details for profile picture
$stmt = $db->prepare("SELECT profile_image FROM admins WHERE id = ?");
$stmt->execute([$_SESSION['admin_id']]);
$admin_profile = $stmt->fetch();
?>
<header class="admin-header">
    <h1><?php echo $page_title; ?></h1>
    <div class="header-profile">
        <span>Welcome, <?php echo htmlspecialchars($_SESSION['admin_name']); ?></span>
        <img src="<?php echo $admin_profile['profile_image'] ? '../uploads/profiles/' . htmlspecialchars($admin_profile['profile_image']) : '../images/avatar.png'; ?>" alt="Admin">
    </div>
</header>