<?php
/**
 * Table Booking Page - Pub & Bar Booking System
 * Visual Table Reservation with 20:00 Cutoff & Opening Package Selection
 */
require_once __DIR__ . '/config/db.php';
requireLogin();

$db = getDb();
$currentUser = currentUser();

// 1. Determine Selected Bar
$selectedBarId = isset($_GET['bar']) ? (int)$_GET['bar'] : 1;
$selectedPromoId = isset($_GET['promo']) ? (int)$_GET['promo'] : 0;
$bookingDate = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

// Fetch Bar details
$barStmt = $db->prepare("SELECT * FROM bars WHERE id = ? AND is_active = 1");
$barStmt->execute([$selectedBarId]);
$currentBar = $barStmt->fetch();

if (!$currentBar) {
    // Fallback to first available bar
    $firstBar = $db->query("SELECT * FROM bars WHERE is_active = 1 ORDER BY id ASC LIMIT 1")->fetch();
    $currentBar = $firstBar;
    $selectedBarId = $firstBar['id'];
}

// Fetch All Bars for Switcher
$bars = $db->query("SELECT * FROM bars WHERE is_active = 1 ORDER BY id ASC")->fetchAll();

// Fetch Promotions
$promotions = $db->query("SELECT * FROM promotions WHERE is_active = 1 ORDER BY id ASC")->fetchAll();

// Fetch Tables for this bar
$tablesStmt = $db->prepare("SELECT * FROM tables WHERE bar_id = ? ORDER BY zone ASC, table_number ASC");
$tablesStmt->execute([$selectedBarId]);
$allTables = $tablesStmt->fetchAll();

// Fetch existing active bookings for this bar and date to show real-time reserved tables
$bookedStmt = $db->prepare("SELECT table_id, status, booking_code FROM bookings 
                            WHERE bar_id = ? AND booking_date = ? 
                            AND status IN ('pending_payment', 'confirmed', 'checked_in')");
$bookedStmt->execute([$selectedBarId, $bookingDate]);
$bookedRows = $bookedStmt->fetchAll();
$bookedTableMap = [];
foreach ($bookedRows as $row) {
    $bookedTableMap[$row['table_id']] = $row;
}

// Group tables by Zone
$groupedTables = [];
foreach ($allTables as $table) {
    $groupedTables[$table['zone']][] = $table;
}

// Handle Form Submission (Create Booking)
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'book') {
    $tableId = (int)($_POST['table_id'] ?? 0);
    $promoId = !empty($_POST['promotion_id']) ? (int)$_POST['promotion_id'] : null;
    $date = trim($_POST['booking_date'] ?? date('Y-m-d'));
    $time = trim($_POST['arrival_time'] ?? '');
    $guests = (int)($_POST['guests_count'] ?? 2);
    $notes = trim($_POST['notes'] ?? '');

    // Cutoff Validation: Must not exceed 20:00
    if (empty($time) || empty($tableId) || empty($date)) {
        $error = 'กรุณาเลือกโต๊ะ ระบุวันที่ และเวลานัดหมายให้ครบถ้วน';
    } elseif (strtotime($time) > strtotime('20:00:00')) {
        $error = 'ไม่สามารถจองเกินเวลา 20:00 น. ได้ (ระบบมีกฎตัดสิทธิ์หากไม่มาภายใน 20:00 น.)';
    } elseif (strtotime($date) < strtotime(date('Y-m-d'))) {
        $error = 'ไม่สามารถเลือกวันที่ในอดีตได้';
    } else {
        // Double check if table is already booked for that date
        $checkDouble = $db->prepare("SELECT id FROM bookings WHERE bar_id = ? AND table_id = ? AND booking_date = ? AND status IN ('pending_payment', 'confirmed', 'checked_in')");
        $checkDouble->execute([$selectedBarId, $tableId, $date]);
        if ($checkDouble->fetch()) {
            $error = 'ขออภัย โต๊ะนี้เพิ่งถูกจองไป กรุณาเลือกโต๊ะอื่น';
        } else {
            // Calculate Total Amount
            $depositAmount = 100.00; // Standard table reservation deposit
            $totalAmount = $depositAmount;
            $pointsEarned = 10;

            if ($promoId) {
                $pStmt = $db->prepare("SELECT price, points_reward FROM promotions WHERE id = ?");
                $pStmt->execute([$promoId]);
                $promoData = $pStmt->fetch();
                if ($promoData) {
                    $totalAmount = $promoData['price'];
                    $pointsEarned = $promoData['points_reward'];
                }
            }

            $bookingCode = generateBookingCode();

            $insertStmt = $db->prepare("INSERT INTO bookings 
                (booking_code, user_id, bar_id, table_id, promotion_id, booking_date, arrival_time, guests_count, total_amount, points_earned, payment_method, status, notes) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'PromptPay', 'pending_payment', ?)");
            
            if ($insertStmt->execute([
                $bookingCode,
                $currentUser['id'],
                $selectedBarId,
                $tableId,
                $promoId,
                $date,
                $time,
                $guests,
                $totalAmount,
                $pointsEarned,
                $notes
            ])) {
                // Redirect directly to payment promptpay page
                header("Location: payment.php?code=" . $bookingCode);
                exit;
            } else {
                $error = 'เกิดข้อผิดพลาดในการบันทึกการจอง กรุณาลองใหม่อีกครั้ง';
            }
        }
    }
}

$pageTitle = "จองโต๊ะ - " . $currentBar['name'];
require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Bar Switcher Tabs (กรอบสี่เหลี่ยมเลือกร้าน) -->
    <div class="mb-8">
        <h1 class="text-2xl sm:text-3xl font-bold text-white mb-2">
            สำรองโต๊ะ <span class="text-gold-gradient"><?php echo htmlspecialchars($currentBar['name']); ?></span>
        </h1>
        <p class="text-xs sm:text-sm text-gray-400 mb-6">
            เลือกร้าน ตรวจสอบผังโต๊ะว่าง ระบุเวลานัดหมาย และเลือกสั่งโปรโมชั่นเปิดโต๊ะ
        </p>

        <!-- Square Pub Selector Buttons -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <?php foreach ($bars as $bar): ?>
                <a href="booking.php?bar=<?php echo $bar['id']; ?>&date=<?php echo htmlspecialchars($bookingDate); ?>&promo=<?php echo $selectedPromoId; ?>" 
                   class="p-4 rounded-2xl border transition-all flex items-center gap-4 <?php echo $bar['id'] == $selectedBarId ? 'bg-gradient-to-r from-yellow-950/50 to-night-800 border-gold shadow-lg shadow-gold/10' : 'bg-night-800/80 border-gray-800 hover:border-gold/40 text-gray-400'; ?>">
                    <img src="<?php echo htmlspecialchars($bar['image']); ?>" alt="" class="w-16 h-16 rounded-xl object-cover border border-gold/30 shrink-0">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between">
                            <h3 class="font-bold text-sm sm:text-base text-white truncate"><?php echo htmlspecialchars($bar['name']); ?></h3>
                            <?php if ($bar['id'] == $selectedBarId): ?>
                                <span class="text-[10px] bg-gold text-black px-2 py-0.5 rounded-full font-bold">เลือกร้านนี้</span>
                            <?php endif; ?>
                        </div>
                        <p class="text-xs text-gray-400 truncate mt-0.5"><i class="fa-solid fa-map-pin text-gold/70 mr-1"></i><?php echo htmlspecialchars($bar['address']); ?></p>
                        <span class="text-[11px] text-amber-400 mt-1 inline-block">ปิดรับจอง: <?php echo substr($bar['cutoff_time'], 0, 5); ?> น.</span>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="bg-red-950/80 border border-red-500/80 text-red-200 text-sm p-4 rounded-2xl mb-6 flex items-center gap-3">
            <i class="fa-solid fa-triangle-exclamation text-xl text-red-400 shrink-0"></i>
            <span><?php echo htmlspecialchars($error); ?></span>
        </div>
    <?php endif; ?>

    <form method="POST" id="bookingForm">
        <input type="hidden" name="action" value="book">
        <input type="hidden" name="table_id" id="selectedTableId" value="">

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Left & Center Col (2 cols): Visual Table Seating Map -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Date Filter Bar -->
                <div class="glass-card p-5 rounded-2xl border border-gold/30 flex flex-wrap items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-gold/10 border border-gold/30 flex items-center justify-center text-gold">
                            <i class="fa-solid fa-calendar-day"></i>
                        </div>
                        <div>
                            <div class="text-xs text-gray-400">เลือกวันที่ต้องการจอง</div>
                            <input type="date" id="bookingDateInput" name="booking_date" 
                                   value="<?php echo htmlspecialchars($bookingDate); ?>" 
                                   min="<?php echo date('Y-m-d'); ?>"
                                   class="bg-night-900 border border-gray-700 rounded-lg px-3 py-1.5 text-sm text-white font-medium focus:outline-none border-gold-focus"
                                   onchange="location.href='booking.php?bar=<?php echo $selectedBarId; ?>&promo=<?php echo $selectedPromoId; ?>&date=' + this.value">
                        </div>
                    </div>

                    <!-- Legend: Map Status Indicators -->
                    <div class="flex items-center gap-4 text-xs">
                        <div class="flex items-center gap-1.5">
                            <span class="w-3.5 h-3.5 rounded bg-night-800 border border-gold"></span>
                            <span class="text-gray-300">โต๊ะว่าง</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span class="w-3.5 h-3.5 rounded bg-red-950 border border-red-500"></span>
                            <span class="text-red-400 font-semibold">จองแล้ว</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span class="w-3.5 h-3.5 rounded bg-gold text-black flex items-center justify-center text-[9px] font-bold">✓</span>
                            <span class="text-gold font-semibold">โต๊ะที่เลือก</span>
                        </div>
                    </div>
                </div>

                <!-- Stage / DJ Banner visual for ambience -->
                <div class="w-full py-2.5 rounded-xl bg-gradient-to-r from-purple-950/60 via-gold/20 to-purple-950/60 border border-gold/30 text-center text-xs text-gold tracking-widest uppercase font-bold shadow">
                    🎤 STAGE & LIVE BAND / DJ BOOTH (เวทีคอนเสิร์ต)
                </div>

                <!-- Visual Seating Map by Zone (ระบบแสดงโต๊ะที่จองไว้ อยู่ในหน้า จองโต๊ะ) -->
                <?php foreach ($groupedTables as $zoneName => $tablesInZone): ?>
                    <div class="glass-card p-6 rounded-2xl border border-gray-800">
                        <div class="flex items-center justify-between mb-4 border-b border-gray-800 pb-3">
                            <h3 class="font-bold text-base text-gold flex items-center gap-2">
                                <i class="fa-solid fa-layer-group text-xs"></i> <?php echo htmlspecialchars($zoneName); ?>
                            </h3>
                            <span class="text-xs text-gray-400"><?php echo count($tablesInZone); ?> โต๊ะในโซนนี้</span>
                        </div>

                        <div class="table-grid">
                            <?php foreach ($tablesInZone as $table): ?>
                                <?php 
                                    $isBooked = isset($bookedTableMap[$table['id']]); 
                                    $isMaintenance = $table['status'] === 'maintenance';
                                ?>
                                <?php if ($isBooked): ?>
                                    <!-- Booked Table (จองแล้ว) -->
                                    <div class="seat-box seat-booked" title="โต๊ะนี้ถูกจองแล้ว">
                                        <div class="text-xs font-bold text-red-400 uppercase tracking-wider mb-1">
                                            <?php echo htmlspecialchars($table['table_number']); ?>
                                        </div>
                                        <div class="text-[10px] text-gray-400">
                                            <i class="fa-solid fa-users"></i> <?php echo $table['capacity']; ?> ที่นั่ง
                                        </div>
                                        <div class="mt-2 text-[10px] bg-red-900/60 text-red-300 py-0.5 px-2 rounded-full border border-red-500/40 inline-block font-semibold">
                                            <i class="fa-solid fa-ban text-[9px] mr-0.5"></i> จองแล้ว
                                        </div>
                                    </div>
                                <?php elseif ($isMaintenance): ?>
                                    <!-- Maintenance Table -->
                                    <div class="seat-box seat-maintenance">
                                        <div class="text-xs font-bold text-gray-500">
                                            <?php echo htmlspecialchars($table['table_number']); ?>
                                        </div>
                                        <div class="text-[10px] text-gray-600 mt-2">งดบริการ</div>
                                    </div>
                                <?php else: ?>
                                    <!-- Available Table (โต๊ะว่าง สามารถเลือกได้) -->
                                    <div class="seat-box seat-available table-item" 
                                         data-id="<?php echo $table['id']; ?>"
                                         data-number="<?php echo htmlspecialchars($table['table_number']); ?>"
                                         data-zone="<?php echo htmlspecialchars($zoneName); ?>"
                                         data-capacity="<?php echo $table['capacity']; ?>">
                                        <div class="text-xs font-extrabold text-gold uppercase tracking-wider mb-1">
                                            <?php echo htmlspecialchars($table['table_number']); ?>
                                        </div>
                                        <div class="text-[10px] text-gray-300">
                                            <i class="fa-solid fa-users text-gold/70"></i> สูงสุด <?php echo $table['capacity']; ?> ท่าน
                                        </div>
                                        <div class="mt-2 text-[10px] bg-gold/10 text-gold py-0.5 px-2 rounded-full border border-gold/30 inline-block font-medium">
                                            <i class="fa-regular fa-circle-check text-[9px] mr-0.5"></i> โต๊ะว่าง
                                        </div>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Right Col (1 col): Reservation Form & Summary -->
            <div class="space-y-6">
                <div class="glass-card p-6 rounded-2xl border border-gold/50 shadow-xl sticky top-28">
                    <h2 class="text-lg font-bold text-white border-b border-gray-800 pb-3 mb-4 flex items-center gap-2">
                        <i class="fa-solid fa-receipt text-gold"></i> รายละเอียดการจอง
                    </h2>

                    <!-- Selected Table Indicator Display -->
                    <div id="selectedTableDisplay" class="p-3.5 rounded-xl bg-night-900 border border-dashed border-gray-700 text-center mb-5">
                        <p class="text-xs text-gray-400">👈 กรุณาคลิกเลือกโต๊ะว่างจากผังด้านซ้าย</p>
                    </div>

                    <!-- Step 1: Appointment Arrival Time (นัดเวลาก่อนได้ & ต้องไม่เกิน 20:00 น.) -->
                    <div class="mb-4">
                        <div class="flex items-center justify-between mb-1.5">
                            <label class="text-xs font-semibold text-gray-200">
                                <i class="fa-solid fa-clock text-gold mr-1"></i> เวลานัดหมายมาถึงร้าน
                            </label>
                            <span class="text-[10px] text-red-400 font-bold">ไม่เกิน 20:00 น.</span>
                        </div>
                        <select name="arrival_time" id="arrivalTimeSelect" required 
                                class="w-full bg-night-900 border border-gray-700 rounded-xl px-3.5 py-2.5 text-sm text-white focus:outline-none border-gold-focus">
                            <option value="">-- เลือกเวลานัดหมาย --</option>
                            <option value="17:30:00">17:30 น.</option>
                            <option value="18:00:00">18:00 น.</option>
                            <option value="18:30:00">18:30 น.</option>
                            <option value="19:00:00">19:00 น. (ยอดนิยม)</option>
                            <option value="19:30:00">19:30 น.</option>
                            <option value="20:00:00">20:00 น. (รอบสุดท้าย - Cutoff)</option>
                        </select>
                        <p class="text-[10px] text-red-300 mt-1.5">
                            * หากไม่มาเช็คอินภายใน 20:00 น. ระบบจะตัดสิทธิ์ให้โต๊ะว่างอัตโนมัติ
                        </p>
                    </div>

                    <!-- Step 2: Select Promotion / Opening Package (เปิดโต๊ะว่าจะเอาโปรอะไร) -->
                    <div class="mb-4">
                        <label class="block text-xs font-semibold text-gray-200 mb-1.5">
                            <i class="fa-solid fa-champagne-glasses text-gold mr-1"></i> โปรโมชั่นเปิดโต๊ะ
                        </label>
                        <select name="promotion_id" id="promoSelect" 
                                class="w-full bg-night-900 border border-gray-700 rounded-xl px-3.5 py-2.5 text-sm text-white focus:outline-none border-gold-focus"
                                onchange="updateSummary()">
                            <option value="" data-price="100" data-points="10">ไม่รับโปรโมชั่น (มัดจำโต๊ะ 100 บาท)</option>
                            <?php foreach ($promotions as $promo): ?>
                                <option value="<?php echo $promo['id']; ?>" 
                                        data-price="<?php echo $promo['price']; ?>" 
                                        data-points="<?php echo $promo['points_reward']; ?>"
                                        <?php echo $selectedPromoId == $promo['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($promo['title']); ?> (<?php echo number_format($promo['price'], 0); ?>.-)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Step 3: Guests count -->
                    <div class="grid grid-cols-2 gap-3 mb-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-200 mb-1.5">จำนวนลูกค้า (ท่าน)</label>
                            <select name="guests_count" class="w-full bg-night-900 border border-gray-700 rounded-xl px-3 py-2 text-sm text-white focus:outline-none border-gold-focus">
                                <option value="2">1 - 2 ท่าน</option>
                                <option value="4" selected>3 - 4 ท่าน</option>
                                <option value="6">5 - 6 ท่าน</option>
                                <option value="8">7 - 8 ท่าน</option>
                                <option value="10">8 ท่านขึ้นไป</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-200 mb-1.5">วิธีชำระเงิน</label>
                            <div class="w-full bg-night-900/60 border border-gray-700 rounded-xl px-3 py-2 text-xs text-yellow-400 font-semibold flex items-center gap-1.5">
                                <i class="fa-solid fa-qrcode"></i> PromptPay QR
                            </div>
                        </div>
                    </div>

                    <!-- Notes -->
                    <div class="mb-5">
                        <label class="block text-xs font-semibold text-gray-200 mb-1.5">หมายเหตุเพิ่มเติม (ถ้ามี)</label>
                        <input type="text" name="notes" placeholder="เช่น ฉลองวันเกิด, ขอเก้าอี้เสริม" 
                               class="w-full bg-night-900 border border-gray-700 rounded-xl px-3.5 py-2 text-xs text-white focus:outline-none border-gold-focus">
                    </div>

                    <!-- Price & Points Summary -->
                    <div class="bg-[#0b0c10] p-4 rounded-xl border border-gray-800 space-y-2 mb-5">
                        <div class="flex justify-between text-xs text-gray-400">
                            <span>ยอดชำระมัดจำ/เปิดโต๊ะ:</span>
                            <span class="font-bold text-white" id="summaryPrice">100.00 บาท</span>
                        </div>
                        <div class="flex justify-between text-xs text-amber-300">
                            <span>แต้มสะสมที่จะได้รับ:</span>
                            <span class="font-bold" id="summaryPoints">+10 แต้ม</span>
                        </div>
                        <hr class="border-gray-800">
                        <div class="flex justify-between items-baseline pt-1">
                            <span class="text-sm font-bold text-white">ยอดรวมทั้งสิ้น:</span>
                            <span class="text-2xl font-extrabold text-gold" id="totalDisplay">100.00 <span class="text-xs text-gray-300 font-normal">บาท</span></span>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" id="submitBtn" disabled 
                            class="w-full py-3.5 rounded-xl text-sm font-bold transition-all disabled:opacity-50 disabled:cursor-not-allowed bg-gray-700 text-gray-400 shadow-lg">
                        <i class="fa-solid fa-credit-card mr-2"></i> กรุณาเลือกโต๊ะก่อนดำเนินการ
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const tableItems = document.querySelectorAll('.table-item');
        const hiddenTableInput = document.getElementById('selectedTableId');
        const displayBox = document.getElementById('selectedTableDisplay');
        const submitBtn = document.getElementById('submitBtn');
        const promoSelect = document.getElementById('promoSelect');
        const summaryPrice = document.getElementById('summaryPrice');
        const summaryPoints = document.getElementById('summaryPoints');
        const totalDisplay = document.getElementById('totalDisplay');

        // Handle Table Selection
        tableItems.forEach(item => {
            item.addEventListener('click', () => {
                // Clear previous selection
                tableItems.forEach(t => t.classList.remove('seat-selected'));

                // Set new selection
                item.classList.add('seat-selected');
                const tableId = item.getAttribute('data-id');
                const tableNum = item.getAttribute('data-number');
                const zone = item.getAttribute('data-zone');
                const cap = item.getAttribute('data-capacity');

                hiddenTableInput.value = tableId;

                // Update UI Display
                displayBox.className = "p-3.5 rounded-xl bg-yellow-950/40 border border-gold text-left mb-5 transition-all";
                displayBox.innerHTML = `
                    <div class="flex items-center justify-between">
                        <div>
                            <span class="text-[10px] text-gray-400 uppercase tracking-wider block">โต๊ะที่คุณเลือก:</span>
                            <span class="text-lg font-bold text-gold">${tableNum}</span>
                            <span class="text-xs text-gray-300 ml-2">(${zone})</span>
                        </div>
                        <span class="text-xs text-gray-300 bg-black/60 px-2.5 py-1 rounded-lg border border-gold/30">
                            <i class="fa-solid fa-users text-gold mr-1"></i> สูงสุด ${cap} ท่าน
                        </span>
                    </div>
                `;

                // Enable submit button
                submitBtn.disabled = false;
                submitBtn.className = "w-full py-3.5 rounded-xl text-sm font-bold btn-gold shadow-lg cursor-pointer";
                submitBtn.innerHTML = `<i class="fa-solid fa-lock mr-1.5"></i> ยืนยันโต๊ะ & ไปหน้าชำระเงิน PromptPay`;
            });
        });

        // Update Price & Points summary when promo changes
        window.updateSummary = function() {
            const selectedOpt = promoSelect.options[promoSelect.selectedIndex];
            const price = parseFloat(selectedOpt.getAttribute('data-price')) || 100;
            const points = parseInt(selectedOpt.getAttribute('data-points')) || 10;

            summaryPrice.textContent = price.toLocaleString('th-TH', {minimumFractionDigits: 2}) + ' บาท';
            summaryPoints.textContent = '+' + points + ' แต้ม';
            totalDisplay.innerHTML = price.toLocaleString('th-TH', {minimumFractionDigits: 2}) + ' <span class="text-xs text-gray-300 font-normal">บาท</span>';
        };

        updateSummary();
    });
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
