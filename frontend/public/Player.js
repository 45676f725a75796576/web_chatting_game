class Player {
    constructor(id, x, y, username, color = null) {
        this.id = id;
        this.x = x;
        this.y = y;
        this.username = username;
        this.color = color || this.generateColor(id);
    }

    generateColor(id) {
        const hue = (id * 137.5) % 360;
        return `hsl(${hue}, 70%, 60%)`;
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
