<?php
require_once('php/auth/session.php');
require_once('config/database.php');
require_once('php/templates/header.php');

$entry_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$couple_id = $_SESSION['couple_id'];
$user_id = $_SESSION['id'];

$sql = "SELECT E.title, E.content, E.entry_date, U.username, E.is_private, E.author_id FROM ENTRIES E JOIN USERS U ON E.author_id = U.id WHERE E.id = ? AND E.couple_id = ?";
$entry_found = false;

if($stmt = $mysqli->prepare($sql)){
    $stmt->bind_param("ii", $entry_id, $couple_id);
    if($stmt->execute()){
        $result = $stmt->get_result();
        if($row = $result->fetch_assoc()){
            // Check for private entry
            if(!$row['is_private'] || ($row['is_private'] && $row['author_id'] == $user_id)){
                $entry_found = true;
                ?>
                <div class="container">
                    <div class="entry-header">
                        <h2><?php echo htmlspecialchars($row['title']); ?></h2>
                        <p><em>By <?php echo htmlspecialchars($row['username']); ?> on <?php echo date("F j, Y", strtotime($row['entry_date'])); ?></em></p>
                        <?php if($row['author_id'] == $user_id): ?>
                            <a href="new_entry.php?edit=<?php echo $entry_id; ?>" class="btn btn-secondary">Edit Entry</a>
                        <?php endif; ?>
                    </div>

                    <div class="entry-content">
                        <?php echo nl2br(htmlspecialchars($row['content'])); ?>
                    </div>

                    <div class="photos">
                        <h3>Photos</h3>
                        <?php
                        $sql_photos = "SELECT file_path, caption FROM PHOTOS WHERE entry_id = ?";
                        if($stmt_photos = $mysqli->prepare($sql_photos)){
                            $stmt_photos->bind_param("i", $entry_id);
                            if($stmt_photos->execute()){
                                $result_photos = $stmt_photos->get_result();
                                if($result_photos->num_rows > 0){
                                    while($photo = $result_photos->fetch_assoc()){
                                        echo "<div class='photo-item'>";
                                        echo "<div class='photo-img-container'>";
                                        echo "<img src='" . htmlspecialchars($photo['file_path']) . "' alt='Journal Photo'>";
                                        echo "</div>";
                                        if(!empty($photo['caption'])){
                                            echo "<p>" . htmlspecialchars($photo['caption']) . "</p>";
                                        }
                                        echo "</div>";
                                    }
                                } else {
                                    echo "<p>No photos for this entry.</p>";
                                }
                            }
                            $stmt_photos->close();
                        }
                        ?>
                    </div>
                </div>
                <?php
            }
        }
    }
    $stmt->close();
}

if(!$entry_found){
    echo "<div class='container'><h2>Entry Not Found</h2><p>The requested entry does not exist or you do not have permission to view it.</p></div>";
}

require_once('php/templates/footer.php');
?>