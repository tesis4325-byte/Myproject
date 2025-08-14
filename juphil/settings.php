<?php
require_once('php/auth/session.php');
require_once('config/database.php');

// Get couple's invite code
$invite_code = '';
$couple_id = $_SESSION['couple_id'];
$sql_invite = "SELECT invite_code FROM COUPLES WHERE id = ?";
if($stmt_invite = $mysqli->prepare($sql_invite)){
    $stmt_invite->bind_param("i", $couple_id);
    if($stmt_invite->execute()){
        $stmt_invite->bind_result($code);
        if($stmt_invite->fetch()){
            $invite_code = $code;
        }
    }
    $stmt_invite->close();
}

// Get available themes
$themes = [];
$sql_themes = "SELECT id, name FROM THEMES";
if($result = $mysqli->query($sql_themes)){
    while($row = $result->fetch_assoc()){
        $themes[] = $row;
    }
}

require_once('php/templates/header.php');
?>

<div class="container">
    <h2>Settings</h2>

    <div class="settings-section">
        <h3>Invite Your Partner</h3>
        <p>Share this code with your partner to let them join your journal:</p>
        <p><strong><?php echo htmlspecialchars($invite_code); ?></strong></p>
    </div>

    <div class="settings-section">
        <h3>Change Theme</h3>
        <?php if(isset($_GET['theme_updated'])) echo "<p style='color:green;'>Theme updated successfully!</p>"; ?>
        <form action="php/core/settings_handler.php" method="post">
            <div class="form-group">
                <label for="theme_id">Select a Theme</label>
                <select name="theme_id" id="theme_id" class="form-control">
                    <?php foreach($themes as $theme): ?>
                        <option value="<?php echo $theme['id']; ?>"><?php echo htmlspecialchars($theme['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <input type="submit" name="update_theme" class="btn btn-primary" value="Update Theme">
            </div>
        </form>
    </div>
</div>

<?php
require_once('php/templates/footer.php');
?>