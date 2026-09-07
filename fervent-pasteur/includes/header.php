<?php
/**
 * Header Template - Pub & Bar Booking System
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/db.php';
$user = currentUser();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) . " | " . SITE_NAME : SITE_NAME; ?></title>
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
    <!-- FontAwesome CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom Style -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-night-900 text-gray-200">

<!-- 20:00 Rule Top Warning Banner -->
<div class="bg-gradient-to-r from-red-950/80 via-black to-red-950/80 border-b border-red-800/40 text-xs py-1.5 px-4 text-center text-red-200 flex items-center justify-center gap-2">
    <i class="fa-solid fa-clock text-yellow-400"></i>
    <span><strong>กติกาการจอง:</strong> กรุณามาเช็คอินที่ร้านก่อน <strong>20:00 น.</strong> หากเกินเวลาระบบจะตัดสิทธิ์และยกเลิกโต๊ะอัตโนมัติ</span>
</div>

<!-- Main Navigation Bar -->
<header class="sticky top-0 z-50 bg-night-900/90 backdrop-blur-md border-b border-gold/20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-20">
            <!-- Logo -->
            <a href="index.php" class="flex items-center gap-3 group">
                <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-gold/30 to-yellow-900/40 flex items-center justify-center border border-gold/50 group-hover:shadow-[0_0_15px_rgba(212,175,55,0.4)] transition-all">
                    <i class="fa-solid fa-champagne-glasses text-gold text-xl"></i>
                </div>
                <div>
                    <div class="text-xl font-bold tracking-wider text-gold-gradient">SONGKHLA PUB</div>
                    <div class="text-[10px] text-gray-400 tracking-widest uppercase">Table Reservation • เมืองสงขลา</div>
                </div>
            </a>

            <!-- Desktop Nav Links -->
            <nav class="hidden md:flex items-center gap-6 text-sm font-medium">
                <a href="index.php" class="hover:text-gold transition-colors <?php echo basename($_SERVER['PHP_SELF']) === 'index.php' ? 'text-gold font-semibold' : 'text-gray-300'; ?>">
                    <i class="fa-solid fa-house mr-1.5 text-xs"></i> หน้าแรก
                </a>
                <a href="booking.php" class="hover:text-gold transition-colors <?php echo basename($_SERVER['PHP_SELF']) === 'booking.php' ? 'text-gold font-semibold' : 'text-gray-300'; ?>">
                    <i class="fa-solid fa-calendar-check mr-1.5 text-xs"></i> จองโต๊ะ
                </a>
                <a href="index.php#promotions" class="hover:text-gold transition-colors text-gray-300">
                    <i class="fa-solid fa-tags mr-1.5 text-xs"></i> โปรโมชั่น
                </a>
                <a href="contact.php" class="hover:text-gold transition-colors <?php echo basename($_SERVER['PHP_SELF']) === 'contact.php' ? 'text-gold font-semibold' : 'text-gray-300'; ?>">
                    <i class="fa-solid fa-headset mr-1.5 text-xs"></i> ติดต่อแอดมิน
                </a>
                <?php if (isLoggedIn()): ?>
                    <a href="my-bookings.php" class="hover:text-gold transition-colors <?php echo basename($_SERVER['PHP_SELF']) === 'my-bookings.php' ? 'text-gold font-semibold' : 'text-gray-300'; ?>">
                        <i class="fa-solid fa-ticket mr-1.5 text-xs"></i> โต๊ะที่จองไว้
                    </a>
                <?php endif; ?>
            </nav>

            <!-- User Auth Action Group -->
            <div class="hidden md:flex items-center gap-4">
                <?php if (isLoggedIn()): ?>
                    <!-- Loyalty Points Pill -->
                    <a href="profile.php" class="flex items-center gap-2 bg-night-800 border border-gold/40 px-3.5 py-1.5 rounded-full hover:border-gold transition-all" title="คะแนนสะสมของคุณ">
                        <i class="fa-solid fa-coins text-yellow-400 text-sm animate-pulse"></i>
                        <span class="text-xs text-gray-300">แต้ม:</span>
                        <span class="text-sm font-bold text-gold"><?php echo number_format($user['points'] ?? 0); ?></span>
                    </a>

                    <!-- User Dropdown Menu (Click-Toggle & Hover Buffer) -->
                    <div class="relative" id="userMenuContainer">
                        <button id="userDropdownBtn" type="button" class="flex items-center gap-2 text-sm text-gray-200 hover:text-gold focus:outline-none cursor-pointer py-2">
                            <div class="w-9 h-9 rounded-full bg-gold/20 border border-gold flex items-center justify-center font-bold text-gold">
                                <?php echo mb_substr($user['name'], 0, 1, 'UTF-8'); ?>
                            </div>
                            <span class="max-w-[120px] truncate font-medium"><?php echo htmlspecialchars($user['name']); ?></span>
                            <i class="fa-solid fa-chevron-down text-xs text-gray-400 transition-transform duration-200" id="userDropdownChevron"></i>
                        </button>
                        
                        <!-- Dropdown Menu Box -->
                        <div id="userDropdownMenu" class="absolute right-0 top-full mt-1 w-56 bg-night-800 border border-gold/40 rounded-2xl shadow-2xl py-2 hidden z-50 transition-all">
                            <div class="px-4 py-2.5 border-b border-gray-800">
                                <p class="text-[11px] text-gray-400">เข้าสู่ระบบในชื่อ</p>
                                <p class="text-xs font-semibold text-white truncate"><?php echo htmlspecialchars($user['email']); ?></p>
                                <?php if (isAdmin()): ?>
                                    <span class="inline-block mt-1.5 text-[10px] bg-red-900/60 border border-red-500/50 text-red-300 px-2 py-0.5 rounded font-medium">👑 ผู้ดูแลระบบ (Admin)</span>
                                <?php endif; ?>
                            </div>
                            <?php if (isAdmin()): ?>
                                <a href="admin/index.php" class="block px-4 py-2.5 text-sm text-yellow-400 hover:bg-gold/15 flex items-center gap-2.5 font-medium transition-colors">
                                    <i class="fa-solid fa-gauge-high text-xs"></i> แผงควบคุมแอดมิน
                                </a>
                            <?php endif; ?>
                            <a href="my-bookings.php" class="block px-4 py-2.5 text-sm text-gray-200 hover:bg-gold/15 hover:text-gold flex items-center gap-2.5 transition-colors">
                                <i class="fa-solid fa-ticket text-xs"></i> รายการจองของฉัน
                            </a>
                            <a href="profile.php" class="block px-4 py-2.5 text-sm text-gray-200 hover:bg-gold/15 hover:text-gold flex items-center gap-2.5 transition-colors">
                                <i class="fa-solid fa-star text-xs text-yellow-400"></i> แต้มสะสม & ข้อมูลส่วนตัว
                            </a>
                            <hr class="my-1.5 border-gray-800">
                            <a href="logout.php" class="block px-4 py-2.5 text-sm text-red-400 hover:bg-red-950/40 flex items-center gap-2.5 transition-colors">
                                <i class="fa-solid fa-right-from-bracket text-xs"></i> ออกจากระบบ
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="login.php" class="btn-outline-gold px-4 py-2 rounded-xl text-sm font-medium">
                        เข้าสู่ระบบ
                    </a>
                    <a href="register.php" class="btn-gold px-5 py-2 rounded-xl text-sm shadow-md">
                        สมัครสมาชิก
                    </a>
                <?php endif; ?>
            </div>

            <!-- Mobile Menu Toggle Button -->
            <button id="mobileMenuBtn" class="md:hidden text-gold text-2xl focus:outline-none">
                <i class="fa-solid fa-bars"></i>
            </button>
        </div>
    </div>

    <!-- Mobile Dropdown Navigation -->
    <div id="mobileMenu" class="md:hidden hidden bg-night-800 border-b border-gold/30 px-4 pt-3 pb-5 space-y-3">
        <a href="index.php" class="block py-2 text-gray-300 hover:text-gold"><i class="fa-solid fa-house mr-2"></i> หน้าแรก</a>
        <a href="booking.php" class="block py-2 text-gray-300 hover:text-gold"><i class="fa-solid fa-calendar-check mr-2"></i> จองโต๊ะ</a>
        <a href="index.php#promotions" class="block py-2 text-gray-300 hover:text-gold"><i class="fa-solid fa-tags mr-2"></i> โปรโมชั่น</a>
        <a href="contact.php" class="block py-2 text-gray-300 hover:text-gold"><i class="fa-solid fa-headset mr-2"></i> ติดต่อแอดมิน</a>
        
        <?php if (isLoggedIn()): ?>
            <a href="my-bookings.php" class="block py-2 text-gray-300 hover:text-gold"><i class="fa-solid fa-ticket mr-2"></i> โต๊ะที่จองไว้</a>
            <a href="profile.php" class="block py-2 text-yellow-400 font-semibold"><i class="fa-solid fa-coins mr-2"></i> แต้มสะสม: <?php echo number_format($user['points'] ?? 0); ?> แต้ม</a>
            <?php if (isAdmin()): ?>
                <a href="admin/index.php" class="block py-2 text-amber-400 font-semibold"><i class="fa-solid fa-gauge-high mr-2"></i> จัดการระบบแอดมิน</a>
            <?php endif; ?>
            <hr class="border-gray-700">
            <a href="logout.php" class="block py-2 text-red-400"><i class="fa-solid fa-right-from-bracket mr-2"></i> ออกจากระบบ</a>
        <?php else: ?>
            <div class="pt-2 flex flex-col gap-2">
                <a href="login.php" class="btn-outline-gold text-center py-2.5 rounded-xl text-sm">เข้าสู่ระบบ</a>
                <a href="register.php" class="btn-gold text-center py-2.5 rounded-xl text-sm">สมัครสมาชิก</a>
            </div>
        <?php endif; ?>
    </div>
</header>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const userBtn = document.getElementById('userDropdownBtn');
    const userMenu = document.getElementById('userDropdownMenu');
    const userChevron = document.getElementById('userDropdownChevron');
    const userContainer = document.getElementById('userMenuContainer');

    if (userBtn && userMenu && userContainer) {
        let isMenuLocked = false;
        let closeTimer = null;

        // 1. Click Toggle: คลิกเพื่อเปิดค้างไว้ หรือคลิกซ้ำเพื่อปิด
        userBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            if (closeTimer) clearTimeout(closeTimer);
            const isHidden = userMenu.classList.contains('hidden');
            if (isHidden) {
                userMenu.classList.remove('hidden');
                if (userChevron) userChevron.classList.add('rotate-180');
                isMenuLocked = true; // ค้างไว้เมื่อคลิก
            } else {
                userMenu.classList.add('hidden');
                if (userChevron) userChevron.classList.remove('rotate-180');
                isMenuLocked = false;
            }
        });

        // 2. Hover Buffer: เมาชี้เปิด และเมื่อเลื่อนเมาส์ออก จะหน่วงเวลาค้างไว้ 400ms ไม่หุบหายทันที
        userContainer.addEventListener('mouseenter', () => {
            if (closeTimer) clearTimeout(closeTimer);
            userMenu.classList.remove('hidden');
            if (userChevron) userChevron.classList.add('rotate-180');
        });

        userContainer.addEventListener('mouseleave', () => {
            if (!isMenuLocked) {
                closeTimer = setTimeout(() => {
                    userMenu.classList.add('hidden');
                    if (userChevron) userChevron.classList.remove('rotate-180');
                }, 450); // หน่วงเวลา 450ms ให้ผู้ใช้เลื่อนเมาส์ลงมากดได้สบายๆ ไม่เด้งหาย
            }
        });

        // 3. คลิกที่อื่นในหน้าเว็บเพื่อปิดเมนู
        document.addEventListener('click', (e) => {
            if (!userContainer.contains(e.target)) {
                userMenu.classList.add('hidden');
                if (userChevron) userChevron.classList.remove('rotate-180');
                isMenuLocked = false;
            }
        });
    }
});
</script>
