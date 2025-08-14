<?php
require_once('php/auth/session.php');
require_once('config/database.php');
require_once('php/templates/header.php');
?>

<div class="container">
    <h2>Memory Timeline</h2>
    <div class="timeline">
        <?php
        $couple_id = $_SESSION['couple_id'];
        $sql = "SELECT E.id, E.title, E.content, E.entry_date, U.username, E.is_private, E.author_id, (SELECT P.file_path FROM PHOTOS P WHERE P.entry_id = E.id ORDER BY P.id LIMIT 1) as first_photo FROM ENTRIES E JOIN USERS U ON E.author_id = U.id WHERE E.couple_id = ? ORDER BY E.entry_date DESC";
        if($stmt = $mysqli->prepare($sql)){
            $stmt->bind_param("i", $couple_id);
            if($stmt->execute()){
                $result = $stmt->get_result();
                if($result->num_rows > 0){
                    while($row = $result->fetch_assoc()){
                        if($row['is_private'] && $row['author_id'] != $_SESSION['id']){
                            continue; // Skip private entries of partner
                        }
                        ?>
                        <div class="timeline-item">
                            <div class="timeline-date"><?php echo date("M j, Y", strtotime($row['entry_date'])); ?></div>
                            <div class="timeline-content">
                                <?php if(!empty($row['first_photo'])): ?>
                                    <div class="timeline-photo">
                                        <a href="view_entry.php?id=<?php echo $row['id']; ?>">
                                            <img src="<?php echo htmlspecialchars($row['first_photo']); ?>" alt="Entry Photo">
                                        </a>
                                    </div>
                                <?php endif; ?>
                                <h3><a href="view_entry.php?id=<?php echo $row['id']; ?>"><?php echo htmlspecialchars($row['title']); ?></a></h3>
                                <p><em>by <?php echo htmlspecialchars($row['username']); ?></em></p>
                                <p><?php echo substr(nl2br(htmlspecialchars($row['content'])), 0, 150); ?>...</p>
                            </div>
                        </div>
                        <?php
                    }
                } else {
                    echo "<p>No entries yet. <a href='new_entry.php'>Write your first one!</a></p>";
                }
            }
            $stmt->close();
        }
        ?>
    </div>
</div>

<?php
require_once('php/templates/footer.php');
?>