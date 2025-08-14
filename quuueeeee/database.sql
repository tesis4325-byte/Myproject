-- Drop existing tables if they exist
DROP TABLE IF EXISTS queue;
DROP TABLE IF EXISTS windows;

-- Create queue table
CREATE TABLE queue (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ticket_number VARCHAR(10) NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'waiting',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create windows table
CREATE TABLE windows (
    id INT AUTO_INCREMENT PRIMARY KEY,
    window_number INT NOT NULL,
    current_ticket VARCHAR(10) NULL,
    status VARCHAR(20) DEFAULT 'active'
);

-- Insert default windows
INSERT INTO windows (window_number, status) VALUES 
(1, 'active'),
(2, 'active');