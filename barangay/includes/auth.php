<?php
/**
 * Authentication Class
 * Handles user authentication and authorization
 */

class Auth {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    /**
     * Check if user is logged in
     */
    public function isLoggedIn() {
        // Make sure session is started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }
    
    /**
     * Check if current user is admin
     */
    public function isAdmin() {
        if (!$this->isLoggedIn()) {
            return false;
        }
        return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
    }
    
    /**
     * Get current user data
     */
    public function getCurrentUser() {
        // Make sure session is started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!$this->isLoggedIn()) {
            return null;
        }
        
        $sql = "SELECT u.id, u.username, u.email, u.password, u.role, u.status, u.created_at, u.updated_at,
                       r.id as resident_id, r.first_name, r.last_name, r.middle_name, r.birth_date, r.gender, 
                       r.civil_status, r.nationality, r.contact_number, r.address, r.barangay, r.city, r.province, 
                       r.postal_code, r.emergency_contact_name, r.emergency_contact_number, r.occupation, 
                       r.monthly_income, r.created_at as resident_created_at, r.updated_at as resident_updated_at
                FROM users u 
                LEFT JOIN residents r ON u.id = r.user_id 
                WHERE u.id = ?";
        $result = $this->db->fetch($sql, [$_SESSION['user_id']]);
        return $result ? $result : null;
    }
    
    /**
     * Login user
     */
    public function login($username, $password) {
        $sql = "SELECT * FROM users WHERE (username = ? OR email = ?) AND status = 'active'";
        $user = $this->db->fetch($sql, [$username, $username]);
        
        if ($user && password_verify($password, $user['password'])) {
            // Start session if not already started
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['status'] = $user['status'];
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Logout user
     */
    public function logout() {
        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Unset all session variables
        $_SESSION = array();
        
        // Destroy the session
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        session_destroy();
    }
    
    /**
     * Check if user exists
     */
    public function userExists($username, $email) {
        $sql = "SELECT id FROM users WHERE username = ? OR email = ?";
        $existingUser = $this->db->fetch($sql, [$username, $email]);
        return $existingUser !== false;
    }
    
    /**
     * Register new resident (alternative method)
     */
    public function registerResident($userData, $residentData) {
        try {
            // Create user account
            $userSql = "INSERT INTO users (username, email, password, role, status) VALUES (?, ?, ?, 'resident', 'active')";
            $hashedPassword = password_hash($userData['password'], PASSWORD_DEFAULT);
            
            $this->db->query($userSql, [
                $userData['username'],
                $userData['email'],
                $hashedPassword
            ]);
            
            $userId = $this->db->lastInsertId();
            
            // Create resident profile
            $sql = "INSERT INTO residents (user_id, first_name, last_name, middle_name, birth_date, gender, civil_status,
                    nationality, contact_number, address, barangay, city, province, postal_code, emergency_contact_name,
                    emergency_contact_number, occupation, monthly_income)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $this->db->query($sql, [
                $userId,
                $residentData['first_name'],
                $residentData['last_name'],
                $residentData['middle_name'] ?? '',
                $residentData['birth_date'],
                $residentData['gender'],
                $residentData['civil_status'],
                $residentData['nationality'] ?? '',
                $residentData['contact_number'] ?? '',
                $residentData['address'],
                $residentData['barangay'],
                $residentData['city'],
                $residentData['province'],
                $residentData['postal_code'] ?? '',
                $residentData['emergency_contact_name'] ?? '',
                $residentData['emergency_contact_number'] ?? '',
                $residentData['occupation'] ?? '',
                $residentData['monthly_income'] ?? 0
            ]);
            
            return $userId;
            
        } catch (Exception $e) {
            throw new Exception('Registration failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Register new resident (original method - kept for compatibility)
     */
    public function residentRegistration($userData, $residentData) {
        try {
            // Check if username or email already exists
            $checkSql = "SELECT id FROM users WHERE username = ? OR email = ?";
            $existingUser = $this->db->fetch($checkSql, [$userData['username'], $userData['email']]);
            
            if ($existingUser) {
                return ['success' => false, 'message' => 'Username or email already exists.'];
            }
            
            // Create user account
            $userSql = "INSERT INTO users (username, email, password, role, status) VALUES (?, ?, ?, 'resident', 'active')";
            $hashedPassword = password_hash($userData['password'], PASSWORD_DEFAULT);
            
            $this->db->query($userSql, [
                $userData['username'],
                $userData['email'],
                $hashedPassword
            ]);
            
            $userId = $this->db->lastInsertId();
            
            // Create resident profile
            $sql = "INSERT INTO residents (user_id, first_name, last_name, middle_name, birth_date, gender, civil_status,
                    nationality, contact_number, address, barangay, city, province, postal_code, emergency_contact_name,
                    emergency_contact_number, occupation, monthly_income)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $this->db->query($sql, [
                $userId,
                $residentData['first_name'],
                $residentData['last_name'],
                $residentData['middle_name'] ?? '',
                $residentData['birth_date'],
                $residentData['gender'],
                $residentData['civil_status'],
                $residentData['nationality'] ?? '', // Added nationality
                $residentData['contact_number'] ?? '',
                $residentData['address'],
                $residentData['barangay'],
                $residentData['city'],
                $residentData['province'],
                $residentData['postal_code'] ?? '',
                $residentData['emergency_contact_name'] ?? '', // Changed from emergency_contact
                $residentData['emergency_contact_number'] ?? '',
                $residentData['occupation'] ?? '',
                $residentData['monthly_income'] ?? 0
            ]);
            
            return ['success' => true, 'message' => 'Registration successful! You can now login.'];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Registration failed: ' . $e->getMessage()];
        }
    }
    
    /**
     * Change password
     */
    public function changePassword($userId, $currentPassword, $newPassword) {
        // Get current user
        $user = $this->db->fetch("SELECT password FROM users WHERE id = ?", [$userId]);
        
        if (!$user) {
            return ['success' => false, 'message' => 'User not found.'];
        }
        
        // Verify current password
        if (!password_verify($currentPassword, $user['password'])) {
            return ['success' => false, 'message' => 'Current password is incorrect.'];
        }
        
        // Update password
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $this->db->query("UPDATE users SET password = ? WHERE id = ?", [$hashedPassword, $userId]);
        
        return ['success' => true, 'message' => 'Password changed successfully.'];
    }
    
    /**
     * Require authentication
     */
    public function requireAuth() {
        if (!$this->isLoggedIn()) {
            header('Location: ../public/index.php'); // Corrected path
            exit();
        }
    }
    
    /**
     * Require admin role
     */
    public function requireAdmin() {
        $this->requireAuth();
        
        if (!$this->isAdmin()) {
            header('Location: ../public/index.php?error=access_denied');
            exit();
        }
    }
    
    /**
     * Require resident role
     */
    public function requireResident() {
        $this->requireAuth();
        
        if ($this->isAdmin()) {
            header('Location: ../admin/dashboard.php?error=access_denied');
            exit();
        }
    }
    
    /**
     * Get user by ID
     */
    public function getUserById($userId) {
        return $this->db->fetch("SELECT * FROM users WHERE id = ?", [$userId]);
    }
    
    /**
     * Update user status
     */
    public function updateUserStatus($userId, $status) {
        return $this->db->query("UPDATE users SET status = ? WHERE id = ?", [$status, $userId]);
    }
    
    /**
     * Delete user
     */
    public function deleteUser($userId) {
        return $this->db->query("DELETE FROM users WHERE id = ?", [$userId]);
    }
    
    /**
     * Get all users
     */
    public function getAllUsers() {
        return $this->db->fetchAll("SELECT * FROM users ORDER BY created_at DESC");
    }
    
    /**
     * Get users by role
     */
    public function getUsersByRole($role) {
        return $this->db->fetchAll("SELECT * FROM users WHERE role = ? ORDER BY created_at DESC", [$role]);
    }
    
    /**
     * Get users by status
     */
    public function getUsersByStatus($status) {
        return $this->db->fetchAll("SELECT * FROM users WHERE status = ? ORDER BY created_at DESC", [$status]);
    }
    
    /**
     * Search users
     */
    public function searchUsers($search) {
        $searchTerm = "%$search%";
        return $this->db->fetchAll(
            "SELECT * FROM users WHERE username LIKE ? OR email LIKE ? ORDER BY created_at DESC",
            [$searchTerm, $searchTerm]
        );
    }
    
    /**
     * Get user statistics
     */
    public function getUserStats() {
        $stats = [];
        
        // Total users
        $result = $this->db->fetch("SELECT COUNT(*) as total FROM users");
        $stats['total'] = $result['total'];
        
        // Active users
        $result = $this->db->fetch("SELECT COUNT(*) as active FROM users WHERE status = 'active'");
        $stats['active'] = $result['active'];
        
        // Pending users
        $result = $this->db->fetch("SELECT COUNT(*) as pending FROM users WHERE status = 'pending'");
        $stats['pending'] = $result['pending'];
        
        // Inactive users
        $result = $this->db->fetch("SELECT COUNT(*) as inactive FROM users WHERE status = 'inactive'");
        $stats['inactive'] = $result['inactive'];
        
        // Admin users
        $result = $this->db->fetch("SELECT COUNT(*) as admin FROM users WHERE role = 'admin'");
        $stats['admin'] = $result['admin'];
        
        // Resident users
        $result = $this->db->fetch("SELECT COUNT(*) as resident FROM users WHERE role = 'resident'");
        $stats['resident'] = $result['resident'];
        
        return $stats;
    }
}
?>
