<?php
/**
 * Admin Promotions Management
 * Pub & Bar Booking System
 */
$adminTitle = "จัดการโปรโมชั่นเครื่องดื่ม";
require_once __DIR__ . '/includes/admin_header.php';

$db = getDb();
$msg = '';
$error = '';

// Handle Add Promotion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_promo'])) {
    $title = trim($_POST['title'] ?? '');
    $desc = trim($_POST['description'] ?? '');
    $price = (float)($_POST['price'] ?? 0);
    $points = (int)($_POST['points_reward'] ?? 10);
    $badge = trim($_POST['badge'] ?? 'HOT');
    $image = trim($_POST['image'] ?? 'assets/images/promotions/chang.jpg');

    if (!empty($title) && $price > 0) {
        $stmt = $db->prepare("INSERT INTO promotions (title, description, price, points_reward, badge, image, is_active) VALUES (?, ?, ?, ?, ?, ?, 1)");
        $stmt->execute([$title, $desc, $price, $points, $badge, $image]);
        $msg = 'เพิ่มโปรโมชั่นใหม่สำเร็จ';
    }
}

// Handle Update Promotion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_promo'])) {
    $pid = (int)$_POST['promo_id'];
    $title = trim($_POST['title']);
    $desc = trim($_POST['description']);
    $price = (float)$_POST['price'];
    $points = (int)$_POST['points_reward'];
    $badge = trim($_POST['badge']);
    $isActive = isset($_POST['is_active']) ? 1 : 0;

    $stmt = $db->prepare("UPDATE promotions SET title = ?, description = ?, price = ?, points_reward = ?, badge = ?, is_active = ? WHERE id = ?");
    $stmt->execute([$title, $desc, $price, $points, $badge, $isActive, $pid]);
    $msg = 'อัปเดตข้อมูลโปรโมชั่นเรียบร้อยแล้ว';
}

// Fetch all promotions
$promotions = $db->query("SELECT * FROM promotions ORDER BY id ASC")->fetchAll();
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-8">
        <h1 class="text-2xl sm:text-3xl font-bold text-white flex items-center gap-2">
            <i class="fa-solid fa-tags text-gold"></i> จัดการโปรโมชั่นเปิดโต๊ะ
        </h1>
        <p class="text-xs sm:text-sm text-gray-400 mt-1">
            กำหนดราคา โปรเบียร์ช้าง 199.- แสงโสม 599.- และแต้มสะสมที่จะมอบให้ลูกค้า
        </p>
    </div>

    <?php if ($msg): ?>
        <div class="bg-green-950/80 border border-green-500 text-green-300 text-xs p-3.5 rounded-xl mb-6 flex items-center gap-2">
            <i class="fa-solid fa-check-circle"></i> <?php echo htmlspecialchars($msg); ?>
        </div>
    <?php endif; ?>

    <!-- Current Promotions Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-12">
        <?php foreach ($promotions as $promo): ?>
            <div class="glass-card rounded-2xl border border-gray-800 p-6 flex flex-col justify-between">
                <div>
                    <div class="flex items-start gap-4 mb-4">
                        <img src="../<?php echo htmlspecialchars($promo['image']); ?>" alt="" class="w-24 h-24 rounded-xl object-cover border border-gold/40 shrink-0">
                        <div>
                            <span class="inline-block px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-red-950 text-red-300 border border-red-500/50 mb-1">
                                <?php echo htmlspecialchars($promo['badge']); ?>
                            </span>
                            <h3 class="font-bold text-base text-white"><?php echo htmlspecialchars($promo['title']); ?></h3>
                            <p class="text-xs text-gray-400 mt-1 line-clamp-2"><?php echo htmlspecialchars($promo['description']); ?></p>
                        </div>
                    </div>

                    <form method="POST" class="space-y-3 pt-3 border-t border-gray-800 text-xs">
                        <input type="hidden" name="promo_id" value="<?php echo $promo['id']; ?>">
                        
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-gray-400 mb-1">ชื่อโปรโมชั่น</label>
                                <input type="text" name="title" value="<?php echo htmlspecialchars($promo['title']); ?>" required 
                                       class="w-full bg-night-900 border border-gray-700 rounded-lg px-2.5 py-1.5 text-white">
                            </div>
                            <div>
                                <label class="block text-gray-400 mb-1">ป้ายกำกับ (Badge)</label>
                                <input type="text" name="badge" value="<?php echo htmlspecialchars($promo['badge']); ?>" 
                                       class="w-full bg-night-900 border border-gray-700 rounded-lg px-2.5 py-1.5 text-white">
                            </div>
                        </div>

                        <div>
                            <label class="block text-gray-400 mb-1">คำอธิบาย</label>
                            <textarea name="description" rows="2" class="w-full bg-night-900 border border-gray-700 rounded-lg px-2.5 py-1.5 text-white"><?php echo htmlspecialchars($promo['description']); ?></textarea>
                        </div>

                        <div class="grid grid-cols-3 gap-3 items-end">
                            <div>
                                <label class="block text-gray-400 mb-1">ราคา (บาท)</label>
                                <input type="number" step="0.01" name="price" value="<?php echo $promo['price']; ?>" required 
                                       class="w-full bg-night-900 border border-gray-700 rounded-lg px-2.5 py-1.5 text-gold font-bold">
                            </div>
                            <div>
                                <label class="block text-gray-400 mb-1">แต้มสะสมที่ได้</label>
                                <input type="number" name="points_reward" value="<?php echo $promo['points_reward']; ?>" required 
                                       class="w-full bg-night-900 border border-gray-700 rounded-lg px-2.5 py-1.5 text-white">
                            </div>
                            <div class="flex items-center gap-2 pb-2">
                                <label class="flex items-center gap-1.5 text-gray-300 cursor-pointer">
                                    <input type="checkbox" name="is_active" value="1" <?php echo $promo['is_active'] ? 'checked' : ''; ?> class="accent-gold">
                                    <span>เปิดใช้งาน</span>
                                </label>
                            </div>
                        </div>

                        <button type="submit" name="update_promo" class="w-full btn-outline-gold py-2 rounded-xl text-xs font-semibold mt-2">
                            บันทึกการแก้ไขโปรนี้
                        </button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Add New Promo Box -->
    <div class="glass-card rounded-2xl border border-gray-800 p-6">
        <h3 class="font-bold text-sm text-white mb-4 flex items-center gap-2">
            <i class="fa-solid fa-plus-circle text-gold"></i> เพิ่มโปรโมชั่นใหม่
        </h3>
        <form method="POST" class="space-y-4 text-xs">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-gray-400 mb-1">ชื่อโปรโมชั่น</label>
                    <input type="text" name="title" required placeholder="เช่น เซ็ตแสงโสม 1 กลม + มิกเซอร์" 
                           class="w-full bg-night-900 border border-gray-700 rounded-xl px-3 py-2 text-white focus:outline-none border-gold-focus">
                </div>
                <div>
                    <label class="block text-gray-400 mb-1">ป้ายกำกับ (Badge)</label>
                    <input type="text" name="badge" placeholder="เช่น HOT, NEW 2026" value="SPECIAL DEAL" 
                           class="w-full bg-night-900 border border-gray-700 rounded-xl px-3 py-2 text-white focus:outline-none border-gold-focus">
                </div>
            </div>

            <div>
                <label class="block text-gray-400 mb-1">คำอธิบายรายละเอียดโปรโมชั่น</label>
                <textarea name="description" rows="2" placeholder="ระบุรายการเครื่องดื่ม เช่น เบียร์, มิกเซอร์, ถังน้ำแข็ง" 
                          class="w-full bg-night-900 border border-gray-700 rounded-xl px-3 py-2 text-white focus:outline-none border-gold-focus"></textarea>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 items-end">
                <div>
                    <label class="block text-gray-400 mb-1">ราคา (บาท)</label>
                    <input type="number" step="0.01" name="price" placeholder="199.00" required 
                           class="w-full bg-night-900 border border-gray-700 rounded-xl px-3 py-2 text-white focus:outline-none border-gold-focus">
                </div>
                <div>
                    <label class="block text-gray-400 mb-1">แต้มสะสมที่จะได้รับ</label>
                    <input type="number" name="points_reward" value="20" required 
                           class="w-full bg-night-900 border border-gray-700 rounded-xl px-3 py-2 text-white focus:outline-none border-gold-focus">
                </div>
                <button type="submit" name="add_promo" class="btn-gold py-2.5 rounded-xl font-bold">
                    + บันทึกโปรโมชั่นใหม่
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
