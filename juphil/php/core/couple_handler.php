<?php
require_once dirname(__FILE__) . '/../../config/database.php';

function generateInviteCode($length = 8) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}

if($_SERVER["REQUEST_METHOD"] == "POST"){
    session_start();
    $user_id = $_SESSION["id"];

    // Create a new couple
    if(isset($_POST['create_couple'])){
        $invite_code = generateInviteCode();
        $sql = "INSERT INTO COUPLES (invite_code) VALUES (?)";
        if($stmt = $mysqli->prepare($sql)){
            $stmt->bind_param("s", $invite_code);
            if($stmt->execute()){
                $couple_id = $stmt->insert_id;
                $sql_user = "UPDATE USERS SET couple_id = ? WHERE id = ?";
                if($stmt_user = $mysqli->prepare($sql_user)){
                    $stmt_user->bind_param("ii", $couple_id, $user_id);
                    if($stmt_user->execute()){
                        $_SESSION["couple_id"] = $couple_id;
                        header("location: ../../dashboard.php");
                    } else {
                        echo "Error updating user record.";
                    }
                    $stmt_user->close();
                }
            } else {
                echo "Error creating couple.";
            }
            $stmt->close();
        }
    }

    // Join an existing couple
    if(isset($_POST['join_couple'])){
        $invite_code = trim($_POST['invite_code']);
        $sql = "SELECT id FROM COUPLES WHERE invite_code = ?";
        if($stmt = $mysqli->prepare($sql)){
            $stmt->bind_param("s", $invite_code);
            if($stmt->execute()){
                $stmt->store_result();
                if($stmt->num_rows == 1){
                    $stmt->bind_result($couple_id);
                    $stmt->fetch();
                    $sql_user = "UPDATE USERS SET couple_id = ? WHERE id = ?";
                    if($stmt_user = $mysqli->prepare($sql_user)){
                        $stmt_user->bind_param("ii", $couple_id, $user_id);
                        if($stmt_user->execute()){
                            $_SESSION["couple_id"] = $couple_id;
                            header("location: ../../dashboard.php");
                        } else {
                            echo "Error updating user record.";
                        }
                        $stmt_user->close();
                    }
                } else {
                    header("location: ../../dashboard.php?error=invalid_code");
                }
            } else {
                echo "Error checking invite code.";
            }
            $stmt->close();
        }
    }
}
?>