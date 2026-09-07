<?php
/**
 * Admin Dashboard - Pub & Bar Booking System
 */
require_once __DIR__ . '/../config/db.php';
requireAdmin();

$db = getDb();
$today = date('Y-m-d');

// Manual Cutoff Trigger
$cutoffMessage = '';
if (isset($_POST['trigger_cutoff'])) {
    $count = runCutoffCheck();
    $cutoffMessage = "ดำเนินการตรวจสอบกฎ 20:00 น. สำเร็จ! ตัดสิทธิ์ No-Show ทั้งหมด {$count} รายการ";
}

// Handle quick booking status update from dashboard BEFORE any HTML output
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $bookingId = (int)$_POST['booking_id'];
    $newStatus = $_POST['new_status'];
    
    $uStmt = $db->prepare("UPDATE bookings SET status = ? WHERE id = ?");
    $uStmt->execute([$newStatus, $bookingId]);
    header('Location: index.php?msg=status_updated');
    exit;
}

// Calculate Statistics
// Today's total bookings
$todayBookingsStmt = $db->prepare("SELECT COUNT(*) FROM bookings WHERE booking_date = ?");
$todayBookingsStmt->execute([$today]);
$todayBookings = $todayBookingsStmt->fetchColumn();

// Today's total revenue (confirmed or checked-in)
$todayRevenueStmt = $db->prepare("SELECT SUM(total_amount) FROM bookings WHERE booking_date = ? AND status IN ('confirmed', 'checked_in')");
$todayRevenueStmt->execute([$today]);
$todayRevenue = $todayRevenueStmt->fetchColumn() ?: 0.00;

// Active checked in tables today
$checkedInStmt = $db->prepare("SELECT COUNT(*) FROM bookings WHERE booking_date = ? AND status = 'checked_in'");
$checkedInStmt->execute([$today]);
$checkedInCount = $checkedInStmt->fetchColumn();

// Unread customer messages
$unreadMsgStmt = $db->query("SELECT COUNT(*) FROM contact_messages WHERE status = 'unread'");
$unreadCount = $unreadMsgStmt->fetchColumn();

// Fetch Recent 10 Bookings
$recentStmt = $db->query("SELECT b.*, 
                                 u.name AS user_name, u.phone AS user_phone, u.email AS user_email,
                                 r.name AS bar_name,
                                 t.table_number, t.zone,
                                 p.title AS promo_title
                          FROM bookings b
                          JOIN users u ON b.user_id = u.id
                          JOIN bars r ON b.bar_id = r.id
                          JOIN tables t ON b.table_id = t.id
                          LEFT JOIN promotions p ON b.promotion_id = p.id
                          ORDER BY b.created_at DESC
                          LIMIT 10");
$recentBookings = $recentStmt->fetchAll();

$adminTitle = "แดชบอร์ดภาพรวม";
require_once __DIR__ . '/includes/admin_header.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
        <div>
            <h1 class="text-2xl sm:text-3xl font-bold text-white flex items-center gap-3">
                <span>แดชบอร์ดผู้ดูแลระบบ</span>
                <span class="text-xs bg-red-900/60 border border-red-500/50 text-red-300 px-2.5 py-1 rounded-full font-normal">ADMIN BACKOFFICE</span>
            </h1>
            <p class="text-xs sm:text-sm text-gray-400 mt-1">
                จัดการรายการจอง ตรวจสอบสลิป PromptPay และจัดผังโต๊ะให้ลูกค้า
            </p>
        </div>

        <!-- Manual 20:00 Cutoff Trigger Button -->
        <form method="POST">
            <button type="submit" name="trigger_cutoff" class="btn-outline-gold px-4 py-2.5 rounded-xl text-xs font-semibold flex items-center gap-2">
                <i class="fa-solid fa-clock-rotate-left text-yellow-400"></i> รันระบบตัดสิทธิ์ No-Show (หลัง 20:00 น.)
            </button>
        </form>
    </div>

    <?php if ($cutoffMessage): ?>
        <div class="bg-yellow-950/80 border border-gold text-yellow-300 text-xs p-4 rounded-xl mb-6 flex items-center gap-3">
            <i class="fa-solid fa-bell text-lg shrink-0"></i>
            <span><?php echo htmlspecialchars($cutoffMessage); ?></span>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['msg']) && $_GET['msg'] === 'status_updated'): ?>
        <div class="bg-green-950/80 border border-green-500 text-green-300 text-xs p-3.5 rounded-xl mb-6 flex items-center gap-2">
            <i class="fa-solid fa-check-circle"></i> อัปเดตสถานะการจองเรียบร้อยแล้ว
        </div>
    <?php endif; ?>

    <!-- Stat Metric Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Card 1: Today Bookings -->
        <div class="glass-card p-5 rounded-2xl border border-gold/30">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs text-gray-400">ยอดจองโต๊ะวันนี้</span>
                <span class="w-8 h-8 rounded-lg bg-yellow-950/60 border border-gold/40 text-gold flex items-center justify-center text-sm">
                    <i class="fa-solid fa-calendar-day"></i>
                </span>
            </div>
            <div class="text-3xl font-extrabold text-white"><?php echo number_format($todayBookings); ?> <span class="text-sm font-normal text-gray-400">โต๊ะ</span></div>
            <p class="text-[11px] text-gray-500 mt-1"><?php echo formatThaiDate($today); ?></p>
        </div>

        <!-- Card 2: Today Revenue -->
        <div class="glass-card p-5 rounded-2xl border border-gold/30">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs text-gray-400">ยอดเงินมัดจำ/โปรโมชั่น</span>
                <span class="w-8 h-8 rounded-lg bg-green-950/60 border border-green-500/40 text-green-400 flex items-center justify-center text-sm">
                    <i class="fa-solid fa-coins"></i>
                </span>
            </div>
            <div class="text-3xl font-extrabold text-gold"><?php echo number_format($todayRevenue, 2); ?> <span class="text-sm font-normal text-gray-400">บาท</span></div>
            <p class="text-[11px] text-green-400 mt-1">PromptPay ชำระแล้ว</p>
        </div>

        <!-- Card 3: Checked In Tables -->
        <div class="glass-card p-5 rounded-2xl border border-gold/30">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs text-gray-400">ลูกค้าเช็คอินแล้ว</span>
                <span class="w-8 h-8 rounded-lg bg-blue-950/60 border border-blue-500/40 text-blue-400 flex items-center justify-center text-sm">
                    <i class="fa-solid fa-user-check"></i>
                </span>
            </div>
            <div class="text-3xl font-extrabold text-blue-400"><?php echo number_format($checkedInCount); ?> <span class="text-sm font-normal text-gray-400">โต๊ะ</span></div>
            <p class="text-[11px] text-gray-500 mt-1">มาถึงร้านก่อน 20:00 น.</p>
        </div>

        <!-- Card 4: Unread Messages -->
        <div class="glass-card p-5 rounded-2xl border border-gold/30">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs text-gray-400">ข้อความรอลูกค้า</span>
                <span class="w-8 h-8 rounded-lg bg-purple-950/60 border border-purple-500/40 text-purple-400 flex items-center justify-center text-sm">
                    <i class="fa-solid fa-comment-dots"></i>
                </span>
            </div>
            <div class="text-3xl font-extrabold text-purple-300"><?php echo number_format($unreadCount); ?> <span class="text-sm font-normal text-gray-400">ข้อความ</span></div>
            <a href="contacts.php" class="text-[11px] text-gold hover:underline mt-1 inline-block">เปิดดูกล่องข้อความ →</a>
        </div>
    </div>

    <!-- Quick Navigation Shortcuts (สามารถแก้ไขการจัดโต๊ะให้ลูกค้าได้และระบบต่างๆ) -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
        <a href="tables.php" class="glass-card p-4 rounded-xl border border-gray-800 hover:border-gold flex items-center gap-4 transition-all">
            <div class="w-12 h-12 rounded-xl bg-yellow-950/50 border border-gold/40 text-gold flex items-center justify-center text-xl shrink-0">
                <i class="fa-solid fa-chair"></i>
            </div>
            <div>
                <h4 class="font-bold text-white text-sm">จัดการและแก้ไขผังโต๊ะ</h4>
                <p class="text-[11px] text-gray-400">เปลี่ยนโต๊ะให้ลูกค้า ปรับสถานะโต๊ะว่าง/ซ่อม</p>
            </div>
        </a>

        <a href="bookings.php" class="glass-card p-4 rounded-xl border border-gray-800 hover:border-gold flex items-center gap-4 transition-all">
            <div class="w-12 h-12 rounded-xl bg-yellow-950/50 border border-gold/40 text-gold flex items-center justify-center text-xl shrink-0">
                <i class="fa-solid fa-qrcode"></i>
            </div>
            <div>
                <h4 class="font-bold text-white text-sm">ตรวจสอบสลิป & เช็คอิน</h4>
                <p class="text-[11px] text-gray-400">ดูสลิป PromptPay อนุมัติการจอง</p>
            </div>
        </a>

        <a href="promotions.php" class="glass-card p-4 rounded-xl border border-gray-800 hover:border-gold flex items-center gap-4 transition-all">
            <div class="w-12 h-12 rounded-xl bg-yellow-950/50 border border-gold/40 text-gold flex items-center justify-center text-xl shrink-0">
                <i class="fa-solid fa-tags"></i>
            </div>
            <div>
                <h4 class="font-bold text-white text-sm">จัดการโปรโมชั่น</h4>
                <p class="text-[11px] text-gray-400">แก้ไขโปรช้าง 199.- / แสงโสม 599.-</p>
            </div>
        </a>
    </div>

    <!-- Recent Bookings Table -->
    <div class="glass-card rounded-2xl border border-gray-800 overflow-hidden">
        <div class="p-5 border-b border-gray-800 flex items-center justify-between">
            <h3 class="font-bold text-base text-white flex items-center gap-2">
                <i class="fa-solid fa-list-check text-gold"></i> รายการจองล่าสุด (Recent Bookings)
            </h3>
            <a href="bookings.php" class="text-xs text-gold hover:underline">ดูทั้งหมด →</a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-xs text-left">
                <thead class="text-gray-400 bg-night-900/60 border-b border-gray-800">
                    <tr>
                        <th class="p-3.5">รหัสการจอง</th>
                        <th class="p-3.5">ลูกค้า</th>
                        <th class="p-3.5">ร้าน / โต๊ะ</th>
                        <th class="p-3.5">วันที่ / เวลานัด</th>
                        <th class="p-3.5">โปรโมชั่น / ยอดเงิน</th>
                        <th class="p-3.5">สลิปโอน</th>
                        <th class="p-3.5">สถานะ</th>
                        <th class="p-3.5 text-right">ดำเนินการ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-800">
                    <?php if (empty($recentBookings)): ?>
                        <tr>
                            <td colspan="8" class="p-6 text-center text-gray-500">ยังไม่มีรายการจองในระบบ</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($recentBookings as $b): ?>
                            <tr class="hover:bg-night-800/40 transition-colors">
                                <td class="p-3.5 font-mono font-bold text-gold"><?php echo htmlspecialchars($b['booking_code']); ?></td>
                                <td class="p-3.5">
                                    <div class="font-bold text-white"><?php echo htmlspecialchars($b['user_name']); ?></div>
                                    <div class="text-[10px] text-gray-400"><?php echo htmlspecialchars($b['user_phone']); ?></div>
                                </td>
                                <td class="p-3.5">
                                    <div class="text-white"><?php echo htmlspecialchars($b['bar_name']); ?></div>
                                    <div class="text-yellow-400 font-bold">โต๊ะ <?php echo htmlspecialchars($b['table_number']); ?> (<?php echo htmlspecialchars($b['zone']); ?>)</div>
                                </td>
                                <td class="p-3.5">
                                    <div><?php echo formatThaiDate($b['booking_date']); ?></div>
                                    <div class="text-yellow-400 font-semibold font-mono"><?php echo substr($b['arrival_time'], 0, 5); ?> น.</div>
                                </td>
                                <td class="p-3.5">
                                    <div class="text-gold font-bold"><?php echo number_format($b['total_amount'], 2); ?> ฿</div>
                                    <div class="text-[10px] text-gray-400 truncate max-w-[120px]"><?php echo htmlspecialchars($b['promo_title'] ?: 'มัดจำโต๊ะ'); ?></div>
                                </td>
                                <td class="p-3.5">
                                    <?php if ($b['payment_slip']): ?>
                                        <a href="../<?php echo htmlspecialchars($b['payment_slip']); ?>" target="_blank" class="inline-flex items-center gap-1 text-[11px] text-green-400 hover:underline bg-green-950/60 px-2 py-1 rounded border border-green-500/40">
                                            <i class="fa-solid fa-image"></i> ดูสลิป
                                        </a>
                                    <?php else: ?>
                                        <span class="text-[10px] text-gray-500">ยังไม่แนบ</span>
                                    <?php endif; ?>
                                </td>
                                <td class="p-3.5">
                                    <?php if ($b['status'] === 'pending_payment'): ?>
                                        <span class="badge-gold px-2.5 py-0.5 rounded-full text-[10px]">รอชำระ</span>
                                    <?php elseif ($b['status'] === 'confirmed'): ?>
                                        <span class="badge-green px-2.5 py-0.5 rounded-full text-[10px]">ยืนยันแล้ว</span>
                                    <?php elseif ($b['status'] === 'checked_in'): ?>
                                        <span class="bg-blue-950 text-blue-300 border border-blue-500/40 px-2.5 py-0.5 rounded-full text-[10px]">เช็คอินแล้ว</span>
                                    <?php elseif ($b['status'] === 'no_show'): ?>
                                        <span class="badge-red px-2.5 py-0.5 rounded-full text-[10px]" title="เกิน 20.00 น.">ไม่ได้มาตามเวลา</span>
                                    <?php else: ?>
                                        <span class="bg-gray-800 text-gray-400 px-2.5 py-0.5 rounded-full text-[10px]">ยกเลิก</span>
                                    <?php endif; ?>
                                </td>
                                <td class="p-3.5 text-right">
                                    <form method="POST" class="inline-flex items-center gap-1">
                                        <input type="hidden" name="booking_id" value="<?php echo $b['id']; ?>">
                                        <select name="new_status" class="bg-night-900 border border-gray-700 rounded-lg text-[11px] text-white px-2 py-1 focus:outline-none">
                                            <option value="confirmed" <?php echo $b['status'] === 'confirmed' ? 'selected' : ''; ?>>ยืนยัน</option>
                                            <option value="checked_in" <?php echo $b['status'] === 'checked_in' ? 'selected' : ''; ?>>เช็คอิน (ถึงร้านแล้ว)</option>
                                            <option value="no_show" <?php echo $b['status'] === 'no_show' ? 'selected' : ''; ?>>ตัดสิทธิ์ (No-Show)</option>
                                            <option value="cancelled" <?php echo $b['status'] === 'cancelled' ? 'selected' : ''; ?>>ยกเลิก</option>
                                        </select>
                                        <button type="submit" name="update_status" class="btn-gold px-2 py-1 rounded text-[11px]" title="บันทึกสถานะ">
                                            ✓
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
