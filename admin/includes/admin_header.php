<?php
/**
 * Admin Panel Header
 */
require_once __DIR__ . '/../../config/db.php';
requireAdmin();

$adminUser = currentUser();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($adminTitle) ? htmlspecialchars($adminTitle) . " | Admin Panel" : "Admin Panel"; ?> - ผับบาร์สงขลา</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        gold: {
                            light: '#f7e096',
                            DEFAULT: '#d4af37',
                            dark: '#997819'
                        },
                        night: {
                            900: '#090a0f',
                            800: '#13151d',
                            700: '#1b1d28',
                            600: '#262938'
                        }
                    }
                }
            }
        }
    </script>
    <!-- Kanit Font -->
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom Style -->
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="bg-night-900 text-gray-200 min-h-screen flex flex-col">

<!-- Top Admin Bar -->
<header class="bg-night-800 border-b border-gold/30 sticky top-0 z-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
            <!-- Brand -->
            <div class="flex items-center gap-4">
                <a href="index.php" class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-lg bg-red-900/60 border border-gold/50 flex items-center justify-center text-gold">
                        <i class="fa-solid fa-crown text-base"></i>
                    </div>
                    <div>
                        <span class="font-bold text-base text-gold tracking-wide">ADMIN CONSOLE</span>
                        <span class="text-[10px] text-gray-400 block -mt-1">ระบบจัดการผับบาร์เมืองสงขลา</span>
                    </div>
                </a>
            </div>

            <!-- Admin Navigation -->
            <nav class="hidden md:flex items-center gap-1 text-xs font-medium">
                <a href="index.php" class="px-3 py-2 rounded-lg hover:bg-gold/10 hover:text-gold transition-all <?php echo basename($_SERVER['PHP_SELF']) === 'index.php' ? 'bg-gold/15 text-gold font-bold' : 'text-gray-300'; ?>">
                    <i class="fa-solid fa-gauge mr-1"></i> ภาพรวม
                </a>
                <a href="tables.php" class="px-3 py-2 rounded-lg hover:bg-gold/10 hover:text-gold transition-all <?php echo basename($_SERVER['PHP_SELF']) === 'tables.php' ? 'bg-gold/15 text-gold font-bold' : 'text-gray-300'; ?>">
                    <i class="fa-solid fa-chair mr-1"></i> จัดการผังโต๊ะ
                </a>
                <a href="bookings.php" class="px-3 py-2 rounded-lg hover:bg-gold/10 hover:text-gold transition-all <?php echo basename($_SERVER['PHP_SELF']) === 'bookings.php' ? 'bg-gold/15 text-gold font-bold' : 'text-gray-300'; ?>">
                    <i class="fa-solid fa-ticket mr-1"></i> จัดการการจอง
                </a>
                <a href="promotions.php" class="px-3 py-2 rounded-lg hover:bg-gold/10 hover:text-gold transition-all <?php echo basename($_SERVER['PHP_SELF']) === 'promotions.php' ? 'bg-gold/15 text-gold font-bold' : 'text-gray-300'; ?>">
                    <i class="fa-solid fa-tags mr-1"></i> จัดการโปรโมชั่น
                </a>
                <a href="contacts.php" class="px-3 py-2 rounded-lg hover:bg-gold/10 hover:text-gold transition-all <?php echo basename($_SERVER['PHP_SELF']) === 'contacts.php' ? 'bg-gold/15 text-gold font-bold' : 'text-gray-300'; ?>">
                    <i class="fa-solid fa-comments mr-1"></i> ข้อความลูกค้า
                </a>
            </nav>

            <!-- Actions & User Profile -->
            <div class="flex items-center gap-3">
                <a href="../index.php" target="_blank" class="hidden sm:flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-gold/40 text-gold hover:bg-gold/10 text-xs font-semibold">
                    <i class="fa-solid fa-arrow-up-right-from-square"></i> ไปหน้าร้าน
                </a>
                <a href="../profile.php" class="text-right hidden sm:block group hover:opacity-80 transition-opacity" title="แก้ไขโปรไฟล์และรหัสผ่าน">
                    <div class="text-xs font-bold text-white group-hover:text-gold flex items-center gap-1 justify-end">
                        <span><?php echo htmlspecialchars($adminUser['name']); ?></span>
                        <i class="fa-solid fa-pen-to-square text-[10px] text-gray-400 group-hover:text-gold"></i>
                    </div>
                    <div class="text-[10px] text-yellow-400 font-mono"><?php echo htmlspecialchars($adminUser['email']); ?></div>
                </a>
                <a href="../logout.php" class="w-8 h-8 rounded-lg bg-red-950/60 border border-red-500/50 text-red-400 flex items-center justify-center hover:bg-red-900/60 text-xs transition-all" title="ออกจากระบบ">
                    <i class="fa-solid fa-right-from-bracket"></i>
                </a>
            </div>
        </div>
    </div>
</header>
