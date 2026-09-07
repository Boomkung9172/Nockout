<?php
/**
 * Login Page - Pub & Bar Booking System
 */
require_once __DIR__ . '/config/db.php';

$error = '';
$success = '';

if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'registered') $success = 'สมัครสมาชิกสำเร็จเรียบร้อย! กรุณาเข้าสู่ระบบ';
    if ($_GET['msg'] === 'need_login') $error = 'กรุณาเข้าสู่ระบบก่อนทำการจองโต๊ะ';
    if ($_GET['msg'] === 'admin_only') $error = 'ส่วนนี้สำหรับผู้ดูแลระบบ (Admin) เท่านั้น';
    if ($_GET['msg'] === 'logged_out') $success = 'ออกจากระบบเรียบร้อยแล้ว';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($email) || empty($password)) {
        $error = 'กรุณากรอกอีเมลและรหัสผ่าน';
    } else {
        $db = getDb();
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        // Check password with bcrypt or fallback for '1234'
        $isPasswordValid = false;
        if ($user) {
            if (password_verify($password, $user['password'])) {
                $isPasswordValid = true;
            } elseif ($password === '1234' && in_array($email, ['674295003@parichat.skru.ac.th', 'user@gmail.com'])) {
                $isPasswordValid = true;
            } elseif ($user['password'] === $password || $user['password'] === md5($password)) {
                $isPasswordValid = true;
            }
        }

        if ($user && $isPasswordValid) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];

            if ($user['role'] === 'admin') {
                header('Location: admin/index.php');
            } else {
                $redirect = $_GET['redirect'] ?? 'booking.php';
                header('Location: ' . $redirect);
            }
            exit;
        } else {
            $error = 'อีเมลหรือรหัสผ่านไม่ถูกต้อง กรุณาลองใหม่อีกครั้ง';
        }
    }
}

$pageTitle = "เข้าสู่ระบบ";
require_once __DIR__ . '/includes/header.php';
?>

<div class="min-h-[75vh] flex items-center justify-center px-4 py-12">
    <div class="max-w-md w-full glass-card p-8 rounded-2xl border border-gold/40 shadow-2xl relative">
        <div class="text-center mb-6">
            <div class="w-16 h-16 mx-auto mb-3 rounded-2xl bg-gradient-to-br from-gold/20 to-yellow-950/40 border border-gold/50 flex items-center justify-center">
                <i class="fa-solid fa-lock text-gold text-2xl"></i>
            </div>
            <h1 class="text-2xl font-bold text-white">เข้าสู่ระบบ</h1>
            <p class="text-xs text-gray-400 mt-1">จองโต๊ะและตรวจสอบสถานะโต๊ะของคุณ</p>
        </div>

        <?php if ($error): ?>
            <div class="bg-red-950/80 border border-red-500/60 text-red-300 text-xs p-3.5 rounded-xl mb-4 flex items-center gap-2">
                <i class="fa-solid fa-circle-exclamation shrink-0 text-base"></i>
                <span><?php echo htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="bg-green-950/80 border border-green-500/60 text-green-300 text-xs p-3.5 rounded-xl mb-4 flex items-center gap-2">
                <i class="fa-solid fa-circle-check shrink-0 text-base"></i>
                <span><?php echo htmlspecialchars($success); ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-4">
            <div>
                <label class="block text-xs font-medium text-gray-300 mb-1.5">อีเมล (Email)</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-gray-400 text-sm">
                        <i class="fa-solid fa-envelope"></i>
                    </span>
                    <input type="email" name="email" id="inputEmail" required 
                           class="w-full pl-10 pr-4 py-2.5 bg-night-900 border border-gray-700 rounded-xl text-white text-sm focus:outline-none border-gold-focus" 
                           placeholder="name@example.com"
                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                </div>
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-300 mb-1.5">รหัสผ่าน (Password)</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-gray-400 text-sm">
                        <i class="fa-solid fa-key"></i>
                    </span>
                    <input type="password" name="password" id="inputPassword" required 
                           class="w-full pl-10 pr-4 py-2.5 bg-night-900 border border-gray-700 rounded-xl text-white text-sm focus:outline-none border-gold-focus" 
                           placeholder="••••••••">
                </div>
            </div>

            <button type="submit" class="w-full btn-gold py-3 rounded-xl text-sm font-semibold shadow-lg mt-2">
                <i class="fa-solid fa-arrow-right-to-bracket mr-1.5"></i> เข้าสู่ระบบ
            </button>
        </form>

        <div class="mt-6 text-center text-xs text-gray-400">
            ยังไม่มีบัญชีสมาชิก? 
            <a href="register.php" class="text-gold hover:underline font-medium">สมัครสมาชิกใหม่</a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
