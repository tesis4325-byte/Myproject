<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/Admin.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    // Create admins table
    $db->exec("DROP TABLE IF EXISTS admins");
    $db->exec("CREATE TABLE IF NOT EXISTS admins (
        id INT PRIMARY KEY AUTO_INCREMENT,
        username VARCHAR(50) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100),
        role VARCHAR(20) DEFAULT 'admin',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        last_login TIMESTAMP NULL
    )");

    $admin = new Admin($db);
    
    // Create default admin account
    $admin->createAdmin('admin', 'admin123', 'Administrator');
    
    echo "Admin setup completed successfully! You can now login with:<br>";
    echo "Username: admin<br>";
    echo "Password: admin123";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>