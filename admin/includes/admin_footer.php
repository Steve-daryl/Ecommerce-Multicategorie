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
        <div class="ai-header-actions">
            <button class="ai-theme-btn" id="aiThemeBtn" title="Changer de thème">
                <i class="fas fa-moon"></i>
            </button>
            <button class="ai-close-btn" id="aiCloseBtn" title="Fermer"><i class="fas fa-times"></i></button>
        </div>
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
    const themeBtn = document.getElementById('toggleDashboardTheme');
    
    if (toggleBtn && sidebar) {
        toggleBtn.addEventListener('click', function() {
            sidebar.classList.toggle('active');
        });
    }

    // Dashboard Theme Toggle
    if (themeBtn) {
        const updateIcon = (isDark) => {
            themeBtn.innerHTML = isDark ? '<i class="fas fa-sun"></i>' : '<i class="fas fa-moon"></i>';
        };

        // Initial icon state
        updateIcon(document.documentElement.classList.contains('dark-mode'));

        themeBtn.addEventListener('click', () => {
            const html = document.documentElement;
            html.classList.toggle('dark-mode');
            const isDark = html.classList.contains('dark-mode');
            localStorage.setItem('admin_theme', isDark ? 'dark' : 'light');
            updateIcon(isDark);
            
            // Sync AI Assistant theme if it exists
            const aiChat = document.getElementById('aiChatWindow');
            if (aiChat) {
                if (isDark) aiChat.classList.add('dark');
                else aiChat.classList.remove('dark');
                localStorage.setItem('ai_theme', isDark ? 'dark' : 'light');
                const aiThemeBtn = document.getElementById('aiThemeBtn');
                if (aiThemeBtn) aiThemeBtn.innerHTML = isDark ? '<i class="fas fa-sun"></i>' : '<i class="fas fa-moon"></i>';
            }

            // Update Chart.js if present
            if (typeof Chart !== 'undefined' && window.adminCharts) {
                window.adminCharts.forEach(chart => {
                    chart.options.scales.x.grid.color = isDark ? 'rgba(255,255,255,0.05)' : 'rgba(0,0,0,0.05)';
                    chart.options.scales.y.grid.color = isDark ? 'rgba(255,255,255,0.05)' : 'rgba(0,0,0,0.05)';
                    chart.update();
                });
            }
        });
    }
});
</script>
</body>
</html>
