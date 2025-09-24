-- Visitor Tracking Database Schema
-- Add visitor tracking tables to monitor website visitors

-- Create visitor_sessions table to track each visit
CREATE TABLE visitor_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id VARCHAR(64) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent TEXT NOT NULL,
    device_type ENUM('desktop', 'mobile', 'tablet', 'bot', 'unknown') DEFAULT 'unknown',
    device_name VARCHAR(100) NULL,
    browser_name VARCHAR(50) NULL,
    browser_version VARCHAR(20) NULL,
    operating_system VARCHAR(50) NULL,
    country VARCHAR(100) NULL,
    city VARCHAR(100) NULL,
    referrer TEXT NULL,
    landing_page VARCHAR(500) NULL,
    is_bot BOOLEAN DEFAULT FALSE,
    is_mobile BOOLEAN DEFAULT FALSE,
    screen_resolution VARCHAR(20) NULL,
    language VARCHAR(10) NULL,
    timezone VARCHAR(50) NULL,
    visit_duration INT DEFAULT 0, -- in seconds
    page_views INT DEFAULT 1,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_session_id (session_id),
    INDEX idx_ip_address (ip_address),
    INDEX idx_created_at (created_at),
    INDEX idx_device_type (device_type),
    INDEX idx_is_bot (is_bot)
);

-- Create page_views table to track individual page visits
CREATE TABLE page_views (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id VARCHAR(64) NOT NULL,
    visitor_session_id INT NOT NULL,
    page_url VARCHAR(500) NOT NULL,
    page_title VARCHAR(200) NULL,
    method VARCHAR(10) DEFAULT 'GET',
    response_time INT NULL, -- in milliseconds  
    http_status INT DEFAULT 200,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (visitor_session_id) REFERENCES visitor_sessions(id) ON DELETE CASCADE,
    INDEX idx_session_id (session_id),
    INDEX idx_page_url (page_url(255)),
    INDEX idx_created_at (created_at)
);

-- Create visitor_analytics table for daily/hourly summaries
CREATE TABLE visitor_analytics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    date DATE NOT NULL,
    hour TINYINT DEFAULT NULL, -- NULL for daily summary, 0-23 for hourly
    unique_visitors INT DEFAULT 0,
    total_visits INT DEFAULT 0,
    total_page_views INT DEFAULT 0,
    bounce_rate DECIMAL(5,2) DEFAULT 0.00,
    avg_session_duration DECIMAL(8,2) DEFAULT 0.00,
    mobile_percentage DECIMAL(5,2) DEFAULT 0.00,
    bot_visits INT DEFAULT 0,
    top_pages JSON NULL,
    top_referrers JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_date_hour (date, hour),
    INDEX idx_date (date)
);

-- Create visitor_locations table for geolocation data
CREATE TABLE visitor_locations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(45) NOT NULL,
    country_code VARCHAR(2) NULL,
    country VARCHAR(100) NULL,
    region VARCHAR(100) NULL,
    city VARCHAR(100) NULL,
    latitude DECIMAL(10, 8) NULL,
    longitude DECIMAL(11, 8) NULL,
    timezone VARCHAR(50) NULL,
    isp VARCHAR(200) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_ip (ip_address),
    INDEX idx_country (country)
);

-- Insert some sample data for testing
INSERT INTO visitor_sessions (session_id, ip_address, user_agent, device_type, device_name, browser_name, operating_system, landing_page, page_views) VALUES
('test_session_1', '192.168.1.100', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36', 'desktop', 'Windows PC', 'Chrome', 'Windows 10', '/index.php', 5),
('test_session_2', '192.168.1.101', 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_0 like Mac OS X) AppleWebKit/605.1.15', 'mobile', 'iPhone', 'Safari', 'iOS 14', '/index.php', 3),
('test_session_3', '192.168.1.102', 'Mozilla/5.0 (iPad; CPU OS 14_0 like Mac OS X) AppleWebKit/605.1.15', 'tablet', 'iPad', 'Safari', 'iOS 14', '/packages.php', 2);

-- Create indexes for better performance
CREATE INDEX idx_visitor_sessions_created_date ON visitor_sessions(DATE(created_at));
CREATE INDEX idx_page_views_created_date ON page_views(DATE(created_at));
CREATE INDEX idx_visitor_sessions_device_mobile ON visitor_sessions(device_type, is_mobile);

-- Create a view for easy visitor analytics
CREATE OR REPLACE VIEW visitor_dashboard_stats AS
SELECT 
    DATE(created_at) as date,
    COUNT(DISTINCT session_id) as unique_visitors,
    COUNT(*) as total_visits,
    SUM(page_views) as total_page_views,
    AVG(page_views) as avg_pages_per_visit,
    AVG(visit_duration) as avg_duration,
    SUM(CASE WHEN is_mobile = 1 THEN 1 ELSE 0 END) as mobile_visits,
    SUM(CASE WHEN is_bot = 1 THEN 1 ELSE 0 END) as bot_visits,
    COUNT(DISTINCT ip_address) as unique_ips
FROM visitor_sessions 
WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
GROUP BY DATE(created_at)
ORDER BY date DESC;

SELECT 'Visitor tracking tables created successfully!' as message;