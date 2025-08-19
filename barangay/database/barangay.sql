-- Barangay Document Request and Tracking System Database Schema
-- Created for professional barangay management

-- Create database
CREATE DATABASE IF NOT EXISTS barangay_system;
USE barangay_system;

-- Users table (for both residents and admins)
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('resident', 'admin') DEFAULT 'resident',
    status ENUM('active', 'inactive', 'pending') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Residents table (extended user information)
CREATE TABLE residents (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    middle_name VARCHAR(50),
    birth_date DATE NOT NULL,
    gender ENUM('male', 'female', 'other') NOT NULL,
    civil_status ENUM('single', 'married', 'widowed', 'divorced') NOT NULL,
    nationality VARCHAR(50),
    contact_number VARCHAR(20),
    address TEXT NOT NULL,
    barangay VARCHAR(100) NOT NULL,
    city VARCHAR(100) NOT NULL,
    province VARCHAR(100) NOT NULL,
    postal_code VARCHAR(10),
    emergency_contact_name VARCHAR(100),
    emergency_contact_number VARCHAR(20),
    occupation VARCHAR(100),
    monthly_income DECIMAL(10,2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Document types table
CREATE TABLE document_types (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    requirements TEXT,
    processing_fee DECIMAL(10,2) DEFAULT 0.00,
    processing_days INT DEFAULT 3,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Document requests table
CREATE TABLE document_requests (
    id INT PRIMARY KEY AUTO_INCREMENT,
    resident_id INT NOT NULL,
    document_type_id INT NOT NULL,
    request_number VARCHAR(20) UNIQUE NOT NULL,
    purpose TEXT NOT NULL,
    status ENUM('pending', 'processing', 'approved', 'rejected', 'released') DEFAULT 'pending',
    admin_notes TEXT,
    resident_notes TEXT,
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    processed_at TIMESTAMP NULL,
    approved_at TIMESTAMP NULL,
    released_at TIMESTAMP NULL,
    rejected_at TIMESTAMP NULL,
    processed_by INT NULL,
    approved_by INT NULL,
    released_by INT NULL,
    rejected_by INT NULL,
    document_file VARCHAR(255) NULL,
    FOREIGN KEY (resident_id) REFERENCES residents(id) ON DELETE CASCADE,
    FOREIGN KEY (document_type_id) REFERENCES document_types(id),
    FOREIGN KEY (processed_by) REFERENCES users(id),
    FOREIGN KEY (approved_by) REFERENCES users(id),
    FOREIGN KEY (released_by) REFERENCES users(id),
    FOREIGN KEY (rejected_by) REFERENCES users(id)
);

-- Document templates table
CREATE TABLE document_templates (
    id INT PRIMARY KEY AUTO_INCREMENT,
    document_type_id INT NOT NULL,
    template_name VARCHAR(100) NOT NULL,
    template_content TEXT NOT NULL,
    header_image VARCHAR(255),
    footer_text TEXT,
    signature_line VARCHAR(100),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (document_type_id) REFERENCES document_types(id)
);

-- Request notes table (for tracking status changes and admin notes)
CREATE TABLE request_notes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    request_id INT NOT NULL,
    note TEXT NOT NULL,
    note_type ENUM('status_change', 'admin_note', 'resident_note') DEFAULT 'admin_note',
    created_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (request_id) REFERENCES document_requests(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- System settings table
CREATE TABLE system_settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    description TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert default document types
INSERT INTO document_types (name, description, requirements, processing_fee, processing_days) VALUES
('Barangay Clearance', 'Official clearance from the barangay for various purposes', 'Valid ID, Proof of Residency, Purpose Letter', 50.00, 3),
('Certificate of Indigency', 'Certificate for indigent residents', 'Valid ID, Proof of Income, Proof of Residency', 25.00, 2),
('Certificate of Residency', 'Proof of residence in the barangay', 'Valid ID, Proof of Residency', 30.00, 2),
('Certificate of Good Moral Character', 'Certificate attesting to good moral character', 'Valid ID, Character References, Purpose Letter', 40.00, 5),
('Business Permit', 'Permit to operate business in the barangay', 'Valid ID, Business Plan, Proof of Residency', 100.00, 7),
('Certificate of Live Birth', 'Certificate for birth registration', 'Birth Certificate, Parents ID, Proof of Residency', 35.00, 3);

-- Insert default system settings
INSERT INTO system_settings (setting_key, setting_value, description) VALUES
('barangay_name', 'Sample Barangay', 'Name of the barangay'),
('barangay_address', 'Sample Address, City, Province', 'Complete address of the barangay'),
('barangay_contact', '+63 912 345 6789', 'Contact number of the barangay'),
('barangay_email', 'barangay@example.com', 'Email address of the barangay'),
('barangay_logo', 'assets/img/barangay-logo.png', 'Path to barangay logo'),
('system_title', 'Barangay Document Request and Tracking System', 'System title'),
('max_file_size', '5242880', 'Maximum file upload size in bytes (5MB)'),
('allowed_file_types', 'jpg,jpeg,png,pdf,doc,docx', 'Allowed file types for uploads');

-- Insert default admin user (password: admin123)
INSERT INTO users (username, email, password, role, status) VALUES
('admin', 'admin@barangay.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'active');

-- Insert default document templates
INSERT INTO document_templates (document_type_id, template_name, template_content, header_image, footer_text, signature_line) VALUES
(1, 'Barangay Clearance Template', 
'<div style="text-align: center; margin-bottom: 30px;">
    <h2>BARANGAY CLEARANCE</h2>
    <p>Republic of the Philippines<br>
    Province of [PROVINCE]<br>
    City/Municipality of [CITY]<br>
    Barangay [BARANGAY]</p>
</div>

<div style="margin-bottom: 20px;">
    <p>TO WHOM IT MAY CONCERN:</p>
</div>

<div style="margin-bottom: 20px;">
    <p>This is to certify that <strong>[FULL_NAME]</strong>, of legal age, [CIVIL_STATUS], Filipino, and a resident of [ADDRESS], Barangay [BARANGAY], [CITY], [PROVINCE], is a person of good moral character and law-abiding citizen.</p>
</div>

<div style="margin-bottom: 20px;">
    <p>This certification is being issued upon the request of the above-named person for [PURPOSE].</p>
</div>

<div style="margin-bottom: 20px;">
    <p>Issued this [DATE] at Barangay [BARANGAY], [CITY], [PROVINCE], Philippines.</p>
</div>

<div style="margin-top: 50px;">
    <p style="text-align: center;">
        <strong>[SIGNATURE_LINE]</strong><br>
        Barangay Captain<br>
        Barangay [BARANGAY]
    </p>
</div>', 
'assets/img/barangay-header.png', 
'This document is valid for 6 months from the date of issuance.', 
'[SIGNATURE_LINE]'),

(2, 'Certificate of Indigency Template',
'<div style="text-align: center; margin-bottom: 30px;">
    <h2>CERTIFICATE OF INDIGENCY</h2>
    <p>Republic of the Philippines<br>
    Province of [PROVINCE]<br>
    City/Municipality of [CITY]<br>
    Barangay [BARANGAY]</p>
</div>

<div style="margin-bottom: 20px;">
    <p>TO WHOM IT MAY CONCERN:</p>
</div>

<div style="margin-bottom: 20px;">
    <p>This is to certify that <strong>[FULL_NAME]</strong>, of legal age, [CIVIL_STATUS], Filipino, and a resident of [ADDRESS], Barangay [BARANGAY], [CITY], [PROVINCE], belongs to the indigent family with a monthly income of [MONTHLY_INCOME].</p>
</div>

<div style="margin-bottom: 20px;">
    <p>This certification is being issued upon the request of the above-named person for [PURPOSE].</p>
</div>

<div style="margin-bottom: 20px;">
    <p>Issued this [DATE] at Barangay [BARANGAY], [CITY], [PROVINCE], Philippines.</p>
</div>

<div style="margin-top: 50px;">
    <p style="text-align: center;">
        <strong>[SIGNATURE_LINE]</strong><br>
        Barangay Captain<br>
        Barangay [BARANGAY]
    </p>
</div>',
'assets/img/barangay-header.png',
'This document is valid for 3 months from the date of issuance.',
'[SIGNATURE_LINE]');

-- Create indexes for better performance
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_username ON users(username);
CREATE INDEX idx_residents_user_id ON residents(user_id);
CREATE INDEX idx_document_requests_resident_id ON document_requests(resident_id);
CREATE INDEX idx_document_requests_status ON document_requests(status);
CREATE INDEX idx_document_requests_request_number ON document_requests(request_number);
CREATE INDEX idx_document_requests_submitted_at ON document_requests(submitted_at);
