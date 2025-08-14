<?php
require_once(dirname(__FILE__) . '/../../config/database.php');
$theme_path = '';
if(isset($_SESSION['couple_id'])){
    $couple_id = $_SESSION['couple_id'];
    $sql = "SELECT T.css_path FROM THEMES T JOIN COUPLES C ON T.id = C.theme_id WHERE C.id = ?";
    if($stmt = $mysqli->prepare($sql)){
        $stmt->bind_param("i", $couple_id);
        if($stmt->execute()){
            $stmt->bind_result($path);
            if($stmt->fetch()){
                $theme_path = $path;
            }
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Digital Love Journal</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <?php if(!empty($theme_path)): ?>
        <link rel="stylesheet" href="<?php echo $theme_path; ?>">
    <?php endif; ?>
</head>
<body>
    <div class="sidebar">
       <div class="sidebar-header" style="text-align: center; padding: 20px;">
    <img src="assets/logo.png" alt="Love Journal Logo" style="max-width: 200px; height: auto;">
    <h3 style="margin-top: 10px;">Love Journal</h3>
</div>

        <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
        <a href="timeline.php"><i class="fas fa-stream"></i> Timeline</a>
        <a href="new_entry.php"><i class="fas fa-plus-circle"></i> New Entry</a>
        <a href="settings.php"><i class="fas fa-cog"></i> Settings</a>
        <a href="php/auth/logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>

    <div class="main-content">