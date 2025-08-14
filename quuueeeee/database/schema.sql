-- Create the database
CREATE DATABASE IF NOT EXISTS registrar_queue;
USE registrar_queue;

-- Create students table
CREATE TABLE IF NOT EXISTS students (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_number VARCHAR(9) UNIQUE NOT NULL,
    name VARCHAR(100) NOT NULL,
    course VARCHAR(100),
    year_level VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create services table
CREATE TABLE IF NOT EXISTS services (
    id INT PRIMARY KEY AUTO_INCREMENT,
    service_name VARCHAR(100) NOT NULL,
    description TEXT,
    estimated_time INT, -- in minutes
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create tickets table
CREATE TABLE IF NOT EXISTS tickets (
    id INT PRIMARY KEY AUTO_INCREMENT,
    ticket_number VARCHAR(15) NOT NULL,
    student_id INT,
    service_id INT,
    status ENUM('waiting', 'serving', 'completed', 'cancelled') DEFAULT 'waiting',
    priority_level INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    called_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    FOREIGN KEY (student_id) REFERENCES students(id),
    FOREIGN KEY (service_id) REFERENCES services(id)
);

-- Insert default services
INSERT INTO services (service_name, description, estimated_time) VALUES 
('Transcript of Records', 'Request for Official Transcript of Records', 30),
('Enrollment', 'New Student Enrollment and Subject Registration', 45),
('Certification', 'Request for Various Academic Certifications', 20),
('Grade Verification', 'Verification of Grades and Academic Records', 15),
('ID Replacement', 'Student ID Replacement or Renewal', 25),
('Subject Adding/Dropping', 'Modification of Enrolled Subjects', 30),
('Document Request', 'Request for Official Academic Documents', 25),
('Grade Submission', 'Submission of Grade Changes or Updates', 20);

-- Create index for faster queries
CREATE INDEX idx_student_number ON students(student_number);
CREATE INDEX idx_ticket_number ON tickets(ticket_number);
CREATE INDEX idx_ticket_status ON tickets(status);