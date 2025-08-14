<?php
// Initialize the session
session_start();
 
// Check if the user is logged in, if yes then redirect him to dashboard
if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true){
    header("location: dashboard.php");
    exit;
} else {
    header("location: login.php");
    exit;
}
?>
