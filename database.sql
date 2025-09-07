-- Create database
CREATE DATABASE IF NOT EXISTS wedding_management;
USE wedding_management;

-- Users table (admin, customers, vendors)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'customer', 'vendor') NOT NULL,
    full_name VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    address TEXT,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Wedding packages table
CREATE TABLE wedding_packages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    duration_hours INT DEFAULT 8,
    max_guests INT DEFAULT 100,
    features JSON,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Vendors table
CREATE TABLE vendors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    business_name VARCHAR(255) NOT NULL,
    service_type ENUM('photography', 'catering', 'decoration', 'music', 'venue', 'other') NOT NULL,
    description TEXT,
    price_range VARCHAR(100),
    portfolio_images JSON,
    rating DECIMAL(3,2) DEFAULT 0.00,
    total_reviews INT DEFAULT 0,
    status ENUM('active', 'inactive', 'pending') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Bookings table
CREATE TABLE bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    package_id INT,
    event_date DATE NOT NULL,
    event_time TIME NOT NULL,
    venue_name VARCHAR(255),
    venue_address TEXT,
    guest_count INT DEFAULT 50,
    special_requests TEXT,
    total_amount DECIMAL(10,2) NOT NULL,
    paid_amount DECIMAL(10,2) DEFAULT 0.00,
    payment_status ENUM('pending', 'partial', 'paid', 'refunded') DEFAULT 'pending',
    booking_status ENUM('pending', 'confirmed', 'cancelled', 'completed') DEFAULT 'pending',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (package_id) REFERENCES wedding_packages(id) ON DELETE SET NULL
);

-- Booking vendors (many-to-many relationship)
CREATE TABLE booking_vendors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL,
    vendor_id INT NOT NULL,
    service_type VARCHAR(100),
    agreed_price DECIMAL(10,2),
    status ENUM('pending', 'confirmed', 'cancelled') DEFAULT 'pending',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
    FOREIGN KEY (vendor_id) REFERENCES vendors(id) ON DELETE CASCADE
);

-- Payments table
CREATE TABLE payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_method ENUM('cash', 'card', 'bank_transfer', 'online') NOT NULL,
    transaction_id VARCHAR(255),
    payment_date DATE NOT NULL,
    status ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE
);

-- Schedule/Timeline table
CREATE TABLE event_schedule (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL,
    task_name VARCHAR(255) NOT NULL,
    description TEXT,
    scheduled_date DATE NOT NULL,
    scheduled_time TIME,
    assigned_vendor_id INT,
    status ENUM('pending', 'in_progress', 'completed', 'cancelled') DEFAULT 'pending',
    priority ENUM('low', 'medium', 'high') DEFAULT 'medium',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_vendor_id) REFERENCES vendors(id) ON DELETE SET NULL
);

-- Reviews table
CREATE TABLE reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL,
    vendor_id INT NOT NULL,
    customer_id INT NOT NULL,
    rating INT CHECK (rating >= 1 AND rating <= 5),
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
    FOREIGN KEY (vendor_id) REFERENCES vendors(id) ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Notifications table
CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('info', 'success', 'warning', 'error') DEFAULT 'info',
    is_read BOOLEAN DEFAULT FALSE,
    related_id INT,
    related_type VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Insert default admin user
INSERT INTO users (email, password, role, full_name, phone, status) VALUES 
('admin@wedding.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'System Administrator', '123456789', 'active');

-- Insert sample wedding packages
INSERT INTO wedding_packages (name, description, price, duration_hours, max_guests, features) VALUES 
('Basic Package', 'Simple and elegant wedding package for intimate ceremonies', 5000.00, 6, 50, '["Photography (4 hours)", "Basic decoration", "Wedding cake", "Bridal bouquet"]'),
('Premium Package', 'Complete wedding package with premium services', 12000.00, 8, 100, '["Photography & Videography (8 hours)", "Premium decoration", "3-tier wedding cake", "Bridal & bridesmaids bouquets", "DJ services", "Basic catering"]'),
('Luxury Package', 'Ultimate luxury wedding experience', 25000.00, 12, 200, '["Full day photography & videography", "Luxury decoration & lighting", "Multi-tier designer cake", "Premium floral arrangements", "Live band", "Full catering service", "Wedding coordinator", "Transportation"]');

-- Insert sample vendors
INSERT INTO users (email, password, role, full_name, phone, status) VALUES 
('photographer@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'vendor', 'John Photography Studio', '123456780', 'active'),
('caterer@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'vendor', 'Delicious Catering', '123456781', 'active'),
('decorator@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'vendor', 'Beautiful Decorations', '123456782', 'active');

INSERT INTO vendors (user_id, business_name, service_type, description, price_range, rating, total_reviews, status) VALUES 
(2, 'John Photography Studio', 'photography', 'Professional wedding photography with 10+ years experience', 'RM 2000 - RM 8000', 4.8, 45, 'active'),
(3, 'Delicious Catering', 'catering', 'Full-service catering for weddings and special events', 'RM 30 - RM 80 per person', 4.6, 32, 'active'),
(4, 'Beautiful Decorations', 'decoration', 'Creative wedding decoration and floral arrangements', 'RM 3000 - RM 15000', 4.9, 28, 'active');

-- Wedding tasks table for timeline management
CREATE TABLE wedding_tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    task_title VARCHAR(255) NOT NULL,
    description TEXT,
    due_date DATE NOT NULL,
    priority ENUM('low', 'medium', 'high') DEFAULT 'medium',
    status ENUM('pending', 'in_progress', 'completed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Wedding budget table
CREATE TABLE wedding_budgets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    total_budget DECIMAL(12,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Budget expenses table
CREATE TABLE budget_expenses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    category ENUM('venue', 'photography', 'attire', 'flowers', 'music', 'transportation', 'stationery', 'rings', 'miscellaneous') NOT NULL,
    description VARCHAR(255) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    vendor_name VARCHAR(255),
    expense_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Add missing columns to users table for profile management
ALTER TABLE users ADD COLUMN IF NOT EXISTS date_of_birth DATE NULL;
ALTER TABLE users ADD COLUMN IF NOT EXISTS gender ENUM('male', 'female', 'other') NULL;
ALTER TABLE users ADD COLUMN IF NOT EXISTS address TEXT NULL;

-- Add missing columns to vendors table for better filtering
ALTER TABLE vendors ADD COLUMN IF NOT EXISTS location VARCHAR(255) NULL;
ALTER TABLE vendors ADD COLUMN IF NOT EXISTS specialties TEXT NULL;

-- Insert sample timeline tasks
INSERT INTO wedding_tasks (customer_id, task_title, description, due_date, priority, status) VALUES 
(1, 'Book venue', 'Research and book the perfect wedding venue', '2024-06-01', 'high', 'pending'),
(1, 'Choose wedding dress', 'Visit bridal shops and select wedding dress', '2024-07-15', 'high', 'pending'),
(1, 'Send invitations', 'Design and send wedding invitations to guests', '2024-08-01', 'medium', 'pending'),
(1, 'Finalize menu', 'Meet with caterer to finalize wedding menu', '2024-08-15', 'medium', 'pending'),
(1, 'Wedding rehearsal', 'Conduct wedding ceremony rehearsal', '2024-09-20', 'high', 'pending');

-- Insert sample budget and expenses
INSERT INTO wedding_budgets (customer_id, total_budget) VALUES (1, 25000.00);

INSERT INTO budget_expenses (customer_id, category, description, amount, vendor_name, expense_date) VALUES 
(1, 'venue', 'Wedding venue booking deposit', 5000.00, 'Grand Ballroom', '2024-02-15'),
(1, 'photography', 'Wedding photography package', 3000.00, 'John Photography Studio', '2024-03-01'),
(1, 'attire', 'Wedding dress and accessories', 2500.00, 'Bridal Boutique', '2024-03-15'),
(1, 'flowers', 'Bridal bouquet and decorations', 1500.00, 'Beautiful Decorations', '2024-04-01');
