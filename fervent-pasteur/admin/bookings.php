<?php
/**
 * Admin Bookings Management
 * Pub & Bar Booking System
 */
$adminTitle = "จัดการรายการจองโต๊ะ";
require_once __DIR__ . '/includes/admin_header.php';

$db = getDb();
$today = date('Y-m-d');

// Status Filter & Search
$statusFilter = $_GET['status'] ?? '';
$barFilter = (int)($_GET['bar'] ?? 0);
$dateFilter = $_GET['date'] ?? '';
$search = trim($_GET['search'] ?? '');

// Handle Action
$msg = '';

// Handle QR Code Instant Check-in Scan from VIP E-Ticket
if (isset($_GET['scan_checkin']) && !empty($_GET['scan_checkin'])) {
    $scanCode = trim($_GET['scan_checkin']);
    $chkStmt = $db->prepare("SELECT id, status, table_id, user_id FROM bookings WHERE booking_code = ?");
    $chkStmt->execute([$scanCode]);
    $chk = $chkStmt->fetch();
    if ($chk) {
        $up = $db->prepare("UPDATE bookings SET status = 'checked_in' WHERE id = ?");
        $up->execute([$chk['id']]);
        $msg = "🎉 สแกน QR Code เช็คอินสำเร็จ! ลูกค้ารหัส {$scanCode} เข้าสู่โต๊ะเรียบร้อยแล้ว";
    } else {
        $msg = "⚠️ ไม่พบรหัสการจอง หรือรหัส QR ไม่ถูกต้อง";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_booking_status'])) {
    $bookingId = (int)$_POST['booking_id'];
    $newStatus = $_POST['status'];

    $uStmt = $db->prepare("UPDATE bookings SET status = ? WHERE id = ?");
    $uStmt->execute([$newStatus, $bookingId]);
    $msg = 'อัปเดตสถานะการจองเรียบร้อยแล้ว';
}

// Build Query
$sql = "SELECT b.*, 
               u.name AS user_name, u.phone AS user_phone, u.email AS user_email,
               r.name AS bar_name,
               t.table_number, t.zone,
               p.title AS promo_title
        FROM bookings b
        JOIN users u ON b.user_id = u.id
        JOIN bars r ON b.bar_id = r.id
        JOIN tables t ON b.table_id = t.id
        LEFT JOIN promotions p ON b.promotion_id = p.id
        WHERE 1=1";

$params = [];

if ($statusFilter) {
    $sql .= " AND b.status = ?";
    $params[] = $statusFilter;
}
if ($barFilter > 0) {
    $sql .= " AND b.bar_id = ?";
    $params[] = $barFilter;
}
if ($dateFilter) {
    $sql .= " AND b.booking_date = ?";
    $params[] = $dateFilter;
}
if ($search) {
    $sql .= " AND (b.booking_code LIKE ? OR u.name LIKE ? OR u.phone LIKE ?)";
    $term = "%{$search}%";
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
}

$sql .= " ORDER BY b.booking_date DESC, b.arrival_time ASC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$bookings = $stmt->fetchAll();

$bars = $db->query("SELECT id, name FROM bars WHERE is_active = 1")->fetchAll();
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8">
        <div>
            <h1 class="text-2xl sm:text-3xl font-bold text-white flex items-center gap-2">
                <i class="fa-solid fa-ticket text-gold"></i> รายการจองโต๊ะทั้งหมด
            </h1>
            <p class="text-xs sm:text-sm text-gray-400 mt-1">
                ตรวจสอบสลิปการโอน PromptPay เช็คอินลูกค้า และจัดการสถานะการจอง
            </p>
        </div>

        <a href="tables.php" class="btn-outline-gold px-4 py-2 rounded-xl text-xs font-semibold flex items-center gap-2">
            <i class="fa-solid fa-chair"></i> ไปหน้าจัดการผังโต๊ะ
        </a>
    </div>

    <?php if ($msg): ?>
        <div class="bg-green-950/80 border border-green-500 text-green-300 text-xs p-3.5 rounded-xl mb-6 flex items-center gap-2">
            <i class="fa-solid fa-check-circle"></i> <?php echo htmlspecialchars($msg); ?>
        </div>
    <?php endif; ?>

    <!-- Search & Filters Filter Bar -->
    <div class="glass-card p-5 rounded-2xl border border-gray-800 mb-6">
        <form method="GET" class="grid grid-cols-1 sm:grid-cols-5 gap-3 items-end text-xs">
            <div>
                <label class="block text-gray-400 mb-1">ค้นหา (รหัสจอง/ชื่อ/เบอร์)</label>
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="ค้นหา..." 
                       class="w-full bg-night-900 border border-gray-700 rounded-xl px-3 py-2 text-white focus:outline-none border-gold-focus">
            </div>

            <div>
                <label class="block text-gray-400 mb-1">เลือกร้าน</label>
                <select name="bar" class="w-full bg-night-900 border border-gray-700 rounded-xl px-3 py-2 text-white focus:outline-none border-gold-focus">
                    <option value="0">ทุกร้าน</option>
                    <?php foreach ($bars as $bar): ?>
                        <option value="<?php echo $bar['id']; ?>" <?php echo $barFilter == $bar['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($bar['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-gray-400 mb-1">สถานะ</label>
                <select name="status" class="w-full bg-night-900 border border-gray-700 rounded-xl px-3 py-2 text-white focus:outline-none border-gold-focus">
                    <option value="">ทุกสถานะ</option>
                    <option value="pending_payment" <?php echo $statusFilter === 'pending_payment' ? 'selected' : ''; ?>>รอชำระเงิน</option>
                    <option value="confirmed" <?php echo $statusFilter === 'confirmed' ? 'selected' : ''; ?>>ยืนยันแล้ว</option>
                    <option value="checked_in" <?php echo $statusFilter === 'checked_in' ? 'selected' : ''; ?>>เช็คอินแล้ว</option>
                    <option value="no_show" <?php echo $statusFilter === 'no_show' ? 'selected' : ''; ?>>ตัดสิทธิ์ (No-Show)</option>
                    <option value="cancelled" <?php echo $statusFilter === 'cancelled' ? 'selected' : ''; ?>>ยกเลิกแล้ว</option>
                </select>
            </div>

            <div>
                <label class="block text-gray-400 mb-1">วันที่จอง</label>
                <input type="date" name="date" value="<?php echo htmlspecialchars($dateFilter); ?>" 
                       class="w-full bg-night-900 border border-gray-700 rounded-xl px-3 py-2 text-white focus:outline-none border-gold-focus">
            </div>

            <div class="flex gap-2">
                <button type="submit" class="flex-1 btn-gold py-2 rounded-xl font-semibold">
                    <i class="fa-solid fa-filter mr-1"></i> กรองข้อมูล
                </button>
                <a href="bookings.php" class="p-2 rounded-xl border border-gray-700 hover:bg-gray-800 text-gray-400 flex items-center justify-center" title="รีเซ็ต">
                    <i class="fa-solid fa-rotate-right"></i>
                </a>
            </div>
        </form>
    </div>

    <!-- Bookings Data Table -->
    <div class="glass-card rounded-2xl border border-gray-800 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-xs text-left">
                <thead class="text-gray-400 bg-night-900/60 border-b border-gray-800">
                    <tr>
                        <th class="p-3.5">รหัสการจอง</th>
                        <th class="p-3.5">ลูกค้า</th>
                        <th class="p-3.5">ร้าน / โต๊ะ</th>
                        <th class="p-3.5">วันที่ / เวลานัด</th>
                        <th class="p-3.5">โปรโมชั่น / ยอดเงิน</th>
                        <th class="p-3.5">หลักฐานสลิป</th>
                        <th class="p-3.5">สถานะ</th>
                        <th class="p-3.5 text-right">จัดการสถานะ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-800">
                    <?php if (empty($bookings)): ?>
                        <tr>
                            <td colspan="8" class="p-8 text-center text-gray-500">ไม่พบข้อมูลการจองตามเงื่อนไขที่ค้นหา</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($bookings as $b): ?>
                            <tr class="hover:bg-night-800/40 transition-colors">
                                <td class="p-3.5 font-mono font-bold text-gold">
                                    <?php echo htmlspecialchars($b['booking_code']); ?>
                                    <div class="text-[10px] text-gray-500 font-normal"><?php echo date('d/m H:i', strtotime($b['created_at'])); ?></div>
                                </td>
                                <td class="p-3.5">
                                    <div class="font-bold text-white"><?php echo htmlspecialchars($b['user_name']); ?></div>
                                    <div class="text-[10px] text-gray-400"><?php echo htmlspecialchars($b['user_phone']); ?></div>
                                    <div class="text-[10px] text-gray-500"><?php echo htmlspecialchars($b['user_email']); ?></div>
                                </td>
                                <td class="p-3.5">
                                    <div class="text-white font-medium"><?php echo htmlspecialchars($b['bar_name']); ?></div>
                                    <div class="text-yellow-400 font-bold">โต๊ะ <?php echo htmlspecialchars($b['table_number']); ?> (<?php echo htmlspecialchars($b['zone']); ?>)</div>
                                    <div class="text-[10px] text-gray-400"><?php echo $b['guests_count']; ?> ท่าน</div>
                                </td>
                                <td class="p-3.5">
                                    <div class="font-medium text-white"><?php echo formatThaiDate($b['booking_date']); ?></div>
                                    <div class="text-yellow-400 font-bold font-mono"><?php echo substr($b['arrival_time'], 0, 5); ?> น.</div>
                                    <div class="text-[10px] text-red-400">กฎตัดสิทธิ์ 20:00 น.</div>
                                </td>
                                <td class="p-3.5">
                                    <div class="text-gold font-bold text-sm"><?php echo number_format($b['total_amount'], 2); ?> ฿</div>
                                    <div class="text-[10px] text-amber-300 max-w-[140px] truncate"><?php echo htmlspecialchars($b['promo_title'] ?: 'มัดจำโต๊ะปกติ'); ?></div>
                                    <div class="text-[10px] text-green-400">+<?php echo $b['points_earned']; ?> แต้ม</div>
                                </td>
                                <td class="p-3.5">
                                    <?php if ($b['payment_slip']): ?>
                                        <a href="../<?php echo htmlspecialchars($b['payment_slip']); ?>" target="_blank" 
                                           class="inline-flex items-center gap-1.5 text-xs text-green-400 bg-green-950/60 border border-green-500/50 px-2.5 py-1 rounded-lg hover:bg-green-900/60">
                                            <i class="fa-solid fa-eye"></i> ตรวจสลิป
                                        </a>
                                    <?php else: ?>
                                        <span class="text-gray-500 text-[10px]">ไม่มีสลิป</span>
                                    <?php endif; ?>
                                </td>
                                <td class="p-3.5">
                                    <?php if ($b['status'] === 'pending_payment'): ?>
                                        <span class="badge-gold px-2.5 py-1 rounded-full text-[10px] font-semibold">รอชำระเงิน</span>
                                    <?php elseif ($b['status'] === 'confirmed'): ?>
                                        <span class="badge-green px-2.5 py-1 rounded-full text-[10px] font-semibold">ยืนยันแล้ว</span>
                                    <?php elseif ($b['status'] === 'checked_in'): ?>
                                        <span class="bg-blue-950 text-blue-300 border border-blue-500/40 px-2.5 py-1 rounded-full text-[10px] font-semibold">เช็คอินแล้ว</span>
                                    <?php elseif ($b['status'] === 'no_show'): ?>
                                        <span class="badge-red px-2.5 py-1 rounded-full text-[10px] font-semibold" title="เกิน 20.00 น.">ไม่ได้มาตามเวลา</span>
                                    <?php else: ?>
                                        <span class="bg-gray-800 text-gray-400 px-2.5 py-1 rounded-full text-[10px] font-semibold">ยกเลิก</span>
                                    <?php endif; ?>
                                </td>
                                <td class="p-3.5 text-right">
                                    <form method="POST" class="inline-flex items-center gap-1.5">
                                        <input type="hidden" name="booking_id" value="<?php echo $b['id']; ?>">
                                        <select name="status" class="bg-night-900 border border-gray-700 rounded-lg text-[11px] text-white px-2 py-1 focus:outline-none">
                                            <option value="confirmed" <?php echo $b['status'] === 'confirmed' ? 'selected' : ''; ?>>ยืนยัน</option>
                                            <option value="checked_in" <?php echo $b['status'] === 'checked_in' ? 'selected' : ''; ?>>เช็คอิน (มาถึงแล้ว)</option>
                                            <option value="no_show" <?php echo $b['status'] === 'no_show' ? 'selected' : ''; ?>>ตัดสิทธิ์ (No-Show)</option>
                                            <option value="cancelled" <?php echo $b['status'] === 'cancelled' ? 'selected' : ''; ?>>ยกเลิก</option>
                                        </select>
                                        <button type="submit" name="update_booking_status" class="btn-gold px-2.5 py-1 rounded-lg text-[11px] font-bold">
                                            บันทึก
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
