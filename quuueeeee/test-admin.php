<?php
require_once 'config/database.php';
require_once 'models/Admin.php';

$database = new Database();
$db = $database->getConnection();
$admin = new Admin($db);

// Check if admin exists
if (!$admin->checkAdmin()) {
    echo "No admin accounts found. Please run setup.php first.";
    exit;
}

// Test the connection and admin table
$query = "SELECT * FROM admins";
$stmt = $db->prepare($query);
$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_ASSOC);

echo "<pre>";
echo "Database connection: SUCCESS\n";
echo "Admin table exists: YES\n";
echo "Number of admins: " . $stmt->rowCount() . "\n";
if ($result) {
    echo "Found admin user: " . $result['username'] . "\n";
} else {
    echo "No admin users found\n";
}
echo "</pre>";
?>