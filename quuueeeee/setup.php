<?php
require_once 'config/Database.php';

$database = new Database();
$db = $database->getConnection();

// Create admin table if not exists
$admin_table = "CREATE TABLE IF NOT EXISTS admins (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

$db->exec($admin_table);

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
    echo "Admin account created successfully!<br>";
    echo "Username: admin<br>";
    echo "Password: admin123";
} catch (PDOException $e) {
    if ($e->getCode() == 23000) {
        echo "Admin account already exists!";
    } else {
        echo "Error: " . $e->getMessage();
    }
}
?>