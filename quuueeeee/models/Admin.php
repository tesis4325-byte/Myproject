<?php
class Admin {
    private $conn;
    private $table_name = "admins";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function verifyLogin($username, $password) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE username = :username";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":username", $username);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Debug information
        error_log("Login attempt - Username: " . $username);
        error_log("Database result: " . print_r($row, true));
        
        if($row) {
            $isValid = password_verify($password, $row['password']);
            error_log("Password verification result: " . ($isValid ? 'true' : 'false'));
            
            if($isValid) {
                return $row;
            }
        }
        return false;
    }

    // Add a method to check if admin exists
    public function checkAdmin() {
        $query = "SELECT COUNT(*) as count FROM " . $this->table_name;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['count'] > 0;
    }
}
?>