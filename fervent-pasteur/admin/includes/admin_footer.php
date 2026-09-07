<?php
/**
 * Admin Panel Footer
 */
?>
<footer class="mt-auto bg-[#07080b] border-t border-gray-800 py-6 text-center text-xs text-gray-500">
    <div class="max-w-7xl mx-auto px-4">
        <p>© <?php echo date('Y'); ?> SONGKHLA NIGHTLIFE - ADMIN BACKOFFICE • แอดมิน: 674295003@parichat.skru.ac.th</p>
    </div>
</footer>

<!-- Toast Alert Container -->
<div id="adminToast" class="fixed bottom-6 right-6 z-50 transform translate-y-32 opacity-0 transition-all duration-500 max-w-sm w-full">
    <div class="bg-night-800 border-2 border-gold rounded-2xl shadow-2xl p-4 flex items-center gap-3">
        <div class="w-10 h-10 rounded-full bg-yellow-950/60 border border-gold flex items-center justify-center text-gold text-lg shrink-0">
            <i class="fa-solid fa-bell animate-bounce"></i>
        </div>
        <div class="flex-1 min-w-0">
            <h4 id="toastTitle" class="font-bold text-sm text-white truncate">แจ้งเตือนใหม่</h4>
            <p id="toastMessage" class="text-xs text-gray-300 truncate">มีรายการใหม่เข้ามาในระบบ</p>
        </div>
        <button onclick="dismissToast()" class="text-gray-400 hover:text-white text-sm p-1">
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>
</div>

<script>
// Web Audio API Synthesizer Chime (สร้างเสียงแจ้งเตือนอัตโนมัติไม่ต้องพึ่งไฟล์ mp3)
function playGoldChime() {
    try {
        const AudioContext = window.AudioContext || window.webkitAudioContext;
        if (!AudioContext) return;
        const ctx = new AudioContext();
        
        const now = ctx.currentTime;
        const osc = ctx.createOscillator();
        const gain = ctx.createGain();
        
        osc.type = 'sine';
        // Pleasant bell chime (E6 to B6 chord)
        osc.frequency.setValueAtTime(1318.51, now);
        osc.frequency.exponentialRampToValueAtTime(1975.53, now + 0.15);
        
        gain.gain.setValueAtTime(0.2, now);
        gain.gain.exponentialRampToValueAtTime(0.001, now + 0.6);
        
        osc.connect(gain);
        gain.connect(ctx.destination);
        
        osc.start(now);
        osc.stop(now + 0.6);
    } catch(e) {}
}

function showToast(title, message) {
    const toast = document.getElementById('adminToast');
    document.getElementById('toastTitle').textContent = title;
    document.getElementById('toastMessage').textContent = message;
    
    toast.classList.remove('translate-y-32', 'opacity-0');
    playGoldChime();

    setTimeout(() => {
        dismissToast();
    }, 6000);
}

function dismissToast() {
    const toast = document.getElementById('adminToast');
    toast.classList.add('translate-y-32', 'opacity-0');
}

// Live Polling every 12 seconds
let lastUnread = null;
let lastPending = null;

function checkAdminAlerts() {
    fetch('../api/admin_alerts.php')
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                if (lastUnread !== null && data.unread_messages > lastUnread) {
                    showToast('💬 มีข้อความแชทใหม่จากลูกค้า!', 'ลูกค้าส่งข้อความตอบกลับในระบบแชท');
                }
                if (lastPending !== null && data.pending_bookings > lastPending) {
                    showToast('🍸 มีรายการจองโต๊ะใหม่!', 'มีลูกค้าทำการจองโต๊ะเข้ามาใหม่ในระบบ');
                }
                lastUnread = data.unread_messages;
                lastPending = data.pending_bookings;
            }
        })
        .catch(() => {});
}

// Start polling
setInterval(checkAdminAlerts, 12000);
checkAdminAlerts(); // initial check
</script>
</body>
</html>
