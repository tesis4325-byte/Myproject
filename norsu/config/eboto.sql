-- Create database
CREATE DATABASE IF NOT EXISTS eboto_db;
USE eboto_db;

-- Users table
CREATE TABLE `users` (    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `voter_id` VARCHAR(50) UNIQUE,
    `firstname` VARCHAR(50) NOT NULL,
    `lastname` VARCHAR(50) NOT NULL,
    `email` VARCHAR(100) UNIQUE NOT NULL,
    `phone` VARCHAR(20),
    `dob` DATE,
    `profile_photo` VARCHAR(255),
    `password` VARCHAR(255) NOT NULL,
    `role` ENUM('admin', 'staff', 'voter') NOT NULL DEFAULT 'voter',
    `status` ENUM('pending', 'active', 'rejected', 'blocked') NOT NULL DEFAULT 'pending',
    `age_group` VARCHAR(20),
    `gender` ENUM('male', 'female', 'other'),
    `location` VARCHAR(100),
    `last_login` DATETIME,
    `login_attempts` INT DEFAULT 0,
    `last_failed_login` DATETIME,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Elections table
CREATE TABLE `elections` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `title` VARCHAR(100) NOT NULL,
    `description` TEXT,
    `start_date` DATETIME NOT NULL,
    `end_date` DATETIME NOT NULL,
    `status` ENUM('upcoming', 'ongoing', 'completed', 'cancelled') DEFAULT 'upcoming',
    `created_by` INT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`)
);

-- Positions table
CREATE TABLE `positions` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `election_id` INT NOT NULL,
    `position_name` VARCHAR(100) NOT NULL,
    `description` TEXT,
    `max_votes` INT DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`election_id`) REFERENCES `elections`(`id`) ON DELETE CASCADE
);

-- Candidates table
CREATE TABLE `candidates` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `position_id` INT NOT NULL,
    `firstname` VARCHAR(50) NOT NULL,
    `lastname` VARCHAR(50) NOT NULL,
    `photo` VARCHAR(255),
    `bio` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`position_id`) REFERENCES `positions`(`id`) ON DELETE CASCADE
);

-- Votes table
CREATE TABLE `votes` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `election_id` INT NOT NULL,
    `position_id` INT NOT NULL,
    `voter_id` INT NOT NULL,
    `candidate_id` INT NOT NULL,
    `device_type` VARCHAR(50),
    `ip_address` VARCHAR(45),
    `user_agent` TEXT,
    `voted_from_location` VARCHAR(100),
    `voted_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`election_id`) REFERENCES `elections`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`position_id`) REFERENCES `positions`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`voter_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`candidate_id`) REFERENCES `candidates`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_vote` (`election_id`, `position_id`, `voter_id`)
);

-- Vote Verification table
CREATE TABLE `vote_verification` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `vote_id` INT NOT NULL,
    `voter_id` INT NOT NULL,
    `verification_hash` VARCHAR(255) NOT NULL,
    `verified` BOOLEAN DEFAULT FALSE,
    `verification_time` DATETIME,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`vote_id`) REFERENCES `votes`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`voter_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    UNIQUE KEY (`vote_id`, `voter_id`)
);

-- Security Logs table
CREATE TABLE `security_logs` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `user_id` INT,
    `election_id` INT,
    `action_type` VARCHAR(50) NOT NULL,
    `ip_address` VARCHAR(45),
    `user_agent` TEXT,
    `details` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`election_id`) REFERENCES `elections`(`id`) ON DELETE SET NULL
);

-- IP Blacklist table
CREATE TABLE `ip_blacklist` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `ip_address` VARCHAR(45) NOT NULL,
    `reason` TEXT,
    `failed_attempts` INT DEFAULT 0,
    `last_attempt` DATETIME,
    `blocked_until` DATETIME,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY (`ip_address`)
);

-- Audit Logs table
CREATE TABLE `audit_logs` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `user_id` INT,
    `election_id` INT,
    `action` VARCHAR(255) NOT NULL,
    `details` TEXT,
    `ip_address` VARCHAR(45),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`election_id`) REFERENCES `elections`(`id`) ON DELETE SET NULL
);

-- Insert default admin user
INSERT INTO `users` (`voter_id`, `firstname`, `lastname`, `email`, `password`, `role`, `status`) 
VALUES ('ADMIN001', 'System', 'Administrator', 'admin@eboto.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'active');

-- Create indexes for better performance
CREATE INDEX idx_election_status ON elections(status);
CREATE INDEX idx_user_role_status ON users(role, status);
CREATE INDEX idx_votes_election ON votes(election_id);
CREATE INDEX idx_votes_position ON votes(position_id);
CREATE INDEX idx_votes_timestamp ON votes(voted_at);
CREATE INDEX idx_security_logs_timestamp ON security_logs(created_at);
CREATE INDEX idx_audit_logs_timestamp ON audit_logs(created_at);

-- Create triggers for audit logging
DELIMITER //

CREATE TRIGGER after_vote_insert
AFTER INSERT ON votes
FOR EACH ROW
BEGIN
    INSERT INTO audit_logs (user_id, election_id, action, details)
    VALUES (NEW.voter_id, NEW.election_id, 'VOTE_CAST', CONCAT('Vote cast for position: ', NEW.position_id));
END//

CREATE TRIGGER after_user_status_update
AFTER UPDATE ON users
FOR EACH ROW
BEGIN
    IF OLD.status != NEW.status THEN
        INSERT INTO audit_logs (user_id, action, details)
        VALUES (NEW.id, 'STATUS_CHANGE', CONCAT('Status changed from ', OLD.status, ' to ', NEW.status));
    END IF;
END//

CREATE TRIGGER after_election_status_update
AFTER UPDATE ON elections
FOR EACH ROW
BEGIN
    IF OLD.status != NEW.status THEN
        INSERT INTO audit_logs (election_id, action, details)
        VALUES (NEW.id, 'ELECTION_STATUS_CHANGE', CONCAT('Status changed from ', OLD.status, ' to ', NEW.status));
    END IF;
END//
ALTER TABLE users ADD COLUMN phone VARCHAR(20) AFTER email; ALTER TABLE users ADD COLUMN dob DATE AFTER phone;
DELIMITER ;

-- Grant permissions
GRANT SELECT, INSERT, UPDATE, DELETE ON eboto_db.* TO 'eboto_user'@'localhost' IDENTIFIED BY 'secure_password';
FLUSH PRIVILEGES;
