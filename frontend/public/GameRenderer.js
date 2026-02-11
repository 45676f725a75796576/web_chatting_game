class GameRenderer {
    constructor(canvas) {
        this.canvas = canvas;
        this.ctx = canvas.getContext('2d');
    }

    clear() {
        this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);
    }

    drawPlayer(player, isMe, chatMessage = null) {
        this.ctx.fillStyle = player.color;
        this.ctx.beginPath();
        this.ctx.arc(player.x, player.y, CONFIG.PLAYER_RADIUS, 0, Math.PI * 2);
        this.ctx.fill();

        if (isMe) {
            this.ctx.strokeStyle = game.testMode ? '#51cf66' : '#ffeb3b';
            this.ctx.lineWidth = 3;
            this.ctx.stroke();
        }

        this.ctx.fillStyle = 'white';
        this.ctx.font = 'bold 12px Arial';
        this.ctx.textAlign = 'center';
        this.ctx.fillText(player.username, player.x, player.y + 35);

        if (chatMessage) {
            this.drawChatBubble(player.x, player.y, chatMessage);
        }
    }

    drawChatBubble(x, y, message) {
        this.ctx.font = '14px Arial';
        const textWidth = this.ctx.measureText(message).width;
        const padding = 10;
        const bubbleWidth = textWidth + padding * 2;
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
