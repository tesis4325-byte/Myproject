<?php
require_once dirname(__FILE__) . '/../../config/database.php';
session_start();

if($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['mood'])){
    $user_id = $_SESSION['id'];
    $emoji = $_POST['mood'];
    $mood_date = date('Y-m-d');

    // Check if a mood is already logged for today
    $sql_check = "SELECT id FROM MOODS WHERE user_id = ? AND mood_date = ?";
    if($stmt_check = $mysqli->prepare($sql_check)){
        $stmt_check->bind_param("is", $user_id, $mood_date);
        $stmt_check->execute();
        $stmt_check->store_result();
        if($stmt_check->num_rows > 0){
            // Update existing mood for today
            $sql = "UPDATE MOODS SET emoji = ? WHERE user_id = ? AND mood_date = ?";
            if($stmt = $mysqli->prepare($sql)){
                $stmt->bind_param("sis", $emoji, $user_id, $mood_date);
                $stmt->execute();
                $stmt->close();
            }
        } else {
            // Insert new mood for today
            $sql = "INSERT INTO MOODS (user_id, emoji, mood_date) VALUES (?, ?, ?)";
            if($stmt = $mysqli->prepare($sql)){
                $stmt->bind_param("iss", $user_id, $emoji, $mood_date);
                $stmt->execute();
                $stmt->close();
            }
        }
        $stmt_check->close();
    }
    header("location: ../../dashboard.php");
}
?>