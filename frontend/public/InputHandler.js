class InputHandler {
    constructor() {
        this.keys = {
            w: false, a: false, s: false, d: false,
            ArrowUp: false, ArrowLeft: false, ArrowDown: false, ArrowRight: false
        };
        this.setupListeners();
    }

    setupListeners() {
        document.addEventListener('keydown', (e) => {
            if (e.target.tagName === 'INPUT') return;
            if (this.keys.hasOwnProperty(e.key)) {
                e.preventDefault();
                this.keys[e.key] = true;
            }
        });

        document.addEventListener('keyup', (e) => {
            if (e.target.tagName === 'INPUT') return;
            if (this.keys.hasOwnProperty(e.key)) {
                this.keys[e.key] = false;
            }
        });
    }

    getMovement() {
        let dx = 0;
        let dy = 0;

        if (this.keys.w || this.keys.ArrowUp) dy -= CONFIG.PLAYER_SPEED;
        if (this.keys.s || this.keys.ArrowDown) dy += CONFIG.PLAYER_SPEED;
        if (this.keys.a || this.keys.ArrowLeft) dx -= CONFIG.PLAYER_SPEED;
        if (this.keys.d || this.keys.ArrowRight) dx += CONFIG.PLAYER_SPEED;

        return { dx, dy };
    }
}
