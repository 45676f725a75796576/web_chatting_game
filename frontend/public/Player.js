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
        this.skinWidth = 0.4; // 40px default
        this.skinHeight = 0.4; // 40px default
    }

    generateColor(id) {
        const hue = (id * 137.5) % 360;
        return `hsl(${hue}, 70%, 60%)`;
    }

    setSkin(url, width = 0.4, height = 0.4) {
        this.skinUrl = url;
        this.skinWidth = width;
        this.skinHeight = height;
        this.skinLoaded = false;
        this.skinImage = new Image();
        
        this.skinImage.onload = () => {
            this.skinLoaded = true;
            console.log('Skin loaded:', url);
        };
        
        this.skinImage.onerror = () => {
            console.error('Failed to load skin:', url);
            this.skinUrl = null;
            this.skinImage = null;
            this.skinLoaded = false;
        };
        
        this.skinImage.src = url;
    }

    setPosition(x, y) {
        this.x = x;
        this.y = y;
    }

    move(dx, dy) {
        this.x += dx;
        this.y += dy;
        this.clampPosition();
    }

    clampPosition() {
        this.x = Math.max(CONFIG.PLAYER_RADIUS, Math.min(CONFIG.CANVAS_WIDTH - CONFIG.PLAYER_RADIUS, this.x));
        this.y = Math.max(CONFIG.PLAYER_RADIUS, Math.min(CONFIG.CANVAS_HEIGHT - CONFIG.PLAYER_RADIUS, this.y));
    }
}
