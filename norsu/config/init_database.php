<?php
require_once 'database.php';

// Users table
$sql = "CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    firstname VARCHAR(50) NOT NULL,
    lastname VARCHAR(50) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(20) NOT NULL,
    voter_id VARCHAR(50) UNIQUE NOT NULL,
    dob DATE NOT NULL,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    profile_photo VARCHAR(255) DEFAULT 'default.jpg',
    role ENUM('admin', 'staff', 'voter') DEFAULT 'voter',
    status ENUM('pending', 'active', 'blocked') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql) === FALSE) {
    die("Error creating users table: " . $conn->error);
}

// Elections table
$sql = "CREATE TABLE IF NOT EXISTS elections (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(100) NOT NULL,
    description TEXT,
    start_date DATETIME NOT NULL,
    end_date DATETIME NOT NULL,
    status ENUM('upcoming', 'ongoing', 'completed', 'cancelled') DEFAULT 'upcoming',
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id)
)";

if ($conn->query($sql) === FALSE) {
    die("Error creating elections table: " . $conn->error);
}

// Positions table
$sql = "CREATE TABLE IF NOT EXISTS positions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    election_id INT,
    position_name VARCHAR(100) NOT NULL,
    max_votes INT DEFAULT 1,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (election_id) REFERENCES elections(id)
)";

if ($conn->query($sql) === FALSE) {
    die("Error creating positions table: " . $conn->error);
}

// Candidates table
$sql = "CREATE TABLE IF NOT EXISTS candidates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    position_id INT,
    firstname VARCHAR(50) NOT NULL,
    lastname VARCHAR(50) NOT NULL,
    photo VARCHAR(255) DEFAULT 'default.jpg',
    platform TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (position_id) REFERENCES positions(id)
)";

if ($conn->query($sql) === FALSE) {
    die("Error creating candidates table: " . $conn->error);
}

// Votes table
$sql = "CREATE TABLE IF NOT EXISTS votes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    election_id INT,
    position_id INT,
    voter_id INT,
    candidate_id INT,
    voted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (election_id) REFERENCES elections(id),
    FOREIGN KEY (position_id) REFERENCES positions(id),
    FOREIGN KEY (voter_id) REFERENCES users(id),
    FOREIGN KEY (candidate_id) REFERENCES candidates(id),
    UNIQUE KEY unique_vote (election_id, position_id, voter_id)
)";

if ($conn->query($sql) === FALSE) {
    die("Error creating votes table: " . $conn->error);
}

// Audit logs table
$sql = "CREATE TABLE IF NOT EXISTS audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(255) NOT NULL,
    details TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
)";

if ($conn->query($sql) === FALSE) {
    die("Error creating audit_logs table: " . $conn->error);
}

// Create default admin account
$admin_password = password_hash('admin123', PASSWORD_DEFAULT);
$sql = "INSERT INTO users (firstname, lastname, email, phone, voter_id, dob, username, password, role, status) 
        VALUES ('System', 'Administrator', 'admin@eboto.com', '1234567890', 'ADMIN001', '1990-01-01', 'admin', ?, 'admin', 'active')";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $admin_password);
$stmt->execute();

// Add analytics fields to users table
$conn->query("ALTER TABLE users 
    ADD COLUMN age_group VARCHAR(20) NULL,
    ADD COLUMN gender VARCHAR(10) NULL,
    ADD COLUMN location VARCHAR(100) NULL,
    ADD COLUMN last_login DATETIME NULL,
    ADD COLUMN login_attempts INT DEFAULT 0,
    ADD COLUMN last_failed_login DATETIME NULL
");

// Add device tracking to votes table
$conn->query("ALTER TABLE votes 
    ADD COLUMN device_type VARCHAR(50) NULL,
    ADD COLUMN ip_address VARCHAR(45) NULL,
    ADD COLUMN user_agent TEXT NULL,
    ADD COLUMN voted_from_location VARCHAR(100) NULL
");

// Create security_logs table
$conn->query("CREATE TABLE IF NOT EXISTS security_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    action_type VARCHAR(50) NOT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    details TEXT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
)");

// Create ip_blacklist table
$conn->query("CREATE TABLE IF NOT EXISTS ip_blacklist (
    id INT PRIMARY KEY AUTO_INCREMENT,
    ip_address VARCHAR(45) NOT NULL,
    reason TEXT NULL,
    failed_attempts INT DEFAULT 0,
    last_attempt DATETIME NULL,
    blocked_until DATETIME NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY (ip_address)
)");

// Create vote_verification table
$conn->query("CREATE TABLE IF NOT EXISTS vote_verification (
    id INT PRIMARY KEY AUTO_INCREMENT,
    vote_id INT NOT NULL,
    voter_id INT NOT NULL,
    verification_hash VARCHAR(255) NOT NULL,
    verified BOOLEAN DEFAULT FALSE,
    verification_time DATETIME NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (vote_id) REFERENCES votes(id),
    FOREIGN KEY (voter_id) REFERENCES users(id),
    UNIQUE KEY (vote_id, voter_id)
)");

echo "Database initialization completed successfully!";
?>
