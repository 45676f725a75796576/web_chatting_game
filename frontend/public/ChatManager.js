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

    sendMessage(text) {
        if (game.testMode) {
            this.addMessage(game.myPlayerId, text);
            setTimeout(() => this.removeMessage(game.myPlayerId), CONFIG.CHAT_DURATION);
        } else {
            networkManager.sendPacket({
                type: 'chat_message',
                message: text
            });
        }
    }

    addMessage(playerId, text) {
        this.messages[playerId] = {
            text: text,
            timestamp: Date.now()
        };
    }

    removeMessage(playerId) {
        delete this.messages[playerId];
    }

    getMessage(playerId) {
        return this.messages[playerId] ? this.messages[playerId].text : null;
    }
}
