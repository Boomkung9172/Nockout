<?php
/**
 * Home Page - Songkhla Pub & Bar Table Booking
 */
$pageTitle = "หน้าแรก จองโต๊ะผับบาร์สงขลา";
require_once __DIR__ . '/includes/header.php';

$db = getDb();

// Fetch active promotions
$promoStmt = $db->query("SELECT * FROM promotions WHERE is_active = 1 ORDER BY id ASC");
$promotions = $promoStmt->fetchAll();

// Fetch active bars in Songkhla
$barStmt = $db->query("SELECT * FROM bars WHERE is_active = 1 ORDER BY id ASC");
$bars = $barStmt->fetchAll();
?>

<!-- Hero Section: AI Promotion Slider -->
<section class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-6 pb-12">
    <!-- Ambient gold glow behind carousel -->
    <div class="ambient-glow top-0 left-1/4 -translate-x-1/2"></div>
    <div class="ambient-glow top-1/3 right-10"></div>

    <div class="slider-container border border-gold/40 relative group">
        <!-- Slide 1: Chang Beer Promo 199.- -->
        <div class="slide-item active relative h-[380px] sm:h-[480px] md:h-[520px] bg-black">
            <img src="assets/images/promotions/chang.jpg" alt="โปรโมชั่นเบียร์ช้าง 199.-" class="w-full h-full object-cover object-center opacity-85 brightness-95 transition-transform duration-700 hover:scale-105">
            <div class="absolute inset-0 bg-gradient-to-t from-night-900 via-night-900/40 to-transparent flex items-end p-6 sm:p-10 md:p-12">
                <div class="max-w-2xl">
                    <span class="inline-block px-3 py-1 bg-yellow-500 text-black text-xs font-bold rounded-full uppercase tracking-wider mb-3 shadow-lg">
                        🔥 โปรโมชั่นยอดฮิต ชนแก้วสุดคุ้ม
                    </span>
                    <h2 class="text-2xl sm:text-4xl md:text-5xl font-extrabold text-white mb-2 leading-tight">
                        เบียร์ช้าง 3 ขวด + น้ำแข็งถังใหญ่
                    </h2>
                    <p class="text-gray-300 text-sm sm:text-base mb-4 font-light drop-shadow">
                        เสิร์ฟเย็นฉ่ำทันทีเมื่อถึงโต๊ะ คุ้มที่สุดในเมืองสงขลา เพียง <span class="text-3xl font-extrabold text-yellow-400">199.-</span> เท่านั้น
                    </p>
                    <div class="flex flex-wrap items-center gap-3 sm:gap-4">
                        <a href="booking.php?promo=1" class="btn-gold px-6 py-3 rounded-xl text-sm font-semibold flex items-center gap-2 shadow-xl">
                            <i class="fa-solid fa-champagne-glasses"></i> จองโต๊ะพร้อมโปรนี้ (199.-)
                        </a>
                        <span class="text-xs text-amber-300 flex items-center gap-1.5 bg-black/60 px-3 py-2 rounded-lg border border-amber-500/40 backdrop-blur">
                            <i class="fa-solid fa-coins text-yellow-400"></i> ได้รับ 20 แต้มสะสม
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Slide 2: SangSom Promo 599.- -->
        <div class="slide-item relative h-[380px] sm:h-[480px] md:h-[520px] bg-black">
            <img src="assets/images/promotions/sangsom.jpg" alt="โปรโมชั่นแสงโสม 599.-" class="w-full h-full object-cover object-center opacity-85 brightness-95 transition-transform duration-700 hover:scale-105">
            <div class="absolute inset-0 bg-gradient-to-t from-night-900 via-night-900/40 to-transparent flex items-end p-6 sm:p-10 md:p-12">
                <div class="max-w-2xl">
                    <span class="inline-block px-3 py-1 bg-gradient-to-r from-amber-500 to-yellow-400 text-black text-xs font-bold rounded-full uppercase tracking-wider mb-3 shadow-lg">
                        ⭐ เซ็ตเปิดโต๊ะ VIP สุดคุ้ม
                    </span>
                    <h2 class="text-2xl sm:text-4xl md:text-5xl font-extrabold text-white mb-2 leading-tight">
                        แสงโสม 1 ลิตร + มิกเซอร์ 4 ขวด + น้ำแข็งถังใหญ่
                    </h2>
                    <p class="text-gray-300 text-sm sm:text-base mb-4 font-light drop-shadow">
                        เลือกมิกเซอร์โค้ก หรือสไปร์ทได้ 4 ขวด จัดเต็มแก๊งเพื่อนสายดื่ม เพียง <span class="text-3xl font-extrabold text-yellow-400">599.-</span> เท่านั้น
                    </p>
                    <div class="flex flex-wrap items-center gap-3 sm:gap-4">
                        <a href="booking.php?promo=2" class="btn-gold px-6 py-3 rounded-xl text-sm font-semibold flex items-center gap-2 shadow-xl">
                            <i class="fa-solid fa-champagne-glasses"></i> จองโต๊ะพร้อมโปรนี้ (599.-)
                        </a>
                        <span class="text-xs text-amber-300 flex items-center gap-1.5 bg-black/60 px-3 py-2 rounded-lg border border-amber-500/40 backdrop-blur">
                            <i class="fa-solid fa-coins text-yellow-400"></i> ได้รับ 60 แต้มสะสม
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Slider Controls -->
        <button id="prevSlide" class="absolute left-4 top-1/2 -translate-y-1/2 w-11 h-11 rounded-full bg-black/60 border border-gold/40 text-gold flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity hover:bg-gold hover:text-black">
            <i class="fa-solid fa-chevron-left"></i>
        </button>
        <button id="nextSlide" class="absolute right-4 top-1/2 -translate-y-1/2 w-11 h-11 rounded-full bg-black/60 border border-gold/40 text-gold flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity hover:bg-gold hover:text-black">
            <i class="fa-solid fa-chevron-right"></i>
        </button>

        <!-- Slider Indicators -->
        <div class="absolute bottom-4 left-1/2 -translate-x-1/2 flex gap-2 z-10">
            <button class="slider-dot w-8 h-2 rounded-full bg-gold transition-all" data-slide="0"></button>
            <button class="slider-dot w-2 h-2 rounded-full bg-gray-600 transition-all" data-slide="1"></button>
        </div>
    </div>
</section>

<!-- Important Notice & Highlight Bar -->
<section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mb-12">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <!-- Rule 1: 20:00 Cutoff -->
        <div class="glass-card p-5 border-l-4 border-l-red-500 flex items-start gap-4">
            <div class="w-12 h-12 rounded-xl bg-red-950/60 border border-red-500/40 flex items-center justify-center shrink-0 text-red-400 text-xl">
                <i class="fa-solid fa-clock"></i>
            </div>
            <div>
                <h3 class="font-bold text-white text-base mb-1">จองได้ไม่เกิน 20:00 น.</h3>
                <p class="text-xs text-gray-400 leading-relaxed">
                    ระบบจะทำการตัดสิทธิ์และยกเลิกโต๊ะอัตโนมัติหากลูกค้าไม่ได้เดินทางมาเช็คอินภายใน 20.00 น.
                </p>
            </div>
        </div>

        <!-- Rule 2: Appointment Time & Packages -->
        <div class="glass-card p-5 border-l-4 border-l-gold flex items-start gap-4">
            <div class="w-12 h-12 rounded-xl bg-yellow-950/60 border border-gold/40 flex items-center justify-center shrink-0 text-gold text-xl">
                <i class="fa-solid fa-wine-glass"></i>
            </div>
            <div>
                <h3 class="font-bold text-white text-base mb-1">นัดเวลาก่อน & เลือกโปรเปิดโต๊ะ</h3>
                <p class="text-xs text-gray-400 leading-relaxed">
                    สามารถเลือกระบุเวลานัดหมายล่วงหน้า และเลือกสั่งโปรโมชั่นเปิดโต๊ะพร้อมชำระเงินมัดจำได้ทันที
                </p>
            </div>
        </div>

        <!-- Feature 3: Points & PromptPay -->
        <div class="glass-card p-5 border-l-4 border-l-yellow-400 flex items-start gap-4">
            <div class="w-12 h-12 rounded-xl bg-yellow-900/40 border border-yellow-400/40 flex items-center justify-center shrink-0 text-yellow-300 text-xl">
                <i class="fa-solid fa-qrcode"></i>
            </div>
            <div>
                <h3 class="font-bold text-white text-base mb-1">สแกน PromptPay & สะสมแต้ม</h3>
                <p class="text-xs text-gray-400 leading-relaxed">
                    ชำระเงินสะดวกรวดเร็วผ่าน PromptPay QR Code พร้อมระบบสะสมแต้มพรีเมียมแลกของรางวัลมากมาย
                </p>
            </div>
        </div>
    </div>
</section>

<!-- Pubs & Bars Selection Grid (กรอบสี่เหลี่ยม เลือกร้านที่จะจองโต๊ะ ในเมืองสงขลา) -->
<section id="bars" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mb-16">
    <div class="flex flex-col md:flex-row md:items-end justify-between mb-8">
        <div>
            <div class="inline-flex items-center gap-2 text-xs font-semibold text-gold uppercase tracking-widest mb-2">
                <i class="fa-solid fa-location-crosshairs"></i> เมืองสงขลา (Songkhla Nightlife)
            </div>
            <h2 class="text-3xl font-extrabold text-white">
                เลือกร้านที่จะจองโต๊ะ <span class="text-gold-gradient">ผับ & บาร์</span>
            </h2>
            <p class="text-gray-400 text-sm mt-1">
                คลิกเลือกผับหรือบาร์ที่คุณต้องการ เพื่อเข้าไปดูผังโต๊ะว่างและทำการสำรองโต๊ะ
            </p>
        </div>
        <div class="mt-4 md:mt-0 text-xs text-gray-400 flex items-center gap-2">
            <span class="w-2.5 h-2.5 rounded-full bg-green-400 animate-ping"></span>
            <span>เปิดรับจองทุกวัน 17:00 - 20:00 น.</span>
        </div>
    </div>

    <!-- Square Grid Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
        <?php foreach ($bars as $bar): ?>
            <div class="glass-card group overflow-hidden rounded-2xl border border-gold/30 hover:border-gold transition-all duration-300 flex flex-col">
                <!-- Image Container (Square / Aspect 16:9) -->
                <div class="relative h-64 sm:h-72 overflow-hidden bg-black">
                    <img src="<?php echo htmlspecialchars($bar['image']); ?>" 
                         alt="<?php echo htmlspecialchars($bar['name']); ?>" 
                         class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                    <div class="absolute inset-0 bg-gradient-to-t from-night-900 via-transparent to-transparent"></div>
                    
                    <!-- City Badge -->
                    <span class="absolute top-4 left-4 bg-black/80 backdrop-blur border border-gold/40 text-gold text-xs font-semibold px-3 py-1 rounded-full flex items-center gap-1.5">
                        <i class="fa-solid fa-map-pin text-red-400"></i> <?php echo htmlspecialchars($bar['city']); ?>
                    </span>

                    <!-- Status Badge -->
                    <span class="absolute top-4 right-4 bg-green-950/90 border border-green-500/60 text-green-300 text-xs font-semibold px-3 py-1 rounded-full flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-green-400"></span> เปิดรับจอง
                    </span>

                    <!-- Cutoff Pill -->
                    <div class="absolute bottom-4 left-4 right-4 flex items-center justify-between text-xs text-gray-300">
                        <span class="bg-black/80 backdrop-blur px-2.5 py-1 rounded-lg border border-gray-800">
                            <i class="fa-regular fa-clock text-gold mr-1"></i> เวลาปิดรับจอง: <?php echo substr($bar['cutoff_time'], 0, 5); ?> น.
                        </span>
                        <span class="bg-black/80 backdrop-blur px-2.5 py-1 rounded-lg border border-gray-800">
                            <i class="fa-solid fa-moon text-yellow-400 mr-1"></i> ปิด: <?php echo substr($bar['close_time'], 0, 5); ?> น.
                        </span>
                    </div>
                </div>

                <!-- Content Body -->
                <div class="p-6 flex-1 flex flex-col justify-between">
                    <div>
                        <h3 class="text-2xl font-bold text-white group-hover:text-gold transition-colors mb-2">
                            <?php echo htmlspecialchars($bar['name']); ?>
                        </h3>
                        <p class="text-xs text-gray-400 mb-4 line-clamp-2">
                            <?php echo htmlspecialchars($bar['description']); ?>
                        </p>
                        
                        <div class="space-y-1.5 text-xs text-gray-400 mb-6 border-t border-gray-800 pt-3">
                            <div class="flex items-center gap-2">
                                <i class="fa-solid fa-location-dot text-gold/80 w-4"></i>
                                <span><?php echo htmlspecialchars($bar['address']); ?></span>
                            </div>
                            <div class="flex items-center gap-2">
                                <i class="fa-solid fa-phone text-gold/80 w-4"></i>
                                <span><?php echo htmlspecialchars($bar['phone']); ?> | LINE: <?php echo htmlspecialchars($bar['line_id']); ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex items-center gap-3 pt-2">
                        <a href="booking.php?bar=<?php echo $bar['id']; ?>" class="flex-1 btn-gold py-3 rounded-xl text-center text-sm font-semibold flex items-center justify-center gap-2 shadow-lg">
                            <i class="fa-solid fa-chair"></i> เลือกดูผังโต๊ะ & จองทันที
                        </a>
                        <a href="contact.php?bar=<?php echo urlencode($bar['name']); ?>" class="px-4 py-3 rounded-xl border border-gold/40 text-gold hover:bg-gold/10 text-sm transition-all" title="ติดต่อร้าน">
                            <i class="fa-solid fa-comment-dots"></i>
                        </a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- Promotions Section -->
<section id="promotions" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mb-16">
    <div class="text-center max-w-2xl mx-auto mb-10">
        <div class="inline-flex items-center gap-2 text-xs font-semibold text-gold uppercase tracking-widest mb-2">
            <i class="fa-solid fa-fire text-red-400"></i> Best Offers
        </div>
        <h2 class="text-3xl font-extrabold text-white">
            โปรโมชั่นเครื่องดื่ม <span class="text-gold-gradient">เปิดโต๊ะสุดพิเศษ</span>
        </h2>
        <p class="text-gray-400 text-sm mt-2">
            สามารถเลือกโปรโมชั่นนี้ในขั้นตอนการจองโต๊ะ เพื่อให้ทางร้านจัดเตรียมไว้รอคุณที่โต๊ะได้ทันที
        </p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
        <?php foreach ($promotions as $promo): ?>
            <div class="glass-card overflow-hidden rounded-2xl border border-gold/30 hover:border-gold transition-all duration-300 flex flex-col sm:flex-row">
                <div class="sm:w-1/2 h-56 sm:h-auto relative overflow-hidden bg-black">
                    <img src="<?php echo htmlspecialchars($promo['image']); ?>" alt="<?php echo htmlspecialchars($promo['title']); ?>" class="w-full h-full object-cover hover:scale-105 transition-transform duration-500">
                    <span class="absolute top-3 left-3 bg-red-600 text-white text-[11px] font-bold px-3 py-1 rounded-full uppercase shadow">
                        <?php echo htmlspecialchars($promo['badge']); ?>
                    </span>
                </div>
                <div class="sm:w-1/2 p-6 flex flex-col justify-between">
                    <div>
                        <h3 class="text-lg font-bold text-white mb-2">
                            <?php echo htmlspecialchars($promo['title']); ?>
                        </h3>
                        <p class="text-xs text-gray-400 leading-relaxed mb-4">
                            <?php echo htmlspecialchars($promo['description']); ?>
                        </p>
                    </div>
                    <div>
                        <div class="flex items-baseline justify-between mb-4 border-t border-gray-800 pt-3">
                            <span class="text-xs text-gray-400">ราคาพิเศษเพียง</span>
                            <span class="text-2xl font-extrabold text-gold"><?php echo number_format($promo['price'], 0); ?> <span class="text-sm font-normal text-gray-300">บาท</span></span>
                        </div>
                        <a href="booking.php?promo=<?php echo $promo['id']; ?>" class="w-full btn-outline-gold py-2.5 rounded-xl text-center text-xs font-semibold block">
                            <i class="fa-solid fa-plus mr-1"></i> จองโต๊ะและรับโปรนี้
                        </a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- Loyalty Points & How It Works -->
<section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mb-16">
    <div class="bg-gradient-to-r from-yellow-950/40 via-night-800 to-yellow-950/40 border border-gold/40 rounded-3xl p-8 sm:p-12 relative overflow-hidden">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 items-center">
            <div>
                <span class="text-gold font-bold text-xs uppercase tracking-widest mb-2 inline-block">
                    👑 VIP Loyalty Program
                </span>
                <h2 class="text-2xl sm:text-3xl font-bold text-white mb-4">
                    สะสมแต้มทุกการจอง แลกรับส่วนลดและของขวัญ VIP
                </h2>
                <p class="text-gray-300 text-sm mb-6 leading-relaxed">
                    ทุกครั้งที่คุณสำรองโต๊ะและเช็คอินที่ร้าน หรือสั่งโปรโมชั่นเปิดโต๊ะ คุณจะได้รับคะแนนสะสมอัตโนมัติ 
                    สมาชิกใหม่สมัครวันนี้รับทันที <strong class="text-yellow-400">50 แต้มฟรี!</strong>
                </p>
                <div class="flex items-center gap-4">
                    <?php if (isLoggedIn()): ?>
                        <a href="profile.php" class="btn-gold px-6 py-3 rounded-xl text-sm font-semibold">
                            ตรวจสอบแต้มสะสมของฉัน
                        </a>
                    <?php else: ?>
                        <a href="register.php" class="btn-gold px-6 py-3 rounded-xl text-sm font-semibold">
                            สมัครสมาชิกเพื่อรับ 50 แต้ม
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div class="bg-night-900/80 p-5 rounded-2xl border border-gold/20 text-center">
                    <div class="text-3xl text-gold mb-2"><i class="fa-solid fa-calendar-check"></i></div>
                    <div class="text-lg font-bold text-white">จอง & เช็คอิน</div>
                    <div class="text-xs text-gray-400 mt-1">รับแต้มทันทีเมื่อถึงร้าน</div>
                </div>
                <div class="bg-night-900/80 p-5 rounded-2xl border border-gold/20 text-center">
                    <div class="text-3xl text-yellow-400 mb-2"><i class="fa-solid fa-gift"></i></div>
                    <div class="text-lg font-bold text-white">แลกส่วนลด</div>
                    <div class="text-xs text-gray-400 mt-1">ใช้แต้มลดราคาโปรโมชั่น</div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Slider JavaScript -->
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const slides = document.querySelectorAll('.slide-item');
        const dots = document.querySelectorAll('.slider-dot');
        const prevBtn = document.getElementById('prevSlide');
        const nextBtn = document.getElementById('nextSlide');
        let currentSlide = 0;
        let slideInterval;

        function showSlide(index) {
            slides.forEach((s, i) => {
                s.classList.toggle('active', i === index);
            });
            dots.forEach((d, i) => {
                if (i === index) {
                    d.classList.add('bg-gold', 'w-8');
                    d.classList.remove('bg-gray-600', 'w-2');
                } else {
                    d.classList.remove('bg-gold', 'w-8');
                    d.classList.add('bg-gray-600', 'w-2');
                }
            });
            currentSlide = index;
        }

        function nextSlide() {
            let next = (currentSlide + 1) % slides.length;
            showSlide(next);
        }

        function prevSlide() {
            let prev = (currentSlide - 1 + slides.length) % slides.length;
            showSlide(prev);
        }

        if (nextBtn && prevBtn) {
            nextBtn.addEventListener('click', () => {
                clearInterval(slideInterval);
                nextSlide();
                startAutoplay();
            });
            prevBtn.addEventListener('click', () => {
                clearInterval(slideInterval);
                prevSlide();
                startAutoplay();
            });
        }

        dots.forEach((dot, i) => {
            dot.addEventListener('click', () => {
                clearInterval(slideInterval);
                showSlide(i);
                startAutoplay();
            });
        });

        function startAutoplay() {
            slideInterval = setInterval(nextSlide, 5000);
        }

        startAutoplay();
    });
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
