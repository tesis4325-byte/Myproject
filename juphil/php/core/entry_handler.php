<?php
require_once dirname(__FILE__) . '/../../config/database.php';
session_start();

function handle_photo_upload($entry_id, $mysqli){
    if(isset($_FILES["photos"])){
        $upload_dir = dirname(__FILE__) . '/../../uploads/';
        foreach($_FILES['photos']['name'] as $key=>$val){
            if(!empty($_FILES['photos']['name'][$key])){
                $file_name = basename($_FILES['photos']['name'][$key]);
                $file_tmp = $_FILES['photos']['tmp_name'][$key];
                $target_file = $upload_dir . $file_name;
                $file_path_for_db = 'uploads/' . $file_name;

                if(move_uploaded_file($file_tmp, $target_file)){
                    $sql = "INSERT INTO PHOTOS (entry_id, file_path, caption) VALUES (?, ?, ?)";
                    if($stmt = $mysqli->prepare($sql)){
                        $caption = trim($_POST['captions'][$key]);
                        $stmt->bind_param("iss", $entry_id, $file_path_for_db, $caption);
                        $stmt->execute();
                        $stmt->close();
                    }
                }
            }
        }
    }
}

if($_SERVER["REQUEST_METHOD"] == "POST"){
    $author_id = $_SESSION['id'];
    $couple_id = $_SESSION['couple_id'];
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    $entry_date = trim($_POST['entry_date']);
    $is_private = isset($_POST['is_private']) ? 1 : 0;
    $entry_id = isset($_POST['entry_id']) ? (int)$_POST['entry_id'] : 0;

    if($entry_id > 0){
        // Update existing entry
        $sql = "UPDATE ENTRIES SET title = ?, content = ?, entry_date = ?, is_private = ? WHERE id = ? AND couple_id = ?";
        if($stmt = $mysqli->prepare($sql)){
            $stmt->bind_param("sssiii", $title, $content, $entry_date, $is_private, $entry_id, $couple_id);
            if($stmt->execute()){
                handle_photo_upload($entry_id, $mysqli);
                header("location: ../../view_entry.php?id=" . $entry_id);
            } else {
                echo "Error updating entry.";
            }
            $stmt->close();
        }
    } else {
        // Create new entry
        $sql = "INSERT INTO ENTRIES (couple_id, author_id, title, content, entry_date, is_private) VALUES (?, ?, ?, ?, ?, ?)";
        if($stmt = $mysqli->prepare($sql)){
            $stmt->bind_param("iisssi", $couple_id, $author_id, $title, $content, $entry_date, $is_private);
            if($stmt->execute()){
                $new_entry_id = $stmt->insert_id;
                handle_photo_upload($new_entry_id, $mysqli);
                header("location: ../../view_entry.php?id=" . $new_entry_id);
            } else {
                echo "Error creating entry.";
            }
            $stmt->close();
        }
    }
}
?>