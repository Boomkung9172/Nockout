<?php
/**
 * Setup Script - Initializes Database, Tables, and Seed Data
 * Pub & Bar Booking System - Songkhla Nightlife
 */

require_once __DIR__ . '/config/db.php';

$message = '';
$status = 'info';

if (isset($_POST['install']) || php_sapi_name() === 'cli') {
    try {
        $rootDsn = "mysql:host=" . DB_HOST . ";charset=utf8mb4";
        $pdo = new PDO($rootDsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);

        $pdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE `" . DB_NAME . "`");

        $schemaFile = __DIR__ . '/database/schema.sql';
        if (file_exists($schemaFile)) {
            $sql = file_get_contents($schemaFile);
            
            // Split queries and execute
            $pdo->exec($sql);
            
            // Update admin and user passwords with exact bcrypt hash of '1234'
            $hashedPass = password_hash('1234', PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email IN ('674295003@parichat.skru.ac.th', 'user@gmail.com')");
            $stmt->execute([$hashedPass]);

            $message = "ติดตั้งฐานข้อมูลและนำเข้าข้อมูลเริ่มต้น (Admin, User, ร้าน Nockout & Fullmoon, โปรโมชั่น AI) สำเร็จเรียบร้อย!";
            $status = "success";
        } else {
            $message = "ไม่พบไฟล์ database/schema.sql";
            $status = "error";
        }
    } catch (PDOException $e) {
        $message = "เกิดข้อผิดพลาด: " . $e->getMessage();
        $status = "error";
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Setup - Pub & Bar Booking</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Kanit', sans-serif; background: #0b0c10; color: #f3e5ab; }
        .gold-border { border: 1px solid #d4af37; }
        .gold-btn { background: linear-gradient(135deg, #d4af37, #aa820a); color: #0b0c10; font-weight: 600; }
        .gold-btn:hover { background: linear-gradient(135deg, #f5d77f, #d4af37); }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4">
    <div class="max-w-xl w-full bg-[#16181f] p-8 rounded-2xl gold-border shadow-2xl shadow-yellow-900/20 text-center">
        <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-yellow-900/30 flex items-center justify-center border border-[#d4af37]">
            <span class="text-3xl">🍸</span>
        </div>
        <h1 class="text-2xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-[#d4af37] via-[#f5d77f] to-[#aa820a] mb-2">
            ระบบจองโต๊ะผับบาร์ เมืองสงขลา
        </h1>
        <p class="text-gray-400 text-sm mb-6">เครื่องมือติดตั้งฐานข้อมูลอัตโนมัติ (Database Auto-Installer)</p>

        <?php if ($message): ?>
            <div class="p-4 mb-6 rounded-lg text-sm <?php echo $status === 'success' ? 'bg-green-950/80 border border-green-500 text-green-300' : 'bg-red-950/80 border border-red-500 text-red-300'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
            <?php if ($status === 'success'): ?>
                <div class="bg-[#0f1015] p-4 rounded-xl text-left text-xs text-gray-300 mb-6 space-y-2 border border-gray-800">
                    <p class="font-bold text-[#d4af37] text-sm mb-2">ข้อมูลบัญชีผู้ใช้สำหรับทดสอบ:</p>
                    <p>👑 <strong class="text-white">Admin:</strong> 674295003@parichat.skru.ac.th | รหัสผ่าน: <span class="text-yellow-400 font-mono">1234</span></p>
                    <p>👤 <strong class="text-white">User:</strong> user@gmail.com | รหัสผ่าน: <span class="text-yellow-400 font-mono">1234</span></p>
                </div>
                <div class="flex gap-4 justify-center">
                    <a href="index.php" class="gold-btn px-6 py-2.5 rounded-lg shadow-lg">เข้าสู่หน้าหลัก</a>
                    <a href="login.php" class="px-6 py-2.5 rounded-lg border border-[#d4af37] text-[#d4af37] hover:bg-[#d4af37]/10">เข้าสู่ระบบ</a>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="bg-[#0f1015] p-5 rounded-xl text-left text-sm text-gray-300 mb-6 space-y-3 border border-gray-800">
                <p class="font-semibold text-[#d4af37]">ระบบจะทำการสร้าง:</p>
                <ul class="list-disc list-inside space-y-1 text-xs text-gray-400">
                    <li>ฐานข้อมูล `pub_booking_db`</li>
                    <li>ตาราง users, bars, tables, promotions, bookings, points, contact_messages</li>
                    <li>บัญชี Admin (`674295003@parichat.skru.ac.th` / `1234`)</li>
                    <li>บัญชี User (`user@gmail.com` / `1234`)</li>
                    <li>ร้าน Nockout Pub & Fullmoon Club Songkhla</li>
                    <li>โปรโมชั่นเบียร์ช้าง (199.-) และแสงโสม (599.-)</li>
                </ul>
            </div>

            <form method="POST">
                <button type="submit" name="install" class="w-full gold-btn py-3 rounded-xl shadow-lg transition-all duration-300 transform hover:scale-[1.02]">
                    ⚡ เริ่มต้นติดตั้งฐานข้อมูล (Click to Install)
                </button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
