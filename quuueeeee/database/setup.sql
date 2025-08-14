CREATE DATABASE IF NOT EXISTS quuueeeee;
USE quuueeeee;

CREATE TABLE IF NOT EXISTS students (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_number VARCHAR(9) UNIQUE NOT NULL,
    name VARCHAR(255) NOT NULL,
    course VARCHAR(100),
    year_level VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS queue_tickets (
    id INT PRIMARY KEY AUTO_INCREMENT,
    ticket_number VARCHAR(10) NOT NULL,
    service VARCHAR(50) NOT NULL,
    student_id INT,
    status ENUM('waiting', 'processing', 'completed', 'cancelled') DEFAULT 'waiting',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id)
);