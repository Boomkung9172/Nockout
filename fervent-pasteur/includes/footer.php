<?php
/**
 * Footer Template - Pub & Bar Booking System
 */
?>
<footer class="mt-auto bg-[#07080b] border-t border-gold/20 pt-12 pb-8 text-gray-400 text-sm">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-8 mb-8">
            <!-- Col 1: Brand & Info -->
            <div class="space-y-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-gold/20 flex items-center justify-center border border-gold/50">
                        <i class="fa-solid fa-champagne-glasses text-gold text-lg"></i>
                    </div>
                    <span class="text-lg font-bold text-gold-gradient">SONGKHLA PUB</span>
                </div>
                <p class="text-xs text-gray-400 leading-relaxed">
                    ระบบจองโต๊ะผับบาร์ชั้นนำในอำเภอเมืองสงขลา สะดวก รวดเร็ว พร้อมโปรโมชั่นเปิดโต๊ะสุดคุ้มและระบบสะสมแต้มพรีเมียม
                </p>
                <div class="flex items-center gap-3 text-gold">
                    <a href="https://line.me" target="_blank" class="w-8 h-8 rounded-lg bg-night-800 border border-gold/30 flex items-center justify-center hover:bg-gold hover:text-black transition-all">
                        <i class="fa-brands fa-line"></i>
                    </a>
                    <a href="https://facebook.com" target="_blank" class="w-8 h-8 rounded-lg bg-night-800 border border-gold/30 flex items-center justify-center hover:bg-gold hover:text-black transition-all">
                        <i class="fa-brands fa-facebook-f"></i>
                    </a>
                    <a href="tel:074123456" class="w-8 h-8 rounded-lg bg-night-800 border border-gold/30 flex items-center justify-center hover:bg-gold hover:text-black transition-all">
                        <i class="fa-solid fa-phone"></i>
                    </a>
                </div>
            </div>

            <!-- Col 2: Pub Locations in Songkhla -->
            <div>
                <h4 class="text-gold font-semibold mb-4 text-base flex items-center gap-2">
                    <i class="fa-solid fa-location-dot"></i> ร้านแนะนำ เมืองสงขลา
                </h4>
                <ul class="space-y-2 text-xs">
                    <li>
                        <a href="booking.php?bar=1" class="hover:text-gold transition-colors flex items-center justify-between">
                            <span>Nockout Pub & Music Bar</span>
                            <span class="text-[10px] text-gray-500">ถ.ไทรบุรี</span>
                        </a>
                    </li>
                    <li>
                        <a href="booking.php?bar=2" class="hover:text-gold transition-colors flex items-center justify-between">
                            <span>Fullmoon Club Songkhla</span>
                            <span class="text-[10px] text-gray-500">หาดชลาทัศน์</span>
                        </a>
                    </li>
                    <li class="pt-2">
                        <span class="text-[11px] text-amber-400/80 bg-amber-950/40 px-2 py-1 rounded border border-amber-800/30 block">
                            ⏰ เปิดบริการ 17:00 - 02:00 น.
                        </span>
                    </li>
                </ul>
            </div>

            <!-- Col 3: Rules & Cutoff -->
            <div>
                <h4 class="text-gold font-semibold mb-4 text-base flex items-center gap-2">
                    <i class="fa-solid fa-triangle-exclamation"></i> กฎและข้อตกลง
                </h4>
                <ul class="space-y-2 text-xs text-gray-400">
                    <li class="flex items-start gap-2">
                        <span class="text-red-400 font-bold">•</span>
                        <span>จองโต๊ะและเดินทางมาเช็คอิน<strong>ก่อนเวลา 20.00 น.</strong> เท่านั้น</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="text-red-400 font-bold">•</span>
                        <span>หากไม่มาตามเวลาที่กำหนด ระบบจะตัดสิทธิ์และยกเลิกโต๊ะอัตโนมัติ</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="text-gold font-bold">•</span>
                        <span>ชำระเงินผ่าน PromptPay แนบสลิปเพื่อยืนยันโต๊ะทันที</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="text-yellow-400 font-bold">•</span>
                        <span>ทุกการจองได้รับคะแนนสะสมแลกรางวัล</span>
                    </li>
                </ul>
            </div>

            <!-- Col 4: Quick Contact & Admin -->
            <div>
                <h4 class="text-gold font-semibold mb-4 text-base flex items-center gap-2">
                    <i class="fa-solid fa-headset"></i> ศูนย์ช่วยเหลือ
                </h4>
                <p class="text-xs text-gray-400 mb-3">ต้องการเปลี่ยนโต๊ะ สอบถามโปรโมชั่น หรือจัดปาร์ตี้ส่วนตัว ติดต่อแอดมินได้ตลอด 24 ชม.</p>
                <div class="space-y-2 text-xs">
                    <div class="flex items-center gap-2 text-gray-300">
                        <i class="fa-solid fa-phone text-gold"></i> 074-123456 / 081-234-5678
                    </div>
                    <div class="flex items-center gap-2 text-gray-300">
                        <i class="fa-brands fa-line text-green-400"></i> LINE ID: @nightlife_sk
                    </div>
                </div>
                <a href="contact.php" class="mt-4 inline-block btn-outline-gold px-4 py-2 rounded-lg text-xs w-full text-center">
                    ส่งข้อความถึงแอดมิน
                </a>
            </div>
        </div>

        <div class="border-t border-gray-800/80 pt-6 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-gray-500">
            <p>© <?php echo date('Y'); ?> SONGKHLA NIGHTLIFE PUB & BAR RESERVATION. All rights reserved.</p>
            <div class="flex items-center gap-4">
                <a href="setup.php" class="text-gray-500 hover:text-gold transition-colors">⚙️ รีเซ็ตระบบ / Setup</a>
                <span>•</span>
                <span class="text-gray-400">Black & Gold Edition</span>
            </div>
        </div>
    </div>
</footer>

<script>
    // Mobile menu toggle
    const menuBtn = document.getElementById('mobileMenuBtn');
    const mobileMenu = document.getElementById('mobileMenu');
    if (menuBtn && mobileMenu) {
        menuBtn.addEventListener('click', () => {
            mobileMenu.classList.toggle('hidden');
        });
    }
</script>
</body>
</html>
