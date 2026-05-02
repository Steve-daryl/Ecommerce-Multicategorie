/**
 * ShopMax AI Assistant - Frontend Logic
 */
document.addEventListener('DOMContentLoaded', function() {
    const floatBtn = document.getElementById('aiFloatBtn');
    const chatWindow = document.getElementById('aiChatWindow');
    const closeBtn = document.getElementById('aiCloseBtn');
    const sendBtn = document.getElementById('aiSendBtn');
    const chatInput = document.getElementById('aiChatInput');
    const messagesContainer = document.getElementById('aiMessages');
    const typingIndicator = document.getElementById('aiTyping');

    if (!floatBtn) return;

    // Toggle Window
    floatBtn.addEventListener('click', () => {
        chatWindow.classList.toggle('active');
        if (chatWindow.classList.contains('active')) {
            chatInput.focus();
        }
    });

    closeBtn.addEventListener('click', () => {
        chatWindow.classList.remove('active');
    });

    let chatHistory = [];

    // Send Message
    const sendMessage = async () => {
        const message = chatInput.value.trim();
        if (!message) return;

        // Add user message to UI
        addMessage(message, 'user');
        chatInput.value = '';

        // Show typing indicator
        typingIndicator.style.display = 'flex';
        messagesContainer.scrollTop = messagesContainer.scrollHeight;

        try {
            const response = await fetch('ai_proxy.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ 
                    message: message,
                    history: chatHistory 
                })
            });

            const data = await response.json();
            
            typingIndicator.style.display = 'none';

            if (data.reply) {
                addMessage(data.reply, 'bot');
                // Update history
                chatHistory.push({ role: 'user', text: message });
                chatHistory.push({ role: 'model', text: data.reply });
                // Keep history reasonable (last 10 messages)
                if (chatHistory.length > 20) chatHistory = chatHistory.slice(-20);
            } else if (data.error) {
                let errText = data.error;
                if (data.http_code) errText += ` (HTTP ${data.http_code})`;
                addMessage("Désolé, une erreur est survenue : " + errText, 'bot');
                console.error("AI Error Details:", data.details);
            }
        } catch (error) {
            typingIndicator.style.display = 'none';
            addMessage("Désolé, je ne parviens pas à me connecter au serveur.", 'bot');
            console.error(error);
        }
    };

    const addMessage = (text, sender) => {
        const msgDiv = document.createElement('div');
        msgDiv.className = `ai-message ${sender}`;
        
        // Better formatting: bold, lists, and line breaks
        let formattedText = text
            .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
            .replace(/^\s*[\-\*]\s+(.*)/gm, '<li>$1</li>')
            .replace(/(<li>.*<\/li>)/s, '<ul>$1</ul>')
            .replace(/\n/g, '<br>');
        
        msgDiv.innerHTML = formattedText;
        messagesContainer.appendChild(msgDiv);
        
        // Smooth scroll
        messagesContainer.scrollTo({
            top: messagesContainer.scrollHeight,
            behavior: 'smooth'
        });
    };

    sendBtn.addEventListener('click', sendMessage);
    chatInput.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') sendMessage();
    });
});
