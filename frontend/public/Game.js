class Game {
    constructor() {
        this.players = {};
        this.myPlayerId = null;
        this.myUsername = null;
        this.authenticated = false;
        this.inGame = false;
        this.testMode = false;
        this.lastSentPos = { x: CONFIG.CANVAS_WIDTH / 2, y: CONFIG.CANVAS_HEIGHT / 2 };

        this.canvas = document.getElementById('gameCanvas');
        this.renderer = new GameRenderer(this.canvas);
        this.inputHandler = new InputHandler();
        this.chatManager = new ChatManager();

        this.setupPacketHandlers();
    }

    setupPacketHandlers() {
        networkManager.on('server_sign_in', (packet) => this.handleSignIn(packet));
        networkManager.on('server_login', (packet) => this.handleLogin(packet));
        networkManager.on('server_place', (packet) => this.handlePlace(packet));
        networkManager.on('server_new_player', (packet) => this.handleNewPlayer(packet));
        networkManager.on('server_player_pos', (packet) => this.handlePlayerPos(packet));
        networkManager.on('server_chat', (packet) => this.handleChat(packet));
        networkManager.on('server_chat_expired', (packet) => this.handleChatExpired(packet));
        networkManager.on('server_disconnect', (packet) => this.handleDisconnect(packet));
        networkManager.on('server_error', (packet) => authUI.showError(packet.message || 'Server error'));
        networkManager.on('server_change_skin', (packet) => {
            if (this.players[packet.player_id]) {
                this.players[packet.player_id].setSkin(
                    packet.skin_url, 
                    packet.skin_width || 0.4, 
                    packet.skin_height || 0.4
                );
            }
        });        
    }

    handleSignIn(packet) {
        if (packet.state === 'success') {
            this.myPlayerId = packet.player_id;
            this.authenticated = true;

            alert(`Registration successful!\n\nSave this identifier:\n${packet.identifier_str}\n\nYour Player ID: ${packet.player_id}`);

            networkManager.sendPacket({ type: 'enter_game' });
        } else {
            authUI.showError(packet.message || 'Registration failed');
        }
    }

    handleLogin(packet) {
        if (packet.state === 'success') {
            this.myPlayerId = packet.player_id;
            this.authenticated = true;

            authUI.showSuccess('Login successful!');

            setTimeout(() => {
                networkManager.sendPacket({ type: 'enter_game' });
            }, 500);
        } else {
            authUI.showError(packet.message || 'Login failed');
        }
    }

    handlePlace(packet) {
        authUI.switchToGame();
        document.getElementById('myPlayerId').textContent = this.myPlayerId;
    
        this.inGame = true;
    
        const startX = CONFIG.CANVAS_WIDTH / 2;
        const startY = CONFIG.CANVAS_HEIGHT / 2;
    
        this.players[this.myPlayerId] = new Player(
            this.myPlayerId,
            startX,
            startY,
            this.myUsername || 'You',
            '#e94560'
        );
    
        this.updatePlayerCount();
        this.startGameLoop();
        
        // Show skin controls
        document.getElementById('skinControls').classList.remove('hidden');
    }
        
    handleNewPlayer(packet) {
        this.players[packet.player_id] = new Player(
            packet.player_id,
            packet.pos.x,
            packet.pos.y,
            packet.username || 'Player' + packet.player_id
        );
        this.updatePlayerCount();
    }

    handlePlayerPos(packet) {
        if (this.players[packet.player_id]) {
            this.players[packet.player_id].setPosition(packet.pos.x, packet.pos.y);
        }
    }

    handleChat(packet) {
        this.chatManager.addMessage(packet.player_id, packet.text);
    }

    handleChatExpired(packet) {
        this.chatManager.removeMessage(packet.player_id);
    }

    handleDisconnect(packet) {
        delete this.players[packet.player_id];
        this.chatManager.removeMessage(packet.player_id);
        this.updatePlayerCount();
    }

    setUsername(username) {
        this.myUsername = username;
    }

    startTestMode(playerId, username) {
        this.testMode = true;
        this.myPlayerId = playerId;
        this.myUsername = username;
        this.authenticated = true;
        this.inGame = true;
    
        networkManager.enableTestMode();
    
        const startX = CONFIG.CANVAS_WIDTH / 2;
        const startY = CONFIG.CANVAS_HEIGHT / 2;
    
        this.players[this.myPlayerId] = new Player(
            this.myPlayerId,
            startX,
            startY,
            username,
            '#51cf66'
        );
    
        this.updatePlayerCount();
        this.startGameLoop();
        
        // Show skin controls
        document.getElementById('skinControls').classList.remove('hidden');
    }
        
    updatePlayerCount() {
        document.getElementById('playerCount').textContent = Object.keys(this.players).length;
    }

    update() {
        if (!this.inGame || !this.myPlayerId) return;

        const myPlayer = this.players[this.myPlayerId];
        if (!myPlayer) return;

        const { dx, dy } = this.inputHandler.getMovement();

        if (dx !== 0 || dy !== 0) {
            const oldX = myPlayer.x;
            const oldY = myPlayer.y;

            myPlayer.move(dx, dy);

            if (!this.testMode) {
                const distMoved = Math.sqrt(
                    Math.pow(myPlayer.x - this.lastSentPos.x, 2) + 
                    Math.pow(myPlayer.y - this.lastSentPos.y, 2)
                );

                if (distMoved > CONFIG.POSITION_UPDATE_THRESHOLD) {
                    networkManager.sendPacket({
                        type: 'player_pos',
                        pos: { x: Math.round(myPlayer.x), y: Math.round(myPlayer.y) }
                    });
                    this.lastSentPos = { x: myPlayer.x, y: myPlayer.y };
                }
            }
        }
    }

    render() {
        this.renderer.clear();

        for (let id in this.players) {
            const player = this.players[id];
            const isMe = id == this.myPlayerId;
            const chatMessage = this.chatManager.getMessage(id);
            this.renderer.drawPlayer(player, isMe, chatMessage);
        }
    }

    startGameLoop() {
        const loop = () => {
            this.update();
            this.render();
            requestAnimationFrame(loop);
        };
        loop();
    }

    openSkinModal() {
        document.getElementById('skinModal').classList.remove('hidden');
    }
    
    closeSkinModal() {
        document.getElementById('skinModal').classList.add('hidden');
        document.getElementById('skinUrlInput').value = '';
    }
    
    applySkin() {
        const url = document.getElementById('skinUrlInput').value.trim();
        
        if (!url) {
            alert('Please enter an image URL');
            return;
        }
        
        const width = parseFloat(document.getElementById('skinWidthSlider').value);
        const height = parseFloat(document.getElementById('skinHeightSlider').value);
        
        const myPlayer = this.players[this.myPlayerId];
        if (myPlayer) {
            myPlayer.setSkin(url, width, height);
            this.closeSkinModal();
            
            // Send to server if not in test mode
            if (!this.testMode) {
                networkManager.sendPacket({
                    type: 'change_skin',
                    skin_url: url,
                    skin_width: width,
                    skin_height: height
                });
            }
        }
    }
    
    updateSkinSize() {
        const width = parseFloat(document.getElementById('skinWidthSlider').value);
        const height = parseFloat(document.getElementById('skinHeightSlider').value);
        
        document.getElementById('widthValue').textContent = width.toFixed(1);
        document.getElementById('heightValue').textContent = height.toFixed(1);
        
        const myPlayer = this.players[this.myPlayerId];
        if (myPlayer && myPlayer.skinUrl) {
            myPlayer.skinWidth = width;
            myPlayer.skinHeight = height;
            
            // Send to server if not in test mode
            if (!this.testMode) {
                networkManager.sendPacket({
                    type: 'change_skin',
                    skin_url: myPlayer.skinUrl,
                    skin_width: width,
                    skin_height: height
                });
            }
        }
    }
    
    
    
}
