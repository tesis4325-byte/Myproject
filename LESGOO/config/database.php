<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'norsu_library');

// Create connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS);

// Create database if not exists
$sql = "CREATE DATABASE IF NOT EXISTS ".DB_NAME;
if ($conn->query($sql) === TRUE) {
    $conn->select_db(DB_NAME);
} else {
    die("Error creating database: " . $conn->error);
}

// Create required tables
$tables = [
    "CREATE TABLE IF NOT EXISTS users (
        id INT PRIMARY KEY AUTO_INCREMENT,
        username VARCHAR(50) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        full_name VARCHAR(100) NOT NULL,
        role ENUM('admin', 'librarian') NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    "CREATE TABLE IF NOT EXISTS members (
        id INT PRIMARY KEY AUTO_INCREMENT,
        member_id VARCHAR(20) UNIQUE NOT NULL,
        full_name VARCHAR(100) NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        phone VARCHAR(20),
        address TEXT,
        status ENUM('active', 'inactive') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    "CREATE TABLE IF NOT EXISTS books (
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
    )",
    "CREATE TABLE IF NOT EXISTS borrowings (
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
    )"
];

foreach ($tables as $table) {
    if ($conn->query($table) !== TRUE) {
        die("Error creating table: " . $conn->error);
    }
}
?>