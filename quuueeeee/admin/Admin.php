<?php
class Admin {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function createAdmin($username, $password, $name) {
        try {
            // Check if admin already exists
            $check = $this->conn->prepare("SELECT id FROM admins WHERE username = ?");
            $check->execute([$username]);
            if ($check->fetch()) {
                throw new Exception('Admin already exists');
            }

            // Hash the password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            // Insert new admin
            $stmt = $this->conn->prepare("INSERT INTO admins (username, password, name) VALUES (?, ?, ?)");
            $stmt->execute([$username, $hashedPassword, $name]);

            return true;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
}
?>