class ChatManager {
    constructor() {
        this.messages = {};
        this.setupChatInput();
    }

    setupChatInput() {
        const chatInput = document.getElementById('chatInput');
        chatInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter' && chatInput.value.trim()) {
                this.sendMessage(chatInput.value);
                chatInput.value = '';
            }
        });
    }

    addMessage(playerId, text, timeoutSeconds = null) {
        this.messages[playerId] = {
            text: text,
            timestamp: Date.now()
        };
    
        const duration = timeoutSeconds ? timeoutSeconds * 1000 : CONFIG.CHAT_DURATION;
    
        setTimeout(() => {
            if (this.messages[playerId]) {
                delete this.messages[playerId];
            }
        }, duration);
    }
    
    sendMessage(text) {
        if (game.testMode) {
            this.addMessage(game.myPlayerId, text);
        } else {
            networkManager.sendPacket({
                type: 'chat',
                message: text
            });
        }
    }
    
    

    removeMessage(playerId) {
        delete this.messages[playerId];
    }
    
    getMessage(playerId) {
        return this.messages[playerId] ? this.messages[playerId].text : null;
    }
    
}
