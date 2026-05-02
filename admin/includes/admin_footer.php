<?php if (!in_array($currentPage, $publicPages)): ?>
        </div> <!-- /.admin-content -->
    </main> <!-- /.admin-main -->
</div> <!-- /.admin-layout -->
<?php endif; ?>


<!-- AI Assistant -->
<link rel="stylesheet" href="css/ai_assistant.css?v=<?= time() ?>">

<button class="ai-float-btn" id="aiFloatBtn" title="Assistant IA ShopMax">
    <i class="fas fa-robot"></i>
</button>

<div class="ai-chat-window" id="aiChatWindow">
    <div class="ai-chat-header">
        <div class="ai-header-info">
            <div class="ai-avatar">
                <i class="fas fa-brain"></i>
            </div>
            <div>
                <h4>Assistant IA</h4>
                <span>En ligne</span>
            </div>
        </div>
        <button class="ai-close-btn" id="aiCloseBtn"><i class="fas fa-times"></i></button>
    </div>
    
    <div class="ai-chat-messages" id="aiMessages">
        <div class="ai-message bot">
            Bonjour ! Je suis votre assistant IA ShopMax. Je peux vous aider à analyser vos ventes, gérer vos stocks ou élaborer des stratégies. Que puis-je faire pour vous ?
        </div>
    </div>

    <div class="ai-typing" id="aiTyping">
        <span></span><span></span><span></span>
    </div>

    <div class="ai-chat-input">
        <input type="text" id="aiChatInput" placeholder="Posez votre question...">
        <button class="ai-send-btn" id="aiSendBtn">
            <i class="fas fa-paper-plane"></i>
        </button>
    </div>
</div>

<script src="js/ai_assistant.js?v=<?= time() ?>"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const toggleBtn = document.getElementById('toggleSidebar');
    const sidebar = document.getElementById('adminSidebar');
    
    if (toggleBtn && sidebar) {
        toggleBtn.addEventListener('click', function() {
            sidebar.classList.toggle('active');
        });
    }
});
</script>
</body>
</html>
