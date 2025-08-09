<?php
require_once '../config/database.php';

// Check if admin user exists
$result = $conn->query("SELECT id FROM users WHERE username = 'admin'");

if ($result->num_rows == 0) {
    // Create admin user with password 'admin123'
    $username = 'admin';
    $password = password_hash('admin123', PASSWORD_DEFAULT);
    $full_name = 'System Administrator';
    $role = 'admin';

    $sql = "INSERT INTO users (username, password, full_name, role) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $username, $password, $full_name, $role);

    if ($stmt->execute()) {
        echo "Admin user created successfully!\n";
        echo "Username: admin\n";
        echo "Password: admin123\n";
    } else {
        echo "Error creating admin user: " . $conn->error;
    }
} else {
    // Update admin password
    $password = password_hash('admin123', PASSWORD_DEFAULT);
    $sql = "UPDATE users SET password = ? WHERE username = 'admin'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $password);
    
    if ($stmt->execute()) {
        echo "Admin password reset successfully!\n";
        echo "Username: admin\n";
        echo "Password: admin123\n";
    } else {
        echo "Error updating admin password: " . $conn->error;
    }
}
?>