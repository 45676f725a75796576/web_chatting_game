class Player {
    constructor(id, x, y, username, color = null) {
        this.id = id;
        this.x = x;
        this.y = y;
        this.username = username;
        this.color = color || this.generateColor(id);
        this.skinUrl = null;
        this.skinImage = null;
        this.skinLoaded = false;

        this.skinWidth  = CONFIG.DEFAULT_SKIN_WIDTH;
        this.skinHeight = CONFIG.DEFAULT_SKIN_HEIGHT;
        
        this.flip = false; // false = вправо, true = влево

        const randomSkin = CONFIG.SKINS[Math.floor(Math.random() * CONFIG.SKINS.length)];
        this.setSkin(randomSkin);
    }

    generateColor(id) {
        const hue = (id * 137.5) % 360;
        return `hsl(${hue}, 70%, 60%)`;
    }

    setSkin(url, width = CONFIG.DEFAULT_SKIN_WIDTH, height = CONFIG.DEFAULT_SKIN_HEIGHT) {
        this.skinUrl = url;
        this.skinWidth = width;
        this.skinHeight = height;
        this.skinLoaded = false;
        this.skinImage = new Image();
        this.skinImage.onload = () => { this.skinLoaded = true; };
        this.skinImage.onerror = () => {
            this.skinUrl = null;
            this.skinImage = null;
            this.skinLoaded = false;
        };
        this.skinImage.src = url;
    }

    setPosition(x, y) { this.x = x; this.y = y; }

    move(dx, dy) {
        // Обновляем flip только при горизонтальном движении
        if (dx < 0) this.flip = true;
        if (dx > 0) this.flip = false;
        this.x += dx;
        this.y += dy;
        this.clampPosition();
    }

    clampPosition() {
        this.x = Math.max(CONFIG.PLAYER_RADIUS, Math.min(CONFIG.CANVAS_WIDTH  - CONFIG.PLAYER_RADIUS, this.x));
        this.y = Math.max(CONFIG.PLAYER_RADIUS, Math.min(CONFIG.CANVAS_HEIGHT - CONFIG.PLAYER_RADIUS, this.y));
    }
}
