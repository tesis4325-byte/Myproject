-- Create database
CREATE DATABASE IF NOT EXISTS norsu_library;
USE norsu_library;

-- Create users table
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    role ENUM('admin', 'librarian') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create students table
CREATE TABLE IF NOT EXISTS students (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id VARCHAR(20) UNIQUE NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    course VARCHAR(50) NOT NULL,
    year_level VARCHAR(10) NOT NULL,
    contact VARCHAR(20) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    address TEXT,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create members table
CREATE TABLE IF NOT EXISTS members (
    id INT PRIMARY KEY AUTO_INCREMENT,
    member_id VARCHAR(20) UNIQUE NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(20),
    address TEXT,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create books table
CREATE TABLE IF NOT EXISTS books (
    id INT PRIMARY KEY AUTO_INCREMENT,
    isbn VARCHAR(13) UNIQUE NOT NULL,
    title VARCHAR(255) NOT NULL,
    author VARCHAR(100) NOT NULL,
    category VARCHAR(50) NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    available INT NOT NULL DEFAULT 1,
    location VARCHAR(50),
    status ENUM('available', 'unavailable') DEFAULT 'available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create borrowings table
CREATE TABLE IF NOT EXISTS borrowings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    book_id INT NOT NULL,
    member_id INT NOT NULL,
    borrow_date DATE NOT NULL,
    due_date DATE NOT NULL,
    return_date DATE,
    fine DECIMAL(10,2) DEFAULT 0.00,
    status ENUM('borrowed', 'returned', 'overdue') DEFAULT 'borrowed',
    FOREIGN KEY (book_id) REFERENCES books(id),
    FOREIGN KEY (member_id) REFERENCES members(id)
);

-- Create default admin user (password: admin123)
INSERT INTO users (username, password, full_name, role) 
VALUES ('admin', '$2y$10$H4EC5Xo.3ancIfLszHrtE.iEeoETmJaIwOsjoxmIQ8Tv.T5dLp9/e', 'System Administrator', 'admin');

-- Insert sample books
INSERT INTO books (isbn, title, author, category, quantity, available) VALUES
('9780141988511', 'Sapiens: A Brief History of Humankind', 'Yuval Noah Harari', 'Non-Fiction', 3, 3),
('9780451524935', '1984', 'George Orwell', 'Fiction', 2, 2),
('9780547928227', 'The Hobbit', 'J.R.R. Tolkien', 'Fiction', 2, 2),
('9780316769488', 'The Catcher in the Rye', 'J.D. Salinger', 'Fiction', 1, 1),
('9780307474278', 'The Da Vinci Code', 'Dan Brown', 'Mystery', 2, 2);

-- Insert sample members
INSERT INTO members (member_id, full_name, email, phone, address) VALUES
('2023-001', 'Juan Dela Cruz', 'juan@email.com', '09123456789', 'Mabinay, Negros Oriental'),
('2023-002', 'Maria Santos', 'maria@email.com', '09234567890', 'Mabinay, Negros Oriental'),
('2023-003', 'Pedro Reyes', 'pedro@email.com', '09345678901', 'Mabinay, Negros Oriental');

-- Insert sample students
INSERT INTO students (student_id, full_name, course, year_level, contact, email, address) VALUES
('2023-0001', 'John Smith', 'BSIT', '2nd', '09123456789', 'john@email.com', 'Mabinay, Negros Oriental'),
('2023-0002', 'Mary Johnson', 'BSEd', '3rd', '09234567890', 'mary@email.com', 'Mabinay, Negros Oriental'),
('2023-0003', 'Peter Parker', 'BSBA', '1st', '09345678901', 'peter@email.com', 'Mabinay, Negros Oriental');