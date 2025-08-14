<?php
class SecurityUtils {
    private $conn;
    private $max_login_attempts = 5;
    private $lockout_duration = 30; // minutes
    
    public function __construct($db_connection) {
        $this->conn = $db_connection;
    }
    
    public function logSecurityEvent($user_id, $action_type, $details = null) {
        $ip = $this->getClientIP();
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        $stmt = $this->conn->prepare("
            INSERT INTO security_logs (user_id, action_type, ip_address, user_agent, details)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("issss", $user_id, $action_type, $ip, $user_agent, $details);
        $stmt->execute();
    }
    
    public function checkIPBlacklist($ip = null) {
        if (!$ip) {
            $ip = $this->getClientIP();
        }
        
        $stmt = $this->conn->prepare("
            SELECT * FROM ip_blacklist 
            WHERE ip_address = ? AND blocked_until > NOW()
        ");
        $stmt->bind_param("s", $ip);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->num_rows > 0;
    }
    
    public function recordLoginAttempt($user_id, $success) {
        $ip = $this->getClientIP();
        
        if ($success) {
            // Reset login attempts on successful login
            $stmt = $this->conn->prepare("
                UPDATE users 
                SET login_attempts = 0, 
                    last_login = NOW(), 
                    last_failed_login = NULL 
                WHERE id = ?
            ");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            
            // Log successful login
            $this->logSecurityEvent($user_id, 'login_success');
        } else {
            // Increment failed login attempts
            $stmt = $this->conn->prepare("
                UPDATE users 
                SET login_attempts = login_attempts + 1,
                    last_failed_login = NOW()
                WHERE id = ?
            ");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            
            // Check if should block IP
            $stmt = $this->conn->prepare("
                SELECT login_attempts FROM users WHERE id = ?
            ");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            
            if ($user['login_attempts'] >= $this->max_login_attempts) {
                $this->blacklistIP($ip, 'Excessive login attempts');
            }
            
            // Log failed login
            $this->logSecurityEvent($user_id, 'login_failed');
        }
    }
    
    public function blacklistIP($ip, $reason) {
        $blocked_until = date('Y-m-d H:i:s', strtotime("+{$this->lockout_duration} minutes"));
        
        $stmt = $this->conn->prepare("
            INSERT INTO ip_blacklist (ip_address, reason, failed_attempts, last_attempt, blocked_until)
            VALUES (?, ?, 1, NOW(), ?)
            ON DUPLICATE KEY UPDATE 
                failed_attempts = failed_attempts + 1,
                last_attempt = NOW(),
                blocked_until = VALUES(blocked_until)
        ");
        $stmt->bind_param("sss", $ip, $reason, $blocked_until);
        $stmt->execute();
    }
    
    public function generateVoteVerification($vote_id, $voter_id) {
        $verification_hash = hash('sha256', $vote_id . $voter_id . time() . random_bytes(32));
        
        $stmt = $this->conn->prepare("
            INSERT INTO vote_verification (vote_id, voter_id, verification_hash)
            VALUES (?, ?, ?)
        ");
        $stmt->bind_param("iis", $vote_id, $voter_id, $verification_hash);
        $stmt->execute();
        
        return $verification_hash;
    }
    
    public function verifyVote($verification_hash) {
        $stmt = $this->conn->prepare("
            UPDATE vote_verification 
            SET verified = TRUE,
                verification_time = NOW()
            WHERE verification_hash = ?
        ");
        $stmt->bind_param("s", $verification_hash);
        $stmt->execute();
        
        return $stmt->affected_rows > 0;
    }
      public function getClientIP() {
        $ip = '';
        if (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } elseif (isset($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        return $ip;
    }
}
