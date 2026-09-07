<?php
/**
 * PromptPay Payment Page - Pub & Bar Booking System
 */
require_once __DIR__ . '/config/db.php';
requireLogin();

$db = getDb();
$currentUser = currentUser();
$bookingCode = trim($_GET['code'] ?? '');

if (empty($bookingCode)) {
    header('Location: my-bookings.php');
    exit;
}

// Fetch Booking details with Bar and Table info
$stmt = $db->prepare("SELECT b.*, 
                             r.name AS bar_name, r.promptpay_number, r.promptpay_name, r.phone AS bar_phone,
                             t.table_number, t.zone,
                             p.title AS promo_title
                      FROM bookings b
                      JOIN bars r ON b.bar_id = r.id
                      JOIN tables t ON b.table_id = t.id
                      LEFT JOIN promotions p ON b.promotion_id = p.id
                      WHERE b.booking_code = ? AND b.user_id = ?");
$stmt->execute([$bookingCode, $currentUser['id']]);
$booking = $stmt->fetch();

if (!$booking) {
    header('Location: my-bookings.php?msg=not_found');
    exit;
}

$error = '';
$success = '';

// Handle Slip Upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_slip'])) {
    if (isset($_FILES['slip_image']) && $_FILES['slip_image']['error'] === UPLOAD_ERR_OK) {
        $fileTmp = $_FILES['slip_image']['tmp_name'];
        $fileName = $_FILES['slip_image']['name'];
        $fileSize = $_FILES['slip_image']['size'];
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        $allowedExts = ['jpg', 'jpeg', 'png', 'webp'];
        if (!in_array($ext, $allowedExts)) {
            $error = 'กรุณาอัปโหลดไฟล์รูปภาพสลิปเท่านั้น (JPG, PNG, WEBP)';
        } elseif ($fileSize > 5 * 1024 * 1024) {
            $error = 'ขนาดไฟล์ต้องไม่เกิน 5 MB';
        } else {
            $uploadDir = __DIR__ . '/uploads/slips/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $newFileName = 'slip_' . $booking['booking_code'] . '_' . time() . '.' . $ext;
            $destination = $uploadDir . $newFileName;

            if (move_uploaded_file($fileTmp, $destination)) {
                $relativeFilePath = 'uploads/slips/' . $newFileName;

                // Update booking status to confirmed & save slip
                $upStmt = $db->prepare("UPDATE bookings SET payment_slip = ?, status = 'confirmed' WHERE id = ?");
                $upStmt->execute([$relativeFilePath, $booking['id']]);

                // Award Loyalty Points to user!
                $pointsAwarded = (int)$booking['points_earned'];
                if ($pointsAwarded > 0) {
                    $userUp = $db->prepare("UPDATE users SET points = points + ? WHERE id = ?");
                    $userUp->execute([$pointsAwarded, $currentUser['id']]);

                    $historyStmt = $db->prepare("INSERT INTO points_history (user_id, booking_id, points_change, description) VALUES (?, ?, ?, ?)");
                    $historyStmt->execute([
                        $currentUser['id'],
                        $booking['id'],
                        $pointsAwarded,
                        'ได้รับแต้มจากการจองโต๊ะ ' . $booking['table_number'] . ' (' . $booking['bar_name'] . ')'
                    ]);
                }

                header("Location: my-bookings.php?msg=paid&code=" . $bookingCode);
                exit;
            } else {
                $error = 'เกิดข้อผิดพลาดในการบันทึกไฟล์สลิป';
            }
        }
    } else {
        $error = 'กรุณาเลือกไฟล์สลิปหลักฐานการโอนเงิน';
    }
}

// Generate PromptPay QR code string / standard format
$ppNumber = preg_replace('/[^0-9]/', '', $booking['promptpay_number'] ?? '0812345678');
$amount = number_format($booking['total_amount'], 2, '.', '');
// Standard promptpay QR generator URL using standard QR API
$qrData = "https://promptpay.io/{$ppNumber}/{$amount}.png";

$pageTitle = "ชำระเงิน PromptPay - " . $booking['booking_code'];
require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <div class="text-center mb-8">
        <span class="text-gold text-xs font-bold uppercase tracking-widest">
            <i class="fa-solid fa-shield-halved"></i> SECURE PAYMENT
        </span>
        <h1 class="text-3xl font-extrabold text-white mt-1">ชำระเงินผ่าน <span class="text-gold-gradient">PromptPay</span></h1>
        <p class="text-xs text-gray-400 mt-1">สแกนจ่ายเพื่อยืนยันการจองโต๊ะและรับแต้มสะสมทันที</p>
    </div>

    <?php if ($error): ?>
        <div class="bg-red-950/80 border border-red-500/80 text-red-200 text-sm p-4 rounded-2xl mb-6 flex items-center gap-3">
            <i class="fa-solid fa-triangle-exclamation text-xl text-red-400 shrink-0"></i>
            <span><?php echo htmlspecialchars($error); ?></span>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
        <!-- Col 1: PromptPay QR Code Display -->
        <div class="glass-card p-6 sm:p-8 rounded-3xl border border-gold/40 text-center flex flex-col items-center justify-between">
            <div class="w-full">
                <!-- PromptPay Header Brand -->
                <div class="bg-white px-6 py-2.5 rounded-xl inline-flex items-center gap-2 mb-6 shadow-md">
                    <span class="text-[#003b64] font-black text-lg tracking-wider">พร้อมเพย์</span>
                    <span class="text-gray-400 text-xs font-bold">PromptPay</span>
                </div>

                <!-- QR Code Box with Gold Border -->
                <div class="p-4 bg-white rounded-2xl shadow-2xl inline-block border-2 border-gold/60 mb-5 relative group">
                    <img src="<?php echo htmlspecialchars($qrData); ?>" 
                         alt="PromptPay QR Code" 
                         class="w-56 h-56 object-contain mx-auto">
                    <div class="text-[10px] text-gray-500 mt-1 font-mono">
                        PromptPay Official QR
                    </div>
                </div>

                <div class="text-center space-y-1">
                    <p class="text-xs text-gray-400">ชื่อบัญชี: <strong class="text-white"><?php echo htmlspecialchars($booking['promptpay_name']); ?></strong></p>
                    <p class="text-sm font-bold text-yellow-400 font-mono tracking-widest"><?php echo htmlspecialchars($booking['promptpay_number']); ?></p>
                </div>
            </div>

            <div class="w-full mt-6 pt-4 border-t border-gray-800">
                <div class="flex justify-between items-baseline">
                    <span class="text-xs text-gray-400">ยอดที่ต้องชำระ:</span>
                    <span class="text-3xl font-extrabold text-gold"><?php echo number_format($booking['total_amount'], 2); ?> <span class="text-xs text-gray-300 font-normal">บาท</span></span>
                </div>
            </div>
        </div>

        <!-- Col 2: Booking Summary & Upload Slip Form -->
        <div class="space-y-6">
            <!-- Reservation Info Card -->
            <div class="glass-card p-6 rounded-2xl border border-gray-800 space-y-3 text-xs">
                <div class="flex items-center justify-between border-b border-gray-800 pb-3">
                    <span class="text-gray-400">รหัสการจอง:</span>
                    <span class="font-mono font-bold text-gold text-sm"><?php echo htmlspecialchars($booking['booking_code']); ?></span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-gray-400">ร้าน:</span>
                    <span class="font-bold text-white"><?php echo htmlspecialchars($booking['bar_name']); ?></span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-gray-400">โต๊ะที่เลือก:</span>
                    <span class="font-bold text-yellow-400"><?php echo htmlspecialchars($booking['table_number']); ?> (<?php echo htmlspecialchars($booking['zone']); ?>)</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-gray-400">วันที่จอง:</span>
                    <span class="text-white font-medium"><?php echo formatThaiDate($booking['booking_date']); ?></span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-gray-400">เวลานัดหมาย:</span>
                    <span class="text-yellow-400 font-bold"><?php echo substr($booking['arrival_time'], 0, 5); ?> น. (ต้องมาก่อน 20:00 น.)</span>
                </div>
                <?php if (!empty($booking['promo_title'])): ?>
                    <div class="flex items-center justify-between border-t border-gray-800 pt-2 text-amber-300">
                        <span>โปรโมชั่นเปิดโต๊ะ:</span>
                        <span class="font-semibold text-right max-w-[200px] truncate"><?php echo htmlspecialchars($booking['promo_title']); ?></span>
                    </div>
                <?php endif; ?>
                <div class="flex items-center justify-between border-t border-gray-800 pt-2 text-green-400">
                    <span>แต้มสะสมที่จะได้รับ:</span>
                    <span class="font-bold">+<?php echo number_format($booking['points_earned']); ?> แต้ม</span>
                </div>
            </div>

            <!-- Upload Slip Form -->
            <div class="glass-card p-6 rounded-2xl border border-gold/40">
                <h3 class="font-bold text-sm text-white mb-3 flex items-center gap-2">
                    <i class="fa-solid fa-cloud-arrow-up text-gold"></i> แนบสลิปหลักฐานการโอนเงิน
                </h3>
                
                <form method="POST" enctype="multipart/form-data" class="space-y-4">
                    <div>
                        <label class="block text-xs text-gray-400 mb-2">เลือกรูปภาพสลิปจากมือถือหรือคอมพิวเตอร์:</label>
                        <input type="file" name="slip_image" id="slipInput" required accept="image/*"
                               class="w-full text-xs text-gray-400 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-gold file:text-black hover:file:bg-yellow-400 cursor-pointer bg-night-900 border border-gray-700 rounded-xl p-2">
                    </div>

                    <!-- Slip Preview Box -->
                    <div id="slipPreviewContainer" class="hidden p-3 rounded-xl bg-night-900 border border-gold/30 text-center">
                        <p class="text-[11px] text-gray-400 mb-2">ตัวอย่างสลิปที่เลือก:</p>
                        <img id="slipPreview" src="" alt="Slip Preview" class="max-h-48 mx-auto rounded-lg shadow">
                    </div>

                    <button type="submit" name="submit_slip" class="w-full btn-gold py-3.5 rounded-xl text-sm font-bold shadow-lg flex items-center justify-center gap-2">
                        <i class="fa-solid fa-check-circle"></i> ยืนยันการชำระเงิน & รับแต้ม
                    </button>
                </form>

                <p class="text-[11px] text-gray-400 text-center mt-3">
                    หากมีข้อสงสัยหรือต้องการความช่วยเหลือ โทร <a href="tel:<?php echo htmlspecialchars($booking['bar_phone']); ?>" class="text-gold underline"><?php echo htmlspecialchars($booking['bar_phone']); ?></a>
                </p>
            </div>
        </div>
    </div>
</div>

<script>
    const slipInput = document.getElementById('slipInput');
    const previewContainer = document.getElementById('slipPreviewContainer');
    const slipPreview = document.getElementById('slipPreview');

    if (slipInput) {
        slipInput.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    slipPreview.src = e.target.result;
                    previewContainer.classList.remove('hidden');
                };
                reader.readAsDataURL(file);
            } else {
                previewContainer.classList.add('hidden');
            }
        });
    }
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
