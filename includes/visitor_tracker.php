<?php

/**
 * Visitor Tracking System
 * Tracks website visitors, devices, browsers, and analytics
 */

class VisitorTracker
{
    private $pdo;
    private $sessionId;
    private $startTime;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
        $this->startTime = microtime(true);
        $this->initializeSession();
    }

    /**
     * Initialize visitor session
     */
    private function initializeSession()
    {
        if (!session_id()) {
            session_start();
        }

        if (!isset($_SESSION['visitor_session_id'])) {
            $_SESSION['visitor_session_id'] = $this->generateSessionId();
            $_SESSION['visit_start_time'] = time();
        }

        $this->sessionId = $_SESSION['visitor_session_id'];
    }

    /**
     * Generate unique session ID
     */
    private function generateSessionId()
    {
        return 'vs_' . uniqid() . '_' . time();
    }

    /**
     * Track the current page visit
     */
    public function trackVisit($pageTitle = null)
    {
        $this->updateOrCreateSession();
        $this->recordPageView($pageTitle);
    }

    /**
     * Update or create visitor session
     */
    private function updateOrCreateSession()
    {
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $ipAddress = $this->getClientIP();
        $deviceInfo = $this->parseUserAgent($userAgent);
        $locationInfo = $this->getLocationInfo($ipAddress);

        // Check if session exists
        $stmt = $this->pdo->prepare("SELECT id, page_views FROM visitor_sessions WHERE session_id = ?");
        $stmt->execute([$this->sessionId]);
        $existingSession = $stmt->fetch();

        if ($existingSession) {
            // Update existing session
            $visitDuration = time() - $_SESSION['visit_start_time'];
            $pageViews = $existingSession['page_views'] + 1;

            $stmt = $this->pdo->prepare("
                UPDATE visitor_sessions 
                SET page_views = ?, 
                    visit_duration = ?,
                    last_activity = CURRENT_TIMESTAMP
                WHERE session_id = ?
            ");
            $stmt->execute([$pageViews, $visitDuration, $this->sessionId]);
        } else {
            // Create new session
            $stmt = $this->pdo->prepare("
                INSERT INTO visitor_sessions (
                    session_id, ip_address, user_agent, device_type, device_name, 
                    browser_name, browser_version, operating_system, country, city,
                    referrer, landing_page, is_bot, is_mobile, language, timezone
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $this->sessionId,
                $ipAddress,
                $userAgent,
                $deviceInfo['device_type'],
                $deviceInfo['device_name'],
                $deviceInfo['browser_name'],
                $deviceInfo['browser_version'],
                $deviceInfo['operating_system'],
                $locationInfo['country'] ?? null,
                $locationInfo['city'] ?? null,
                $_SERVER['HTTP_REFERER'] ?? null,
                $_SERVER['REQUEST_URI'] ?? '/',
                $deviceInfo['is_bot'] ? 1 : 0,
                $deviceInfo['is_mobile'] ? 1 : 0,
                $this->getAcceptLanguage(),
                date_default_timezone_get()
            ]);
        }
    }

    /**
     * Record individual page view
     */
    private function recordPageView($pageTitle = null)
    {
        $responseTime = round((microtime(true) - $this->startTime) * 1000); // Convert to milliseconds

        $stmt = $this->pdo->prepare("
            SELECT id FROM visitor_sessions WHERE session_id = ?
        ");
        $stmt->execute([$this->sessionId]);
        $session = $stmt->fetch();

        if ($session) {
            $stmt = $this->pdo->prepare("
                INSERT INTO page_views (
                    session_id, visitor_session_id, page_url, page_title, 
                    method, response_time, http_status
                ) VALUES (?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $this->sessionId,
                $session['id'],
                $_SERVER['REQUEST_URI'] ?? '/',
                $pageTitle,
                $_SERVER['REQUEST_METHOD'] ?? 'GET',
                $responseTime,
                http_response_code() ?: 200
            ]);
        }
    }

    /**
     * Get client IP address
     */
    private function getClientIP()
    {
        $ipKeys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CF_CONNECTING_IP', 'REMOTE_ADDR'];
        
        foreach ($ipKeys as $key) {
            if (!empty($_SERVER[$key])) {
                $ips = explode(',', $_SERVER[$key]);
                $ip = trim($ips[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }

        return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }

    /**
     * Parse user agent to extract device and browser information
     */
    private function parseUserAgent($userAgent)
    {
        $info = [
            'device_type' => 'unknown',
            'device_name' => null,
            'browser_name' => 'Unknown',
            'browser_version' => null,
            'operating_system' => 'Unknown',
            'is_mobile' => false,
            'is_bot' => false
        ];

        if (empty($userAgent)) {
            return $info;
        }

        // Check for bots
        $botPatterns = [
            'bot', 'crawl', 'spider', 'slurp', 'facebook', 'twitter', 'linkedin',
            'whatsapp', 'telegram', 'googlebot', 'bingbot', 'yandex'
        ];
        
        foreach ($botPatterns as $pattern) {
            if (stripos($userAgent, $pattern) !== false) {
                $info['is_bot'] = true;
                $info['device_type'] = 'bot';
                return $info;
            }
        }

        // Operating System Detection
        if (preg_match('/Windows NT (\d+\.\d+)/', $userAgent, $matches)) {
            $info['operating_system'] = 'Windows ' . $this->getWindowsVersion($matches[1]);
        } elseif (stripos($userAgent, 'Mac OS X') !== false) {
            $info['operating_system'] = 'macOS';
        } elseif (stripos($userAgent, 'Linux') !== false) {
            $info['operating_system'] = 'Linux';
        } elseif (stripos($userAgent, 'Android') !== false) {
            if (preg_match('/Android (\d+\.\d+)/', $userAgent, $matches)) {
                $info['operating_system'] = 'Android ' . $matches[1];
            } else {
                $info['operating_system'] = 'Android';
            }
        } elseif (stripos($userAgent, 'iOS') !== false || stripos($userAgent, 'iPhone OS') !== false) {
            $info['operating_system'] = 'iOS';
        }

        // Device Type Detection
        if (stripos($userAgent, 'Mobile') !== false || stripos($userAgent, 'Android') !== false) {
            $info['is_mobile'] = true;
            if (stripos($userAgent, 'iPad') !== false) {
                $info['device_type'] = 'tablet';
                $info['device_name'] = 'iPad';
                $info['is_mobile'] = false;
            } elseif (stripos($userAgent, 'iPhone') !== false) {
                $info['device_type'] = 'mobile';
                $info['device_name'] = 'iPhone';
            } elseif (stripos($userAgent, 'Android') !== false) {
                if (stripos($userAgent, 'Mobile') !== false) {
                    $info['device_type'] = 'mobile';
                    $info['device_name'] = 'Android Phone';
                } else {
                    $info['device_type'] = 'tablet';
                    $info['device_name'] = 'Android Tablet';
                    $info['is_mobile'] = false;
                }
            } else {
                $info['device_type'] = 'mobile';
                $info['device_name'] = 'Mobile Device';
            }
        } else {
            $info['device_type'] = 'desktop';
            $info['device_name'] = 'Desktop Computer';
        }

        // Browser Detection
        if (stripos($userAgent, 'Chrome') !== false && stripos($userAgent, 'Edg') === false) {
            $info['browser_name'] = 'Chrome';
            if (preg_match('/Chrome\/(\d+\.\d+)/', $userAgent, $matches)) {
                $info['browser_version'] = $matches[1];
            }
        } elseif (stripos($userAgent, 'Safari') !== false && stripos($userAgent, 'Chrome') === false) {
            $info['browser_name'] = 'Safari';
            if (preg_match('/Version\/(\d+\.\d+)/', $userAgent, $matches)) {
                $info['browser_version'] = $matches[1];
            }
        } elseif (stripos($userAgent, 'Firefox') !== false) {
            $info['browser_name'] = 'Firefox';
            if (preg_match('/Firefox\/(\d+\.\d+)/', $userAgent, $matches)) {
                $info['browser_version'] = $matches[1];
            }
        } elseif (stripos($userAgent, 'Edg') !== false) {
            $info['browser_name'] = 'Edge';
            if (preg_match('/Edg\/(\d+\.\d+)/', $userAgent, $matches)) {
                $info['browser_version'] = $matches[1];
            }
        } elseif (stripos($userAgent, 'Opera') !== false || stripos($userAgent, 'OPR') !== false) {
            $info['browser_name'] = 'Opera';
        }

        return $info;
    }

    /**
     * Get Windows version name from NT version
     */
    private function getWindowsVersion($ntVersion)
    {
        $versions = [
            '10.0' => '10/11',
            '6.3' => '8.1',
            '6.2' => '8',
            '6.1' => '7',
            '6.0' => 'Vista',
            '5.1' => 'XP'
        ];

        return $versions[$ntVersion] ?? $ntVersion;
    }

    /**
     * Get location information (basic implementation)
     */
    private function getLocationInfo($ipAddress)
    {
        // Check if we already have location data for this IP
        $stmt = $this->pdo->prepare("SELECT country, city FROM visitor_locations WHERE ip_address = ?");
        $stmt->execute([$ipAddress]);
        $existing = $stmt->fetch();

        if ($existing) {
            return $existing;
        }

        // For privacy and performance, we'll use a simple country detection
        // In production, you might want to integrate with a proper GeoIP service
        $location = [
            'country' => null,
            'city' => null
        ];

        // Basic country detection based on common IP ranges (very limited)
        if (strpos($ipAddress, '192.168.') === 0 || strpos($ipAddress, '10.') === 0 || strpos($ipAddress, '127.') === 0) {
            $location['country'] = 'Local Network';
        } else {
            // You can integrate with services like:
            // - ipapi.co
            // - ipstack.com  
            // - MaxMind GeoIP2
            $location['country'] = 'Unknown';
        }

        // Store the location data
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO visitor_locations (ip_address, country, city) 
                VALUES (?, ?, ?) 
                ON DUPLICATE KEY UPDATE updated_at = CURRENT_TIMESTAMP
            ");
            $stmt->execute([$ipAddress, $location['country'], $location['city']]);
        } catch (Exception $e) {
            // Ignore errors in location storage
        }

        return $location;
    }

    /**
     * Get accepted language from HTTP headers
     */
    private function getAcceptLanguage()
    {
        $language = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '';
        if ($language) {
            $languages = explode(',', $language);
            return substr(trim($languages[0]), 0, 5);
        }
        return null;
    }

    /**
     * Get visitor analytics for dashboard
     */
    public static function getAnalytics($pdo, $days = 7)
    {
        $analytics = [];

        // Today's stats
        $stmt = $pdo->query("
            SELECT 
                COUNT(DISTINCT session_id) as today_visitors,
                COUNT(*) as today_visits,
                SUM(page_views) as today_page_views,
                AVG(visit_duration) as avg_duration,
                SUM(CASE WHEN is_mobile = 1 THEN 1 ELSE 0 END) as mobile_visits,
                COUNT(DISTINCT ip_address) as unique_ips
            FROM visitor_sessions 
            WHERE DATE(created_at) = CURDATE()
        ");
        $analytics['today'] = $stmt->fetch();

        // Recent visitors (last 24 hours)
        $stmt = $pdo->query("
            SELECT 
                ip_address, 
                device_type, 
                device_name,
                browser_name, 
                operating_system, 
                country,
                page_views,
                visit_duration,
                created_at,
                last_activity
            FROM visitor_sessions 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
            ORDER BY created_at DESC 
            LIMIT 50
        ");
        $analytics['recent_visitors'] = $stmt->fetchAll();

        // Daily stats for the last N days
        $stmt = $pdo->prepare("
            SELECT 
                DATE(created_at) as date,
                COUNT(DISTINCT session_id) as unique_visitors,
                COUNT(*) as total_visits,
                SUM(page_views) as total_page_views,
                AVG(page_views) as avg_pages_per_visit,
                SUM(CASE WHEN is_mobile = 1 THEN 1 ELSE 0 END) as mobile_visits,
                SUM(CASE WHEN is_bot = 1 THEN 1 ELSE 0 END) as bot_visits
            FROM visitor_sessions 
            WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
            GROUP BY DATE(created_at)
            ORDER BY date DESC
        ");
        $stmt->execute([$days]);
        $analytics['daily_stats'] = $stmt->fetchAll();

        // Device breakdown
        $stmt = $pdo->query("
            SELECT 
                device_type,
                COUNT(*) as count,
                COUNT(DISTINCT ip_address) as unique_count
            FROM visitor_sessions 
            WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
            GROUP BY device_type
            ORDER BY count DESC
        ");
        $analytics['device_breakdown'] = $stmt->fetchAll();

        // Browser breakdown
        $stmt = $pdo->query("
            SELECT 
                browser_name,
                COUNT(*) as count
            FROM visitor_sessions 
            WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
            AND is_bot = 0
            GROUP BY browser_name
            ORDER BY count DESC
            LIMIT 10
        ");
        $analytics['browser_breakdown'] = $stmt->fetchAll();

        // Top pages
        $stmt = $pdo->query("
            SELECT 
                page_url,
                COUNT(*) as views,
                COUNT(DISTINCT pv.session_id) as unique_views
            FROM page_views pv
            WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
            GROUP BY page_url
            ORDER BY views DESC
            LIMIT 10
        ");
        $analytics['top_pages'] = $stmt->fetchAll();

        return $analytics;
    }

    /**
     * Clean old visitor data
     */
    public static function cleanOldData($pdo, $daysToKeep = 90)
    {
        try {
            $stmt = $pdo->prepare("DELETE FROM page_views WHERE created_at < DATE_SUB(CURDATE(), INTERVAL ? DAY)");
            $stmt->execute([$daysToKeep]);

            $stmt = $pdo->prepare("DELETE FROM visitor_sessions WHERE created_at < DATE_SUB(CURDATE(), INTERVAL ? DAY)");
            $stmt->execute([$daysToKeep]);

            return true;
        } catch (Exception $e) {
            error_log("Error cleaning visitor data: " . $e->getMessage());
            return false;
        }
    }
}