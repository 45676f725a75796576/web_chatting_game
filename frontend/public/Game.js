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

        this.currentFloorId  = null;
        this.availableRooms  = [];
        this.availableFloors = [];
        this.isInRoom        = false;

        this.setupPacketHandlers();
    }

    setupPacketHandlers() {
        networkManager.on('server_sign_in',         (p) => this.handleSignIn(p));
        networkManager.on('server_login',            (p) => this.handleLogin(p));
        networkManager.on('server_room',             (p) => this.handleRoom(p));
        networkManager.on('server_floor',            (p) => this.handleFloor(p));
        networkManager.on('server_new_player',       (p) => this.handleNewPlayer(p));
        networkManager.on('server_player_pos',       (p) => this.handlePlayerPos(p));
        networkManager.on('server_chat',             (p) => this.handleChat(p));
        networkManager.on('server_disconnect',       (p) => this.handleDisconnect(p));
        networkManager.on('server_skin',             (p) => this.handleSkinResponse(p));
        networkManager.on('server_skin_update',      (p) => this.handleSkinUpdate(p));
        networkManager.on('server_room_skin',        (p) => this.handleRoomSkinResponse(p));
        networkManager.on('server_room_skin_update', (p) => this.handleRoomSkinUpdate(p));
        networkManager.on('server_utm',              ()  => console.log('UTM tracked'));
        networkManager.on('server_error',            (p) => authUI.showError(p.message || 'Server error'));
    }

    // ── Auth ────────────────────────────────────────────────────

    handleSignIn(packet) {
        if (packet.state === 'success') {
            this.myPlayerId = packet.player_id;
            this.authenticated = true;
            this.mySkinUrl = this._isCatUrl(packet.img) ? this._randomSkin() : packet.img;
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
            this.mySkinUrl = this._isCatUrl(packet.img) ? this._randomSkin() : packet.img;
            authUI.showSuccess('Login successful!');
            setTimeout(() => networkManager.sendPacket({ type: 'enter_game' }), 500);
        } else {
            authUI.showError(packet.message || 'Login failed');
        }
    }

    // ── Room ────────────────────────────────────────────────────

    handleRoom(packet) {
        console.log('[handleRoom]', JSON.stringify(packet));
        if (packet.state === 'error') {
            authUI.showError(packet.message || 'Failed to enter room');
            return;
        }

        const roomId    = packet.room_id    || packet.place?.room_id;
        const floor     = packet.floor      || packet.place?.floor;
        const serverImg = packet.img        || packet.place?.img;

        // Фон: берём серверный если он не кошачий, иначе — детерминированный по roomId
        const bg = (serverImg && !this._isCatUrl(serverImg))
            ? serverImg
            : this._roomBg(roomId);

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
            this.mySkinUrl = this.players[this.myPlayerId].skinUrl;

            document.getElementById('myPlayerId').textContent = this.myPlayerId;
            document.getElementById('skinControls').classList.remove('hidden');
            this.updatePlayerCount();
            this.startGameLoop();
            this.sendUtm();

        } else {
            for (let id in this.players) {
                if (id != this.myPlayerId) delete this.players[id];
            }
            this.updatePlayerCount();
        }

        this.currentRoomId  = roomId;
        this.currentFloor   = floor;
        this.currentRoomBg  = bg; // ← сохраняем текущий фон
        document.getElementById('currentRoomId').textContent       = roomId || '-';
        document.getElementById('currentFloorDisplay').textContent = floor ?? '—';

        this._applyRoomBackground(bg); // без if — применяем всегда

        this._showRoomNav(roomId);
        this._announceMyself();
    }

    joinRoom() {
        const roomId = parseInt(document.getElementById('roomIdInput').value);
        if (!roomId || isNaN(roomId)) {
            authUI.showError('Please enter a valid room ID');
            return;
        }
        if (roomId == this.currentRoomId) {
            authUI.showError('You are already in this room');
            return;
        }
        networkManager.sendPacket({ type: 'enter_room', room_id: roomId });
        document.getElementById('roomIdInput').value = '';
    }

    handleFloor(packet) {
        if (packet.state === 'error') {
            authUI.showError(packet.message || 'Failed to enter floor');
            return;
        }
        this.currentFloorId = packet.floor_id;
        this.availableRooms = packet.rooms || [];
        document.getElementById('currentFloorDisplay').textContent = packet.floor_id;
        console.log('Entered floor:', packet.floor_id, 'Rooms:', packet.rooms);

        // Показываем панель в режиме «этажа»
        this._showFloorNav(packet.floor_id, packet.rooms || []);
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

    const skinUrl = this._isCatUrl(packet.skin_url) ? this._randomSkin() : packet.skin_url;
    if (skinUrl) {
        this.players[packet.player_id].setSkin(skinUrl);
    }

    this.updatePlayerCount();
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

    _announceMyself() {
        if (this.testMode) return;
        const me = this.players[this.myPlayerId];
        if (!me) return;

        networkManager.sendPacket({
            type: 'player_pos',
            pos: { x: Math.round(me.x), y: Math.round(me.y) },
            flip: me.flip
        });

        if (me.skinUrl) {
            networkManager.sendPacket({ type: 'skin', url: me.skinUrl });
        }
    }

    setUsername(username) { this.myUsername = username; }

    updatePlayerCount() {
        document.getElementById('playerCount').textContent = Object.keys(this.players).length;
    }

    startTestMode(playerId, username) {
        this.testMode      = true;
        this.myPlayerId    = playerId;
        this.myUsername    = username;
        this.authenticated = true;
        this.inGame        = true;

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
                        flip: me.flip
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
        if (!player) return;
        const url = this._isCatUrl(packet.url) ? this._randomSkin() : packet.url;
        player.setSkin(url, player.skinWidth, player.skinHeight);
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
        this.currentRoomBg = url; // ← сохраняем
        this._applyRoomBackground(url);
        this.closeRoomSkinModal();
    }

    handleRoomSkinResponse(packet) {
        if (packet.state === 'error') authUI.showError(packet.message || 'Failed to change room skin');
    }


    handleRoomSkinUpdate(packet) {
        const url = packet.img || packet.url; // ← сервер шлёт img, не url
        if (!url) return;
        this.currentRoomBg = url;
        this._applyRoomBackground(url);
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
        networkManager.sendPacket({ type: 'room_skin', img: url});
        this._applyRoomBackground(url);
    }

    // ═══════════════════════════════════════════════════════════
    // ── Навигация по этажам и комнатам ──────────────────────────
    // ═══════════════════════════════════════════════════════════

    /**
     * Показывает правую панель в режиме ЭТАЖА:
     * 3 кнопки дверей + кнопка лифта.
     * Кнопка «Фон комнаты» скрыта.
     */
    _showFloorNav(floorId, rooms) {
        this.isInRoom = false;

        document.getElementById('navPanel').classList.remove('hidden');
        document.getElementById('floorNav').classList.remove('hidden');
        document.getElementById('roomNav').classList.add('hidden');
        document.getElementById('navFloorId').textContent = floorId;

        // Фон комнаты нельзя менять на этаже
        document.getElementById('btnRoomSkin').classList.add('hidden');

        // Строим 3 кнопки дверей
        const doorList = document.getElementById('doorButtons');
        doorList.innerHTML = '';
        const roomsToShow = rooms.slice(0, 3);

        roomsToShow.forEach((room, i) => {
            const btn = document.createElement('button');
            btn.className = 'nav-btn nav-door-btn';
            if (room.room_id == this.currentRoomId) btn.classList.add('active-room');
            btn.textContent = room.username
                ? `🚪 ${room.username}`
                : `🚪 Комната ${i + 1}`;
            btn.title = `Room ID: ${room.room_id}`;
            btn.onclick = () => this._joinRoomById(room.room_id);
            doorList.appendChild(btn);
        });

        // Заглушки если комнат меньше 3
        for (let i = roomsToShow.length; i < 3; i++) {
            const btn = document.createElement('button');
            btn.className = 'nav-btn nav-door-btn';
            btn.textContent = `🚪 Комната ${i + 1}`;
            btn.style.opacity = '0.35';
            btn.disabled = true;
            doorList.appendChild(btn);
        }
    }

    /**
     * Показывает правую панель в режиме КОМНАТЫ:
     * только кнопка «Выйти».
     * Кнопка «Фон комнаты» появляется.
     */
    _showRoomNav(roomId) {
        this.isInRoom = true;

        document.getElementById('navPanel').classList.remove('hidden');
        document.getElementById('floorNav').classList.add('hidden');
        document.getElementById('roomNav').classList.remove('hidden');
        document.getElementById('navRoomId').textContent = roomId;

        // Фон комнаты можно менять только внутри комнаты
        document.getElementById('btnRoomSkin').classList.remove('hidden');
    }

    /**
     * Выйти из комнаты → вернуться на текущий этаж.
     */
    exitRoom() {
        if (this.currentFloorId !== null && this.currentFloorId !== undefined) {
            networkManager.sendPacket({ type: 'enter_floor', floor_id: this.currentFloorId });
        } else {
            authUI.showError('Не удалось определить текущий этаж');
        }
    }

    /**
     * Войти в комнату по ID (из кнопок дверей).
     */
    _joinRoomById(roomId) {
        if (roomId == this.currentRoomId && this.isInRoom) return;
        networkManager.sendPacket({ type: 'enter_room', room_id: roomId });
    }

    // ── Лифт ───────────────────────────────────────────────────

    openElevatorModal() {
        const list = document.getElementById('elevatorFloorList');
        list.innerHTML = '';

        if (this.availableFloors.length > 0) {
            this.availableFloors.forEach(floor => {
                const btn = document.createElement('button');
                btn.className = 'elevator-floor-btn';
                if (floor.floor_id == this.currentFloorId) btn.classList.add('active-floor');
                btn.textContent = `🏢 Этаж ${floor.floor_id}${floor.name ? ' — ' + floor.name : ''}`;
                btn.onclick = () => {
                    this._goToFloor(floor.floor_id);
                    this.closeElevatorModal();
                };
                list.appendChild(btn);
            });
        } else {
            list.innerHTML = '<p class="modal-hint" style="text-align:center;margin:8px 0;">Список этажей не получен.<br>Введите номер вручную ↓</p>';
        }

        if (this.currentFloorId !== null) {
            document.getElementById('elevatorFloorInput').value = this.currentFloorId;
        }

        document.getElementById('elevatorModal').classList.remove('hidden');
    }

    closeElevatorModal() {
        document.getElementById('elevatorModal').classList.add('hidden');
    }

    goToFloorFromInput() {
        const val = parseInt(document.getElementById('elevatorFloorInput').value);
        if (isNaN(val)) { authUI.showError('Введите корректный номер этажа'); return; }
        this._goToFloor(val);
    }

    _goToFloor(floorId) {
        if (floorId == this.currentFloorId && !this.isInRoom) return;
        networkManager.sendPacket({ type: 'enter_floor', floor_id: floorId });
        this.closeElevatorModal();
    }


    _isCatUrl(url) {
    return !url || url.includes('cataas') || url.includes('placekitten') || url.includes('loremflickr');
    }

    _randomSkin() {
        return CONFIG.SKINS[Math.floor(Math.random() * CONFIG.SKINS.length)];
    }

    _randomBg() {
        return CONFIG.ROOM_BACKGROUNDS[Math.floor(Math.random() * CONFIG.ROOM_BACKGROUNDS.length)];
    }
    _roomBg(roomId) {
    const id = parseInt(roomId) || 0;
    return CONFIG.ROOM_BACKGROUNDS[id % CONFIG.ROOM_BACKGROUNDS.length];
}


}


