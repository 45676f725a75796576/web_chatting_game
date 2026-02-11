class AuthUI {
    showTab(tabName) {
        document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.form-container').forEach(f => f.classList.remove('active'));

        if (tabName === 'signIn') {
            document.querySelector('.tabs .tab:first-child').classList.add('active');
            document.getElementById('signInForm').classList.add('active');
        } else {
            document.querySelector('.tabs .tab:last-child').classList.add('active');
            document.getElementById('loginForm').classList.add('active');
        }

        this.clearMessages();
    }

    showError(message) {
        const errorEl = document.getElementById('errorMessage');
        errorEl.textContent = message;
        document.getElementById('successMessage').textContent = '';
        setTimeout(() => errorEl.textContent = '', 5000);
    }

    showSuccess(message) {
        const successEl = document.getElementById('successMessage');
        successEl.textContent = message;
        document.getElementById('errorMessage').textContent = '';
    }

    clearMessages() {
        document.getElementById('errorMessage').textContent = '';
        document.getElementById('successMessage').textContent = '';
    }

    signIn() {
        const username = document.getElementById('signInUsername').value.trim();

        if (!username) {
            this.showError('Please enter a username');
            return;
        }

        if (username.length < 3) {
            this.showError('Username must be at least 3 characters');
            return;
        }

        game.setUsername(username);
        networkManager.sendPacket({
            type: 'sign_in',
            username: username
        });
    }

    login() {
        const username = document.getElementById('loginUsername').value.trim();
        const identifier = document.getElementById('loginIdentifier').value.trim();

        if (!username || !identifier) {
            this.showError('Please fill in all fields');
            return;
        }

        game.setUsername(username);
        networkManager.sendPacket({
            type: 'login',
            username: username,
            identifier_str: identifier
        });
    }

    quickTestMode() {
        const randomId = Math.floor(Math.random() * 10000);
        const testUsername = `Player${randomId}`;

        game.startTestMode(randomId, testUsername);
        this.showSuccess(`Testing as ${testUsername}`);

        setTimeout(() => {
            document.getElementById('testBadge').classList.remove('hidden');
            this.switchToGame();
        }, 500);
    }

    switchToGame() {
        document.getElementById('authContainer').style.display = 'none';
        document.getElementById('gameContainer').style.display = 'block';
    }
}
