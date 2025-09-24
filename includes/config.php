<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'wedding_user');
define('DB_PASS', 'W3dd1ng_P@ss2024');
define('DB_NAME', 'wedding_management');

// Application settings
define('SITE_URL', 'https://shazwan-danial.my/wedding-management-system/');
define('SITE_NAME', 'Wedding Management System');

// Create connection
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Helper functions
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function getUserRole() {
    return $_SESSION['user_role'] ?? null;
}

function redirectTo($url) {
    header("Location: " . $url);
    exit();
}

function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function generateToken() {
    return bin2hex(random_bytes(32));
}

// Initialize visitor tracking (only for public pages)
if (!defined('ADMIN_ACCESS') && !defined('CUSTOMER_ACCESS') && !isset($_SESSION['skip_tracking'])) {
    require_once __DIR__ . '/visitor_tracker.php';
    
    try {
        $visitor_tracker = new VisitorTracker($pdo);
        
        // Get page title if available
        $page_title = null;
        if (isset($GLOBALS['page_title'])) {
            $page_title = $GLOBALS['page_title'];
        } elseif (defined('PAGE_TITLE')) {
            $page_title = PAGE_TITLE;
        }
        
        // Track the visit
        $visitor_tracker->trackVisit($page_title);
    } catch (Exception $e) {
        // Silently ignore tracking errors to not break the site
        error_log("Visitor tracking error: " . $e->getMessage());
    }
}
?>
