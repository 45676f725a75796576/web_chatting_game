class Game {
    constructor() {
        this.players = {};
        this.myPlayerId = null;
        this.myUsername = null;
        this.authenticated = false;
        this.inGame = false;
        this.mySkinUrl = null;
        this.testMode = false;
        this.lastSentPos = { x: CONFIG.CANVAS_WIDTH / 2, y: CONFIG.CANVAS_HEIGHT / 2 };

        this.canvas = document.getElementById('gameCanvas');
        this.renderer = new GameRenderer(this.canvas);
        this.inputHandler = new InputHandler();
        this.chatManager = new ChatManager();

        this.setupPacketHandlers();
    }

    setupPacketHandlers() {
        networkManager.on('server_sign_in',        (p) => this.handleSignIn(p));
        networkManager.on('server_login',           (p) => this.handleLogin(p));
        networkManager.on('server_room',            (p) => this.handleRoom(p));
        networkManager.on('server_floor',           (p) => this.handleFloor(p));
        networkManager.on('server_new_player',      (p) => this.handleNewPlayer(p));
        networkManager.on('server_player_pos',      (p) => this.handlePlayerPos(p));
        networkManager.on('server_chat',            (p) => this.handleChat(p));
        networkManager.on('server_disconnect',      (p) => this.handleDisconnect(p));
        networkManager.on('server_skin',            (p) => this.handleSkinResponse(p));
        networkManager.on('server_skin_update',     (p) => this.handleSkinUpdate(p));
        networkManager.on('server_room_skin',       (p) => this.handleRoomSkinResponse(p));
        networkManager.on('server_room_skin_update',(p) => this.handleRoomSkinUpdate(p));
        networkManager.on('server_utm',             ()  => console.log('UTM tracked'));
        networkManager.on('server_error',           (p) => authUI.showError(p.message || 'Server error'));
    }

    // ── Auth ────────────────────────────────────────────────────

    handleSignIn(packet) {
        if (packet.state === 'success') {
            this.myPlayerId = packet.player_id;
            this.authenticated = true;
            this.mySkinUrl = packet.img || null;
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
            this.mySkinUrl = packet.img || null;
            authUI.showSuccess('Login successful!');
            setTimeout(() => networkManager.sendPacket({ type: 'enter_game' }), 500);
        } else {
            authUI.showError(packet.message || 'Login failed');
        }
    }
    

    // ── Room ────────────────────────────────────────────────────

    handleRoom(packet) {
        if (packet.state === 'error') {
            authUI.showError(packet.message || 'Failed to enter room');
            return;
        }
    
        const roomId = packet.room_id || packet.place?.room_id;
        const floor  = packet.floor  || packet.place?.floor;
        const img    = packet.img    || packet.place?.img;
    
        if (!this.inGame) {
            authUI.switchToGame();
            this.inGame = true;
    
            this.players[this.myPlayerId] = new Player(
                this.myPlayerId,
                CONFIG.CANVAS_WIDTH / 2,
                CONFIG.CANVAS_HEIGHT / 2,
                this.myUsername || 'You',
                '#e94560'
            );
    
            if (this.mySkinUrl) {
                this.players[this.myPlayerId].setSkin(this.mySkinUrl);
            }
    
            document.getElementById('myPlayerId').textContent = this.myPlayerId;
            document.getElementById('skinControls').classList.remove('hidden');
            this.updatePlayerCount();
            this.startGameLoop();
            this.sendUtm();
        } else {
            // Смена комнаты — чистим чужих игроков
            for (let id in this.players) {
                if (id != this.myPlayerId) delete this.players[id];
            }
            this.updatePlayerCount();
        }
    
        this.currentRoomId = roomId;
        this.currentFloor  = floor;
        document.getElementById('currentRoomId').textContent = roomId || '-';
    
        if (img) this._applyRoomBackground(img);
    
        // Небольшая задержка — даём серверу оформить вход в комнату
        this._announceMyself(); 
    }
    
    joinRoom() {
        const roomId = parseInt(document.getElementById('roomIdInput').value);
        if (!roomId || isNaN(roomId)) {
            authUI.showError('Please enter a valid room ID');
            return;
        }
        // == вместо === — сравниваем без учёта типа
        if (roomId == this.currentRoomId) {
            authUI.showError('You are already in this room');
            return;
        }
        networkManager.sendPacket({ type: 'enter_room', room_id: roomId });
        document.getElementById('roomIdInput').value = '';
    }
    
    

    handleFloor(packet) {
        if (packet.state === 'error') { authUI.showError(packet.message || 'Failed to enter floor'); return; }
        this.currentFloorId  = packet.floor_id;
        this.availableRooms  = packet.rooms;
        console.log('Entered floor:', packet.floor_id, 'Rooms:', packet.rooms);
    }

    // ── Players ─────────────────────────────────────────────────

    handleNewPlayer(packet) {
        if (packet.player_id == this.myPlayerId) return;

        this.players[packet.player_id] = new Player(
            packet.player_id,
            packet.pos.x,
            packet.pos.y,
            packet.username || 'Player'
        );

        // Если у нового игрока уже есть скин в пакете — применяем
        if (packet.skin_url) {
            this.players[packet.player_id].setSkin(packet.skin_url);
        }

        this.updatePlayerCount();

        // Отвечаем своей позицией и скином чтобы новый игрок нас увидел
        this._announceMyself();
    }

    handlePlayerPos(packet) {
        if (packet.player_id == this.myPlayerId) return;
    
        if (!this.players[packet.player_id]) {
            this.players[packet.player_id] = new Player(
                packet.player_id,
                packet.pos.x,
                packet.pos.y,
                packet.username || 'Player'
            );
            this.updatePlayerCount();
            this._announceMyself();
        } else {
            if (packet.username) this.players[packet.player_id].username = packet.username;
            this.players[packet.player_id].setPosition(packet.pos.x, packet.pos.y);
        }
    
        // Применяем flip независимо от того новый игрок или нет
        if (packet.flip !== undefined) {
            this.players[packet.player_id].flip = packet.flip;
        }
    }
    
    handleDisconnect(packet) {
        delete this.players[packet.player_id];
        this.updatePlayerCount();
    }

    // ── Chat ────────────────────────────────────────────────────

    handleChat(packet) {
        this.chatManager.addMessage(packet.player_id, packet.message, packet.timeout);
    }

    handleChatExpired(packet) {
        this.chatManager.removeMessage(packet.player_id);
    }

    // ── Helpers ─────────────────────────────────────────────────

    /**
     * Шлёт всем текущую позицию + скин.
     * Вызывается при входе в комнату и при появлении нового игрока.
     */
    _announceMyself() {
        if (this.testMode) return;
        const me = this.players[this.myPlayerId];
        if (!me) return;

        // Позиция
        networkManager.sendPacket({
            type: 'player_pos',
            pos: { x: Math.round(me.x), y: Math.round(me.y) },
            flip: me.flip  // ← добавить
        });
        

        // Скин — чтобы все видели наш текущий скин
        if (me.skinUrl) {
            networkManager.sendPacket({ type: 'skin', url: me.skinUrl });
        }
    }

    setUsername(username) { this.myUsername = username; }

    updatePlayerCount() {
        document.getElementById('playerCount').textContent = Object.keys(this.players).length;
    }

    startTestMode(playerId, username) {
        this.testMode     = true;
        this.myPlayerId   = playerId;
        this.myUsername   = username;
        this.authenticated = true;
        this.inGame       = true;

        networkManager.enableTestMode();

        this.players[this.myPlayerId] = new Player(
            this.myPlayerId,
            CONFIG.CANVAS_WIDTH / 2,
            CONFIG.CANVAS_HEIGHT / 2,
            username,
            '#51cf66'
        );

        this.updatePlayerCount();
        this.startGameLoop();
        document.getElementById('skinControls').classList.remove('hidden');
    }

    // ── Game loop ───────────────────────────────────────────────

    update() {
        if (!this.inGame || !this.myPlayerId) return;
        const me = this.players[this.myPlayerId];
        if (!me) return;

        const { dx, dy } = this.inputHandler.getMovement();
        if (dx !== 0 || dy !== 0) {
            me.move(dx, dy);

            if (!this.testMode) {
                const dist = Math.hypot(me.x - this.lastSentPos.x, me.y - this.lastSentPos.y);
                if (dist > CONFIG.POSITION_UPDATE_THRESHOLD) {
                    networkManager.sendPacket({
                        type: 'player_pos',
                        pos: { x: Math.round(me.x), y: Math.round(me.y) },
                        flip: me.flip  // ← добавить
                    });
                    this.lastSentPos = { x: me.x, y: me.y };
                }
                
            }
        }
    }

    render() {
        this.renderer.clear();
        for (let id in this.players) {
            this.renderer.drawPlayer(
                this.players[id],
                id == this.myPlayerId,
                this.chatManager.getMessage(id)
            );
        }
    }

    startGameLoop() {
        const loop = () => { this.update(); this.render(); requestAnimationFrame(loop); };
        loop();
    }

    // ── Skin modal ──────────────────────────────────────────────

    openSkinModal() {
        const grid = document.getElementById('skinGrid');
        grid.innerHTML = '';
        CONFIG.SKINS.forEach(url => {
            const img = document.createElement('img');
            img.src = url;
            img.className = 'skin-grid-item';
            img.title = url.split('/').pop();
            img.onclick = () => {
                document.querySelectorAll('#skinGrid .skin-grid-item').forEach(i => i.classList.remove('selected'));
                img.classList.add('selected');
                document.getElementById('skinUrlInput').value = url;
                this.previewSkin();
            };
            grid.appendChild(img);
        });

        const me = this.players[this.myPlayerId];
        if (me?.skinUrl) {
            document.getElementById('skinUrlInput').value = me.skinUrl;
            this.previewSkin();
        }

        document.getElementById('skinModal').classList.remove('hidden');
    }

    closeSkinModal() {
        document.getElementById('skinModal').classList.add('hidden');
        document.getElementById('skinUrlInput').value = '';
        this._hideSkinPreview();
    }

    previewSkin() {
        const url = document.getElementById('skinUrlInput').value.trim();
        const img = document.getElementById('skinPreviewImg');
        if (url) {
            img.src = url;
            img.classList.remove('hidden');
            document.getElementById('skinPreviewPlaceholder').classList.add('hidden');
        } else {
            this._hideSkinPreview();
        }
    }

    _hideSkinPreview() {
        document.getElementById('skinPreviewImg').classList.add('hidden');
        document.getElementById('skinPreviewPlaceholder').classList.remove('hidden');
    }

    applySkin() {
        const url    = document.getElementById('skinUrlInput').value.trim();
        if (!url) { alert('Введи URL скина'); return; }
        const width  = parseFloat(document.getElementById('skinWidthSlider').value);
        const height = parseFloat(document.getElementById('skinHeightSlider').value);

        const me = this.players[this.myPlayerId];
        if (me) me.setSkin(url, width, height);

        if (!this.testMode) networkManager.sendPacket({ type: 'skin', url });
        this.closeSkinModal();
    }

    updateSkinSize() {
        const width  = parseFloat(document.getElementById('skinWidthSlider').value);
        const height = parseFloat(document.getElementById('skinHeightSlider').value);
        document.getElementById('widthValue').textContent  = width.toFixed(1);
        document.getElementById('heightValue').textContent = height.toFixed(1);
        const me = this.players[this.myPlayerId];
        if (me) { me.skinWidth = width; me.skinHeight = height; }
    }

    handleSkinResponse(packet) {
        if (packet.state === 'error') authUI.showError(packet.message || 'Failed to change skin');
    }

    handleSkinUpdate(packet) {
        const player = this.players[packet.player_id];
        if (player) player.setSkin(packet.url, player.skinWidth, player.skinHeight);
    }

    // ── Room skin modal ─────────────────────────────────────────

    openRoomSkinModal() {
        const grid = document.getElementById('roomSkinGrid');
        grid.innerHTML = '';
        CONFIG.ROOM_BACKGROUNDS.forEach(url => {
            const img = document.createElement('img');
            img.src = url;
            img.className = 'skin-grid-item';
            img.onclick = () => {
                document.querySelectorAll('#roomSkinGrid .skin-grid-item').forEach(i => i.classList.remove('selected'));
                img.classList.add('selected');
                document.getElementById('roomSkinUrlInput').value = url;
                this.previewRoomSkin();
            };
            grid.appendChild(img);
        });
        document.getElementById('roomSkinModal').classList.remove('hidden');
    }

    closeRoomSkinModal() {
        document.getElementById('roomSkinModal').classList.add('hidden');
        document.getElementById('roomSkinUrlInput').value = '';
        this._hideRoomSkinPreview();
    }

    previewRoomSkin() {
        const url = document.getElementById('roomSkinUrlInput').value.trim();
        const img = document.getElementById('roomSkinPreviewImg');
        if (url) {
            img.src = url;
            img.classList.remove('hidden');
            document.getElementById('roomSkinPreviewPlaceholder').classList.add('hidden');
        } else {
            this._hideRoomSkinPreview();
        }
    }

    _hideRoomSkinPreview() {
        document.getElementById('roomSkinPreviewImg').classList.add('hidden');
        document.getElementById('roomSkinPreviewPlaceholder').classList.remove('hidden');
    }

    applyRoomSkin() {
        const url = document.getElementById('roomSkinUrlInput').value.trim();
        if (!url) { alert('Введи URL фона'); return; }
        if (!this.testMode) networkManager.sendPacket({ type: 'room_skin', url });
        this._applyRoomBackground(url);
        this.closeRoomSkinModal();
    }

    handleRoomSkinResponse(packet) {
        if (packet.state === 'error') authUI.showError(packet.message || 'Failed to change room skin');
    }

    handleRoomSkinUpdate(packet) {
        if (packet.url) this._applyRoomBackground(packet.url);
    }

    _applyRoomBackground(url) {
        this.canvas.style.backgroundImage    = `url('${url}')`;
        this.canvas.style.backgroundSize     = 'cover';
        this.canvas.style.backgroundPosition = 'center';
    }

    // ── Room lock ───────────────────────────────────────────────

    lockRoom(lock) {
        if (this.testMode) return;
        networkManager.sendPacket({ type: 'room_lock', lock });
    }

    // ── UTM ─────────────────────────────────────────────────────

    sendUtm() {
        if (document.cookie.includes('utm_sent=true')) return;
        const p = new URLSearchParams(window.location.search);
        const utm_source   = p.get('utm_source');
        const utm_campaign = p.get('utm_campaign');
        const utm_medium   = p.get('utm_medium');
        if (utm_source || utm_campaign || utm_medium) {
            networkManager.sendPacket({
                type: 'utm',
                utm_source:   utm_source   || '',
                utm_campaign: utm_campaign || '',
                utm_medium:   utm_medium   || ''
            });
            const expires = new Date(Date.now() + 30 * 24 * 60 * 60 * 1000).toUTCString();
            document.cookie = `utm_sent=true; expires=${expires}; path=/`;
        }
    }
    changeRoomSkin(url) {
        if (!url || this.testMode) return;
        networkManager.sendPacket({ type: 'room_skin', url });
        this._applyRoomBackground(url);
    }
    
}
