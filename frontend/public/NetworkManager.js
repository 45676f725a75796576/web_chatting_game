class NetworkManager {
    constructor() {
        this.ws = null;
        this.reconnectAttempts = 0;
        this.testMode = false;
        this.messageHandlers = {};
        this.intentionalClose = false; // ← новый флаг
    }

    connect() {
        if (this.ws && (
            this.ws.readyState === WebSocket.OPEN ||
            this.ws.readyState === WebSocket.CONNECTING
        )) {
            return;
        }

        this.intentionalClose = false;
        this.ws = new WebSocket(CONFIG.WS_URL);

        this.ws.onopen = () => {
            console.log('Connected to server');
            this.updateConnectionStatus(true);
            this.reconnectAttempts = 0;
        };

        this.ws.onmessage = (event) => {
            const messages = event.data.split('\n').filter(m => m.trim());
            messages.forEach(msg => {
                try {
                    const packet = JSON.parse(msg);
                    this.handlePacket(packet);
                } catch(e) {
                    console.error('Failed to parse packet:', e);
                }
            });
        };

        this.ws.onclose = (event) => {
            console.log('Disconnected from server, code:', event.code);
            this.updateConnectionStatus(false);

            // Реконнект только если это не намеренное закрытие
            if (!this.testMode && !this.intentionalClose && this.reconnectAttempts < CONFIG.MAX_RECONNECT_ATTEMPTS) {
                this.reconnectAttempts++;
                console.log(`Reconnecting... attempt ${this.reconnectAttempts}`);
                setTimeout(() => this.connect(), CONFIG.RECONNECT_DELAY);
            }
        };

        this.ws.onerror = (error) => {
            console.error('WebSocket error:', error);
            if (!this.testMode) authUI.showError('Connection error');
        };
    }

    // Закрыть соединение намеренно (без реконнекта)
    disconnect() {
        this.intentionalClose = true;
        if (this.ws) this.ws.close();
    }

    sendPacket(packet) {
        if (this.testMode) {
            console.log('[TEST] Packet not sent:', packet);
            return;
        }

        if (this.ws && this.ws.readyState === WebSocket.OPEN) {
            this.ws.send(JSON.stringify(packet) + '\n');
        } else {
            console.error('WebSocket not connected');
            authUI.showError('No connection to server');
        }
    }

    handlePacket(packet) {
        console.log('Received:', packet);
        const handler = this.messageHandlers[packet.type];
        if (handler) handler(packet);
    }

    on(packetType, handler) {
        this.messageHandlers[packetType] = handler;
    }

    updateConnectionStatus(connected) {
        const dot = document.getElementById('statusDot');
        const text = document.getElementById('statusText');
        if (connected) {
            dot.className = 'status-dot connected';
            text.textContent = 'Connected';
            text.style.color = '#51cf66';
        } else {
            dot.className = 'status-dot disconnected';
            text.textContent = 'Disconnected';
            text.style.color = '#ff6b6b';
        }
    }

    
    enableTestMode() {
        this.testMode = true;
    }
}
