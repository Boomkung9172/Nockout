<?php
/**
 * Admin Customer Inquiries & Live 2-Way Chat Management
 * Pub & Bar Booking System
 */
require_once __DIR__ . '/../config/db.php';
requireAdmin();

$db = getDb();
ensureRepliesTable($db);
$msg = '';

// Handle Admin Sending Reply to Customer BEFORE any HTML output
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_admin_reply'])) {
    $msgId = (int)$_POST['message_id'];
    $replyText = trim($_POST['admin_reply'] ?? '');

    if (!empty($replyText)) {
        // 1. Insert into contact_replies thread
        $insStmt = $db->prepare("INSERT INTO contact_replies (message_id, sender_role, sender_name, message) VALUES (?, 'admin', 'แอดมินร้าน', ?)");
        $insStmt->execute([$msgId, $replyText]);

        // 2. Update parent message
        $stmt = $db->prepare("UPDATE contact_messages SET admin_reply = ?, status = 'replied' WHERE id = ?");
        $stmt->execute([$replyText, $msgId]);

        header("Location: contacts.php?msg=replied");
        exit;
    }
}

// Fetch all messages
$messagesStmt = $db->query("SELECT * FROM contact_messages ORDER BY created_at DESC");
$messages = $messagesStmt->fetchAll();

// Fetch replies for each conversation
foreach ($messages as &$mItem) {
    $rStmt = $db->prepare("SELECT * FROM contact_replies WHERE message_id = ? ORDER BY created_at ASC");
    $rStmt->execute([$mItem['id']]);
    $mItem['replies'] = $rStmt->fetchAll();
}
unset($mItem);

$adminTitle = "กล่องข้อความ & แชทตอบโต้ลูกค้า";
require_once __DIR__ . '/includes/admin_header.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8">
        <div>
            <h1 class="text-2xl sm:text-3xl font-bold text-white flex items-center gap-2">
                <i class="fa-solid fa-headset text-gold"></i> กล่องข้อความ & แชทตอบโต้กับลูกค้า
            </h1>
            <p class="text-xs sm:text-sm text-gray-400 mt-1">
                อ่านข้อความสอบถามจากลูกค้า และพิมพ์แชทตอบโต้กันได้แบบเรียลไทม์ 2 ทาง
            </p>
        </div>
        <div class="flex items-center gap-2 text-xs">
            <span class="badge-gold px-3 py-1 rounded-full font-bold">
                <i class="fa-solid fa-comments mr-1"></i> ระบบแชท 2 ทาง (Two-way Chat)
            </span>
        </div>
    </div>

    <?php if (isset($_GET['msg']) && $_GET['msg'] === 'replied'): ?>
        <div class="bg-green-950/80 border border-green-500 text-green-300 text-xs p-3.5 rounded-xl mb-6 flex items-center gap-2 shadow-lg">
            <i class="fa-solid fa-check-circle"></i> ส่งข้อความตอบกลับลูกค้าเรียบร้อยแล้ว!
        </div>
    <?php endif; ?>

    <?php if (empty($messages)): ?>
        <div class="glass-card p-12 text-center rounded-2xl border border-gray-800">
            <div class="w-16 h-16 mx-auto mb-3 rounded-full bg-yellow-950/40 border border-gold/40 flex items-center justify-center text-gold text-2xl">
                <i class="fa-solid fa-inbox"></i>
            </div>
            <h3 class="font-bold text-white text-base">ไม่มีข้อความใหม่</h3>
            <p class="text-xs text-gray-500 mt-1">เมื่อลูกค้าส่งข้อความผ่านหน้าติดต่อแอดมิน จะแสดงขึ้นที่นี่</p>
        </div>
    <?php else: ?>
        <div class="space-y-6">
            <?php foreach ($messages as $m): ?>
                <div class="glass-card rounded-2xl border transition-all overflow-hidden shadow-xl <?php echo $m['status'] === 'unread' ? 'border-gold bg-night-800/90' : 'border-gray-800'; ?>">
                    <!-- Top Summary Bar -->
                    <div class="p-5 border-b border-gray-800 flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-night-900/60">
                        <div>
                            <div class="flex items-center gap-2.5">
                                <span class="font-bold text-base text-white"><?php echo htmlspecialchars($m['name']); ?></span>
                                <?php if ($m['status'] === 'unread'): ?>
                                    <span class="badge-gold px-2.5 py-0.5 rounded-full text-[10px] font-bold animate-pulse">
                                        <i class="fa-solid fa-bell mr-1"></i> มีข้อความใหม่
                                    </span>
                                <?php else: ?>
                                    <span class="badge-green px-2.5 py-0.5 rounded-full text-[10px] font-bold">
                                        <i class="fa-solid fa-check-double mr-1"></i> ตอบกลับแล้ว
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div class="text-xs text-gray-400 mt-1 flex flex-wrap gap-4">
                                <span><i class="fa-solid fa-envelope text-gold/70 mr-1"></i><?php echo htmlspecialchars($m['email']); ?></span>
                                <?php if (!empty($m['phone'])): ?>
                                    <span><i class="fa-solid fa-phone text-gold/70 mr-1"></i><?php echo htmlspecialchars($m['phone']); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="text-xs text-gray-400 font-mono">
                            เปิดเรื่องเมื่อ: <?php echo date('d/m/Y H:i', strtotime($m['created_at'])); ?> น.
                        </div>
                    </div>

                    <!-- Subject Topic -->
                    <div class="px-5 pt-3">
                        <span class="text-xs font-semibold text-yellow-400 bg-yellow-950/40 border border-gold/30 px-3 py-1 rounded-lg inline-block">
                            <i class="fa-solid fa-tag mr-1 text-[10px]"></i> หัวข้อ: <?php echo htmlspecialchars($m['subject']); ?>
                        </span>
                    </div>

                    <!-- Chat Timeline Stream -->
                    <div class="p-5 space-y-4 max-h-[450px] overflow-y-auto">
                        <!-- 1. Customer's Initial Inquiry (Bubble Left) -->
                        <div class="flex items-start gap-3">
                            <div class="w-8 h-8 rounded-full bg-night-800 border border-gold/50 flex items-center justify-center font-bold text-gold text-xs shrink-0">
                                <?php echo mb_substr($m['name'], 0, 1, 'UTF-8'); ?>
                            </div>
                            <div class="max-w-[80%] bg-[#1a1d28] border border-gray-700/80 rounded-2xl rounded-tl-none p-3.5 shadow-md">
                                <div class="flex items-center justify-between gap-2 text-[10px] text-gray-400 mb-1 border-b border-gray-800 pb-1">
                                    <span class="font-bold text-gold"><?php echo htmlspecialchars($m['name']); ?> (ลูกค้า)</span>
                                    <span><?php echo date('H:i', strtotime($m['created_at'])); ?> น.</span>
                                </div>
                                <p class="text-xs text-gray-200 leading-relaxed whitespace-pre-wrap"><?php echo htmlspecialchars($m['message']); ?></p>
                            </div>
                        </div>

                        <!-- 2. Legacy Admin Reply if exists and no thread entries -->
                        <?php if (!empty($m['admin_reply']) && empty($m['replies'])): ?>
                            <div class="flex items-start gap-3 justify-end">
                                <div class="max-w-[80%] bg-gradient-to-r from-yellow-950/40 via-night-800 to-yellow-950/20 border border-gold/40 rounded-2xl rounded-tr-none p-3.5 shadow-md">
                                    <div class="flex items-center justify-between gap-2 text-[10px] text-yellow-400 mb-1 border-b border-gray-800 pb-1">
                                        <span class="font-bold">👑 แอดมินร้าน (คุณ)</span>
                                    </div>
                                    <p class="text-xs text-gray-200 leading-relaxed whitespace-pre-wrap"><?php echo htmlspecialchars($m['admin_reply']); ?></p>
                                </div>
                                <div class="w-8 h-8 rounded-full bg-red-950 border border-gold flex items-center justify-center text-gold text-xs shrink-0 shadow-lg">
                                    <i class="fa-solid fa-crown text-[10px]"></i>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- 3. Two-way Conversation Thread Replies -->
                        <?php if (!empty($m['replies'])): ?>
                            <?php foreach ($m['replies'] as $rep): ?>
                                <?php if ($rep['sender_role'] === 'admin'): ?>
                                    <!-- Admin message on Right -->
                                    <div class="flex items-start gap-3 justify-end">
                                        <div class="max-w-[80%] bg-gradient-to-r from-yellow-950/50 via-night-800 to-yellow-950/30 border border-gold/40 rounded-2xl rounded-tr-none p-3.5 shadow-md">
                                            <div class="flex items-center justify-between gap-2 text-[10px] text-yellow-400 mb-1 border-b border-gray-800 pb-1">
                                                <span class="font-bold">👑 <?php echo htmlspecialchars($rep['sender_name']); ?> (คุณ)</span>
                                                <span class="text-gray-400"><?php echo date('d/m H:i', strtotime($rep['created_at'])); ?> น.</span>
                                            </div>
                                            <p class="text-xs text-gray-200 leading-relaxed whitespace-pre-wrap"><?php echo htmlspecialchars($rep['message']); ?></p>
                                        </div>
                                        <div class="w-8 h-8 rounded-full bg-red-950 border border-gold flex items-center justify-center text-gold text-xs shrink-0 shadow-lg">
                                            <i class="fa-solid fa-crown text-[10px]"></i>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <!-- User message on Left -->
                                    <div class="flex items-start gap-3">
                                        <div class="w-8 h-8 rounded-full bg-night-800 border border-gold/50 flex items-center justify-center font-bold text-gold text-xs shrink-0">
                                            <?php echo mb_substr($rep['sender_name'], 0, 1, 'UTF-8'); ?>
                                        </div>
                                        <div class="max-w-[80%] bg-[#1a1d28] border border-gray-700/80 rounded-2xl rounded-tl-none p-3.5 shadow-md">
                                            <div class="flex items-center justify-between gap-2 text-[10px] text-gray-400 mb-1 border-b border-gray-800 pb-1">
                                                <span class="font-bold text-gold"><?php echo htmlspecialchars($rep['sender_name']); ?> (ลูกค้าตอบกลับ)</span>
                                                <span><?php echo date('d/m H:i', strtotime($rep['created_at'])); ?> น.</span>
                                            </div>
                                            <p class="text-xs text-gray-200 leading-relaxed whitespace-pre-wrap"><?php echo htmlspecialchars($rep['message']); ?></p>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <!-- Admin Reply Box (แอดมินพิมพ์ตอบกลับลูกค้า) -->
                    <form method="POST" class="p-4 bg-night-900 border-t border-gray-800 flex flex-col sm:flex-row gap-3">
                        <input type="hidden" name="message_id" value="<?php echo $m['id']; ?>">
                        <input type="text" name="admin_reply" required 
                               placeholder="พิมพ์ข้อความตอบกลับลูกค้าที่นี่..." 
                               class="flex-1 bg-night-800 border border-gray-700 rounded-xl px-4 py-2.5 text-xs text-white focus:outline-none border-gold-focus">
                        <button type="submit" name="send_admin_reply" class="btn-gold px-6 py-2.5 rounded-xl text-xs font-bold flex items-center justify-center gap-1.5 shadow">
                            <i class="fa-solid fa-paper-plane"></i> ส่งคำตอบ
                        </button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
