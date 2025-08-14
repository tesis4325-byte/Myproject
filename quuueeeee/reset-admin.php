<?php
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

// Clear existing admin accounts
$clear_query = "TRUNCATE TABLE admins";
$db->exec($clear_query);

// Create new admin account
$username = "admin";
$password = password_hash("admin123", PASSWORD_DEFAULT);
$name = "System Administrator";

$query = "INSERT INTO admins (username, password, name) VALUES (:username, :password, :name)";
$stmt = $db->prepare($query);

try {
    $stmt->execute([
        ':username' => $username,
        ':password' => $password,
        ':name' => $name
    ]);
    echo "Admin account reset successfully!<br>";
    echo "New credentials:<br>";
    echo "Username: admin<br>";
    echo "Password: admin123";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>