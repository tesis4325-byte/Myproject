<?php
require_once dirname(__FILE__) . '/../../config/database.php';
session_start();

if($_SERVER["REQUEST_METHOD"] == "POST"){
    $couple_id = $_SESSION['couple_id'];

    if(isset($_POST['update_theme'])){
        $theme_id = (int)$_POST['theme_id'];
        $sql = "UPDATE COUPLES SET theme_id = ? WHERE id = ?";
        if($stmt = $mysqli->prepare($sql)){
            $stmt->bind_param("ii", $theme_id, $couple_id);
            if($stmt->execute()){
                header("location: ../../settings.php?theme_updated=1");
            } else {
                echo "Error updating theme.";
            }
            $stmt->close();
        }
    }
}
?>