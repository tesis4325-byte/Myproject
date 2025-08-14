<?php
// Initialize the session
require_once('php/auth/session.php');
require_once('config/database.php');

// Check if user is part of a couple
if(empty($_SESSION["couple_id"])){
    // If not, show the form to create or join a couple
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Join or Create a Couple</title>
        <link rel="stylesheet" href="css/style.css">
    </head>
    <body>
        <div class="wrapper">
            <h2>Welcome, <?php echo htmlspecialchars($_SESSION["username"]); ?>!</h2>
            <p>You are not yet part of a couple. Create a new couple or join one using an invite code.</p>
            
            <form action="php/core/couple_handler.php" method="post">
                <div class="form-group">
                    <input type="submit" name="create_couple" class="btn btn-primary" value="Create a New Couple">
                </div>
            </form>

            <hr>

            <form action="php/core/couple_handler.php" method="post">
                <div class="form-group">
                    <label>Join with Invite Code</label>
                    <input type="text" name="invite_code" class="form-control">
                    <?php if(isset($_GET['error']) && $_GET['error'] == 'invalid_code') echo '<span class="help-block">Invalid invite code.</span>'; ?>
                </div>
                <div class="form-group">
                    <input type="submit" name="join_couple" class="btn btn-primary" value="Join Couple">
                </div>
            </form>
            <p><a href="php/auth/logout.php">Sign Out of Your Account</a></p>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// If user is part of a couple, show the main dashboard
require_once('php/templates/header.php');
?>

<div class="container">
    <h2>Dashboard</h2>
    <p>Welcome back, <?php echo htmlspecialchars($_SESSION["username"]); ?>!</p>

    <div class="dashboard-section">
        <h3>Recent Entries</h3>
        <?php
        $couple_id = $_SESSION['couple_id'];
        $sql_entries = "SELECT E.id, E.title, E.entry_date, U.username FROM ENTRIES E JOIN USERS U ON E.author_id = U.id WHERE E.couple_id = ? ORDER BY E.entry_date DESC LIMIT 5";
        if($stmt = $mysqli->prepare($sql_entries)){
            $stmt->bind_param("i", $couple_id);
            if($stmt->execute()){
                $result = $stmt->get_result();
                if($result->num_rows > 0){
                    echo "<ul>";
                    while($row = $result->fetch_assoc()){
                        echo "<li><a href='view_entry.php?id=" . $row['id'] . "'>" . htmlspecialchars($row['title']) . "</a> by " . htmlspecialchars($row['username']) . " on " . $row['entry_date'] . "</li>";
                    }
                    echo "</ul>";
                } else {
                    echo "<p>No entries yet. <a href='new_entry.php'>Write your first one!</a></p>";
                }
            }
            $stmt->close();
        }
        ?>
    </div>

    <div class="dashboard-section">
        <h3>Recent Moods</h3>
        <?php
        $sql_moods = "SELECT M.emoji, M.mood_date, U.username FROM MOODS M JOIN USERS U ON M.user_id = U.id WHERE U.couple_id = ? ORDER BY M.mood_date DESC LIMIT 10";
        if($stmt = $mysqli->prepare($sql_moods)){
            $stmt->bind_param("i", $couple_id);
            if($stmt->execute()){
                $result = $stmt->get_result();
                if($result->num_rows > 0){
                    echo "<ul>";
                    while($row = $result->fetch_assoc()){
                        echo "<li>" . htmlspecialchars($row['username']) . " felt " . $row['emoji'] . " on " . $row['mood_date'] . "</li>";
                    }
                    echo "</ul>";
                } else {
                    echo "<p>No moods logged yet.</p>";
                }
            }
            $stmt->close();
        }
        ?>
    </div>

    <div class="dashboard-section">
        <h3>How are you feeling today?</h3>
        <form action="php/core/mood_handler.php" method="post" class="mood-form">
            <button type="submit" name="mood" value="😊" class="mood-btn">😊</button>
            <button type="submit" name="mood" value="🥰" class="mood-btn">🥰</button>
            <button type="submit" name="mood" value="😂" class="mood-btn">😂</button>
            <button type="submit" name="mood" value="😢" class="mood-btn">😢</button>
            <button type="submit" name="mood" value="😠" class="mood-btn">😠</button>
        </form>
    </div>
</div>

<?php
require_once('php/templates/footer.php');
?>