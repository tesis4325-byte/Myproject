<?php
require_once('php/auth/session.php');
require_once('config/database.php');

$entry_id = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
$title = $content = $entry_date = "";
$is_private = 0;
$page_title = "Create New Entry";

if($entry_id > 0){
    $page_title = "Edit Entry";
    $sql = "SELECT title, content, entry_date, is_private, author_id FROM ENTRIES WHERE id = ? AND couple_id = ?";
    if($stmt = $mysqli->prepare($sql)){
        $stmt->bind_param("ii", $entry_id, $_SESSION['couple_id']);
        if($stmt->execute()){
            $result = $stmt->get_result();
            if($row = $result->fetch_assoc()){
                if($row['author_id'] != $_SESSION['id']){
                    // Prevent editing if not the author
                    header("location: dashboard.php");
                    exit;
                }
                $title = $row['title'];
                $content = $row['content'];
                $entry_date = $row['entry_date'];
                $is_private = $row['is_private'];
            } else {
                // Entry not found or not part of the couple
                header("location: dashboard.php");
                exit;
            }
        }
        $stmt->close();
    }
}

require_once('php/templates/header.php');
?>

<div class="container">
    <h2><?php echo $page_title; ?></h2>
    <?php if(isset($_GET['new'])): ?>
        <p style="color:green;">Entry created successfully! You can now add photos.</p>
    <?php endif; ?>
    <form action="php/core/entry_handler.php" method="post" enctype="multipart/form-data">
        <input type="hidden" name="entry_id" value="<?php echo $entry_id; ?>">
        <div class="form-group">
            <label>Title</label>
            <input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($title); ?>" required>
        </div>
        <div class="form-group">
            <label>Content</label>
            <textarea name="content" class="form-control" rows="10" required><?php echo htmlspecialchars($content); ?></textarea>
        </div>
        <div class="form-group">
            <label>Date</label>
            <input type="date" name="entry_date" class="form-control" value="<?php echo $entry_date; ?>" required>
        </div>
        <div class="form-group">
            <label>
                <input type="checkbox" name="is_private" value="1" <?php if($is_private) echo "checked"; ?>>
                Mark as Private (only you can see this)
            </label>
        </div>

        <hr>
        <h3>Upload Photos</h3>
        <div id="photo-upload-fields">
            <div class="photo-upload-item">
                <div class="form-group">
                    <label>Photo</label>
                    <input type="file" name="photos[]" class="form-control">
                </div>
                <div class="form-group">
                    <label>Caption</label>
                    <input type="text" name="captions[]" class="form-control">
                </div>
            </div>
        </div>
        <button type="button" id="add-photo-field" class="btn" style="margin-bottom: 10px;">Add Another Photo</button>
        
        <div class="form-group" style="margin-top: 20px;">
            <input type="submit" class="btn btn-primary" value="Save Entry">
        </div>
    </form>
</div>

<?php
require_once('php/templates/footer.php');
?>