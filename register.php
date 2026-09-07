<?php
/**
 * Register Page - Pub & Bar Booking System
 */
require_once __DIR__ . '/config/db.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');

    if (empty($name) || empty($email) || empty($password)) {
        $error = 'กรุณากรอกข้อมูลให้ครบถ้วน';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'รูปแบบอีเมลไม่ถูกต้อง';
    } elseif ($password !== $confirm_password) {
        $error = 'รหัสผ่านทั้งสองช่องไม่ตรงกัน';
    } elseif (strlen($password) < 4) {
        $error = 'รหัสผ่านต้องมีความยาวอย่างน้อย 4 ตัวอักษร';
    } else {
        $db = getDb();
        // Check if email already exists
        $checkStmt = $db->prepare("SELECT id FROM users WHERE email = ?");
        $checkStmt->execute([$email]);
        if ($checkStmt->fetch()) {
            $error = 'อีเมลนี้ถูกใช้งานในระบบแล้ว กรุณาใช้อีเมลอื่น';
        } else {
            $hashedPass = password_hash($password, PASSWORD_DEFAULT);
            $initialPoints = 50; // New member bonus points

            $insertStmt = $db->prepare("INSERT INTO users (name, email, phone, password, role, points) VALUES (?, ?, ?, ?, 'user', ?)");
            if ($insertStmt->execute([$name, $email, $phone, $hashedPass, $initialPoints])) {
                $newUserId = $db->lastInsertId();
                
                // Record points history
                $ptStmt = $db->prepare("INSERT INTO points_history (user_id, points_change, description) VALUES (?, ?, ?)");
                $ptStmt->execute([$newUserId, $initialPoints, 'โบนัสแต้มต้อนรับสมาชิกใหม่']);

                header('Location: login.php?msg=registered');
                exit;
            } else {
                $error = 'เกิดข้อผิดพลาดในการสมัครสมาชิก กรุณาลองใหม่อีกครั้ง';
            }
        }
    }
}

$pageTitle = "สมัครสมาชิก";
require_once __DIR__ . '/includes/header.php';
?>

<div class="min-h-[80vh] flex items-center justify-center px-4 py-12">
    <div class="max-w-md w-full glass-card p-8 rounded-2xl border border-gold/40 shadow-2xl relative">
        <div class="text-center mb-6">
            <div class="w-16 h-16 mx-auto mb-3 rounded-2xl bg-gradient-to-br from-gold/20 to-yellow-950/40 border border-gold/50 flex items-center justify-center">
                <i class="fa-solid fa-user-plus text-gold text-2xl"></i>
            </div>
            <h1 class="text-2xl font-bold text-white">สมัครสมาชิกใหม่</h1>
            <p class="text-xs text-amber-300/90 mt-1 flex items-center justify-center gap-1">
                <i class="fa-solid fa-gift"></i> สมัครวันนี้รับฟรีทันที 50 แต้มสะสม
            </p>
        </div>

        <?php if ($error): ?>
            <div class="bg-red-950/80 border border-red-500/60 text-red-300 text-xs p-3.5 rounded-xl mb-4 flex items-center gap-2">
                <i class="fa-solid fa-circle-exclamation shrink-0 text-base"></i>
                <span><?php echo htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-4">
            <div>
                <label class="block text-xs font-medium text-gray-300 mb-1.5">ชื่อ-นามสกุล / ชื่อเล่น</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-gray-400 text-sm">
                        <i class="fa-solid fa-user"></i>
                    </span>
                    <input type="text" name="name" required 
                           class="w-full pl-10 pr-4 py-2.5 bg-night-900 border border-gray-700 rounded-xl text-white text-sm focus:outline-none border-gold-focus" 
                           placeholder="เช่น ปาร์ตี้ สุขสันต์"
                           value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
                </div>
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-300 mb-1.5">อีเมล (Email)</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-gray-400 text-sm">
                        <i class="fa-solid fa-envelope"></i>
                    </span>
                    <input type="email" name="email" required 
                           class="w-full pl-10 pr-4 py-2.5 bg-night-900 border border-gray-700 rounded-xl text-white text-sm focus:outline-none border-gold-focus" 
                           placeholder="yourname@gmail.com"
                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                </div>
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-300 mb-1.5">เบอร์โทรศัพท์ติดต่อ</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-gray-400 text-sm">
                        <i class="fa-solid fa-phone"></i>
                    </span>
                    <input type="tel" name="phone" required 
                           class="w-full pl-10 pr-4 py-2.5 bg-night-900 border border-gray-700 rounded-xl text-white text-sm focus:outline-none border-gold-focus" 
                           placeholder="08X-XXX-XXXX"
                           value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-medium text-gray-300 mb-1.5">รหัสผ่าน</label>
                    <input type="password" name="password" required 
                           class="w-full px-3.5 py-2.5 bg-night-900 border border-gray-700 rounded-xl text-white text-sm focus:outline-none border-gold-focus" 
                           placeholder="••••••••">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-300 mb-1.5">ยืนยันรหัสผ่าน</label>
                    <input type="password" name="confirm_password" required 
                           class="w-full px-3.5 py-2.5 bg-night-900 border border-gray-700 rounded-xl text-white text-sm focus:outline-none border-gold-focus" 
                           placeholder="••••••••">
                </div>
            </div>

            <button type="submit" class="w-full btn-gold py-3 rounded-xl text-sm font-semibold shadow-lg mt-3">
                <i class="fa-solid fa-user-check mr-1.5"></i> สมัครสมาชิก
            </button>
        </form>

        <div class="mt-6 text-center text-xs text-gray-400 pt-4 border-t border-gray-800">
            มีบัญชีผู้ใช้งานแล้ว? 
            <a href="login.php" class="text-gold hover:underline font-medium">เข้าสู่ระบบ</a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
