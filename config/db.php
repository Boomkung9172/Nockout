<?php
/**
 * Database Configuration & Core Functions
 * Pub & Bar Booking System - Songkhla Nightlife
 */

if (session_status() === PHP_SESSION_NONE) {
    ob_start();
    session_start();
}

// Database Credentials (Default for XAMPP / Localhost)
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'pub_booking_db');
define('SITE_NAME', 'SONGKHLA NIGHTLIFE - ผับบาร์สงขลา');
define('ADMIN_EMAIL', '674295003@parichat.skru.ac.th');

/**
 * Get PDO Database Connection
 */
function getDb() {
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    try {
        // First try connecting directly to the target database
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        return $pdo;
    } catch (PDOException $e) {
        // If database doesn't exist, try connecting to MySQL server to create it
        try {
            $rootDsn = "mysql:host=" . DB_HOST . ";charset=utf8mb4";
            $rootPdo = new PDO($rootDsn, DB_USER, DB_PASS);
            $rootPdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            
            // Reconnect to newly created DB
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);

            // Execute schema.sql if tables are empty
            $schemaFile = __DIR__ . '/../database/schema.sql';
            if (file_exists($schemaFile)) {
                $sql = file_get_contents($schemaFile);
                $pdo->exec($sql);
            }
            return $pdo;
        } catch (PDOException $ex) {
            die("<div style='background:#111;color:#d4af37;padding:30px;font-family:sans-serif;text-align:center;'>
                <h2>⚠️ ข้อผิดพลาดในการเชื่อมต่อฐานข้อมูล</h2>
                <p>กรุณาตรวจสอบว่า Apache และ MySQL (XAMPP) ทำงานอยู่</p>
                <p style='color:#ff6b6b;'>" . htmlspecialchars($ex->getMessage()) . "</p>
                <a href='setup.php' style='color:#fff;background:#d4af37;padding:10px 20px;text-decoration:none;border-radius:5px;'>ไปยังหน้าติดตั้งฐานข้อมูล</a>
            </div>");
        }
    }
}

function ensureRepliesTable($db) {
    try {
        $db->exec("CREATE TABLE IF NOT EXISTS `contact_replies` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `message_id` INT NOT NULL,
            `sender_role` ENUM('user', 'admin') NOT NULL,
            `sender_name` VARCHAR(100) NOT NULL,
            `message` TEXT NOT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX (`message_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    } catch (Exception $e) {}
}

/**
 * Authentication Helper Functions
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function isAdmin() {
    return isLoggedIn() && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php?msg=need_login');
        exit;
    }
}

function requireAdmin() {
    if (!isAdmin()) {
        header('Location: ../login.php?msg=admin_only');
        exit;
    }
}

function currentUser() {
    if (!isLoggedIn()) return null;
    $db = getDb();
    $stmt = $db->prepare("SELECT id, name, email, phone, role, points, created_at FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

/**
 * Automated 20:00 Cutoff Verification Rule
 * If current time exceeds 20:00 on the booking date and customer has not checked in,
 * mark status as 'no_show' (ระบบตัดว่าลูกค้าไม่ได้มาตามเวลา)
 */
function runCutoffCheck() {
    try {
        $db = getDb();
        $currentDate = date('Y-m-d');
        $currentTime = date('H:i:s');

        // Check bookings for today or past dates that are still pending or confirmed past 20:00:00
        $sql = "UPDATE bookings 
                SET status = 'no_show' 
                WHERE (booking_date < :curr_date 
                       OR (booking_date = :curr_date AND :curr_time > '20:00:00'))
                  AND status IN ('pending_payment', 'confirmed')";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':curr_date' => $currentDate,
            ':curr_time' => $currentTime
        ]);
        
        $affected = $stmt->rowCount();
        return $affected;
    } catch (Exception $e) {
        return 0;
    }
}

// Run cutoff check on each request to keep database real-time synchronized
runCutoffCheck();

/**
 * Utilities
 */
function formatThaiDate($dateStr) {
    if (!$dateStr) return '-';
    $months = [
        1 => 'ม.ค.', 2 => 'ก.พ.', 3 => 'มี.ค.', 4 => 'เม.ย.',
        5 => 'พ.ค.', 6 => 'มิ.ย.', 7 => 'ก.ค.', 8 => 'ส.ค.',
        9 => 'ก.ย.', 10 => 'ต.ค.', 11 => 'พ.ย.', 12 => 'ธ.ค.'
    ];
    $ts = strtotime($dateStr);
    $d = date('j', $ts);
    $m = $months[(int)date('n', $ts)];
    $y = date('Y', $ts) + 543;
    return "$d $m $y";
}

function generateBookingCode() {
    return 'SK-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4));
}
