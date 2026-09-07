<?php
/**
 * Contact Admin Page - Pub & Bar Booking System
 * Interactive 2-way Support & Conversation Chat System
 */
require_once __DIR__ . '/config/db.php';

$db = getDb();
ensureRepliesTable($db);
$currentUser = currentUser();

$success = '';
$error = '';

// Handle New Topic Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    $userId = $currentUser ? $currentUser['id'] : null;

    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        $error = 'กรุณากรอกข้อมูลและข้อความให้ครบถ้วน';
    } else {
        $stmt = $db->prepare("INSERT INTO contact_messages (user_id, name, email, phone, subject, message, status) VALUES (?, ?, ?, ?, ?, ?, 'unread')");
        if ($stmt->execute([$userId, $name, $email, $phone, $subject, $message])) {
            $success = 'ส่งข้อความถึงแอดมินเรียบร้อยแล้ว แอดมินจะติดต่อกลับโดยเร็วที่สุด';
        } else {
            $error = 'เกิดข้อผิดพลาดในการส่งข้อความ';
        }
    }
}

// Handle User Replying to an Existing Conversation (ผู้ใช้ส่งข้อความตอบกลับแอดมิน)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_user_reply'])) {
    $msgId = (int)$_POST['message_id'];
    $replyText = trim($_POST['reply_text'] ?? '');
    $senderName = $currentUser ? $currentUser['name'] : 'ผู้ใช้งาน';

    if (empty($replyText)) {
        $error = 'กรุณากรอกข้อความตอบกลับ';
    } else {
        // Insert reply into contact_replies
        $insStmt = $db->prepare("INSERT INTO contact_replies (message_id, sender_role, sender_name, message) VALUES (?, 'user', ?, ?)");
        if ($insStmt->execute([$msgId, $senderName, $replyText])) {
            // Update parent status to 'unread' so admin is notified
            $uStmt = $db->prepare("UPDATE contact_messages SET status = 'unread' WHERE id = ?");
            $uStmt->execute([$msgId]);
            $success = 'ส่งข้อความตอบกลับไปยังแอดมินเรียบร้อยแล้ว';
        } else {
            $error = 'ไม่สามารถส่งข้อความตอบกลับได้';
        }
    }
}

// Fetch conversations for this user
$myMessages = [];
if ($currentUser) {
    $msgStmt = $db->prepare("SELECT * FROM contact_messages WHERE user_id = ? OR email = ? ORDER BY created_at DESC");
    $msgStmt->execute([$currentUser['id'], $currentUser['email']]);
    $myMessages = $msgStmt->fetchAll();

    // Fetch replies for each conversation
    foreach ($myMessages as &$msgItem) {
        $repStmt = $db->prepare("SELECT * FROM contact_replies WHERE message_id = ? ORDER BY created_at ASC");
        $repStmt->execute([$msgItem['id']]);
        $msgItem['replies'] = $repStmt->fetchAll();
    }
    unset($msgItem);
}

$pageTitle = "ติดต่อแอดมิน & แชทพูดคุย";
require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <div class="text-center max-w-2xl mx-auto mb-10">
        <span class="text-gold text-xs font-bold uppercase tracking-widest">
            <i class="fa-solid fa-comments"></i> 24/7 CUSTOMER SUPPORT & LIVE INQUIRY
        </span>
        <h1 class="text-3xl font-extrabold text-white mt-1">
            ติดต่อแอดมิน & <span class="text-gold-gradient">กล่องสนทนาโต้ตอบ</span>
        </h1>
        <p class="text-xs sm:text-sm text-gray-400 mt-2">
            สอบถามการจองโต๊ะ เปลี่ยนแปลงเวลา หรือสนทนาตอบโต้กับแอดมินร้านได้โดยตรง
        </p>
    </div>

    <?php if ($success): ?>
        <div class="bg-green-950/80 border border-green-500/80 text-green-200 text-sm p-4 rounded-2xl mb-8 flex items-center gap-3 shadow-lg">
            <i class="fa-solid fa-circle-check text-xl text-green-400 shrink-0"></i>
            <span><?php echo htmlspecialchars($success); ?></span>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="bg-red-950/80 border border-red-500/80 text-red-200 text-sm p-4 rounded-2xl mb-8 flex items-center gap-3 shadow-lg">
            <i class="fa-solid fa-triangle-exclamation text-xl text-red-400 shrink-0"></i>
            <span><?php echo htmlspecialchars($error); ?></span>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Col 1: Contact Channels & Info -->
        <div class="space-y-6">
            <div class="glass-card p-6 rounded-2xl border border-gold/30 space-y-4">
                <h3 class="font-bold text-sm text-gold uppercase tracking-wider flex items-center gap-2">
                    <i class="fa-solid fa-address-book"></i> ช่องทางติดต่อด่วน
                </h3>

                <div class="space-y-3 text-xs">
                    <a href="tel:0812345678" class="flex items-center gap-3 p-3 bg-night-900 rounded-xl border border-gray-800 hover:border-gold/50 transition-all">
                        <div class="w-9 h-9 rounded-lg bg-yellow-950/60 border border-gold/40 flex items-center justify-center text-gold">
                            <i class="fa-solid fa-phone"></i>
                        </div>
                        <div>
                            <div class="text-gray-400 text-[10px]">สายด่วนจองโต๊ะ (24 ชม.)</div>
                            <div class="text-white font-bold font-mono">081-234-5678 / 074-123456</div>
                        </div>
                    </a>

                    <a href="https://line.me" target="_blank" class="flex items-center gap-3 p-3 bg-night-900 rounded-xl border border-gray-800 hover:border-green-500/50 transition-all">
                        <div class="w-9 h-9 rounded-lg bg-green-950/60 border border-green-500/40 flex items-center justify-center text-green-400">
                            <i class="fa-brands fa-line text-lg"></i>
                        </div>
                        <div>
                            <div class="text-gray-400 text-[10px]">LINE Official Account</div>
                            <div class="text-white font-bold">@nightlife_sk</div>
                        </div>
                    </a>

                    <div class="flex items-center gap-3 p-3 bg-night-900 rounded-xl border border-gray-800">
                        <div class="w-9 h-9 rounded-lg bg-gold/10 border border-gold/30 flex items-center justify-center text-gold">
                            <i class="fa-solid fa-envelope"></i>
                        </div>
                        <div>
                            <div class="text-gray-400 text-[10px]">อีเมลแอดมิน</div>
                            <div class="text-white font-bold">674295003@parichat.skru.ac.th</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Operating Pubs Info -->
            <div class="glass-card p-6 rounded-2xl border border-gray-800 space-y-3 text-xs text-gray-400">
                <h4 class="font-bold text-white text-sm mb-2">ที่ตั้งสาขาในอำเภอเมืองสงขลา</h4>
                <div class="border-b border-gray-800 pb-2">
                    <p class="font-semibold text-gold">1. Nockout Pub & Music Bar</p>
                    <p class="text-[11px]">ถ.ไทรบุรี ต.บ่อยาง อ.เมืองสงขลา</p>
                </div>
                <div>
                    <p class="font-semibold text-gold">2. Fullmoon Club Songkhla</p>
                    <p class="text-[11px]">ถ.ชลาทัศน์ (เลียบหาดชลาทัศน์) อ.เมืองสงขลา</p>
                </div>
            </div>
        </div>

        <!-- Col 2 & 3: Chat History Threads & New Inquiry Form -->
        <div class="lg:col-span-2 space-y-8">
            <!-- 1. Customer Interactive Conversation Threads (บทสนทนาโต้ตอบกับแอดมิน) -->
            <?php if (!empty($myMessages)): ?>
                <div class="space-y-6">
                    <div class="flex items-center justify-between">
                        <h2 class="text-lg font-bold text-white flex items-center gap-2">
                            <i class="fa-solid fa-comments text-gold"></i> บทสนทนากับแอดมิน (<?php echo count($myMessages); ?> รายการ)
                        </h2>
                        <span class="text-xs text-gold font-medium">คุณสามารถพิมพ์ตอบโต้กับแอดมินได้ตลอดเวลา</span>
                    </div>

                    <?php foreach ($myMessages as $m): ?>
                        <div class="glass-card rounded-2xl border border-gold/30 overflow-hidden shadow-xl">
                            <!-- Thread Header -->
                            <div class="bg-night-900/90 px-5 py-3 border-b border-gray-800 flex flex-wrap items-center justify-between gap-2">
                                <div class="flex items-center gap-2">
                                    <span class="w-2.5 h-2.5 rounded-full bg-gold animate-pulse"></span>
                                    <span class="font-bold text-sm text-white"><?php echo htmlspecialchars($m['subject']); ?></span>
                                </div>
                                <div class="flex items-center gap-3">
                                    <?php if ($m['status'] === 'replied'): ?>
                                        <span class="badge-green px-2.5 py-0.5 rounded-full text-[10px] font-bold">
                                            <i class="fa-solid fa-check-double mr-1"></i> แอดมินตอบกลับแล้ว
                                        </span>
                                    <?php else: ?>
                                        <span class="badge-gold px-2.5 py-0.5 rounded-full text-[10px] font-bold">
                                            <i class="fa-solid fa-clock mr-1"></i> รอแอดมินตรวจสอบ
                                        </span>
                                    <?php endif; ?>
                                    <span class="text-[10px] text-gray-500 font-mono"><?php echo date('d/m/Y H:i', strtotime($m['created_at'])); ?></span>
                                </div>
                            </div>

                            <!-- Chat Stream Container -->
                            <div class="p-5 space-y-4 max-h-[420px] overflow-y-auto">
                                <!-- 1. Original User Message (Bubble Right) -->
                                <div class="flex items-start gap-3 justify-end">
                                    <div class="max-w-[80%] bg-[#1a1d28] border border-gray-700/80 rounded-2xl rounded-tr-none p-3.5 shadow-md">
                                        <div class="flex items-center justify-between gap-2 text-[10px] text-gray-400 mb-1 border-b border-gray-800 pb-1">
                                            <span class="font-bold text-gold"><?php echo htmlspecialchars($m['name']); ?> (คุณ)</span>
                                            <span><?php echo date('H:i', strtotime($m['created_at'])); ?> น.</span>
                                        </div>
                                        <p class="text-xs text-gray-200 leading-relaxed whitespace-pre-wrap"><?php echo htmlspecialchars($m['message']); ?></p>
                                    </div>
                                    <div class="w-8 h-8 rounded-full bg-night-800 border border-gold/50 flex items-center justify-center font-bold text-gold text-xs shrink-0">
                                        <?php echo mb_substr($m['name'], 0, 1, 'UTF-8'); ?>
                                    </div>
                                </div>

                                <!-- 2. Legacy Admin Reply if exists and no replies table yet -->
                                <?php if (!empty($m['admin_reply']) && empty($m['replies'])): ?>
                                    <div class="flex items-start gap-3">
                                        <div class="w-8 h-8 rounded-full bg-red-950 border border-gold flex items-center justify-center text-gold text-xs shrink-0 shadow-lg">
                                            <i class="fa-solid fa-crown text-[10px]"></i>
                                        </div>
                                        <div class="max-w-[80%] bg-gradient-to-r from-yellow-950/40 via-night-800 to-yellow-950/20 border border-gold/40 rounded-2xl rounded-tl-none p-3.5 shadow-md">
                                            <div class="flex items-center justify-between gap-2 text-[10px] text-yellow-400 mb-1 border-b border-gray-800 pb-1">
                                                <span class="font-bold">👑 แอดมินร้าน (Admin)</span>
                                            </div>
                                            <p class="text-xs text-gray-200 leading-relaxed whitespace-pre-wrap"><?php echo htmlspecialchars($m['admin_reply']); ?></p>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <!-- 3. Interactive Chronological Thread Replies (บทสนทนาโต้ตอบกัน) -->
                                <?php if (!empty($m['replies'])): ?>
                                    <?php foreach ($m['replies'] as $rep): ?>
                                        <?php if ($rep['sender_role'] === 'admin'): ?>
                                            <!-- Admin Message (Bubble Left) -->
                                            <div class="flex items-start gap-3">
                                                <div class="w-8 h-8 rounded-full bg-red-950 border border-gold flex items-center justify-center text-gold text-xs shrink-0 shadow-lg">
                                                    <i class="fa-solid fa-crown text-[10px]"></i>
                                                </div>
                                                <div class="max-w-[80%] bg-gradient-to-r from-yellow-950/50 via-night-800 to-yellow-950/30 border border-gold/40 rounded-2xl rounded-tl-none p-3.5 shadow-md">
                                                    <div class="flex items-center justify-between gap-2 text-[10px] text-yellow-400 mb-1 border-b border-gray-800 pb-1">
                                                        <span class="font-bold">👑 <?php echo htmlspecialchars($rep['sender_name']); ?></span>
                                                        <span class="text-gray-400"><?php echo date('d/m H:i', strtotime($rep['created_at'])); ?> น.</span>
                                                    </div>
                                                    <p class="text-xs text-gray-200 leading-relaxed whitespace-pre-wrap"><?php echo htmlspecialchars($rep['message']); ?></p>
                                                </div>
                                            </div>
                                        <?php else: ?>
                                            <!-- User Message (Bubble Right) -->
                                            <div class="flex items-start gap-3 justify-end">
                                                <div class="max-w-[80%] bg-[#1a1d28] border border-gray-700/80 rounded-2xl rounded-tr-none p-3.5 shadow-md">
                                                    <div class="flex items-center justify-between gap-2 text-[10px] text-gray-400 mb-1 border-b border-gray-800 pb-1">
                                                        <span class="font-bold text-gold"><?php echo htmlspecialchars($rep['sender_name']); ?> (คุณ)</span>
                                                        <span><?php echo date('d/m H:i', strtotime($rep['created_at'])); ?> น.</span>
                                                    </div>
                                                    <p class="text-xs text-gray-200 leading-relaxed whitespace-pre-wrap"><?php echo htmlspecialchars($rep['message']); ?></p>
                                                </div>
                                                <div class="w-8 h-8 rounded-full bg-night-800 border border-gold/50 flex items-center justify-center font-bold text-gold text-xs shrink-0">
                                                    <?php echo mb_substr($rep['sender_name'], 0, 1, 'UTF-8'); ?>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>

                            <!-- User Reply Input Box (กล่องพิมพ์ข้อความตอบกลับแอดมิน) -->
                            <form method="POST" class="p-3 bg-night-900 border-t border-gray-800 flex items-center gap-2">
                                <input type="hidden" name="message_id" value="<?php echo $m['id']; ?>">
                                <input type="text" name="reply_text" required 
                                       placeholder="พิมพ์ข้อความตอบกลับแอดมินที่นี่..." 
                                       class="flex-1 bg-night-800 border border-gray-700 rounded-xl px-4 py-2.5 text-xs text-white focus:outline-none border-gold-focus">
                                <button type="submit" name="send_user_reply" class="btn-gold px-5 py-2.5 rounded-xl text-xs font-bold flex items-center gap-1.5 shadow">
                                    <i class="fa-solid fa-paper-plane"></i> ตอบกลับ
                                </button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- 2. Form for Sending New Message / Inquiry (เริ่มเปิดข้อความหัวข้อใหม่) -->
            <div class="glass-card p-6 sm:p-8 rounded-2xl border border-gold/40 shadow-xl">
                <h2 class="text-lg font-bold text-white mb-2 flex items-center gap-2">
                    <i class="fa-solid fa-pen-to-square text-gold"></i> ส่งข้อความเรื่องใหม่ถึงแอดมิน
                </h2>
                <p class="text-xs text-gray-400 mb-6">หากต้องการสอบถามเรื่องใหม่ หรือเปิดประเด็นอื่น สามารถกรอกฟอร์มด้านล่างได้ทันที</p>

                <form method="POST" class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-300 mb-1.5">ชื่อของคุณ</label>
                            <input type="text" name="name" required 
                                   value="<?php echo htmlspecialchars($currentUser['name'] ?? ''); ?>"
                                   class="w-full px-3.5 py-2.5 bg-night-900 border border-gray-700 rounded-xl text-white text-xs focus:outline-none border-gold-focus" 
                                   placeholder="ระบุชื่อ">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-300 mb-1.5">อีเมลติดต่อกลับ</label>
                            <input type="email" name="email" required 
                                   value="<?php echo htmlspecialchars($currentUser['email'] ?? ''); ?>"
                                   class="w-full px-3.5 py-2.5 bg-night-900 border border-gray-700 rounded-xl text-white text-xs focus:outline-none border-gold-focus" 
                                   placeholder="yourname@gmail.com">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-300 mb-1.5">เบอร์โทรศัพท์</label>
                            <input type="tel" name="phone" 
                                   value="<?php echo htmlspecialchars($currentUser['phone'] ?? ''); ?>"
                                   class="w-full px-3.5 py-2.5 bg-night-900 border border-gray-700 rounded-xl text-white text-xs focus:outline-none border-gold-focus" 
                                   placeholder="08X-XXX-XXXX">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-300 mb-1.5">หัวข้อเรื่อง</label>
                            <input type="text" name="subject" required 
                                   value="<?php echo isset($_GET['booking']) ? 'สอบถามการจองรหัส ' . htmlspecialchars($_GET['booking']) : ''; ?>"
                                   class="w-full px-3.5 py-2.5 bg-night-900 border border-gray-700 rounded-xl text-white text-xs focus:outline-none border-gold-focus" 
                                   placeholder="เช่น ขอเปลี่ยนโต๊ะ, สอบถามโปรโมชั่น">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-300 mb-1.5">รายละเอียดข้อความ</label>
                        <textarea name="message" rows="3" required 
                                  class="w-full px-3.5 py-2.5 bg-night-900 border border-gray-700 rounded-xl text-white text-xs focus:outline-none border-gold-focus" 
                                  placeholder="พิมพ์ข้อความที่ต้องการแจ้งหรือสอบถามแอดมินที่นี่..."></textarea>
                    </div>

                    <button type="submit" name="send_message" class="w-full btn-gold py-3 rounded-xl text-sm font-semibold shadow-lg">
                        <i class="fa-solid fa-paper-plane mr-1.5"></i> ส่งข้อความทันที
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
