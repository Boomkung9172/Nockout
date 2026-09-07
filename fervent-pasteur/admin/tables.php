<?php
/**
 * Admin Table Seating & Layout Management
 * Pub & Bar Booking System
 * Allows admin to edit table layouts, reassign customer seats, and toggle table statuses
 */
require_once __DIR__ . '/../config/db.php';
requireAdmin();

$db = getDb();
$selectedBarId = isset($_GET['bar']) ? (int)$_GET['bar'] : 1;
$today = date('Y-m-d');

$success = '';
$error = '';

// Handle Toggle Table Status (available / maintenance) BEFORE any HTML output
if (isset($_GET['toggle_status']) && isset($_GET['id'])) {
    $tid = (int)$_GET['id'];
    $curStatus = $_GET['toggle_status'];
    $newStatus = ($curStatus === 'available') ? 'maintenance' : 'available';

    $togStmt = $db->prepare("UPDATE tables SET status = ? WHERE id = ?");
    $togStmt->execute([$newStatus, $tid]);
    header("Location: tables.php?bar=" . $selectedBarId . "&msg=status_changed");
    exit;
}

// Handle Reassigning Table for a Customer Booking (แก้ไขการจัดโต๊ะให้ลูกค้า)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reassign_table'])) {
    $bookingId = (int)$_POST['booking_id'];
    $newTableId = (int)$_POST['new_table_id'];

    if ($bookingId > 0 && $newTableId > 0) {
        // Fetch booking info
        $bInfoStmt = $db->prepare("SELECT booking_date, bar_id FROM bookings WHERE id = ?");
        $bInfoStmt->execute([$bookingId]);
        $bInfo = $bInfoStmt->fetch();

        if ($bInfo) {
            // Check if new table is already occupied on that date
            $occStmt = $db->prepare("SELECT id FROM bookings WHERE table_id = ? AND booking_date = ? AND id != ? AND status IN ('pending_payment', 'confirmed', 'checked_in')");
            $occStmt->execute([$newTableId, $bInfo['booking_date'], $bookingId]);
            if ($occStmt->fetch()) {
                $error = 'ไม่สามารถย้ายโต๊ะได้ เนื่องจากโต๊ะปลายทางมีลูกค้ารายอื่นจองแล้วในวันดังกล่าว';
            } else {
                $upStmt = $db->prepare("UPDATE bookings SET table_id = ? WHERE id = ?");
                $upStmt->execute([$newTableId, $bookingId]);
                $success = 'แก้ไขการจัดโต๊ะให้ลูกค้าเรียบร้อยแล้ว!';
            }
        }
    }
}

// Handle Add New Table
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_table'])) {
    $barId = (int)$_POST['bar_id'];
    $tableNum = trim($_POST['table_number']);
    $zone = trim($_POST['zone']);
    $capacity = (int)$_POST['capacity'];

    if (!empty($tableNum) && !empty($zone)) {
        $addStmt = $db->prepare("INSERT INTO tables (bar_id, table_number, zone, capacity, status) VALUES (?, ?, ?, ?, 'available')");
        $addStmt->execute([$barId, $tableNum, $zone, $capacity]);
        $success = "เพิ่มโต๊ะใหม่ {$tableNum} สำเร็จ";
    }
}

$adminTitle = "จัดการการจัดโต๊ะและผังที่นั่ง";
require_once __DIR__ . '/includes/admin_header.php';

// Fetch All Bars
$bars = $db->query("SELECT * FROM bars WHERE is_active = 1 ORDER BY id ASC")->fetchAll();

// Fetch Tables for selected Bar
$tablesStmt = $db->prepare("SELECT t.*, 
                                  b.id AS active_booking_id, b.booking_code, b.user_id, b.status AS booking_status,
                                  u.name AS customer_name, u.phone AS customer_phone
                           FROM tables t
                           LEFT JOIN bookings b ON t.id = b.table_id 
                                AND b.booking_date = ? 
                                AND b.status IN ('pending_payment', 'confirmed', 'checked_in')
                           LEFT JOIN users u ON b.user_id = u.id
                           WHERE t.bar_id = ?
                           ORDER BY t.zone ASC, t.table_number ASC");
$tablesStmt->execute([$today, $selectedBarId]);
$tables = $tablesStmt->fetchAll();

// Fetch active bookings today for reassign dropdown
$activeBookingsTodayStmt = $db->prepare("SELECT b.id, b.booking_code, b.table_id, t.table_number, u.name AS user_name 
                                         FROM bookings b
                                         JOIN tables t ON b.table_id = t.id
                                         JOIN users u ON b.user_id = u.id
                                         WHERE b.bar_id = ? AND b.booking_date = ? AND b.status IN ('pending_payment', 'confirmed')");
$activeBookingsTodayStmt->execute([$selectedBarId, $today]);
$activeBookingsToday = $activeBookingsTodayStmt->fetchAll();

// Fetch available tables list for target reassign
$availTablesStmt = $db->prepare("SELECT id, table_number, zone FROM tables WHERE bar_id = ? AND status = 'available' ORDER BY zone, table_number");
$availTablesStmt->execute([$selectedBarId]);
$allBarTables = $availTablesStmt->fetchAll();
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8">
        <div>
            <h1 class="text-2xl sm:text-3xl font-bold text-white flex items-center gap-2">
                <i class="fa-solid fa-chair text-gold"></i> จัดการผังโต๊ะและแก้ไขที่นั่งลูกค้า
            </h1>
            <p class="text-xs sm:text-sm text-gray-400 mt-1">
                สลับโต๊ะให้ลูกค้า ปรับสถานะโต๊ะ และจัดการโซนที่นั่งในร้าน
            </p>
        </div>

        <!-- Bar Selector -->
        <div class="flex items-center gap-2">
            <span class="text-xs text-gray-400">เลือกร้าน:</span>
            <div class="flex gap-2">
                <?php foreach ($bars as $bar): ?>
                    <a href="tables.php?bar=<?php echo $bar['id']; ?>" 
                       class="px-3.5 py-1.5 rounded-xl text-xs font-semibold border transition-all <?php echo $bar['id'] == $selectedBarId ? 'bg-gold text-black border-gold' : 'bg-night-800 text-gray-300 border-gray-700 hover:border-gold'; ?>">
                        <?php echo htmlspecialchars($bar['name']); ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <?php if ($success || isset($_GET['msg'])): ?>
        <div class="bg-green-950/80 border border-green-500 text-green-300 text-xs p-3.5 rounded-xl mb-6 flex items-center gap-2">
            <i class="fa-solid fa-check-circle"></i> <?php echo htmlspecialchars($success ?: 'บันทึกการเปลี่ยนแปลงเรียบร้อยแล้ว'); ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="bg-red-950/80 border border-red-500 text-red-300 text-xs p-3.5 rounded-xl mb-6 flex items-center gap-2">
            <i class="fa-solid fa-triangle-exclamation"></i> <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <!-- Modal Box: Quick Reassign Table to Customer (ฟังก์ชันแก้ไขการจัดโต๊ะให้ลูกค้า) -->
    <div class="glass-card p-6 rounded-2xl border border-gold/50 mb-8 bg-gradient-to-r from-yellow-950/20 via-night-800 to-yellow-950/20">
        <h3 class="font-bold text-base text-gold mb-2 flex items-center gap-2">
            <i class="fa-solid fa-shuffle"></i> ย้าย / สลับการจัดโต๊ะให้ลูกค้า (Reassign Seating)
        </h3>
        <p class="text-xs text-gray-400 mb-4">
            กรณีลูกค้าขอเปลี่ยนโต๊ะ หรือต้องการอัปเกรดไปโซน VIP แอดมินสามารถเลือกรายการจองและระบุโต๊ะใหม่ได้ทันที
        </p>

        <?php if (empty($activeBookingsToday)): ?>
            <p class="text-xs text-gray-500 italic bg-night-900/60 p-3 rounded-lg border border-gray-800">
                ขณะนี้ไม่มีรายการจองที่รอดำเนินการในวันนี้ของร้านนี้
            </p>
        <?php else: ?>
            <form method="POST" class="grid grid-cols-1 sm:grid-cols-3 gap-4 items-end">
                <div>
                    <label class="block text-xs font-semibold text-gray-300 mb-1.5">เลือกลูกค้า / รายการจองวันนี้:</label>
                    <select name="booking_id" required class="w-full bg-night-900 border border-gray-700 rounded-xl px-3 py-2 text-xs text-white focus:outline-none border-gold-focus">
                        <option value="">-- เลือกลูกค้า --</option>
                        <?php foreach ($activeBookingsToday as $ab): ?>
                            <option value="<?php echo $ab['id']; ?>">
                                <?php echo htmlspecialchars($ab['user_name']); ?> (โต๊ะปัจจุบัน: <?php echo htmlspecialchars($ab['table_number']); ?>) - <?php echo $ab['booking_code']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-300 mb-1.5">เลือกโต๊ะใหม่ที่ต้องการย้ายไป:</label>
                    <select name="new_table_id" required class="w-full bg-night-900 border border-gray-700 rounded-xl px-3 py-2 text-xs text-white focus:outline-none border-gold-focus">
                        <option value="">-- เลือกโต๊ะใหม่ --</option>
                        <?php foreach ($allBarTables as $at): ?>
                            <option value="<?php echo $at['id']; ?>">
                                โต๊ะ <?php echo htmlspecialchars($at['table_number']); ?> (<?php echo htmlspecialchars($at['zone']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button type="submit" name="reassign_table" class="btn-gold py-2.5 px-4 rounded-xl text-xs font-bold shadow-lg">
                    <i class="fa-solid fa-arrows-rotate mr-1"></i> ยืนยันการย้ายโต๊ะให้ลูกค้า
                </button>
            </form>
        <?php endif; ?>
    </div>

    <!-- Table Grid Overview -->
    <div class="glass-card p-6 rounded-2xl border border-gray-800 mb-8">
        <div class="flex items-center justify-between mb-6">
            <h3 class="font-bold text-base text-white flex items-center gap-2">
                <i class="fa-solid fa-map-location-dot text-gold"></i> ผังโต๊ะและสถานะประจำวันนี้ (<?php echo formatThaiDate($today); ?>)
            </h3>
            <div class="flex items-center gap-4 text-xs">
                <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-night-800 border border-gold"></span> ว่าง</span>
                <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-red-950 border border-red-500"></span> มีลูกค้าจองแล้ว</span>
                <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-gray-800 border border-gray-600"></span> ปิดปรับปรุง</span>
            </div>
        </div>

        <div class="table-grid">
            <?php foreach ($tables as $t): ?>
                <?php 
                    $isOccupied = !empty($t['active_booking_id']);
                    $isMaint = $t['status'] === 'maintenance';
                ?>
                <div class="p-3.5 rounded-xl border text-center transition-all <?php 
                    if ($isOccupied) echo 'bg-red-950/40 border-red-500/60 text-red-200';
                    elseif ($isMaint) echo 'bg-gray-900 border-gray-700 text-gray-500';
                    else echo 'bg-night-800/80 border-gold/40 text-gold hover:border-gold';
                ?>">
                    <div class="font-bold text-sm mb-0.5"><?php echo htmlspecialchars($t['table_number']); ?></div>
                    <div class="text-[10px] text-gray-400 mb-2"><?php echo htmlspecialchars($t['zone']); ?> (<?php echo $t['capacity']; ?> ที่นั่ง)</div>

                    <?php if ($isOccupied): ?>
                        <div class="bg-red-900/60 text-red-300 py-1 px-2 rounded text-[10px] font-semibold mb-2">
                            👤 <?php echo htmlspecialchars($t['customer_name']); ?>
                            <div class="text-[9px] text-gray-300 font-mono"><?php echo htmlspecialchars($t['customer_phone']); ?></div>
                        </div>
                    <?php elseif ($isMaint): ?>
                        <div class="bg-gray-800 text-gray-400 py-1 px-2 rounded text-[10px] mb-2">
                            🛠️ ปิดปรับปรุง
                        </div>
                    <?php else: ?>
                        <div class="bg-gold/10 text-gold py-1 px-2 rounded text-[10px] font-semibold mb-2">
                            ✓ ว่างพร้อมจอง
                        </div>
                    <?php endif; ?>

                    <!-- Quick Status Toggle Link -->
                    <a href="tables.php?bar=<?php echo $selectedBarId; ?>&id=<?php echo $t['id']; ?>&toggle_status=<?php echo $t['status']; ?>" 
                       class="text-[10px] underline text-gray-400 hover:text-white block mt-1">
                        สลับสถานะ (<?php echo $t['status'] === 'available' ? 'ปิดซ่อม' : 'เปิดบริการ'; ?>)
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Add New Table Form -->
    <div class="glass-card p-6 rounded-2xl border border-gray-800">
        <h3 class="font-bold text-sm text-white mb-4 flex items-center gap-2">
            <i class="fa-solid fa-plus-circle text-gold"></i> เพิ่มโต๊ะใหม่ในร้าน
        </h3>
        <form method="POST" class="grid grid-cols-1 sm:grid-cols-4 gap-4 items-end">
            <input type="hidden" name="bar_id" value="<?php echo $selectedBarId; ?>">
            <div>
                <label class="block text-xs text-gray-400 mb-1">หมายเลขโต๊ะ</label>
                <input type="text" name="table_number" required placeholder="เช่น VIP-04, B-05" 
                       class="w-full bg-night-900 border border-gray-700 rounded-xl px-3 py-2 text-xs text-white focus:outline-none border-gold-focus">
            </div>
            <div>
                <label class="block text-xs text-gray-400 mb-1">โซนที่นั่ง</label>
                <input type="text" name="zone" required placeholder="เช่น VIP โซนพิเศษ, หน้าเวที" 
                       class="w-full bg-night-900 border border-gray-700 rounded-xl px-3 py-2 text-xs text-white focus:outline-none border-gold-focus">
            </div>
            <div>
                <label class="block text-xs text-gray-400 mb-1">จำนวนที่นั่งสูงสุด (ท่าน)</label>
                <input type="number" name="capacity" value="4" min="1" max="20" required 
                       class="w-full bg-night-900 border border-gray-700 rounded-xl px-3 py-2 text-xs text-white focus:outline-none border-gold-focus">
            </div>
            <button type="submit" name="add_table" class="btn-outline-gold py-2 rounded-xl text-xs font-semibold">
                + บันทึกโต๊ะใหม่
            </button>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
