class GameRenderer {
    constructor(canvas) {
        this.canvas = canvas;
        this.ctx = canvas.getContext('2d');
    }

    clear() {
        this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);
    }

    drawPlayer(player, isMe, chatMessage = null) {
        if (player.skinUrl && player.skinLoaded && player.skinImage) {
            const drawWidth  = player.skinWidth  * 100;
            const drawHeight = player.skinHeight * 100;
            const drawX = player.x - drawWidth  / 2;
            const drawY = player.y - drawHeight / 2;

            this.ctx.save();

            if (player.flip) {
                // Зеркалим по горизонтали относительно центра игрока
                this.ctx.translate(player.x * 2, 0);
                this.ctx.scale(-1, 1);
            }

            this.ctx.drawImage(player.skinImage, drawX, drawY, drawWidth, drawHeight);
            this.ctx.restore();
        } else {
            this.ctx.fillStyle = player.color;
            this.ctx.beginPath();
            this.ctx.arc(player.x, player.y, CONFIG.PLAYER_RADIUS, 0, Math.PI * 2);
            this.ctx.fill();
        }

        // Имя (без flip — всегда читаемо)
        this.ctx.fillStyle = 'white';
        this.ctx.font = 'bold 12px Arial';
        this.ctx.textAlign = 'center';
        const nameOffsetY = player.skinUrl && player.skinLoaded
            ? (player.skinHeight * 100 / 2) + 15
            : CONFIG.PLAYER_RADIUS + 15;
        this.ctx.fillText(player.username, player.x, player.y + nameOffsetY);

        // Чат-баббл
        if (chatMessage) {
            const bubbleOffsetY = player.skinUrl && player.skinLoaded
                ? -(player.skinHeight * 100 / 2)
                : -CONFIG.PLAYER_RADIUS;
            this.drawChatBubble(player.x, player.y + bubbleOffsetY, chatMessage);
        }
    }

    drawChatBubble(x, y, message) {
        this.ctx.font = '14px Arial';
        const textWidth  = this.ctx.measureText(message).width;
        const padding    = 10;
        const bubbleWidth  = textWidth + padding * 2;
        const bubbleHeight = 26;

        this.ctx.fillStyle = 'rgba(0, 0, 0, 0.85)';
        this.ctx.beginPath();
        this.roundRect(x - bubbleWidth / 2, y - 55, bubbleWidth, bubbleHeight, 6);
        this.ctx.fill();

        this.ctx.fillStyle = '#ffeb3b';
        this.ctx.textAlign = 'center';
        this.ctx.fillText(message, x, y - 37);
    }

    roundRect(x, y, width, height, radius) {
        this.ctx.beginPath();
        this.ctx.moveTo(x + radius, y);
        this.ctx.lineTo(x + width - radius, y);
        this.ctx.quadraticCurveTo(x + width, y, x + width, y + radius);
        this.ctx.lineTo(x + width, y + height - radius);
        this.ctx.quadraticCurveTo(x + width, y + height, x + width - radius, y + height);
        this.ctx.lineTo(x + radius, y + height);
        this.ctx.quadraticCurveTo(x, y + height, x, y + height - radius);
        this.ctx.lineTo(x, y + radius);
        this.ctx.quadraticCurveTo(x, y, x + radius, y);
        this.ctx.closePath();
    }
}
