<?php
// Database configuration
$db_host = 'localhost';
$db_username = 'root';
$db_password = '';
$db_name = 'sari_sari_store';

// Attempt to connect to MySQL database
$mysqli = new mysqli($db_host, $db_username, $db_password, $db_name);

// Check connection
if($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

// Set charset to utf8
$mysqli->set_charset("utf8mb4");
?>