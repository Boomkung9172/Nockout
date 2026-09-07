<?php
/**
 * My Bookings Page - Pub & Bar Booking System
 * Features: E-Ticket VIP Pass, QR Code Check-in, 20:00 Live Countdown, LINE Share, Pre-cancellation
 */
require_once __DIR__ . '/config/db.php';
requireLogin();

$db = getDb();
$currentUser = currentUser();

// Handle Customer Pre-cancellation (ขอยกเลิกการจองล่วงหน้า)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_booking'])) {
    $bid = (int)$_POST['booking_id'];
    $canStmt = $db->prepare("UPDATE bookings SET status = 'cancelled' WHERE id = ? AND user_id = ? AND status IN ('pending_payment', 'confirmed')");
    $canStmt->execute([$bid, $currentUser['id']]);
    header("Location: my-bookings.php?msg=cancelled");
    exit;
}

// Trigger cutoff check
runCutoffCheck();

// Fetch user's bookings
$stmt = $db->prepare("SELECT b.*, 
                             r.name AS bar_name, r.address AS bar_address, r.phone AS bar_phone, r.line_id AS bar_line,
                             t.table_number, t.zone,
                             p.title AS promo_title
                      FROM bookings b
                      JOIN bars r ON b.bar_id = r.id
                      JOIN tables t ON b.table_id = t.id
                      LEFT JOIN promotions p ON b.promotion_id = p.id
                      WHERE b.user_id = ?
                      ORDER BY b.created_at DESC");
$stmt->execute([$currentUser['id']]);
$bookings = $stmt->fetchAll();

$msg = $_GET['msg'] ?? '';

$pageTitle = "ประวัติการจองโต๊ะ & บัตร VIP E-Ticket";
require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8">
        <div>
            <h1 class="text-3xl font-extrabold text-white">
                โต๊ะที่จองไว้ & <span class="text-gold-gradient">บัตร VIP E-Ticket</span>
            </h1>
            <p class="text-xs sm:text-sm text-gray-400 mt-1">
                ตรวจสอบสถานะโต๊ะ แสดง QR Code เช็คอินหน้าร้าน และแชร์เข้ากลุ่ม LINE แก๊งเพื่อน
            </p>
        </div>
        <a href="booking.php" class="btn-gold px-5 py-2.5 rounded-xl text-sm font-semibold flex items-center justify-center gap-2 shadow-lg">
            <i class="fa-solid fa-plus"></i> จองโต๊ะใหม่
        </a>
    </div>

    <?php if ($msg === 'paid'): ?>
        <div class="bg-green-950/80 border border-green-500/80 text-green-200 text-sm p-4 rounded-2xl mb-8 flex items-center gap-3 shadow-lg">
            <i class="fa-solid fa-circle-check text-2xl text-green-400 shrink-0"></i>
            <div>
                <p class="font-bold">ชำระเงินและแนบสลิปเรียบร้อยแล้ว!</p>
                <p class="text-xs text-green-300">ระบบได้มอบแต้มสะสมให้คุณแล้ว กรุณาเปิด QR Code บัตร VIP ด้านล่างเพื่อแสดงต่อพนักงานก่อนเวลา 20.00 น.</p>
            </div>
        </div>
    <?php elseif ($msg === 'cancelled'): ?>
        <div class="bg-yellow-950/80 border border-gold/80 text-yellow-200 text-sm p-4 rounded-2xl mb-8 flex items-center gap-3 shadow-lg">
            <i class="fa-solid fa-circle-info text-2xl text-yellow-400 shrink-0"></i>
            <div>
                <p class="font-bold">ยกเลิกการจองโต๊ะเรียบร้อยแล้ว</p>
                <p class="text-xs text-yellow-300">ระบบได้ปล่อยโต๊ะคืนเป็นสถานะว่าง เพื่อให้ผู้อื่นสามารถจองต่อได้เรียบร้อยครับ</p>
            </div>
        </div>
    <?php endif; ?>

    <?php if (empty($bookings)): ?>
        <div class="glass-card p-12 text-center rounded-3xl border border-gray-800">
            <div class="w-20 h-20 mx-auto mb-4 rounded-full bg-yellow-950/30 flex items-center justify-center border border-gold/40 text-gold text-3xl">
                <i class="fa-solid fa-calendar-xmark"></i>
            </div>
            <h3 class="text-lg font-bold text-white mb-2">คุณยังไม่มีรายการจองโต๊ะ</h3>
            <p class="text-xs text-gray-400 max-w-md mx-auto mb-6">เลือกร้านและโต๊ะที่คุณชื่นชอบในเมืองสงขลา พร้อมรับโปรโมชั่นเปิดโต๊ะสุดคุ้ม</p>
            <a href="booking.php" class="btn-gold px-6 py-3 rounded-xl text-sm font-semibold inline-flex items-center gap-2">
                <i class="fa-solid fa-chair"></i> จองโต๊ะตอนนี้เลย
            </a>
        </div>
    <?php else: ?>
        <div class="space-y-8">
            <?php foreach ($bookings as $b): ?>
                <?php
                    $isToday = ($b['booking_date'] === date('Y-m-d'));
                    $isPastCutoff = (strtotime($b['booking_date']) < strtotime(date('Y-m-d'))) || 
                                    ($isToday && strtotime(date('H:i:s')) > strtotime('20:00:00'));
                    
                    // Share text for LINE
                    $lineShareText = "🍻 คืนนี้เจอกันที่ " . $b['bar_name'] . "!\n" .
                                     "📍 โต๊ะ: " . $b['table_number'] . " (" . $b['zone'] . ")\n" .
                                     "📅 วันที่: " . date('d/m/Y', strtotime($b['booking_date'])) . "\n" .
                                     "⏰ เวลานัด: " . substr($b['arrival_time'], 0, 5) . " น.\n" .
                                     "⚠️ ย้ำ: ต้องมาถึงก่อน 20:00 น. นะพวกเรา ร้านมีกฎตัดสิทธิ์!\n" .
                                     "🎫 รหัสจอง: " . $b['booking_code'];
                    $lineShareUrl = "https://line.me/R/msg/text/?" . urlencode($lineShareText);

                    // Check-in QR Data
                    $checkinUrl = "http://" . ($_SERVER['HTTP_HOST'] ?? 'localhost') . "/fervent-pasteur/admin/bookings.php?scan_checkin=" . urlencode($b['booking_code']);
                    $qrCodeImg = "https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=" . urlencode($checkinUrl);
                ?>

                <div class="glass-card overflow-hidden rounded-3xl border transition-all duration-300 <?php echo $b['status'] === 'confirmed' ? 'border-gold shadow-xl shadow-gold/10' : 'border-gray-800'; ?>">
                    <!-- Top Bar with Status Badge & Countdown -->
                    <div class="bg-night-900/90 px-6 py-3.5 border-b border-gray-800 flex flex-wrap items-center justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <span class="text-xs text-gray-400">รหัสจอง VIP:</span>
                            <span class="font-mono font-bold text-gold text-base tracking-wider"><?php echo htmlspecialchars($b['booking_code']); ?></span>
                        </div>

                        <!-- Live Countdown Timer (สำหรับโต๊ะยืนยันวันนี้) -->
                        <?php if ($b['status'] === 'confirmed' && $isToday): ?>
                            <div class="countdown-box flex items-center gap-2 bg-red-950/60 border border-red-500/60 px-3.5 py-1 rounded-full text-xs font-bold text-red-300 shadow" 
                                 data-date="<?php echo $b['booking_date']; ?>" 
                                 data-cutoff="20:00:00">
                                <i class="fa-solid fa-hourglass-half animate-spin text-yellow-400"></i>
                                <span>นับถอยหลัง:</span>
                                <span class="countdown-timer text-yellow-400 font-mono tracking-wider">กำลังคำนวณ...</span>
                            </div>
                        <?php endif; ?>

                        <!-- Status Badge -->
                        <div>
                            <?php if ($b['status'] === 'pending_payment'): ?>
                                <span class="badge-gold px-3 py-1 rounded-full text-xs font-semibold flex items-center gap-1.5">
                                    <i class="fa-solid fa-hourglass-half text-[10px]"></i> รอชำระเงิน PromptPay
                                </span>
                            <?php elseif ($b['status'] === 'confirmed'): ?>
                                <span class="badge-green px-3.5 py-1 rounded-full text-xs font-bold flex items-center gap-1.5 shadow">
                                    <i class="fa-solid fa-circle-check text-[10px]"></i> ยืนยันแล้ว (พร้อมเช็คอิน)
                                </span>
                            <?php elseif ($b['status'] === 'checked_in'): ?>
                                <span class="bg-blue-950/90 border border-blue-500 text-blue-300 px-3.5 py-1 rounded-full text-xs font-bold flex items-center gap-1.5 shadow">
                                    <i class="fa-solid fa-champagne-glasses text-[10px]"></i> เช็คอินแล้ว (กำลังใช้บริการ)
                                </span>
                            <?php elseif ($b['status'] === 'no_show'): ?>
                                <span class="badge-red px-3 py-1 rounded-full text-xs font-semibold" title="เกิน 20.00 น.">
                                    <i class="fa-solid fa-ban text-[10px]"></i> ไม่ได้มาตามเวลา (ตัดสิทธิ์ No-Show)
                                </span>
                            <?php else: ?>
                                <span class="bg-gray-800 text-gray-400 px-3 py-1 rounded-full text-xs font-semibold">
                                    ยกเลิกแล้ว
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Main Info Body -->
                    <div class="p-6">
                        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-center">
                            <!-- Pub & Table Details (5 cols) -->
                            <div class="lg:col-span-5 space-y-2 border-b lg:border-b-0 lg:border-r border-gray-800 pb-5 lg:pb-0 lg:pr-5">
                                <div class="flex items-center gap-2">
                                    <span class="text-xs bg-gold/15 text-gold px-2.5 py-0.5 rounded-full font-bold">ผับสงขลา</span>
                                    <h3 class="text-xl font-extrabold text-white"><?php echo htmlspecialchars($b['bar_name']); ?></h3>
                                </div>
                                <p class="text-xs text-gray-400 truncate"><i class="fa-solid fa-map-pin text-gold mr-1"></i><?php echo htmlspecialchars($b['bar_address']); ?></p>
                                
                                <div class="flex items-center gap-3 pt-2">
                                    <div class="p-2.5 rounded-xl bg-gradient-to-r from-yellow-950/60 to-night-800 border border-gold/40 text-center">
                                        <div class="text-[10px] text-gray-400 uppercase">โต๊ะที่จอง</div>
                                        <div class="text-xl font-black text-gold"><?php echo htmlspecialchars($b['table_number']); ?></div>
                                    </div>
                                    <div class="p-2.5 rounded-xl bg-night-900 border border-gray-800 text-center flex-1">
                                        <div class="text-[10px] text-gray-400 uppercase">โซนที่นั่ง</div>
                                        <div class="text-xs font-bold text-white"><?php echo htmlspecialchars($b['zone']); ?></div>
                                    </div>
                                </div>
                            </div>

                            <!-- Date, Time, Cutoff Rule (3 cols) -->
                            <div class="lg:col-span-3 space-y-2.5 border-b lg:border-b-0 lg:border-r border-gray-800 pb-5 lg:pb-0 lg:pr-5 text-xs">
                                <div>
                                    <span class="text-gray-400">วันที่จอง:</span>
                                    <span class="font-bold text-white ml-2"><?php echo formatThaiDate($b['booking_date']); ?></span>
                                </div>
                                <div>
                                    <span class="text-gray-400">เวลานัดหมาย:</span>
                                    <span class="font-bold text-yellow-400 ml-2 font-mono text-sm"><?php echo substr($b['arrival_time'], 0, 5); ?> น.</span>
                                </div>
                                <div class="p-2 rounded-xl bg-red-950/30 border border-red-900/50 text-red-300 text-[11px] flex items-center gap-1.5">
                                    <i class="fa-solid fa-clock text-yellow-400 shrink-0"></i>
                                    <span>กติกา: ต้องเช็คอิน<strong>ก่อน 20.00 น.</strong></span>
                                </div>
                                <?php if (!empty($b['promo_title'])): ?>
                                    <div class="text-amber-300 text-[11px]">
                                        <i class="fa-solid fa-champagne-glasses mr-1 text-gold"></i> <?php echo htmlspecialchars($b['promo_title']); ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Payment, QR E-Ticket & Actions (4 cols) -->
                            <div class="lg:col-span-4 flex flex-col justify-between items-start lg:items-end space-y-4">
                                <div class="text-left lg:text-right">
                                    <span class="text-xs text-gray-400 block">ยอดชำระ:</span>
                                    <span class="text-2xl font-black text-gold"><?php echo number_format($b['total_amount'], 2); ?> <span class="text-xs text-gray-300 font-normal">บาท</span></span>
                                    <span class="text-[11px] text-green-400 block mt-0.5"><i class="fa-solid fa-coins mr-1"></i>+<?php echo number_format($b['points_earned']); ?> แต้มสะสม</span>
                                </div>

                                <!-- Action Button Row -->
                                <div class="w-full flex flex-wrap items-center justify-start lg:justify-end gap-2">
                                    <?php if ($b['status'] === 'pending_payment'): ?>
                                        <a href="payment.php?code=<?php echo urlencode($b['booking_code']); ?>" class="btn-gold px-4 py-2 rounded-xl text-xs font-bold flex items-center gap-1.5 shadow">
                                            <i class="fa-solid fa-qrcode"></i> ชำระเงิน PromptPay
                                        </a>
                                    <?php endif; ?>

                                    <!-- Button: View VIP E-Ticket with QR Code -->
                                    <?php if ($b['status'] === 'confirmed' || $b['status'] === 'checked_in'): ?>
                                        <button type="button" onclick="openTicketModal('<?php echo htmlspecialchars($b['booking_code']); ?>', '<?php echo htmlspecialchars($b['bar_name']); ?>', '<?php echo htmlspecialchars($b['table_number']); ?>', '<?php echo htmlspecialchars($b['zone']); ?>', '<?php echo formatThaiDate($b['booking_date']); ?>', '<?php echo substr($b['arrival_time'], 0, 5); ?>', '<?php echo $qrCodeImg; ?>')" 
                                                class="btn-gold px-3.5 py-2 rounded-xl text-xs font-bold flex items-center gap-1.5 shadow">
                                            <i class="fa-solid fa-qrcode"></i> ตั๋ว VIP เช็คอิน
                                        </button>
                                    <?php endif; ?>

                                    <!-- Button: Share to LINE -->
                                    <a href="<?php echo $lineShareUrl; ?>" target="_blank" 
                                       class="px-3 py-2 rounded-xl bg-green-950/80 hover:bg-green-900 border border-green-500/50 text-green-300 text-xs font-semibold flex items-center gap-1.5 transition-all shadow" 
                                       title="แชร์รายละเอียดโต๊ะเข้ากลุ่ม LINE">
                                        <i class="fa-brands fa-line text-sm text-green-400"></i> แชร์เข้า LINE
                                    </a>

                                    <!-- Button: Pre-cancellation (ขอยกเลิกโต๊ะล่วงหน้า) -->
                                    <?php if (in_array($b['status'], ['pending_payment', 'confirmed']) && !$isPastCutoff): ?>
                                        <form method="POST" onsubmit="return confirm('คุณแน่ใจหรือไม่ว่าต้องการยกเลิกการจองโต๊ะนี้? เมื่อยกเลิกแล้วโต๊ะจะถูกปล่อยให้ผู้อื่นจองทันที');" class="inline">
                                            <input type="hidden" name="booking_id" value="<?php echo $b['id']; ?>">
                                            <button type="submit" name="cancel_booking" class="px-2.5 py-2 rounded-xl bg-red-950/50 hover:bg-red-900/70 border border-red-800 text-red-300 text-xs font-medium transition-all" title="ยกเลิกการจองนี้">
                                                <i class="fa-solid fa-xmark"></i> ขอยกเลิก
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- VIP E-Ticket Modal (ป๊อปอัปบัตรเช็คอินหน้าร้าน) -->
<div id="ticketModal" class="fixed inset-0 z-50 bg-black/80 backdrop-blur-md hidden flex items-center justify-center p-4">
    <div class="max-w-sm w-full bg-[#11131a] rounded-3xl border-2 border-gold shadow-2xl overflow-hidden relative text-center">
        <!-- Close Button -->
        <button onclick="closeTicketModal()" class="absolute top-4 right-4 text-gray-400 hover:text-white text-xl">
            <i class="fa-solid fa-xmark"></i>
        </button>

        <!-- Ticket Header -->
        <div class="bg-gradient-to-b from-yellow-950/60 to-[#11131a] p-6 border-b border-gray-800">
            <div class="inline-flex items-center gap-2 text-xs font-bold text-yellow-400 uppercase tracking-widest bg-black/60 px-3 py-1 rounded-full border border-gold/40 mb-2">
                👑 SONGKHLA NIGHTLIFE VIP PASS
            </div>
            <h3 id="modalBarName" class="text-xl font-extrabold text-white">Nockout Pub</h3>
            <p class="text-[11px] text-gray-400 mt-0.5">บัตรเช็คอินเข้าโต๊ะประจำตัวลูกค้า</p>
        </div>

        <!-- Ticket Body: QR Code -->
        <div class="p-6 space-y-4">
            <div class="p-3 bg-white rounded-2xl shadow-xl inline-block border-2 border-gold/50 mx-auto">
                <img id="modalQrImg" src="" alt="Checkin QR" class="w-48 h-48 mx-auto object-contain">
                <p class="text-[9px] text-gray-500 mt-1 font-mono">ให้พนักงานร้านสแกนเพื่อเช็คอิน</p>
            </div>

            <div class="grid grid-cols-2 gap-2 text-xs bg-night-900 p-3.5 rounded-xl border border-gray-800 text-left">
                <div>
                    <span class="text-[10px] text-gray-400 block">หมายเลขโต๊ะ:</span>
                    <span id="modalTableNum" class="font-extrabold text-gold text-base">VIP-01</span>
                </div>
                <div>
                    <span class="text-[10px] text-gray-400 block">โซนที่นั่ง:</span>
                    <span id="modalZone" class="font-bold text-white">VIP Lounge</span>
                </div>
                <div class="col-span-2 pt-2 border-t border-gray-800 flex justify-between items-center text-[11px]">
                    <span class="text-gray-400">เวลานัดหมาย:</span>
                    <span id="modalTime" class="font-bold text-yellow-400">19:00 น. (ก่อน 20:00)</span>
                </div>
            </div>

            <div class="text-[11px] text-red-400 bg-red-950/40 p-2.5 rounded-xl border border-red-900/40">
                <i class="fa-solid fa-triangle-exclamation mr-1"></i> กรุณาแสดงหน้านี้แก่การ์ดหน้าร้านเพื่อเช็คอินก่อน 20:00 น.
            </div>
        </div>
    </div>
</div>

<script>
// Modal Controller
function openTicketModal(code, barName, tableNum, zone, dateStr, timeStr, qrUrl) {
    document.getElementById('modalBarName').textContent = barName;
    document.getElementById('modalTableNum').textContent = tableNum;
    document.getElementById('modalZone').textContent = zone;
    document.getElementById('modalTime').textContent = timeStr + ' น. (' + dateStr + ')';
    document.getElementById('modalQrImg').src = qrUrl;
    document.getElementById('ticketModal').classList.remove('hidden');
}

function closeTicketModal() {
    document.getElementById('ticketModal').classList.add('hidden');
}

// Live 20:00:00 Countdown Timer
document.addEventListener('DOMContentLoaded', () => {
    const boxes = document.querySelectorAll('.countdown-box');
    
    function updateCountdowns() {
        const now = new Date();
        
        boxes.forEach(box => {
            const dateStr = box.getAttribute('data-date'); // YYYY-MM-DD
            const cutoffTime = box.getAttribute('data-cutoff'); // 20:00:00
            
            // Construct cutoff Date object
            const parts = dateStr.split('-');
            const timeParts = cutoffTime.split(':');
            const targetDate = new Date(parts[0], parts[1] - 1, parts[2], timeParts[0], timeParts[1], timeParts[2]);

            const diff = targetDate - now;
            const timerSpan = box.querySelector('.countdown-timer');

            if (diff > 0) {
                const hours = Math.floor(diff / (1000 * 60 * 60));
                const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
                const seconds = Math.floor((diff % (1000 * 60)) / 1000);

                const hStr = String(hours).padStart(2, '0');
                const mStr = String(minutes).padStart(2, '0');
                const sStr = String(seconds).padStart(2, '0');

                timerSpan.textContent = `${hStr} ชม. ${mStr} นาที ${sStr} วิ`;
            } else {
                timerSpan.textContent = 'หมดเวลาเช็คอินแล้ว';
                box.classList.remove('text-red-300');
                box.classList.add('text-gray-400');
            }
        });
    }

    if (boxes.length > 0) {
        updateCountdowns();
        setInterval(updateCountdowns, 1000);
    }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
