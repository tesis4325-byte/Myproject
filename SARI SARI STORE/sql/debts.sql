-- Create debts table
CREATE TABLE IF NOT EXISTS debts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    creditor_name VARCHAR(100) NOT NULL,
    items TEXT NOT NULL COMMENT 'Items purchased on credit',
    total_amount DECIMAL(10,2) NOT NULL,
    balance DECIMAL(10,2) NOT NULL,
    date_created DATETIME DEFAULT CURRENT_TIMESTAMP,
    status ENUM('active', 'paid', 'partial') DEFAULT 'active',
    date_updated DATETIME ON UPDATE CURRENT_TIMESTAMP,
    last_updated DATETIME ON UPDATE CURRENT_TIMESTAMP
);

-- Create payments table
CREATE TABLE IF NOT EXISTS debt_payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    debt_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    date_paid DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (debt_id) REFERENCES debts(id) ON DELETE CASCADE
);