-- ToyyibPay Integration Database Updates - Compatible Version
-- Run these queries to add support for ToyyibPay payment gateway

-- First, let's check if columns exist and add them if they don't
SET @exist := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
               WHERE TABLE_SCHEMA = DATABASE() 
               AND TABLE_NAME = 'payments' 
               AND COLUMN_NAME = 'gateway');

SET @sql := IF(@exist = 0, 
               'ALTER TABLE payments ADD COLUMN gateway VARCHAR(50) NULL AFTER payment_method', 
               'SELECT "gateway column already exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @exist := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
               WHERE TABLE_SCHEMA = DATABASE() 
               AND TABLE_NAME = 'payments' 
               AND COLUMN_NAME = 'gateway_reference');

SET @sql := IF(@exist = 0, 
               'ALTER TABLE payments ADD COLUMN gateway_reference VARCHAR(100) NULL AFTER transaction_id', 
               'SELECT "gateway_reference column already exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @exist := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
               WHERE TABLE_SCHEMA = DATABASE() 
               AND TABLE_NAME = 'payments' 
               AND COLUMN_NAME = 'gateway_response');

SET @sql := IF(@exist = 0, 
               'ALTER TABLE payments ADD COLUMN gateway_response TEXT NULL AFTER notes', 
               'SELECT "gateway_response column already exists"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Update payment_method enum to include more online options
ALTER TABLE payments MODIFY payment_method ENUM('cash', 'card', 'bank_transfer', 'online', 'toyyibpay', 'fpx', 'ewallet') NOT NULL;

-- Add indexes for better performance (ignore errors if they already exist)
CREATE INDEX idx_payments_gateway ON payments(gateway);
CREATE INDEX idx_payments_gateway_reference ON payments(gateway_reference);
CREATE INDEX idx_payments_status_method ON payments(status, payment_method);

-- Create toyyibpay_transactions table for detailed tracking
DROP TABLE IF EXISTS toyyibpay_transactions;
CREATE TABLE toyyibpay_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    bill_code VARCHAR(50) NOT NULL,
    booking_id INT NOT NULL,
    payment_id INT NULL,
    amount DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'successful', 'failed', 'cancelled') DEFAULT 'pending',
    transaction_id VARCHAR(100) NULL,
    callback_received_at TIMESTAMP NULL,
    raw_callback TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_bill_code (bill_code),
    INDEX idx_booking_id (booking_id),
    INDEX idx_payment_id (payment_id),
    INDEX idx_status (status),
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
    FOREIGN KEY (payment_id) REFERENCES payments(id) ON DELETE SET NULL
);

-- Add configuration table for storing ToyyibPay settings (optional)
DROP TABLE IF EXISTS payment_gateway_config;
CREATE TABLE payment_gateway_config (
    id INT AUTO_INCREMENT PRIMARY KEY,
    gateway_name VARCHAR(50) NOT NULL,
    config_key VARCHAR(100) NOT NULL,
    config_value TEXT NULL,
    is_encrypted BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_gateway_key (gateway_name, config_key)
);

-- Insert default ToyyibPay configuration (values should be updated with actual credentials)
INSERT INTO payment_gateway_config (gateway_name, config_key, config_value, is_encrypted) VALUES 
('toyyibpay', 'base_url', 'https://dev.toyyibpay.com', FALSE),
('toyyibpay', 'secret_key', 'your_secret_key_here', TRUE),
('toyyibpay', 'category_code', 'your_category_code_here', FALSE),
('toyyibpay', 'callback_secret', 'your_callback_secret_here', TRUE),
('toyyibpay', 'environment', 'sandbox', FALSE)
ON DUPLICATE KEY UPDATE 
    config_value = VALUES(config_value),
    updated_at = CURRENT_TIMESTAMP;

-- Add payment gateway activity log
DROP TABLE IF EXISTS payment_gateway_logs;
CREATE TABLE payment_gateway_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    gateway_name VARCHAR(50) NOT NULL,
    action VARCHAR(100) NOT NULL,
    request_data TEXT NULL,
    response_data TEXT NULL,
    status ENUM('success', 'error', 'pending') NOT NULL,
    error_message TEXT NULL,
    booking_id INT NULL,
    payment_id INT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_gateway_name (gateway_name),
    INDEX idx_action (action),
    INDEX idx_status (status),
    INDEX idx_booking_id (booking_id),
    INDEX idx_created_at (created_at)
);

-- Update existing online payments to use ToyyibPay gateway
UPDATE payments 
SET gateway = 'toyyibpay' 
WHERE payment_method = 'online' AND notes LIKE '%ToyyibPay%' AND gateway IS NULL;

-- Create a view for easier payment reporting
DROP VIEW IF EXISTS payment_summary;
CREATE VIEW payment_summary AS
SELECT 
    p.id,
    p.booking_id,
    b.customer_id,
    u.full_name as customer_name,
    u.email as customer_email,
    p.amount,
    p.payment_method,
    p.gateway,
    p.status,
    p.transaction_id,
    p.gateway_reference,
    p.payment_date,
    p.created_at,
    b.event_date,
    b.total_amount as booking_total,
    b.paid_amount as booking_paid,
    (b.total_amount - b.paid_amount) as remaining_balance
FROM payments p
LEFT JOIN bookings b ON p.booking_id = b.id
LEFT JOIN users u ON b.customer_id = u.id;

-- Insert a sample customer for testing (if not exists)
INSERT IGNORE INTO users (id, email, password, role, full_name, phone, status) VALUES 
(999, 'test@customer.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'customer', 'Test Customer', '60123456789', 'active');

-- Insert a sample booking for testing (if not exists)
INSERT IGNORE INTO bookings (id, customer_id, event_date, event_time, venue_name, guest_count, total_amount, booking_status) VALUES 
(999, 999, '2024-06-15', '18:00:00', 'Test Venue', 100, 1000.00, 'confirmed');

SELECT 'ToyyibPay database updates completed successfully!' as message;